<?php

namespace App\Services;

use App\Models\Gestion;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class RespaldoService
{
    private const CARPETA = 'sita-backups';
    private const PATRON = '/\Asita-completo-\d{8}-\d{6}-[a-f0-9]{8}\.zip\z/D';

    /**
     * Crea una copia completa de la BD y los archivos de almacenamiento.
     * Incluye todas las gestiones; no modifica ni elimina registros.
     */
    public function crear(): string
    {
        $conexion = DB::connection();
        if (!in_array($conexion->getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Los respaldos SQL requieren una conexión MySQL o MariaDB.');
        }

        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('La extensión PHP zip no está disponible.');
        }

        $directorio = $this->directorio();
        if (!is_dir($directorio) && !mkdir($directorio, 0700, true) && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo crear el directorio privado de respaldos.');
        }

        $candado = fopen($directorio . DIRECTORY_SEPARATOR . '.respaldo.lock', 'c');
        if ($candado === false) {
            throw new RuntimeException('No se pudo abrir el control de concurrencia.');
        }

        if (!flock($candado, LOCK_EX | LOCK_NB)) {
            fclose($candado);
            throw new RuntimeException('Ya se está generando otra copia de seguridad.');
        }

        $nombre = 'sita-completo-' . now()->format('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.zip';
        $archivoZip = $directorio . DIRECTORY_SEPARATOR . $nombre;
        $archivoSql = tempnam($directorio, '.sita-sql-');

        try {
            if ($archivoSql === false) {
                throw new RuntimeException('No se pudo crear el archivo SQL temporal.');
            }

            $this->volcarBaseDeDatos($archivoSql);

            $zip = new ZipArchive();
            if ($zip->open($archivoZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo crear el archivo ZIP.');
            }

            try {
                if (!$zip->addFile($archivoSql, 'base_de_datos.sql')) {
                    throw new RuntimeException('No se pudo incluir la base de datos en el ZIP.');
                }

                $cantidadArchivos = $this->incluirArchivos($zip, $directorio);
                $manifiesto = json_encode([
                    'sistema' => 'SITA',
                    'tipo' => 'respaldo_completo',
                    'creado_en' => now()->toIso8601String(),
                    'gestiones_incluidas' => Gestion::orderBy('anio')->pluck('anio')->all(),
                    'archivos_adjuntos' => $cantidadArchivos,
                    'descripcion' => 'Incluye todas las gestiones, no solamente un año.',
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

                if (!$zip->addFromString('manifiesto.json', $manifiesto)
                    || !$zip->addFromString('LEEME.txt', $this->instrucciones())) {
                    throw new RuntimeException('No se pudo escribir el manifiesto del respaldo.');
                }
            } finally {
                if (!$zip->close()) {
                    throw new RuntimeException('No se pudo cerrar el archivo ZIP.');
                }
            }

            if (!is_file($archivoZip) || filesize($archivoZip) === 0) {
                throw new RuntimeException('El respaldo resultó vacío.');
            }

            $hash = hash_file('sha256', $archivoZip);
            if ($hash === false || file_put_contents($archivoZip . '.sha256', $hash . PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('No se pudo registrar la integridad del respaldo.');
            }

            @chmod($archivoZip, 0600);
            @chmod($archivoZip . '.sha256', 0600);

            return $nombre;
        } catch (\Throwable $e) {
            @unlink($archivoZip);
            @unlink($archivoZip . '.sha256');
            throw $e;
        } finally {
            if ($archivoSql !== false) {
                @unlink($archivoSql);
            }
            flock($candado, LOCK_UN);
            fclose($candado);
        }
    }

    /**
     * mysqldump es la herramienta nativa para generar un SQL restaurable.
     * La contraseña se pasa como variable de entorno, nunca como argumento.
     */
    private function volcarBaseDeDatos(string $destino): void
    {
        $conexion = DB::connection();
        $config = $conexion->getConfig();
        $baseDeDatos = (string) $conexion->getDatabaseName();

        if ($baseDeDatos === '') {
            throw new RuntimeException('No se pudo determinar la base de datos.');
        }

        $argumentos = [
            (string) config('sita_backup.mysqldump_bin', 'mysqldump'),
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--hex-blob',
            '--default-character-set=utf8mb4',
            '--user=' . (string) ($config['username'] ?? ''),
            '--result-file=' . $destino,
        ];

        if (!empty($config['unix_socket'])) {
            $argumentos[] = '--socket=' . $config['unix_socket'];
        } else {
            $argumentos[] = '--host=' . (string) ($config['host'] ?? '127.0.0.1');
            $argumentos[] = '--port=' . (string) ($config['port'] ?? '3306');
        }

        $argumentos[] = $baseDeDatos;

        $proceso = new Process($argumentos, null, [
            'MYSQL_PWD' => (string) ($config['password'] ?? ''),
        ]);
        $proceso->setTimeout(300);
        $proceso->run();

        if (!$proceso->isSuccessful() || !is_file($destino) || filesize($destino) === 0) {
            throw new RuntimeException(
                'No se pudo ejecutar mysqldump. Revise SITA_MYSQLDUMP_BIN y los permisos de la base de datos.'
            );
        }
    }

    private function incluirArchivos(ZipArchive $zip, string $directorioRespaldos): int
    {
        $cantidad = 0;
        $raices = [
            'public' => storage_path('app/public'),
            'private' => storage_path('app/private'),
        ];

        foreach ($raices as $tipo => $raiz) {
            if (!is_dir($raiz)) {
                continue;
            }

            $iterador = new \RecursiveIteratorIterator(
                new \RecursiveCallbackFilterIterator(
                    new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS),
                    static function (\SplFileInfo $elemento) use ($directorioRespaldos): bool {
                        if ($elemento->isLink()) {
                            return false;
                        }

                        $ruta = $elemento->getRealPath();
                        return $ruta !== false
                            && $ruta !== $directorioRespaldos
                            && !str_starts_with($ruta, $directorioRespaldos . DIRECTORY_SEPARATOR);
                    }
                )
            );

            foreach ($iterador as $archivo) {
                if (!$archivo->isFile()) {
                    continue;
                }

                $ruta = $archivo->getPathname();
                $relativa = str_replace('\\', '/', substr($ruta, strlen($raiz) + 1));
                if (!$zip->addFile($ruta, 'archivos/' . $tipo . '/' . $relativa)) {
                    throw new RuntimeException('No se pudo incluir un archivo adjunto.');
                }
                $cantidad++;
            }
        }

        return $cantidad;
    }

    public function listar(): array
    {
        $directorio = $this->directorio();
        if (!is_dir($directorio)) {
            return [];
        }

        $resultados = [];
        foreach (new \DirectoryIterator($directorio) as $archivo) {
            if (!$archivo->isFile() || !preg_match(self::PATRON, $archivo->getFilename())) {
                continue;
            }

            $ruta = $archivo->getPathname();
            $resultados[] = [
                'nombre' => $archivo->getFilename(),
                'tamano' => $archivo->getSize(),
                'fecha' => $archivo->getMTime(),
                'sha256' => is_file($ruta . '.sha256')
                    ? trim((string) file_get_contents($ruta . '.sha256'))
                    : null,
            ];
        }

        usort($resultados, static fn (array $a, array $b) => $b['fecha'] <=> $a['fecha']);

        return $resultados;
    }

    public function rutaDescarga(string $nombre): ?string
    {
        if (!preg_match(self::PATRON, $nombre)) {
            return null;
        }

        $ruta = $this->directorio() . DIRECTORY_SEPARATOR . $nombre;

        return is_file($ruta) ? $ruta : null;
    }

    private function directorio(): string
    {
        return storage_path('app/private/' . self::CARPETA);
    }

    private function instrucciones(): string
    {
        return "SITA - COPIA COMPLETA DE SEGURIDAD\n\n"
            . "Este ZIP incluye la base de datos SQL y archivos de storage/app/public y storage/app/private.\n"
            . "Incluye TODAS las gestiones academicas presentes al momento del respaldo.\n"
            . "Contiene datos personales y hashes de contrasenas: conservar en un lugar seguro.\n\n"
            . "RESTAURACION (solo por personal autorizado):\n"
            . "1. Probar primero en una base de datos de pruebas, nunca directamente en produccion.\n"
            . "2. Importar base_de_datos.sql con un cliente MySQL/MariaDB apropiado.\n"
            . "3. Restaurar archivos/public en storage/app/public y archivos/private en storage/app/private.\n"
            . "4. Comprobar claves foraneas, permisos, rutas y documentos antes de usar el sistema.\n"
            . "5. No reemplazar APP_KEY ni el archivo .env del servidor.\n\n"
            . "La aplicacion NO restaura automaticamente los datos y NO borra gestiones.\n";
    }
}

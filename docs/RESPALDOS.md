# Copias de seguridad de SITA

## Qué se respalda

El respaldo es **completo**: guarda todas las tablas de MySQL/MariaDB (incluidas las gestiones anteriores), y los archivos de \`storage/app/public\` y \`storage/app/private\` (excluyendo los propios respaldos).

No es una exportación de un solo año. Cerrar una gestión no elimina sus datos, pero **tampoco genera automáticamente** un respaldo.

Los ZIP contienen información personal y hashes de contraseñas existentes; **no** se modifican las contraseñas ni las políticas de acceso.

## Crear y descargar (administrador)

1. Iniciar sesión con rol administrador.
2. Abrir **Respaldos** en el menú superior.
3. Pulsar **Generar copia completa** y esperar a que termine.
4. Descargar el ZIP de la tabla y guardarlo en un medio seguro, distinto al disco del servidor.
5. Comprobar que contiene \`base_de_datos.sql\`, \`manifiesto.json\`, \`LEEME.txt\` y, si existen, \`archivos/public/...\` y \`archivos/private/...\`.

Los archivos se guardan en \`storage/app/private/sita-backups/\`, fuera de \`public/\`. Solo las rutas con rol administrador permiten descargarlos. Cada ZIP tiene un archivo \`.sha256\` junto a él para verificar su integridad.

## Requisitos

- Laravel con conexión **MySQL o MariaDB** (no SQLite para esta función).
- PHP con extensión \`zip\` habilitada.
- Herramienta \`mysqldump\` disponible en PATH, o configurada mediante \`SITA_MYSQLDUMP_BIN\` en \`.env\`.
- Permiso de escritura en \`storage/app/private\` y espacio libre suficiente.

### Laragon / Windows

Desde Git Bash, comprobar:

\`\`\`bash
php -m | grep -i zip
command -v mysqldump
\`\`\`

Si \`mysqldump\` no está en PATH, buscar \`mysqldump.exe\` dentro de la carpeta MySQL de Laragon y añadir **solo su ruta de ejecutable** a \`.env\`:

\`\`\`dotenv
SITA_MYSQLDUMP_BIN="C:/laragon/bin/mysql/tu-version/bin/mysqldump.exe"
\`\`\`

Cambiar \`tu-version\` por la carpeta real; no copiar una ruta de ejemplo sin verificarla. Ejecutar \`php artisan config:clear\` después de cambiar \`.env\`.

### Alternativa para copias grandes

Para evitar límites de tiempo del navegador:

\`\`\`bash
php artisan sita:respaldo
\`\`\`

La copia aparecerá en el listado web y podrá descargarse allí. No requiere programador de tareas ni cola de trabajos.

## Seguridad, integridad y recuperación

- **Nunca** poner los ZIP en \`public/\`, compartirlos públicamente ni subirlos a GitHub.
- El ZIP no está cifrado. Guardarlo en almacenamiento cifrado y con acceso restringido.
- El archivo \`.sha256\` permite verificar alteraciones accidentales del ZIP.
- Para recuperar información, **probar primero** en una base de datos MySQL/MariaDB y un directorio de almacenamiento separados.
- Importar \`base_de_datos.sql\` en la base de pruebas, copiar \`archivos/public\` a \`storage/app/public\` y \`archivos/private\` a \`storage/app/private\`, y comprobar documentos y registros.
- **No** reemplazar \`.env\` ni \`APP_KEY\`. No restaurar sobre producción sin plan de recuperación y aprobación expresa.
- El sistema **no ofrece restauración automática** y no elimina gestiones ni copias existentes.
- \`mysqldump --single-transaction\` proporciona consistencia para tablas transaccionales (InnoDB). Los adjuntos se copian después; generar respaldos en un periodo de baja actividad para reducir diferencias entre BD y archivos.

## Pruebas antes de fusionar

1. Comprobar que un administrador ve el menú y un docente/estudiante no puede entrar ni descargar.
2. Crear una copia con datos de prueba; confirmar que el ZIP abre y contiene SQL y adjuntos de licencias.
3. Comparar tamaño, fecha y SHA-256; descargar desde la interfaz.
4. Crear un respaldo de prueba con gestiones de dos años; comprobar que ambas figuran en el manifiesto.
5. Restaurar en un **entorno aislado de prueba** y comprobar que se conservan inscripciones, asistencias y documentos.
6. Confirmar que no se cambió ninguna contraseña, API, configuración Android ni registro académico.

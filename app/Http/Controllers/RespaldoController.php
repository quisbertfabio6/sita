<?php

namespace App\Http\Controllers;

use App\Services\RespaldoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RespaldoController extends Controller
{
    public function index(RespaldoService $respaldos)
    {
        return view('admin.respaldos.index', [
            'respaldos' => $respaldos->listar(),
        ]);
    }

    public function crear(Request $request, RespaldoService $respaldos): RedirectResponse
    {
        // La ruta ya exige sesión, cuenta activa, rol administrador y CSRF.
        // Una copia grande puede tardar más que una petición web habitual.
        if (function_exists('set_time_limit')) {
            @set_time_limit(330);
        }

        try {
            $nombre = $respaldos->crear();

            return redirect()->route('respaldos.index')
                ->with('success', 'Copia completa creada: ' . $nombre);
        } catch (\Throwable $e) {
            Log::error('No se pudo generar el respaldo de SITA.', [
                'tipo' => get_class($e),
                'detalle' => $e->getMessage(),
            ]);

            return redirect()->route('respaldos.index')
                ->with('error', 'No se pudo generar la copia. Revise el registro de Laravel y la configuración de mysqldump.');
        }
    }

    public function descargar(string $nombre, RespaldoService $respaldos): BinaryFileResponse
    {
        $ruta = $respaldos->rutaDescarga($nombre);
        abort_unless($ruta !== null, 404);

        return response()->download($ruta, $nombre, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

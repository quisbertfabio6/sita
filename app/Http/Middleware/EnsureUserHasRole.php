<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Rechazar accesos a rutas de otros roles, incluso con una sesion valida.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();
        $rol = $usuario?->rol?->nombre;

        abort_unless($rol && in_array($rol, $roles, true), 403, 'No tienes permiso para acceder a esta funcion.');

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Impide que sesiones web o tokens Sanctum de cuentas desactivadas
     * sigan accediendo al sistema.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->activo) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Cuenta desactivada. Contacta al administrador.'], 403);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'La cuenta esta desactivada. Contacta al administrador.',
            ]);
        }

        return $next($request);
    }
}

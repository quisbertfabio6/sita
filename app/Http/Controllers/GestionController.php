<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gestion;

class GestionController extends Controller
{
    // Función para crear e iniciar un nuevo año académico (Ej: 2026)
    public function iniciarNuevaGestion(Request $request)
    {
        $request->validate([
            'anio' => 'required|string|max:4|unique:gestiones,anio'
        ]);

        // 1. Apagamos (cerramos) todas las gestiones anteriores
        Gestion::query()->update(['es_activa' => false]);

        // 2. Creamos la nueva gestión y la activamos
        Gestion::create([
            'anio' => $request->anio,
            'es_activa' => true
        ]);

        return redirect()->back()->with('success', 'Gestión ' . $request->anio . ' iniciada correctamente. La gestión anterior ha sido cerrada.');
    }

    // Función manual por si el administrador solo quiere cerrar el sistema por vacaciones
    public function cerrarGestionActual()
    {
        Gestion::query()->update(['es_activa' => false]);
        return redirect()->back()->with('warning', 'La gestión actual ha sido cerrada. No se podrán registrar más asistencias hasta abrir una nueva.');
    }
}
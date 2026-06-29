<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;

// Modelos
use App\Models\Estudiante;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Usuario;
use App\Models\Asistencia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Auth::routes(['login' => false, 'logout' => false, 'register' => false, 'verify' => false]);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    // ==========================================
    //      DASHBOARD ADMIN (3 KPIs)
    // ==========================================
    Route::get('/admin/dashboard', function () {
        
        // Lógica de los 3 KPIs solicitados
        $datos = [
            'total_estudiantes' => Estudiante::count(),
            // Contamos a todos los usuarios cuyo rol NO sea estudiante (Admins, Jefes, Docentes)
            'total_plantel'     => Usuario::whereHas('rol', function($q) {
                                        $q->where('nombre', '!=', 'estudiante');
                                   })->count(),
            'total_usuarios'    => Usuario::count(),
            'ultimos_usuarios'  => Usuario::latest()->take(5)->with('rol')->get()
        ];

        // ASISTENCIA DE HOY
        $hoy = now()->format('Y-m-d');
        
        $asistenciasHoy = Asistencia::whereDate('fecha', $hoy)
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        $chart_data = [
            $asistenciasHoy['presente'] ?? 0,
            $asistenciasHoy['falta'] ?? 0,
            $asistenciasHoy['atraso'] ?? 0,
            $asistenciasHoy['licencia'] ?? 0,
        ];

        $chart_labels = ['Presentes', 'Faltas', 'Atrasos', 'Licencias'];

        return view('admin.dashboard', compact('datos', 'chart_labels', 'chart_data'));
    })->name('admin.dashboard');

    // --- RESTO DE RUTAS ---
    Route::get('/admin/usuarios', [App\Http\Controllers\UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/admin/usuarios/create', [App\Http\Controllers\UsuarioController::class, 'create'])->name('usuarios.create');
    Route::post('/admin/usuarios', [App\Http\Controllers\UsuarioController::class, 'store'])->name('usuarios.store');
    Route::get('/admin/usuarios/{id}/edit', [App\Http\Controllers\UsuarioController::class, 'edit'])->name('usuarios.edit');
    Route::put('/admin/usuarios/{id}', [App\Http\Controllers\UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/admin/usuarios/{id}', [App\Http\Controllers\UsuarioController::class, 'destroy'])->name('usuarios.destroy');

    Route::resource('/admin/cursos', App\Http\Controllers\CursoController::class)->names('cursos');
    Route::resource('/admin/materias', App\Http\Controllers\MateriaController::class)->names('materias');
    Route::resource('/admin/asignaciones', App\Http\Controllers\AsignacionController::class)->except(['show', 'edit', 'update'])->names('asignaciones');
    Route::resource('/admin/estudiantes', App\Http\Controllers\EstudianteController::class)->names('estudiantes');
    
    Route::get('/admin/reportes', [App\Http\Controllers\ReporteController::class, 'index'])->name('reportes.index');
    Route::post('/admin/reportes/generar', [App\Http\Controllers\ReporteController::class, 'generarReporteFechas'])->name('reportes.generar');
    Route::get('/reportes/lista-materia/{id}', [App\Http\Controllers\ReporteController::class, 'listaAsistenciaPorMateria'])->name('reportes.lista');
    
    Route::post('/admin/estudiantes/importar', [App\Http\Controllers\EstudianteController::class, 'importar'])->name('estudiantes.importar');

    // Roles
    Route::get('/jefe/dashboard', [App\Http\Controllers\JefeController::class, 'index'])->name('jefe.dashboard');
    Route::get('/jefe/licencias', [App\Http\Controllers\JefeController::class, 'licencias'])->name('jefe.licencias');
    Route::put('/jefe/licencias/{id}', [App\Http\Controllers\JefeController::class, 'cambiarEstadoLicencia'])->name('jefe.licencias.update');
    Route::get('/jefe/reportes', [App\Http\Controllers\JefeController::class, 'showReportForm'])->name('jefe.reportes.index');
    Route::post('/jefe/reportes/licencias', [App\Http\Controllers\JefeController::class, 'generateLicenseReport'])->name('jefe.reportes.generar');

    Route::get('/docente/dashboard', [App\Http\Controllers\DocentePanelController::class, 'index'])->name('docente.dashboard');
    Route::get('/docente/curso/{id}', [App\Http\Controllers\DocentePanelController::class, 'verCurso'])->name('docente.curso.show');
    
    Route::get('/estudiante/dashboard', [App\Http\Controllers\EstudiantePanelController::class, 'index'])->name('estudiante.dashboard');
    Route::get('/estudiante/asistencias', [App\Http\Controllers\EstudiantePanelController::class, 'misAsistencias'])->name('estudiante.asistencias');
    Route::get('/estudiante/licencias', [App\Http\Controllers\EstudiantePanelController::class, 'misLicencias'])->name('estudiante.licencias');
    Route::get('/estudiante/reporte-personal', [App\Http\Controllers\ReporteController::class, 'generarHistorialPersonal'])->name('reportes.personal');
});
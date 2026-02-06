<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Ruta raíz: Redirige al login
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de Autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
// Activa solo las rutas de reseteo de contraseña.
Auth::routes(['login' => false, 'logout' => false, 'register' => false, 'verify' => false]);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas Protegidas (Solo usuarios logueados)
Route::middleware('auth')->group(function () {

    // Panel Administrador
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    // Panel Jefe de Carrera (Placeholder)
    Route::get('/jefe/dashboard', function () {
        return "Bienvenido Jefe de Carrera";
    })->name('jefe.dashboard');

    // Panel Docente (Placeholder)
    Route::get('/docente/dashboard', function () {
        return "Bienvenido Docente";
    })->name('docente.dashboard');

    // Panel Estudiante (Placeholder)
    Route::get('/estudiante/dashboard', function () {
        return "Bienvenido Estudiante";
    })->name('estudiante.dashboard');

    // Rutas de Usuarios
    Route::get('/admin/usuarios', [App\Http\Controllers\UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/admin/usuarios/create', [App\Http\Controllers\UsuarioController::class, 'create'])->name('usuarios.create');
    Route::post('/admin/usuarios', [App\Http\Controllers\UsuarioController::class, 'store'])->name('usuarios.store');
    Route::get('/admin/usuarios/{id}/edit', [App\Http\Controllers\UsuarioController::class, 'edit'])->name('usuarios.edit');
    Route::put('/admin/usuarios/{id}', [App\Http\Controllers\UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/admin/usuarios/{id}', [App\Http\Controllers\UsuarioController::class, 'destroy'])->name('usuarios.destroy');
    // --- Rutas para Reportes PDF ---
    
    // Arregla la ruta del docente que ya teníamos
    Route::get('/reportes/lista-materia/{id}', [App\Http\Controllers\ReporteController::class, 'listaAsistenciaPorMateria'])->name('reportes.lista');
    
    // (NUEVO) Muestra la página de filtros
    Route::get('/admin/reportes', [App\Http\Controllers\ReporteController::class, 'index'])->name('reportes.index');
    
    // (NUEVO) Procesa el formulario y genera el PDF
    Route::post('/admin/reportes/generar', [App\Http\Controllers\ReporteController::class, 'generarReporteFechas'])->name('reportes.generar');
    

    // Rutas de Cursos
    Route::resource('/admin/cursos', App\Http\Controllers\CursoController::class)->names('cursos');
    // Rutas de Materias
    Route::resource('/admin/materias', App\Http\Controllers\MateriaController::class)->names('materias');
    // Rutas de Asignaciones (Docente <-> Materia)
    Route::resource('/admin/asignaciones', App\Http\Controllers\AsignacionController::class)
        ->except(['show', 'edit', 'update']) // No necesitamos editar, si se equivoca que borre y cree de nuevo
        ->names('asignaciones');
    // Rutas de Estudiantes
    Route::post('/admin/estudiantes/importar', [App\Http\Controllers\EstudianteController::class, 'importar'])->name('estudiantes.importar');
    Route::resource('/admin/estudiantes', App\Http\Controllers\EstudianteController::class)->names('estudiantes');

    // --- Rutas JEFE DE CARRERA ---
    Route::get('/jefe/dashboard', [App\Http\Controllers\JefeController::class, 'index'])->name('jefe.dashboard');
    Route::get('/jefe/licencias', [App\Http\Controllers\JefeController::class, 'licencias'])->name('jefe.licencias');
    Route::put('/jefe/licencias/{id}', [App\Http\Controllers\JefeController::class, 'cambiarEstadoLicencia'])->name('jefe.licencias.update');
    Route::get('/jefe/reportes', [App\Http\Controllers\JefeController::class, 'showReportForm'])->name('jefe.reportes.index');
    Route::post('/jefe/reportes/licencias', [App\Http\Controllers\JefeController::class, 'generateLicenseReport'])->name('jefe.reportes.generar');

    // --- Rutas DOCENTE ---
    Route::get('/docente/dashboard', [App\Http\Controllers\DocentePanelController::class, 'index'])->name('docente.dashboard');
    Route::get('/docente/curso/{id}', [App\Http\Controllers\DocentePanelController::class, 'verCurso'])->name('docente.curso.show');
    
    // --- Rutas ESTUDIANTE ---
    Route::get('/estudiante/dashboard', [App\Http\Controllers\EstudiantePanelController::class, 'index'])->name('estudiante.dashboard');
    Route::get('/estudiante/asistencias', [App\Http\Controllers\EstudiantePanelController::class, 'misAsistencias'])->name('estudiante.asistencias');
    Route::get('/estudiante/licencias', [App\Http\Controllers\EstudiantePanelController::class, 'misLicencias'])->name('estudiante.licencias');
    // Ruta para el reporte personal del estudiante
    Route::get('/estudiante/reporte-personal', [App\Http\Controllers\ReporteController::class, 'generarHistorialPersonal'])->name('reportes.personal');


    Route::get('/prueba-web', function () {
        return '¡Si ves esto, el servidor funciona!';
    });

    // Ruta para Reportes PDF
    Route::get('/reportes/lista-asistencia/{id}', [App\Http\Controllers\ReporteController::class, 'listaAsistencia'])->name('reportes.lista');
});
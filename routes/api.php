<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Api\DocenteApiController;
use App\Http\Controllers\Api\EstudianteApiController;

// --- RUTAS PÚBLICAS (Cualquiera puede entrar) ---

// 1. Login
Route::post('/login', function (Request $request) {
    // ... (tu código de login sigue igual) ...
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'device_name' => 'required',
    ]);

    $user = Usuario::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Credenciales incorrectas'], 401);
    }

    $user->tokens()->delete();
    $token = $user->createToken($request->device_name)->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
        'rol' => $user->rol->nombre
    ]);
});

// 2. Ruta de Prueba (AHORA SÍ ES PÚBLICA)
Route::get('/prueba', function () {
    return ['mensaje' => '¡Conexión Exitosa! La API funciona.'];
});


// --- RUTAS PROTEGIDAS (Solo con Token) ---
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Aquí pondremos luego las rutas de Asistencia...

    Route::middleware('auth:sanctum')->group(function () {
        // ...
        Route::post('/estudiante/licencias', [App\Http\Controllers\EstudiantePanelController::class, 'guardarLicencia']);
    });

    Route::get('/docente/materias', [DocenteApiController::class, 'misMaterias']);

    
    Route::post('/docente/registrar-qr', [DocenteApiController::class, 'registrarAsistencia']);
    Route::post('/docente/lista-control', [DocenteApiController::class, 'listaEstudiantesConEstadistica']);
    Route::post('/docente/registrar-manual', [App\Http\Controllers\Api\DocenteApiController::class, 'registrarManual']);
    Route::post('/docente/fechas-clases', [App\Http\Controllers\Api\DocenteApiController::class, 'obtenerFechasClases']);

    Route::get('/estudiante/dashboard', [EstudianteApiController::class, 'dashboard']);
    Route::get('/estudiante/mis-licencias', [EstudianteApiController::class, 'misLicencias']);
    Route::get('/estudiante/materias', [EstudianteApiController::class, 'misMaterias']);
    Route::delete('/estudiante/licencias/{id}', [EstudianteApiController::class, 'anularLicencia']);
});
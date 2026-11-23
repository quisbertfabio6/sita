<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use App\Http\Controllers\Api\DocenteApiController;
use App\Http\Controllers\Api\EstudianteApiController;

// --- RUTAS PÚBLICAS (Cualquiera puede entrar) ---

// 1. Login
Route::post('/login', function (Request $request) {
    // ... (tu código de login sigue igual) ...
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'device_name' => 'required|string|max:100',
    ]);

    $user = Usuario::where('email', $request->email)->first();

    if (! $user || ! $user->activo || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Credenciales incorrectas'], 401);
    }

    $user->tokens()->delete();
    $minutosValidez = max(1, (int) config('sanctum.expiration', 43200));
    $token = $user->createToken($request->device_name, ['*'], now()->addMinutes($minutosValidez))->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
        'rol' => $user->rol->nombre
    ]);
})->middleware('throttle:sita-login');

// 2. Ruta de Prueba (AHORA SÍ ES PÚBLICA)
Route::get('/prueba', function () {
    return ['mensaje' => '¡Conexión Exitosa! La API funciona.'];
});


// API privada: token Sanctum + cuenta activa + rol correspondiente.
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Revocar el token del dispositivo que cierra sesion.
    Route::post('/logout', function (Request $request) {
        $token = $request->user()->currentAccessToken();

        if (!$token instanceof PersonalAccessToken) {
            return response()->json(['message' => 'Se requiere un token de acceso'], 400);
        }

        $token->delete();

        return response()->json(['message' => 'Sesion cerrada correctamente']);
    });

    Route::middleware('role:docente')->group(function () {
        Route::get('/docente/materias', [DocenteApiController::class, 'misMaterias']);
        Route::post('/docente/registrar-qr', [DocenteApiController::class, 'registrarAsistencia']);
        Route::post('/docente/lista-control', [DocenteApiController::class, 'listaEstudiantesConEstadistica']);
        Route::post('/docente/registrar-manual', [DocenteApiController::class, 'registrarManual']);
        Route::post('/docente/finalizar-asistencia', [DocenteApiController::class, 'finalizarAsistencia']);
        Route::post('/docente/fechas-clases', [DocenteApiController::class, 'obtenerFechasClases']);
    });

    Route::middleware('role:estudiante')->group(function () {
        Route::get('/estudiante/categorias-licencia', [EstudianteApiController::class, 'categoriasLicencia']);
        Route::get('/estudiante/licencias/cupo', [EstudianteApiController::class, 'cupoLicencias']);
        Route::post('/estudiante/licencias', [App\Http\Controllers\EstudiantePanelController::class, 'guardarLicencia']);
        Route::get('/estudiante/dashboard', [EstudianteApiController::class, 'dashboard']);
        Route::get('/estudiante/mis-licencias', [EstudianteApiController::class, 'misLicencias']);
        Route::get('/estudiante/materias', [EstudianteApiController::class, 'misMaterias']);
        Route::delete('/estudiante/licencias/{id}', [EstudianteApiController::class, 'anularLicencia']);
    });
});

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Nueva Contraseña - SITA</title>
    
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/estilos.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body, html {
            height: 100%;
            margin: 0;
            overflow-x: hidden; /* Evita scroll horizontal */
        }
        .login-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }
        .login-form-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            padding: 40px;
            position: relative;
            transition: all 0.3s ease;
        }
        .login-logo-container {
            position: absolute;
            top: 30px;
            left: 40px;
        }
        .login-logo-container img {
            height: 60px;
            width: auto;
        }
        .login-card {
            background: white;
            padding: 3.5rem;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 480px; /* Ancho ideal */
        }
        .login-title {
            font-size: 2rem;
            font-weight: 800;
            color: #2d3436;
            margin-bottom: 0.5rem;
        }
        .login-subtitle {
            font-size: 1.2rem;
            font-weight: 600;
            color: #D32F2F;
            margin-bottom: 2rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control {
            padding: 14px 18px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            background-color: #fcfcfc;
        }
        .form-control:focus {
            border-color: #D32F2F;
            box-shadow: 0 0 0 4px rgba(211, 47, 47, 0.1);
        }
        .btn-login {
            background: #D32F2F;
            background: linear-gradient(45deg, #D32F2F, #b71c1c);
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 1px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(211, 47, 47, 0.3);
            color: white;
        }
        .login-image-section {
            flex: 1.2; /* Un poco más ancha que el form */
            background-image: url("{{ asset('img/ita_foto.png') }}"); 
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .login-image-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.6));
        }
        @media (max-width: 991.98px) {
            .login-image-section { display: none; }
            .login-form-section { width: 100%; padding: 20px; background: white; }
            .login-logo-container { position: static; margin-bottom: 30px; text-align: center; width: 100%; }
            .login-logo-container img { height: 80px; }
            .login-card { box-shadow: none; padding: 1rem; max-width: 100%; }
            .login-title { font-size: 1.8rem; text-align: center; }
            .login-subtitle { font-size: 1rem; text-align: center; }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        
        <div class="login-form-section">
            
            <div class="login-logo-container">
                <img src="{{ asset('img/logo_ita.png') }}" alt="Logo ITA">
            </div>

            <div class="login-card">
                <div class="mb-4">
                    <h1 class="login-title">CREAR</h1>
                    <h2 class="login-subtitle">Nueva Contraseña</h2>
                </div>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold">CORREO REGISTRADO</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required>
                        </div>
                        @error('email')
                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold">NUEVA CONTRASEÑA</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control border-start-0 ps-0 @error('password') is-invalid @enderror" name="password" required>
                        </div>
                         @error('password')
                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold">CONFIRMAR CONTRASEÑA</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control border-start-0 ps-0" name="password_confirmation" required>
                        </div>
                    </div>

                    <div class="d-grid gap-2 pt-2">
                        <button type="submit" class="btn btn-primary btn-login text-white">
                            ACTUALIZAR CONTRASEÑA
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="login-image-section">
        </div>

    </div>

</body>
</html>
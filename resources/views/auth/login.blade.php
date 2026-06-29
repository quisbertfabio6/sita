<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Acceso SITA - Instituto Tecnológico Ayacucho</title>
    
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/estilos.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* --- CONTENEDOR PRINCIPAL (Fondo de pantalla completo) --- */
        .login-wrapper {
            position: relative;
            min-height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url("{{ asset('img/ita_fotoo.png') }}"); 
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 20px;
        }

        /* Capa oscura sobre la imagen para que resalte el formulario */
        .login-wrapper::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 1;
        }

        /* --- LOGO DEL INSTITUTO --- */
        .login-logo-container {
            position: absolute;
            top: 40px;
            left: 50px;
            z-index: 2;
        }
        .login-logo-container img {
            height: 150px; /* Tamaño incrementado según lo solicitado */
            width: auto;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3)); /* Sutil sombra para que destaque sobre el fondo */
        }

        /* --- TARJETA DE FORMULARIO CENTRADA --- */
        .login-card {
            background: rgba(255, 255, 255, 0.95); /* Blanco ligeramente translúcido */
            backdrop-filter: blur(10px); /* Efecto cristal moderno */
            padding: 3.5rem;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            z-index: 2; /* Por encima de la capa oscura */
        }

        .login-title {
            font-size: 2rem;
            font-weight: 800;
            color: #2d3436;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .login-subtitle {
            font-size: 1.1rem;
            font-weight: 600;
            color: #D32F2F;
            margin-bottom: 2rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
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

        /* --- RESPONSIVIDAD (MÓVIL) --- */
        @media (max-width: 768px) {
            .login-logo-container { 
                position: static; 
                margin-bottom: 20px; 
                text-align: center; 
                width: 100%; 
            }
            .login-wrapper {
                flex-direction: column;
            }
            .login-card { 
                padding: 2.5rem 2rem; 
                max-width: 100%; 
            }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        
        <div class="login-logo-container">
            <img src="{{ asset('img/logo_ita.png') }}" alt="Logo ITA">
        </div>

        <div class="login-card animate__animated animate__fadeIn">
            <div class="mb-4">
                <h1 class="login-title">BIENVENIDO</h1>
                <h2 class="login-subtitle">INSTITUTO TECNOLÓGICO AYACUCHO</h2>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm mb-4 small">
                    <i class="fas fa-exclamation-triangle me-2"></i> Credenciales incorrectas.
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label class="form-label text-secondary small fw-bold">CORREO INSTITUCIONAL</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                        <input type="email" class="form-control border-start-0 ps-0" name="email" placeholder="usuario@ita.edu.bo" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary small fw-bold">CONTRASEÑA</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" name="password" id="passwordInput" placeholder="••••••••" required>
                        <button class="btn btn-outline-secondary border-start-0 border" type="button" id="togglePassword">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="d-grid gap-2 pt-2">
                    <button type="submit" class="btn btn-primary btn-login text-white">
                        INICIAR SESIÓN
                    </button>
                </div>

                <div class="text-center mt-4">
                    <a href="{{ route('password.request') }}" class="text-decoration-none text-muted small">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
            </form>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('passwordInput');
            const toggleButton = document.getElementById('togglePassword');
            const icon = document.getElementById('passwordIcon');

            toggleButton.addEventListener('click', function () {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    </script>
</body>
</html>
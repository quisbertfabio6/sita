<!DOCTYPE html>
<html lang="es">
<head>
    <script>
        (function () {
            try {
                document.documentElement.setAttribute(
                    'data-sita-theme',
                    localStorage.getItem('sita-web-theme') === 'dark' ? 'dark' : 'light'
                );
            } catch (_) {
                document.documentElement.setAttribute('data-sita-theme', 'light');
            }
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>SITA - Instituto Tecnológico Ayacucho</title>

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            background-color: #f8f9fa; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* --- NAVBAR MODERNA ROJO INSTITUCIONAL --- */
        .navbar-ita {
            background: linear-gradient(135deg, #b71c1c 0%, #D32F2F 100%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            padding: 0.8rem 2rem;
        }

        .navbar-ita .navbar-brand {
            color: #ffffff;
            font-weight: 800;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
        }

        .navbar-ita .navbar-brand img {
            height: 40px;
            margin-right: 12px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
            background: white; 
            border-radius: 5px;
            padding: 2px;
        }

        .navbar-ita .nav-link {
            color: rgba(255, 255, 255, 0.85);
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.5rem 1rem;
            margin: 0 0.3rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .navbar-ita .nav-link:hover, 
        .navbar-ita .nav-link.active {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.15);
        }

        .navbar-ita .dropdown-menu {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 0;
            margin-top: 10px;
        }

        .navbar-ita .dropdown-item {
            font-weight: 500;
            color: #4a5568;
            padding: 0.6rem 1.5rem;
            transition: background 0.2s;
        }

        .navbar-ita .dropdown-item:hover {
            background-color: #fff5f5;
            color: #D32F2F;
        }

        .navbar-ita .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.5);
        }

        .navbar-ita .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.9%29' stroke-linecap='round' stroke-miterlimit='round' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .main-content {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
    </style>
    <link href="{{ asset('css/sita-ui.css') }}" rel="stylesheet">
    <link href="{{ asset('css/sita-dark.css') }}" rel="stylesheet">
</head>
<body class="sita-app">
    <a class="sita-skip-link" href="#contenido-principal">Saltar al contenido principal</a>

    <nav class="navbar navbar-expand-lg navbar-ita sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ url('/') }}">
                <img src="{{ asset('img/logo_ita.png') }}" alt="Logo ITA"> 
                SITA
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar" aria-controls="topNavbar" aria-expanded="false" aria-label="Alternar navegación">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="topNavbar">
                @auth
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    
                    @if(Auth::user()->rol->nombre == 'administrador')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                                <i class="fas fa-home me-1"></i> Inicio
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('cursos.*', 'materias.*', 'asignaciones.*') ? 'active' : '' }}" href="#" id="academicoMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-graduation-cap me-1"></i> Gestión Académica
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="academicoMenu">
                                <li><a class="dropdown-item" href="{{ route('cursos.index') }}"><i class="fas fa-chalkboard me-2 text-muted"></i> Cursos</a></li>
                                <li><a class="dropdown-item" href="{{ route('materias.index') }}"><i class="fas fa-book me-2 text-muted"></i> Materias</a></li>
                                <li><a class="dropdown-item" href="{{ route('asignaciones.index') }}"><i class="fas fa-user-tag me-2 text-muted"></i> Asignaciones</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('usuarios.index') ? 'active' : '' }}" href="{{ route('usuarios.index') }}" @if(request()->routeIs('usuarios.index')) aria-current="page" @endif>
                                <i class="fas fa-users me-1"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('reportes.index') ? 'active' : '' }}" href="{{ route('reportes.index') }}" @if(request()->routeIs('reportes.index')) aria-current="page" @endif>
                                <i class="fas fa-chart-bar me-1"></i> Reportes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('respaldos.*') ? 'active' : '' }}" href="{{ route('respaldos.index') }}" @if(request()->routeIs('respaldos.*')) aria-current="page" @endif>
                                <i class="fas fa-shield-alt me-1"></i> Respaldos
                            </a>
                        </li>

                    @elseif(Auth::user()->rol->nombre == 'jefe_carrera')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('jefe.dashboard') ? 'active' : '' }}" href="{{ route('jefe.dashboard') }}" @if(request()->routeIs('jefe.dashboard')) aria-current="page" @endif>
                                <i class="fas fa-home me-1"></i> Inicio
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('jefe.licencias') ? 'active' : '' }}" href="{{ route('jefe.licencias') }}" @if(request()->routeIs('jefe.licencias')) aria-current="page" @endif>
                                <i class="fas fa-file-signature me-1"></i> Licencias
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('jefe.reportes.index') ? 'active' : '' }}" href="{{ route('jefe.reportes.index') }}" @if(request()->routeIs('jefe.reportes.index')) aria-current="page" @endif>
                                <i class="fas fa-chart-bar me-1"></i> Reportes
                            </a>
                        </li>

                    @elseif(Auth::user()->rol->nombre == 'docente')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('docente.dashboard') ? 'active' : '' }}" href="{{ route('docente.dashboard') }}" @if(request()->routeIs('docente.dashboard')) aria-current="page" @endif>
                                <i class="fas fa-home me-1"></i> Inicio
                            </a>
                        </li>
                        @elseif(Auth::user()->rol->nombre == 'estudiante')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('estudiante.dashboard') ? 'active' : '' }}" href="{{ route('estudiante.dashboard') }}" @if(request()->routeIs('estudiante.dashboard')) aria-current="page" @endif>
                                <i class="fas fa-home me-1"></i> Inicio
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('estudiante.asistencias') ? 'active' : '' }}" href="{{ route('estudiante.asistencias') }}" @if(request()->routeIs('estudiante.asistencias')) aria-current="page" @endif>
                                <i class="fas fa-user-check me-1"></i> Mis Asistencias
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('estudiante.licencias') ? 'active' : '' }}" href="{{ route('estudiante.licencias') }}" @if(request()->routeIs('estudiante.licencias')) aria-current="page" @endif>
                                <i class="fas fa-file-medical me-1"></i> Mis Licencias
                            </a>
                        </li>
                    @endif
                </ul>

                <button type="button" class="sita-theme-switch me-lg-3 mb-2 mb-lg-0"
                    data-sita-theme-toggle aria-label="Activar modo oscuro" aria-pressed="false">
                    <i class="fas fa-moon" data-sita-theme-icon aria-hidden="true"></i>
                    <span data-sita-theme-text>Modo oscuro</span>
                </button>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userProfile" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="bg-white text-danger rounded-circle d-flex justify-content-center align-items-center me-2 shadow-sm" style="width: 35px; height: 35px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="fw-bold">{{ Auth::user()->nombre_completo ?? Auth::user()->nombre }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userProfile">
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger fw-bold">
                                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
                @endauth
            </div>
        </div>
    </nav>

    <main class="main-content" id="contenido-principal" tabindex="-1">
        <div class="container-fluid px-4">
            @yield('contenido')
        </div>
    </main>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/sita-theme.js') }}"></script>
</body>
</html>
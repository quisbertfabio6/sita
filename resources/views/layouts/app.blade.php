<!DOCTYPE html>
<html lang="es">
<head>
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
</head>
<body>

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
                            <a class="nav-link" href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-home me-1"></i> Inicio
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="academicoMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-graduation-cap me-1"></i> Gestión Académica
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="academicoMenu">
                                <li><a class="dropdown-item" href="{{ route('cursos.index') }}"><i class="fas fa-chalkboard me-2 text-muted"></i> Cursos</a></li>
                                <li><a class="dropdown-item" href="{{ route('materias.index') }}"><i class="fas fa-book me-2 text-muted"></i> Materias</a></li>
                                <li><a class="dropdown-item" href="{{ route('asignaciones.index') }}"><i class="fas fa-user-tag me-2 text-muted"></i> Asignaciones</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('usuarios.index') }}">
                                <i class="fas fa-users me-1"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('reportes.index') }}">
                                <i class="fas fa-chart-bar me-1"></i> Reportes
                            </a>
                        </li>

                    @elseif(Auth::user()->rol->nombre == 'jefe_carrera')
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('jefe.dashboard') }}">
                                <i class="fas fa-home me-1"></i> Inicio
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('jefe.licencias') }}">
                                <i class="fas fa-file-signature me-1"></i> Licencias
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('jefe.reportes.index') }}">
                                <i class="fas fa-chart-bar me-1"></i> Reportes
                            </a>
                        </li>

                    @elseif(Auth::user()->rol->nombre == 'docente')
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('docente.dashboard') }}">
                                <i class="fas fa-home me-1"></i> Inicio
                            </a>
                        </li>
                        @elseif(Auth::user()->rol->nombre == 'estudiante')
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('estudiante.dashboard') }}">
                                <i class="fas fa-home me-1"></i> Inicio
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('estudiante.asistencias') }}">
                                <i class="fas fa-user-check me-1"></i> Mis Asistencias
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('estudiante.licencias') }}">
                                <i class="fas fa-file-medical me-1"></i> Mis Licencias
                            </a>
                        </li>
                    @endif
                </ul>

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

    <main class="main-content">
        <div class="container-fluid px-4">
            @yield('contenido')
        </div>
    </main>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
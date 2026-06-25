<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITA - @yield('titulo')</title>
    
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/estilos.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    @php
        $rol = Auth::user()->rol->nombre;
        $sidebarColor = '#D32F2F'; // Rojo por defecto (Admin/Estudiante)
        $rolLabel = 'Panel SITA';

        if($rol == 'docente') {
            $sidebarColor = '#3F51B5'; // Azul Índigo
            $rolLabel = 'Panel Docente';
        } elseif ($rol == 'jefe_carrera') {
            $sidebarColor = '#B71C1C'; // Rojo Oscuro
            $rolLabel = 'Jefatura';
        } elseif ($rol == 'administrador') {
            $rolLabel = 'Administración';
        }
    @endphp

    <div class="wrapper">
        <nav id="sidebar" style="background: {{ $sidebarColor }};">
            <div class="sidebar-header">
                <h3><i class="fas fa-university"></i> SITA</h3>
                <p class="small">{{ $rolLabel }}</p>
            </div>

            <ul class="list-unstyled components">
                
                @if($rol == 'administrador')
                    <li><a href="{{ route('admin.dashboard') }}"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a href="{{ route('usuarios.index') }}"><i class="fas fa-users"></i> Usuarios</a></li>
                    <hr class="mx-3 opacity-25">
                    <small class="px-3 text-uppercase opacity-50">Académico</small>
                    <li><a href="{{ route('estudiantes.index') }}"><i class="fas fa-user-graduate"></i> Estudiantes</a></li>
                    <li><a href="{{ route('cursos.index') }}"><i class="fas fa-layer-group"></i> Cursos</a></li>
                    <li><a href="{{ route('materias.index') }}"><i class="fas fa-book"></i> Materias</a></li>
                    <li><a href="{{ route('asignaciones.index') }}"><i class="fas fa-chalkboard-teacher"></i> Asignaciones</a></li>
                    <li><a href="{{ route('reportes.index') }}"><i class="fas fa-file-pdf"></i> Reportes</a></li>
                
                    @elseif($rol == 'jefe_carrera')
                    <li><a href="{{ route('jefe.dashboard') }}"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a href="{{ route('jefe.licencias') }}"><i class="fas fa-file-signature"></i> Gestión Licencias</a></li>
                    <li><a href="{{ route('jefe.reportes.index') }}"><i class="fas fa-file-pdf"></i> Reportes</a></li>

                @elseif($rol == 'docente')
                    <li><a href="{{ route('docente.dashboard') }}"><i class="fas fa-home"></i> Mis Cursos</a></li>
                    @elseif($rol == 'estudiante')
                    <li><a href="{{ route('estudiante.dashboard') }}"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a href="{{ route('estudiante.asistencias') }}"><i class="fas fa-calendar-check"></i> Asistencias</a></li>
                    <li><a href="{{ route('estudiante.licencias') }}"><i class="fas fa-file-medical"></i> Trámites</a></li>
                @endif

                <hr class="mx-3 opacity-25">
                <li>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link text-white text-decoration-none w-100 text-start px-3 py-2">
                            <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                        </button>
                    </form>
                </li>
            </ul>
        </nav>

        <div id="content">
            <div class="overlay"></div>

            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 rounded">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn text-white" style="background: {{ $sidebarColor }}">
                        <i class="fas fa-bars"></i>
                    </button>

                    <span class="navbar-text fw-bold ms-3" style="color: {{ $sidebarColor }}">
                        @yield('titulo') </span>
                    
                    <div class="ms-auto d-flex align-items-center">
                        <span class="navbar-text text-dark d-none d-md-block">
                            {{ Auth::user()->nombre_completo }}
                        </span>
                        <i class="fas fa-user-circle fa-2x ms-2 text-secondary"></i>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                @yield('contenido')
            </div>
        </div>
    </div>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/chart.min.js') }}"></script>
    <script>
        // Script para el menú lateral responsivo
        document.addEventListener("DOMContentLoaded", function () {
            const sidebar = document.getElementById('sidebar');
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const overlay = document.querySelector('.overlay');

            function toggleSidebar() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }

            if(sidebarCollapse) sidebarCollapse.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);
        });
    </script>
</body>
</html>
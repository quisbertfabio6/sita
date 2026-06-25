@extends('layouts.app')

@section('contenido')
<div class="container-fluid">
    <h2 class="mb-4">Panel de Administración</h2>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Estudiantes</h5>
                    <p class="card-text display-4 font-weight-bold">{{ $datos['total_estudiantes'] }}</p>
                    <small>Inscritos en el sistema</small>
                </div>
                <div class="card-footer bg-primary border-0">
                    <a href="{{ route('estudiantes.index') }}" class="text-white text-decoration-none">Ver lista &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-white bg-success mb-3 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Docentes</h5>
                    <p class="card-text display-4 font-weight-bold">{{ $datos['total_docentes'] }}</p>
                    <small>Activos actualmente</small>
                </div>
                <div class="card-footer bg-success border-0">
                    <a href="{{ route('usuarios.index') }}" class="text-white text-decoration-none">Gestionar &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Materias</h5>
                    <p class="card-text display-4 font-weight-bold">{{ $datos['total_materias'] }}</p>
                    <small>Cursos habilitados</small>
                </div>
                <div class="card-footer bg-warning border-0">
                    <a href="{{ route('materias.index') }}" class="text-white text-decoration-none">Ver todas &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Usuarios</h5>
                    <p class="card-text display-4 font-weight-bold">{{ $datos['total_usuarios'] }}</p>
                    <small>Acceso al sistema</small>
                </div>
                <div class="card-footer bg-danger border-0">
                    <a href="{{ route('usuarios.index') }}" class="text-white text-decoration-none">Administrar &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white font-weight-bold">
                    Resumen de Asistencia (Hoy: {{ date('d/m/Y') }})
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="chartUsuarios"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
                    <span>Últimos Registros</span>
                    <a href="{{ route('usuarios.create') }}" class="btn btn-sm btn-outline-primary">+ Nuevo</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($datos['ultimos_usuarios'] as $usuario)
                            <tr>
                                <td>{{ $usuario->nombre_completo }}</td>
                                <td>
                                    <span class="badge badge-{{ $usuario->rol->nombre == 'administrador' ? 'danger' : ($usuario->rol->nombre == 'docente' ? 'success' : 'info') }}">
                                        {{ ucfirst($usuario->rol->nombre) }}
                                    </span>
                                </td>
                                <td>
                                    @if($usuario->activo)
                                        <span class="text-success">&#9679; Activo</span>
                                    @else
                                        <span class="text-secondary">&#9679; Inactivo</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center p-3">No hay usuarios recientes.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Verificamos si Chart.js cargó
    if (typeof Chart === 'undefined') {
        console.error('La librería Chart.js no está cargada. Revisa layouts/app.blade.php');
        return;
    }

    const ctx = document.getElementById('chartUsuarios').getContext('2d');
    
    // Datos desde Laravel (Labels ahora son: Presente, Falta, etc.)
    const labels = {!! json_encode($chart_labels) !!};
    const data = {!! json_encode($chart_data) !!};

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#28a745', // Verde (Presentes)
                    '#dc3545', // Rojo (Faltas)
                    '#ffc107', // Amarillo (Atrasos)
                    '#17a2b8'  // Cian (Licencias)
                ],
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>
@endsection
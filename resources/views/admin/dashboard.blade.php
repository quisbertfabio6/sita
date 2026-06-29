@extends('layouts.app')

@section('contenido')
<style>
    .soft-card {
        background: #ffffff;
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .soft-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    }
    .icon-box {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }
    .icon-primary { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
    .icon-success { background: rgba(25, 135, 84, 0.1); color: #198754; }
    .icon-danger  { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
    
    .kpi-title { font-size: 0.9rem; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
    .kpi-value { font-size: 2.2rem; font-weight: 800; color: #2b2b2b; margin-bottom: 0; }
    .kpi-link { font-size: 0.85rem; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 10px; }

    .table-modern { border-collapse: separate; border-spacing: 0 8px; }
    .table-modern thead th { border-bottom: none; color: #a0aec0; font-size: 0.8rem; text-transform: uppercase; font-weight: 600; letter-spacing: 1px; }
    .table-modern tbody tr { background-color: #f8f9fa; transition: background-color 0.2s; }
    .table-modern tbody tr:hover { background-color: #f1f3f5; }
    .table-modern tbody td { border: none; vertical-align: middle; padding: 15px; }
    .table-modern tbody td:first-child { border-radius: 10px 0 0 10px; }
    .table-modern tbody td:last-child { border-radius: 0 10px 10px 0; }
    
    .soft-badge { padding: 6px 15px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; }
    .badge-admin { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
    .badge-docente { background: rgba(25, 135, 84, 0.1); color: #198754; }
    .badge-info-soft { background: rgba(13, 202, 240, 0.1); color: #08a0c2; }
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="font-weight-bold text-dark mb-0">Panel de Administración</h2>
        <div class="text-muted bg-white px-3 py-2 rounded shadow-sm border">
            <i class="fas fa-calendar-alt me-2 text-primary"></i> <strong>{{ date('d/m/Y') }}</strong>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card soft-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="kpi-title">Estudiantes</h6>
                        <h2 class="kpi-value">{{ $datos['total_estudiantes'] }}</h2>
                    </div>
                    <div class="icon-box icon-primary">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
                <a href="{{ route('estudiantes.index') }}" class="kpi-link text-primary">Ver todos los estudiantes <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card soft-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="kpi-title">Plantel Institucional</h6>
                        <h2 class="kpi-value">{{ $datos['total_plantel'] }}</h2>
                    </div>
                    <div class="icon-box icon-success">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
                <a href="{{ route('usuarios.index', ['filtro' => 'plantel']) }}" class="kpi-link text-success">Ver docentes, jefes y admin <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card soft-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="kpi-title">Total Usuarios</h6>
                        <h2 class="kpi-value">{{ $datos['total_usuarios'] }}</h2>
                    </div>
                    <div class="icon-box icon-danger">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <a href="{{ route('usuarios.index', ['filtro' => 'todos']) }}" class="kpi-link text-danger">Ver sistema completo <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card soft-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="font-weight-bold text-dark mb-0">Resumen de Asistencia</h5>
                    <span class="badge bg-light text-dark border"><i class="fas fa-chart-pie me-1"></i> Hoy</span>
                </div>
                <div class="card-body p-0 d-flex align-items-center justify-content-center">
                    <div style="height: 280px; width: 100%;">
                        <canvas id="chartUsuarios"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card soft-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="font-weight-bold text-dark mb-0">Últimos Registros</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-modern w-100 mb-0">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Rol Asignado</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($datos['ultimos_usuarios'] as $usuario)
                            <tr>
                                <td class="font-weight-bold text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-white border rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 45px; height: 45px;">
                                            <i class="fas fa-user text-secondary"></i>
                                        </div>
                                        <span>{{ $usuario->nombre_completo }}</span>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = 'badge-info-soft'; 
                                        if($usuario->rol->nombre == 'administrador') $badgeClass = 'badge-admin';
                                        if($usuario->rol->nombre == 'docente') $badgeClass = 'badge-docente';
                                    @endphp
                                    <span class="soft-badge {{ $badgeClass }}">
                                        {{ ucfirst($usuario->rol->nombre) }}
                                    </span>
                                </td>
                                <td>
                                    @if($usuario->activo)
                                        <span class="text-success small fw-bold"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Activo</span>
                                    @else
                                        <span class="text-secondary small fw-bold"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Inactivo</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center p-5 text-muted">No hay usuarios recientes.</td>
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
    if (typeof Chart === 'undefined') return;
    const ctx = document.getElementById('chartUsuarios').getContext('2d');
    const labels = {!! json_encode($chart_labels) !!};
    const data = {!! json_encode($chart_data) !!};

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: ['#20c997', '#ff6b6b', '#feca57', '#48dbfb'],
                borderWidth: 0, hoverOffset: 8
            }]
        },
        options: { maintainAspectRatio: false, responsive: true, cutout: '75%', plugins: { legend: { position: 'bottom' } } }
    });
});
</script>
@endsection
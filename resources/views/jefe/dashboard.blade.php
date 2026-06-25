@extends('layouts.app')

@section('contenido')
<div class="container-fluid py-4">
    <h2 class="mb-4">Panel de Jefatura de Carrera</h2>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Licencias Pendientes</h5>
                    <p class="card-text display-4 font-weight-bold">{{ $kpis['licencias_pendientes'] }}</p>
                    <small>Requieren aprobación</small>
                </div>
                <div class="card-footer bg-warning border-0">
                    <a href="{{ route('jefe.licencias') }}" class="text-dark text-decoration-none font-weight-bold">Atender ahora &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-white bg-info mb-3 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Asistencia Global</h5>
                    <p class="card-text display-4 font-weight-bold">{{ $kpis['promedio_general'] }}%</p>
                    <small>Promedio de todos los cursos</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Cursos Activos</h5>
                    <p class="card-text display-4 font-weight-bold">{{ $kpis['total_cursos'] }}</p>
                    <small>Niveles académicos</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-white bg-secondary mb-3 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Materias</h5>
                    <p class="card-text display-4 font-weight-bold">{{ $kpis['total_materias'] }}</p>
                    <small>Asignaturas registradas</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white font-weight-bold">
                    <i class="fas fa-chart-bar text-primary mr-2"></i> Asistencia Promedio por Curso
                </div>
                <div class="card-body">
                    <div style="height: 350px;">
                        <canvas id="barChartCursos"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white font-weight-bold">
                    <i class="fas fa-chart-pie text-warning mr-2"></i> Estado de Licencias
                </div>
                <div class="card-body">
                    <div style="height: 250px;">
                        <canvas id="pieChartLicencias"></canvas>
                    </div>
                    <div class="mt-4 text-center">
                        <small class="text-muted">Distribución total de solicitudes</small>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // Verificación de seguridad
    if (typeof Chart === 'undefined') {
        console.error('Chart.js no está cargado.');
        return;
    }

    // --- GRÁFICO DE BARRAS (Asistencia por Curso) ---
    const ctxBar = document.getElementById('barChartCursos').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: {!! json_encode($barLabels) !!},
            datasets: [{
                label: '% Asistencia Promedio',
                data: {!! json_encode($barData) !!},
                backgroundColor: 'rgba(54, 162, 235, 0.6)', // Azul transparente
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: { display: true, text: 'Porcentaje (%)' }
                }
            }
        }
    });

    // --- GRÁFICO DE DONA (Licencias) ---
    const ctxPie = document.getElementById('pieChartLicencias').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ['Aprobadas', 'Rechazadas', 'Pendientes'],
            datasets: [{
                data: {!! json_encode($pieData) !!},
                backgroundColor: [
                    '#28a745', // Verde
                    '#dc3545', // Rojo
                    '#ffc107'  // Amarillo
                ],
                hoverOffset: 4
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
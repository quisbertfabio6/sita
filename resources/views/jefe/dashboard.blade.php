@extends('layouts.app')

@section('contenido')
<style>
    /* Estilos Minimalistas y Soft UI */
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
    
    /* Cajas de los Iconos */
    .icon-box { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
    .icon-primary { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
    .icon-success { background: rgba(25, 135, 84, 0.1); color: #198754; }
    .icon-warning { background: rgba(255, 193, 7, 0.15); color: #ffc107; }
    .icon-info    { background: rgba(13, 202, 240, 0.1); color: #0dcaf0; }
    
    /* Textos de los KPIs */
    .kpi-title { font-size: 0.85rem; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
    .kpi-value { font-size: 2.2rem; font-weight: 800; color: #2b2b2b; margin-bottom: 0; }
    .kpi-link { font-size: 0.85rem; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 10px; }

    /* --- MAGIA DEL CARRUSEL/SCROLL HORIZONTAL --- */
    .scrollable-chart-container {
        overflow-x: auto;
        overflow-y: hidden;
        padding-bottom: 15px; /* Espacio para la barra */
    }
    
    /* Personalización de la barra de desplazamiento (Scrollbar moderna) */
    .scrollable-chart-container::-webkit-scrollbar {
        height: 8px; /* Barra delgada */
    }
    .scrollable-chart-container::-webkit-scrollbar-track {
        background: #f8f9fa; 
        border-radius: 10px;
    }
    .scrollable-chart-container::-webkit-scrollbar-thumb {
        background: #cbd5e1; 
        border-radius: 10px;
    }
    .scrollable-chart-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8; 
    }
</style>

<div class="container-fluid py-3">
    
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="font-weight-bold text-dark mb-0">Panel de Jefatura de Carrera</h2>
            <small class="text-muted">Resumen estadístico y gestión de solicitudes</small>
        </div>
        <div class="text-muted bg-white px-3 py-2 rounded shadow-sm border">
            <i class="fas fa-calendar-alt me-2 text-danger"></i> <strong>{{ date('d/m/Y') }}</strong>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card soft-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="kpi-title">Licencias Pendientes</h6>
                        <h2 class="kpi-value text-warning">{{ $kpis['licencias_pendientes'] }}</h2>
                    </div>
                    <div class="icon-box icon-warning"><i class="fas fa-file-signature"></i></div>
                </div>
                <a href="{{ route('jefe.licencias') }}" class="kpi-link text-warning">Atender solicitudes <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card soft-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="kpi-title">Asistencia Global</h6>
                        <h2 class="kpi-value">{{ $kpis['promedio_general'] }}<span style="font-size: 1.2rem; color: #6c757d;">%</span></h2>
                    </div>
                    <div class="icon-box icon-info"><i class="fas fa-chart-line"></i></div>
                </div>
                <span class="kpi-link text-muted"><i class="fas fa-info-circle me-1"></i> Promedio de carreras</span>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card soft-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="kpi-title">Cursos Activos</h6>
                        <h2 class="kpi-value">{{ $kpis['total_cursos'] }}</h2>
                    </div>
                    <div class="icon-box icon-primary"><i class="fas fa-chalkboard"></i></div>
                </div>
                <span class="kpi-link text-muted"><i class="fas fa-check-circle me-1"></i> Niveles académicos</span>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card soft-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="kpi-title">Materias Registradas</h6>
                        <h2 class="kpi-value">{{ $kpis['total_materias'] }}</h2>
                    </div>
                    <div class="icon-box icon-success"><i class="fas fa-book-open"></i></div>
                </div>
                <span class="kpi-link text-muted"><i class="fas fa-layer-group me-1"></i> Asignaturas totales</span>
            </div>
        </div>
    </div>

    <div class="row">
        
        <div class="col-lg-8 mb-4">
            <div class="card soft-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="font-weight-bold text-dark mb-0"><i class="fas fa-chart-bar text-primary me-2"></i> Asistencia Promedio por Curso</h5>
                    <span class="badge bg-light text-primary border px-3 py-2 rounded-pill d-none d-sm-inline-block">Desliza para ver más <i class="fas fa-arrows-alt-h ms-1"></i></span>
                </div>
                
                <div class="card-body p-0 scrollable-chart-container">
                    <div id="chartAreaContainer" style="position: relative; height: 320px; width: 100%;">
                        <canvas id="barChartCursos"></canvas>
                    </div>
                </div>
                <div class="text-center mt-2 d-sm-none text-muted small"><i class="fas fa-arrows-alt-h me-1"></i> Desliza horizontalmente</div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card soft-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="font-weight-bold text-dark mb-0"><i class="fas fa-file-medical text-warning me-2"></i> Estado de Licencias</h5>
                </div>
                <div class="card-body p-0 d-flex flex-column align-items-center justify-content-center">
                    <div style="height: 250px; width: 100%;">
                        <canvas id="pieChartLicencias"></canvas>
                    </div>
                    <div class="mt-4 text-center">
                        <p class="text-muted small fw-bold mb-0">Distribución total de solicitudes</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    
    if (typeof Chart === 'undefined') {
        console.error('Chart.js no está cargado.');
        return;
    }

    // --- MAGIA PARA EL ANCHO DINÁMICO DEL GRÁFICO DE BARRAS ---
    const labelsCursos = {!! json_encode($barLabels) !!};
    const chartAreaContainer = document.getElementById('chartAreaContainer');
    
    // Calculamos: Si hay muchos cursos, cada uno necesita mínimo 60px de espacio para verse bien.
    const anchoMinimoRequerido = labelsCursos.length * 60;
    
    // Si el ancho calculado es mayor al de la pantalla, estiramos el contenedor interno
    // Esto es lo que provoca que aparezca la barra de scroll y nada se apriete.
    if(anchoMinimoRequerido > chartAreaContainer.parentElement.clientWidth) {
        chartAreaContainer.style.width = anchoMinimoRequerido + 'px';
    }

    // --- DIBUJAR GRÁFICO DE BARRAS ---
    const ctxBar = document.getElementById('barChartCursos').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: labelsCursos,
            datasets: [{
                label: '% Asistencia Promedio',
                data: {!! json_encode($barData) !!},
                backgroundColor: 'rgba(13, 110, 253, 0.7)', 
                hoverBackgroundColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 0,
                borderRadius: 6, 
                barPercentage: 0.5 // Barras más delgadas y estéticas
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: { display: false } 
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        // Opcional: Rota ligeramente los textos si son muy largos
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 11 }
                    }
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: 'rgba(0, 0, 0, 0.05)', borderDash: [5, 5] },
                    ticks: { callback: function(value) { return value + '%' } }
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
                backgroundColor: ['#20c997', '#ff6b6b', '#feca57'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            cutout: '75%', 
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: { padding: 20, usePointStyle: true, font: { size: 13, weight: '500' } }
                }
            }
        }
    });
});
</script>
@endsection
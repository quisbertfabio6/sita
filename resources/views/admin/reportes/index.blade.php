@extends('layouts.app')

@section('titulo', 'Generador de Reportes')

@section('contenido')
<style>
    /* Estilos Soft UI */
    .soft-card { background: #ffffff; border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); }
    
    /* Caja seleccionadora principal */
    .materia-box { border: 2px dashed #dc3545; border-radius: 10px; padding: 15px; text-align: center; background: #fff5f5; cursor: pointer; transition: 0.3s; }
    .materia-box:hover { background: #ffebeb; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(220, 53, 69, 0.1); }
    .materia-box.selected { border-color: #198754; background: #f0fdf4; }
    
    /* Estilos tabla dentro del modal */
    .table-modal tbody tr { transition: background 0.2s; }
    .table-modal tbody tr:hover { background-color: #f8f9fa; }
    
    /* Buscador del modal */
    .search-modal { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 30px; padding: 8px 20px; }
    .search-modal input { border: none; outline: none; box-shadow: none; background: transparent; width: 100%; font-size: 0.95rem; }
</style>

<div class="row justify-content-center py-4">
    <div class="col-lg-8">
        <div class="card soft-card">
            <div class="card-header bg-white border-bottom py-3 rounded-top" style="border-radius: 15px 15px 0 0;">
                <h5 class="mb-0 text-danger fw-bold"><i class="fas fa-file-pdf me-2"></i> Reporte de Asistencia por Rango</h5>
            </div>
            <div class="card-body p-5">
                
                <form action="{{ route('reportes.generar') }}" method="POST" target="_blank" id="formReporte">
                    @csrf

                    <div class="mb-5">
                        <label class="form-label fw-bold text-dark">1. Seleccione la Materia</label>
                        
                        <input type="hidden" name="materia_id" id="materia_id" required>
                        
                        <div class="materia-box mt-2" id="boxSelector" data-bs-toggle="modal" data-bs-target="#modalMaterias">
                            <h5 id="materia_seleccionada_texto" class="text-danger fw-bold mb-1">
                                <i class="fas fa-search me-2"></i> Clic aquí para buscar la materia
                            </h5>
                            <small id="materia_seleccionada_sub" class="text-muted">El reporte se generará en base a su selección.</small>
                        </div>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">2. Fecha de Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control form-control-lg bg-light border-0 shadow-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">3. Fecha de Fin</label>
                            <input type="date" name="fecha_fin" class="form-control form-control-lg bg-light border-0 shadow-sm" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-danger btn-lg rounded-pill px-5 fw-bold shadow-sm">
                            <i class="fas fa-cogs me-2"></i> Generar Reporte PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMaterias" tabindex="-1" aria-labelledby="modalMateriasLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #b71c1c 0%, #D32F2F 100%); border-radius: 15px 15px 0 0;">
                <h5 class="modal-title fw-bold" id="modalMateriasLabel"><i class="fas fa-book me-2"></i> Directorio de Materias</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            
            <div class="modal-body p-4">
                
                <div class="d-flex align-items-center search-modal mb-4 shadow-sm">
                    <i class="fas fa-search text-muted me-2"></i>
                    <input type="text" id="buscadorModal" placeholder="Escribe el nombre de la materia o del curso...">
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-modal align-middle mb-0" id="tablaMaterias">
                        <thead class="bg-light text-secondary">
                            <tr>
                                <th class="ps-3">Asignatura</th>
                                <th>Curso / Paralelo</th>
                                <th class="text-end pe-3">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($materias as $materia)
                            <tr>
                                <td class="ps-3 fw-bold text-dark materia-nombre">{{ $materia->nombre }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border border-secondary materia-curso px-2 py-1">
                                        <i class="fas fa-chalkboard text-primary me-1"></i> {{ $materia->curso->nombre ?? 'Sin curso' }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold btn-seleccionar" 
                                            data-id="{{ $materia->id }}" 
                                            data-texto="{{ $materia->nombre }}"
                                            data-curso="{{ $materia->curso->nombre ?? 'Sin curso' }}">
                                        Elegir
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div id="noResultados" class="text-center text-muted py-5 d-none">
                    <i class="fas fa-search-minus fa-3x mb-3 text-light"></i><br>
                    <h6 class="fw-bold">No se encontraron resultados</h6>
                    <small>Intenta buscar con otras palabras.</small>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        const buscadorModal = document.getElementById('buscadorModal');
        const filasMaterias = document.querySelectorAll('#tablaMaterias tbody tr');
        const mensajeNoResultados = document.getElementById('noResultados');
        
        const inputOcultoMateria = document.getElementById('materia_id');
        const boxSelector = document.getElementById('boxSelector');
        const textoMateriaSeleccionada = document.getElementById('materia_seleccionada_texto');
        const subMateriaSeleccionada = document.getElementById('materia_seleccionada_sub');
        
        const botonesSeleccionar = document.querySelectorAll('.btn-seleccionar');

        // 1. Lógica del Buscador en tiempo real
        buscadorModal.addEventListener('keyup', function() {
            const textoBusqueda = this.value.toLowerCase();
            let resultadosVisibles = 0;

            filasMaterias.forEach(fila => {
                const nombreMateria = fila.querySelector('.materia-nombre').textContent.toLowerCase();
                const nombreCurso = fila.querySelector('.materia-curso').textContent.toLowerCase();
                
                // Comparamos si lo que escribimos coincide con la materia o el curso
                if(nombreMateria.includes(textoBusqueda) || nombreCurso.includes(textoBusqueda)) {
                    fila.style.display = '';
                    resultadosVisibles++;
                } else {
                    fila.style.display = 'none';
                }
            });

            // Mostramos la imagen de "No hay resultados" si todo se ocultó
            if(resultadosVisibles === 0) {
                mensajeNoResultados.classList.remove('d-none');
            } else {
                mensajeNoResultados.classList.add('d-none');
            }
        });

        // 2. Lógica al presionar el botón "Elegir"
        botonesSeleccionar.forEach(boton => {
            boton.addEventListener('click', function() {
                const idMateria = this.getAttribute('data-id');
                const nombreMateria = this.getAttribute('data-texto');
                const nombreCurso = this.getAttribute('data-curso');

                // Llenamos el input oculto silenciosamente
                inputOcultoMateria.value = idMateria;
                
                // Transformamos la caja para que se vea que ya se eligió algo (Verde)
                boxSelector.classList.add('selected');
                
                textoMateriaSeleccionada.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i> ' + nombreMateria;
                textoMateriaSeleccionada.classList.remove('text-danger');
                textoMateriaSeleccionada.classList.add('text-success');
                
                subMateriaSeleccionada.innerHTML = 'Curso: <strong>' + nombreCurso + '</strong> (Clic para cambiar)';

                // Cerramos el modal
                const modalEl = document.getElementById('modalMaterias');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
            });
        });

        // 3. Autolimpieza al abrir el modal
        const modalMaterias = document.getElementById('modalMaterias');
        modalMaterias.addEventListener('show.bs.modal', function () {
            buscadorModal.value = ''; // Limpia el texto anterior
            filasMaterias.forEach(fila => fila.style.display = ''); // Muestra todas las filas
            mensajeNoResultados.classList.add('d-none');
            
            // Hace que el teclado escriba automáticamente en el buscador sin tener que hacer clic
            setTimeout(() => { buscadorModal.focus(); }, 400); 
        });
    });
</script>
@endsection
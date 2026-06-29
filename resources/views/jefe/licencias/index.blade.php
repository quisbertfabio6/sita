@extends('layouts.app')

@section('titulo', 'Gestión de Licencias')

@section('contenido')
<style>
    /* Estilos Soft UI para la Tabla */
    .soft-card { background: #ffffff; border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); }
    .table-modern { border-collapse: separate; border-spacing: 0 8px; }
    .table-modern thead th { border-bottom: none; color: #a0aec0; font-size: 0.8rem; text-transform: uppercase; font-weight: 600; letter-spacing: 1px; }
    .table-modern tbody tr { background-color: #f8f9fa; transition: background-color 0.2s; }
    .table-modern tbody tr:hover { background-color: #f1f3f5; }
    .table-modern tbody td { border: none; vertical-align: middle; padding: 15px; }
    .table-modern tbody td:first-child { border-radius: 10px 0 0 10px; }
    .table-modern tbody td:last-child { border-radius: 0 10px 10px 0; }
    
    /* Etiquetas (Soft Badges) */
    .soft-badge { padding: 6px 15px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; letter-spacing: 0.5px; display: inline-flex; align-items: center;}
    .badge-aprobada { background: rgba(25, 135, 84, 0.1); color: #198754; }
    .badge-rechazada { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
    .badge-pendiente { background: rgba(255, 193, 7, 0.15); color: #d39e00; }
</style>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="text-dark fw-bold mb-0">Bandeja de Solicitudes</h4>
            <small class="text-muted">Aprobación y rechazo de licencias de estudiantes</small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" style="border-radius: 10px;" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card soft-card p-4">
        <div class="table-responsive">
            <table class="table table-modern w-100 mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Estudiante / Curso</th>
                        <th>Motivo</th>
                        <th>Fechas</th>
                        <th>Evidencia</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($licencias as $licencia)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-white border rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 40px; height: 40px;">
                                    <i class="fas fa-user-graduate text-secondary"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $licencia->estudiante->usuario->nombre_completo }}</div>
                                    <span class="badge bg-white text-dark border shadow-sm mt-1 px-2 py-1" style="border-radius: 6px;">
                                        <i class="fas fa-chalkboard text-primary me-1"></i> {{ $licencia->estudiante->curso->nombre }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted fst-italic" style="max-width: 250px;">
                            "{{ Str::limit($licencia->motivo, 40) }}"
                        </td>
                        <td>
                            <div class="small fw-bold text-dark">
                                <i class="far fa-calendar-alt text-primary me-1"></i> {{ \Carbon\Carbon::parse($licencia->fecha_inicio)->format('d/m/Y') }} <br>
                                <i class="fas fa-arrow-down text-muted mx-1 my-1"></i> {{ \Carbon\Carbon::parse($licencia->fecha_fin)->format('d/m/Y') }}
                            </div>
                        </td>
                        <td>
                            @if($licencia->archivo_adjunto)
                                <a href="{{ asset('storage/' . $licencia->archivo_adjunto) }}" target="_blank" class="btn btn-sm btn-light text-primary shadow-sm rounded-pill fw-bold px-3">
                                    <i class="fas fa-paperclip me-1"></i> Ver Adjunto
                                </a>
                            @else
                                <span class="text-muted small"><i class="fas fa-minus"></i></span>
                            @endif
                        </td>
                        <td>
                            @if($licencia->estado == 'pendiente')
                                <span class="soft-badge badge-pendiente"><i class="fas fa-clock me-1"></i> Pendiente</span>
                            @elseif($licencia->estado == 'aprobada')
                                <span class="soft-badge badge-aprobada"><i class="fas fa-check-circle me-1"></i> Aprobada</span>
                            @else
                                <span class="soft-badge badge-rechazada"><i class="fas fa-times-circle me-1"></i> Rechazada</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            @if($licencia->estado == 'pendiente')
                                <button class="btn btn-sm btn-light text-success shadow-sm rounded-circle me-1" style="width: 35px; height: 35px;" onclick="abrirModal('{{ $licencia->id }}', 'aprobada')" title="Aprobar">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger shadow-sm rounded-circle" style="width: 35px; height: 35px;" onclick="abrirModal('{{ $licencia->id }}', 'rechazada')" title="Rechazar">
                                    <i class="fas fa-times"></i>
                                </button>
                            @else
                                <span class="badge bg-light text-muted border px-3 py-2 rounded-pill"><i class="fas fa-lock me-1"></i> Procesado</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                            No tienes solicitudes pendientes en tus carreras asignadas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalGestion" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                <form id="formGestion" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-header border-0 pb-0 mt-2 mx-2">
                        <h5 class="modal-title fw-bold" id="modalTitulo">Gestionar Licencia</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4">
                        <input type="hidden" name="estado" id="inputEstado">
                        
                        <div class="alert alert-light border" id="textoConfirmacion" style="border-radius: 10px;">
                            ¿Estás seguro?
                        </div>

                        <div class="mb-2 mt-4">
                            <label class="form-label fw-bold text-dark small">Comentario / Observación (Opcional):</label>
                            <textarea name="comentario_admin" class="form-control bg-light border-0 shadow-sm" rows="3" placeholder="Escribe una razón o mensaje para el estudiante..." style="border-radius: 10px;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 mx-2 mb-2">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" id="btnConfirmar">Confirmar Acción</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function abrirModal(id, estado) {
            var form = document.getElementById('formGestion');
            form.action = '/jefe/licencias/' + id;
            document.getElementById('inputEstado').value = estado;
            
            var titulo = document.getElementById('modalTitulo');
            var btn = document.getElementById('btnConfirmar');
            var texto = document.getElementById('textoConfirmacion');

            if(estado === 'aprobada') {
                titulo.innerHTML = '<i class="fas fa-check-circle me-2"></i> Aprobar Solicitud';
                titulo.className = "modal-title fw-bold text-success";
                btn.className = "btn btn-success rounded-pill px-4 fw-bold shadow-sm";
                btn.innerText = "Sí, Aprobar Licencia";
                texto.className = "alert alert-success border-0 text-center mb-0";
                texto.innerHTML = "<strong>Atención:</strong> Estás a punto de APROBAR esta licencia. Las inasistencias en este rango de fechas quedarán justificadas automáticamente.";
            } else {
                titulo.innerHTML = '<i class="fas fa-times-circle me-2"></i> Rechazar Solicitud';
                titulo.className = "modal-title fw-bold text-danger";
                btn.className = "btn btn-danger rounded-pill px-4 fw-bold shadow-sm";
                btn.innerText = "Sí, Rechazar Licencia";
                texto.className = "alert alert-danger border-0 text-center mb-0";
                texto.innerHTML = "<strong>Atención:</strong> Estás a punto de RECHAZAR esta licencia. El estudiante será notificado.";
            }

            var modal = new bootstrap.Modal(document.getElementById('modalGestion'));
            modal.show();
        }
    </script>
@endsection
@extends('layouts.app')

@section('titulo', 'Gestión de Licencias')

@section('contenido')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-dark fw-bold">Bandeja de Entrada de Solicitudes</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-secondary">
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
                                <div class="fw-bold text-dark">{{ $licencia->estudiante->usuario->nombre_completo }}</div>
                                <span class="badge bg-light text-dark border">{{ $licencia->estudiante->curso->nombre }}</span>
                            </td>
                            <td class="text-muted fst-italic">
                                "{{ Str::limit($licencia->motivo, 40) }}"
                            </td>
                            <td>
                                <div class="small">
                                    <i class="far fa-calendar text-primary"></i> {{ $licencia->fecha_inicio }} <br>
                                    <i class="fas fa-arrow-down text-muted mx-1"></i> {{ $licencia->fecha_fin }}
                                </div>
                            </td>
                            <td>
                                @if($licencia->archivo_adjunto)
                                    <a href="{{ asset('storage/' . $licencia->archivo_adjunto) }}" target="_blank" class="btn btn-sm btn-outline-primary border-0">
                                        <i class="fas fa-paperclip me-1"></i> Ver Archivo
                                    </a>
                                @else
                                    <span class="text-muted small">--</span>
                                @endif
                            </td>
                            <td>
                                @if($licencia->estado == 'pendiente')
                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pendiente</span>
                                @elseif($licencia->estado == 'aprobada')
                                    <span class="badge bg-success"><i class="fas fa-check"></i> Aprobada</span>
                                @else
                                    <span class="badge bg-danger"><i class="fas fa-times"></i> Rechazada</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @if($licencia->estado == 'pendiente')
                                    <button class="btn btn-sm btn-success fw-bold shadow-sm me-1" onclick="abrirModal('{{ $licencia->id }}', 'aprobada')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger fw-bold shadow-sm" onclick="abrirModal('{{ $licencia->id }}', 'rechazada')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-light text-muted border" disabled>Procesado</button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i><br>
                                No tienes solicitudes pendientes en tus carreras asignadas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalGestion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <form id="formGestion" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold" id="modalTitulo">Gestionar Licencia</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="estado" id="inputEstado">
                        
                        <div class="alert alert-light border" id="textoConfirmacion">
                            ¿Estás seguro?
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Comentario / Observación:</label>
                            <textarea name="comentario_admin" class="form-control" rows="3" placeholder="Escribe una razón o comentario para el estudiante..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold" id="btnConfirmar">Confirmar Acción</button>
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
                titulo.innerText = "Aprobar Solicitud";
                titulo.className = "modal-title fw-bold text-success";
                btn.className = "btn btn-success fw-bold";
                btn.innerText = "Sí, Aprobar";
                texto.className = "alert alert-success border-0 text-center";
                texto.innerText = "Estás a punto de APROBAR esta licencia.";
            } else {
                titulo.innerText = "Rechazar Solicitud";
                titulo.className = "modal-title fw-bold text-danger";
                btn.className = "btn btn-danger fw-bold";
                btn.innerText = "Sí, Rechazar";
                texto.className = "alert alert-danger border-0 text-center";
                texto.innerText = "Estás a punto de RECHAZAR esta licencia.";
            }

            var modal = new bootstrap.Modal(document.getElementById('modalGestion'));
            modal.show();
        }
    </script>
@endsection
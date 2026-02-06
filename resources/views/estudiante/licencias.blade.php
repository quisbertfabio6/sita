@extends('layouts.app')

@section('titulo', 'Mis Trámites de Licencia')

@section('contenido')

    <div class="alert alert-info border-0 shadow-sm mb-4">
        <i class="fas fa-info-circle me-2"></i> Para solicitar una nueva licencia o anular una pendiente, utiliza la <b>App Móvil</b>.
    </div>

    <h4 class="text-secondary fw-bold mb-4">Historial de Solicitudes</h4>

    <div class="row g-4">
        @forelse($licencias as $lic)
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title fw-bold text-dark">Solicitud #{{ $lic->id }}</h5>
                        
                        @if($lic->estado == 'aprobada')
                            <span class="badge fs-6 bg-success"><i class="fas fa-check me-1"></i> Aprobada</span>
                        @elseif($lic->estado == 'rechazada')
                            <span class="badge fs-6 bg-danger"><i class="fas fa-times me-1"></i> Rechazada</span>
                        @else
                            <span class="badge fs-6 bg-warning text-dark"><i class="fas fa-clock me-1"></i> Pendiente</span>
                        @endif
                    </div>
                    
                    <p class="text-muted small mb-2">
                        <b><i class="fas fa-calendar-alt me-1"></i> Fechas:</b> 
                        {{ $lic->fecha_inicio }} al {{ $lic->fecha_fin }}
                    </p>
                    <p class="mb-3">
                        <b><i class="fas fa-comment me-1"></i> Motivo:</b> {{ $lic->motivo }}
                    </p>
                    
                    @if($lic->archivo_adjunto)
                        <a href="{{ asset('storage/' . $lic->archivo_adjunto) }}" target="_blank" class="btn btn-sm btn-outline-primary border-0">
                            <i class="fas fa-paperclip me-1"></i> Ver Archivo Adjunto
                        </a>
                    @endif

                    @if($lic->comentario_admin)
                        <div class="alert alert-light border border-secondary p-2 small mt-3">
                            <b><i class="fas fa-user-tie me-1"></i> Observación:</b> 
                            {{ $lic->comentario_admin }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <div class="py-5 bg-white rounded shadow-sm">
                <i class="fas fa-folder-open fa-4x text-muted mb-3 opacity-50"></i>
                <h4 class="text-muted">No has enviado ninguna solicitud.</h4>
            </div>
        </div>
        @endforelse
    </div>

@endsection
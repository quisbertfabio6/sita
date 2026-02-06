@extends('layouts.app')

@section('titulo')
    {{ $materia->nombre }} - {{ $materia->curso->nombre }}
@endsection

@section('contenido')
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">{{ $materia->nombre }}</h3>
            <h5 class="text-muted">{{ $materia->curso->nombre }}</h5>
        </div>
        <a href="{{ route('reportes.lista', $materia->id) }}" target="_blank" class="btn btn-dark shadow-sm">
            <i class="fas fa-file-pdf me-2"></i> Generar PDF Oficial
        </a>
    </div>

    <div class="card shadow border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-bold text-secondary"><i class="fas fa-user-graduate me-2"></i> Estudiantes Inscritos</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-4">Estudiante</th>
                            <th>Matrícula</th>
                            <th>Estado Licencias</th>
                            <th>Últimas Clases</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($estudiantes as $est)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $est->usuario->nombre_completo }}</div>
                                <small class="text-muted">{{ $est->usuario->email }}</small>
                            </td>
                            <td class="text-secondary fw-bold">{{ $est->matricula }}</td>
                            <td>
                                @if($est->licencias->count() > 0)
                                    <span class="badge bg-warning text-dark border border-warning">
                                        <i class="fas fa-exclamation-circle me-1"></i> Licencia Vigente
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border">
                                        <i class="fas fa-check-circle me-1"></i> Sin Novedad
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @foreach($est->asistencias as $asist)
                                        @if($asist->estado == 'presente')
                                            <span class="badge rounded-pill bg-success" title="{{ $asist->fecha }}">P</span>
                                        @elseif($asist->estado == 'falta')
                                            <span class="badge rounded-pill bg-danger" title="{{ $asist->fecha }}">F</span>
                                        @elseif($asist->estado == 'atraso')
                                            <span class="badge rounded-pill bg-warning text-dark" title="{{ $asist->fecha }}">A</span>
                                        @else
                                            <span class="badge rounded-pill bg-info" title="{{ $asist->fecha }}">L</span>
                                        @endif
                                    @endforeach
                                    @if($est->asistencias->isEmpty())
                                        <span class="text-muted small fst-italic">Sin registros</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fas fa-folder-open fa-3x mb-3 opacity-25"></i><br>
                                No hay estudiantes inscritos en este curso.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
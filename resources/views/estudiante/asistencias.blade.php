@extends('layouts.app')

@section('titulo', 'Mi Historial de Asistencias')

@section('contenido')
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-secondary fw-bold">Historial Completo de Asistencias</h4>
        <a href="{{ route('reportes.personal') }}" target="_blank" class="btn btn-dark shadow-sm">
            <i class="fas fa-file-pdf me-2"></i> Descargar Historial Completo
        </a>
    </div>

    <div class="card shadow border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-4">Fecha</th>
                            <th>Hora</th>
                            <th>Materia</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historial as $asis)
                        <tr>
                            <td class="ps-4">{{ $asis->fecha }}</td>
                            <td>{{ $asis->hora }}</td>
                            <td class="fw-bold">{{ $asis->materia->nombre }}</td>
                            <td>
                                @if($asis->estado == 'presente')
                                    <span class="badge bg-success">Presente</span>
                                @elseif($asis->estado == 'falta')
                                    <span class="badge bg-danger">Falta</span>
                                @elseif($asis->estado == 'atraso')
                                    <span class="badge bg-warning text-dark">Atraso</span>
                                @else
                                    <span class="badge bg-info">Licencia</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fas fa-folder-open fa-3x mb-3 opacity-25"></i><br>
                                Aún no tienes ningún registro.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
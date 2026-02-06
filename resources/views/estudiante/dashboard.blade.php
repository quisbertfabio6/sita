@extends('layouts.app')

@section('titulo', 'Mi Panel de Control')

@section('contenido')
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 fw-bold">Hola, {{ Auth::user()->nombre_completo }}</h4>
                        <p class="text-muted mb-0">
                            {{ $estudiante->curso->nombre ?? 'Sin Curso' }} | Matrícula: {{ $estudiante->matricula }}
                        </p>
                    </div>
                    <div class="text-end d-none d-md-block">
                        <span class="badge bg-dark p-2 fs-6">
                            <i class="fas fa-qrcode me-1"></i> {{ $estudiante->codigo_qr }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-3x mb-2 opacity-50"></i>
                    <h1 class="fw-bold mb-0">{{ $asistencias }}</h1>
                    <p class="mb-0">Asistencias</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm bg-danger text-white">
                <div class="card-body text-center">
                    <i class="fas fa-times-circle fa-3x mb-2 opacity-50"></i>
                    <h1 class="fw-bold mb-0">{{ $faltas }}</h1>
                    <p class="mb-0">Faltas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-3x mb-2 opacity-50"></i>
                    <h1 class="fw-bold mb-0">{{ $atrasos }}</h1>
                    <p class="mb-0">Atrasos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-file-alt fa-3x mb-2 opacity-50"></i>
                    <h1 class="fw-bold mb-0">{{ $licencias }}</h1>
                    <p class="mb-0">Licencias</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-secondary">Actividad Reciente</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Fecha</th>
                            <th>Materia</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ultimasAsistencias as $asis)
                        <tr>
                            <td class="ps-4">{{ $asis->fecha }} <small class="text-muted">({{ $asis->hora }})</small></td>
                            <td class="fw-bold text-primary">{{ $asis->materia->nombre }}</td>
                            <td>
                                @if($asis->estado == 'presente') <span class="badge bg-success">Presente</span>
                                @elseif($asis->estado == 'falta') <span class="badge bg-danger">Falta</span>
                                @elseif($asis->estado == 'atraso') <span class="badge bg-warning text-dark">Atraso</span>
                                @else <span class="badge bg-info">Licencia</span> @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">No hay registros recientes.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
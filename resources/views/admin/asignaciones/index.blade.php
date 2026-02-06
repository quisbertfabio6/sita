@extends('layouts.app')

@section('titulo', 'Gestión de Asignaciones')

@section('contenido')
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-secondary fw-bold">Carga Horaria Docente</h4>
        <a href="{{ route('asignaciones.create') }}" class="btn btn-danger shadow-sm">
            <i class="fas fa-plus"></i> Nueva Asignación
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-4">Docente</th>
                            <th>Materia</th>
                            <th>Curso / Paralelo</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($asignaciones as $asig)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">
                                <i class="fas fa-user-tie text-muted me-2"></i>
                                {{ $asig->docente->usuario->nombre_completo }}
                            </td>
                            <td>
                                <span class="fw-bold text-primary">{{ $asig->materia->nombre }}</span>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark">
                                    {{ $asig->materia->curso->nombre }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <form action="{{ route('asignaciones.destroy', $asig->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Quitar esta materia al docente?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light text-danger" title="Eliminar Asignación">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
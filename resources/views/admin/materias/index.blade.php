@extends('layouts.app')

@section('titulo', 'Gestión de Materias')

@section('contenido')
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-secondary fw-bold">Listado de Asignaturas</h4>
        <a href="{{ route('materias.create') }}" class="btn btn-success fw-bold shadow-sm">
            <i class="fas fa-plus"></i> Nueva Materia
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
                            <th class="ps-4">Nombre de Materia</th>
                            <th>Sigla</th>
                            <th>Curso Perteneciente</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($materias as $materia)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $materia->nombre }}</td>
                            <td>
                                @if($materia->sigla)
                                    <span class="badge bg-secondary">{{ $materia->sigla }}</span>
                                @else
                                    <span class="text-muted small">--</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border border-secondary">
                                    {{ $materia->curso->nombre }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('materias.edit', $materia->id) }}" class="btn btn-sm btn-light text-primary border" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('materias.destroy', $materia->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Borrar esta materia?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light text-danger border" title="Eliminar">
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
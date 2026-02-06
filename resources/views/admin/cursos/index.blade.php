@extends('layouts.app')

@section('titulo', 'Gestión de Cursos')

@section('contenido')
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-secondary fw-bold">Cursos y Paralelos</h4>
        <a href="{{ route('cursos.create') }}" class="btn btn-warning text-dark fw-bold shadow-sm">
            <i class="fas fa-plus"></i> Nuevo Curso
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
                            <th class="ps-4">Nombre del Curso</th>
                            <th>Gestión / Año</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cursos as $curso)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $curso->nombre }}</td>
                            <td>
                                <span class="badge bg-light text-dark border border-secondary">
                                    <i class="far fa-calendar-alt me-1"></i> {{ $curso->gestion }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('cursos.edit', $curso->id) }}" class="btn btn-sm btn-light text-warning border" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('cursos.destroy', $curso->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Eliminar este curso?');">
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
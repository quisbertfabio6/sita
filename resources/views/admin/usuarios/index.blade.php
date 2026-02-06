@extends('layouts.app')

@section('titulo', 'Gestión de Usuarios')

@section('contenido')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-secondary fw-bold">Listado del Personal</h4>
        <a href="{{ route('usuarios.create') }}" class="btn btn-danger shadow-sm">
            <i class="fas fa-plus"></i> Nuevo Usuario
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
                            <th class="ps-4">Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($usuarios as $usuario)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $usuario->nombre_completo }}</td>
                            <td class="text-muted">{{ $usuario->email }}</td>
                            <td>
                                @if($usuario->rol->nombre == 'administrador')
                                    <span class="badge bg-danger">ADMIN</span>
                                @elseif($usuario->rol->nombre == 'docente')
                                    <span class="badge bg-success">DOCENTE</span>
                                @elseif($usuario->rol->nombre == 'jefe_carrera')
                                    <span class="badge bg-warning text-dark">JEFE</span>
                                @else
                                    <span class="badge bg-secondary">{{ strtoupper($usuario->rol->nombre) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($usuario->activo)
                                    <span class="text-success small"><i class="fas fa-circle"></i> Activo</span>
                                @else
                                    <span class="text-danger small"><i class="fas fa-circle"></i> Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('usuarios.edit', $usuario->id) }}" class="btn btn-sm btn-light text-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('usuarios.destroy', $usuario->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Eliminar este usuario?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light text-danger" title="Eliminar">
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
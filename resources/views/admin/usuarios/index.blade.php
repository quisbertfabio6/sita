@extends('layouts.app')

@section('titulo', 'Gestión de Usuarios')

@section('contenido')
<style>
    .soft-card { background: #ffffff; border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); }
    .table-modern { border-collapse: separate; border-spacing: 0 8px; }
    .table-modern thead th { border-bottom: none; color: #a0aec0; font-size: 0.8rem; text-transform: uppercase; font-weight: 600; letter-spacing: 1px; }
    .table-modern tbody tr { background-color: #f8f9fa; transition: background-color 0.2s; }
    .table-modern tbody tr:hover { background-color: #f1f3f5; }
    .table-modern tbody td { border: none; vertical-align: middle; padding: 15px; }
    .table-modern tbody td:first-child { border-radius: 10px 0 0 10px; }
    .table-modern tbody td:last-child { border-radius: 0 10px 10px 0; }
    .soft-badge { padding: 6px 15px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; letter-spacing: 0.5px; }
    .badge-admin { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
    .badge-docente { background: rgba(25, 135, 84, 0.1); color: #198754; }
    .badge-jefe { background: rgba(255, 193, 7, 0.15); color: #d39e00; }
    .badge-default { background: rgba(108, 117, 125, 0.1); color: #6c757d; }
    
    /* Buscador */
    .search-box { background: #fff; border: 1px solid #e0e0e0; border-radius: 30px; padding: 2px 5px 2px 15px; }
    .search-box input { border: none; outline: none; box-shadow: none; font-size: 0.9rem; }
</style>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="text-dark fw-bold mb-0">
                {{ isset($filtro) && $filtro === 'todos' ? 'Todos los Usuarios (Plantel y Alumnos)' : 'Personal Administrativo y Docente' }}
            </h4>
            <small class="text-muted">Gestión de accesos para el sistema</small>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            
            <form action="{{ route('usuarios.index') }}" method="GET" class="d-flex align-items-center search-box shadow-sm m-0">
                <input type="hidden" name="filtro" value="{{ $filtro ?? 'plantel' }}">
                <input type="text" name="buscar" class="form-control form-control-sm bg-transparent p-1" placeholder="Buscar por nombre..." value="{{ request('buscar') }}" style="width: 200px;">
                <button type="submit" class="btn btn-sm btn-danger rounded-circle text-white ms-1" style="width:30px; height:30px; padding:0;"><i class="fas fa-search"></i></button>
            </form>

            <a href="{{ route('usuarios.create') }}" class="btn btn-danger rounded-pill shadow-sm fw-bold px-4">
                <i class="fas fa-plus me-1"></i> Nuevo Usuario
            </a>
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
                        <th class="ps-4">Usuario</th>
                        <th>Email de Contacto</th>
                        <th>Rol Asignado</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr>
                            <td class="ps-4 font-weight-bold text-dark">
                                <div class="d-flex align-items-center">
                                    <div class="bg-white border rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user-tie text-secondary"></i>
                                    </div>
                                    <span class="fw-bold">{{ $usuario->nombre_completo }}</span>
                                </div>
                            </td>
                            <td class="text-muted">{{ $usuario->email }}</td>
                            <td>
                                @if($usuario->rol->nombre == 'administrador')
                                    <span class="soft-badge badge-admin">Administrador</span>
                                @elseif($usuario->rol->nombre == 'docente')
                                    <span class="soft-badge badge-docente">Docente</span>
                                @elseif($usuario->rol->nombre == 'jefe_carrera')
                                    <span class="soft-badge badge-jefe">Jefe de Carrera</span>
                                @else
                                    <span class="soft-badge badge-default">{{ ucfirst($usuario->rol->nombre) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($usuario->activo)
                                    <span class="text-success small fw-bold"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Activo</span>
                                @else
                                    <span class="text-secondary small fw-bold"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('usuarios.edit', $usuario->id) }}" class="btn btn-sm btn-light text-primary shadow-sm rounded-circle me-1" style="width: 32px; height: 32px;" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form action="{{ route('usuarios.destroy', $usuario->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Eliminar este usuario del sistema?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light text-danger shadow-sm rounded-circle" style="width: 32px; height: 32px;" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center p-4 text-muted">No se encontraron usuarios con ese nombre.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
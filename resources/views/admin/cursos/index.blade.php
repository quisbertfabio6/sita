@extends('layouts.app')

@section('titulo', 'Gestión de Cursos')

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
    .search-box { background: #fff; border: 1px solid #e0e0e0; border-radius: 30px; padding: 2px 5px 2px 15px; }
    .search-box input { border: none; outline: none; box-shadow: none; font-size: 0.9rem; }
</style>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="text-dark fw-bold mb-0">Cursos y Paralelos</h4>
            <small class="text-muted">Gestión de aulas y gestiones académicas</small>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <form action="{{ route('cursos.index') }}" method="GET" class="d-flex align-items-center search-box shadow-sm m-0">
                <input type="text" name="buscar" class="form-control form-control-sm bg-transparent p-1" placeholder="Buscar curso..." value="{{ request('buscar') }}" style="width: 180px;">
                <button type="submit" class="btn btn-sm btn-warning rounded-circle text-dark fw-bold ms-1" style="width:30px; height:30px; padding:0;"><i class="fas fa-search"></i></button>
            </form>

            <a href="{{ route('cursos.create') }}" class="btn btn-warning text-dark fw-bold rounded-pill shadow-sm px-4">
                <i class="fas fa-plus me-1"></i> Nuevo Curso
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
                        <th class="ps-4">Nombre del Curso</th>
                        <th>Gestión / Año</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cursos as $curso)
                    <tr>
                        <td class="ps-4 fw-bold text-dark">
                            <i class="fas fa-chalkboard text-warning me-2"></i> {{ $curso->nombre }}
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border border-secondary px-3 py-2" style="border-radius: 8px;">
                                <i class="far fa-calendar-alt me-1 text-primary"></i> {{ $curso->gestion }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('cursos.edit', $curso->id) }}" class="btn btn-sm btn-light text-warning shadow-sm rounded-circle me-1" style="width: 32px; height: 32px;" title="Editar">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form action="{{ route('cursos.destroy', $curso->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Eliminar este curso permanentemente?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-light text-danger shadow-sm rounded-circle" style="width: 32px; height: 32px;" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center p-4 text-muted">No se encontraron cursos registrados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
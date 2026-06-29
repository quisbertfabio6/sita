@extends('layouts.app')

@section('titulo', 'Gestión de Asignaciones')

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
            <h4 class="text-dark fw-bold mb-0">Carga Horaria Docente</h4>
            <small class="text-muted">Asignación de materias al personal</small>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <form action="{{ route('asignaciones.index') }}" method="GET" class="d-flex align-items-center search-box shadow-sm m-0">
                <input type="text" name="buscar" class="form-control form-control-sm bg-transparent p-1" placeholder="Buscar docente o materia..." value="{{ request('buscar') }}" style="width: 210px;">
                <button type="submit" class="btn btn-sm btn-danger rounded-circle text-white ms-1" style="width:30px; height:30px; padding:0;"><i class="fas fa-search"></i></button>
            </form>

            <a href="{{ route('asignaciones.create') }}" class="btn btn-danger rounded-pill fw-bold shadow-sm px-4">
                <i class="fas fa-plus me-1"></i> Nueva Asignación
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
                        <th class="ps-4">Docente</th>
                        <th>Materia</th>
                        <th>Curso / Paralelo</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($asignaciones as $asig)
                    <tr>
                        <td class="ps-4 fw-bold text-dark">
                            <div class="d-flex align-items-center">
                                <div class="bg-white border rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 35px; height: 35px;">
                                    <i class="fas fa-user-tie text-secondary"></i>
                                </div>
                                {{ $asig->docente->usuario->nombre_completo ?? 'Docente no encontrado' }}
                            </div>
                        </td>
                        <td>
                            <span class="fw-bold text-primary">{{ $asig->materia->nombre ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm">
                                {{ $asig->materia->curso->nombre ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <form action="{{ route('asignaciones.destroy', $asig->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Quitar esta materia al docente?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-light text-danger shadow-sm rounded-circle" style="width: 32px; height: 32px;" title="Eliminar Asignación">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center p-4 text-muted">No se encontraron asignaciones.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
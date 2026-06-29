@extends('layouts.app')

@section('titulo', 'Gestión de Estudiantes')

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

    .import-box { background: rgba(25, 135, 84, 0.05); border: 1px dashed rgba(25, 135, 84, 0.4); border-radius: 12px; padding: 8px 15px; display: flex; align-items: center; }
    .qr-badge { background: #f8f9fa; border: 1px solid #e0e0e0; padding: 6px 12px; border-radius: 8px; font-family: monospace; color: #333; font-weight: bold; }
    
    /* Buscador */
    .search-box { background: #fff; border: 1px solid #e0e0e0; border-radius: 30px; padding: 2px 5px 2px 15px; }
    .search-box input { border: none; outline: none; box-shadow: none; font-size: 0.9rem; }
</style>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="text-dark fw-bold mb-0">Listado de Estudiantes</h4>
            <small class="text-muted">Administración del alumnado y códigos QR</small>
        </div>
        
        <div class="d-flex align-items-center gap-3 flex-wrap">
            
            <form action="{{ route('estudiantes.index') }}" method="GET" class="d-flex align-items-center search-box shadow-sm m-0">
                <input type="text" name="buscar" class="form-control form-control-sm bg-transparent p-1" placeholder="Buscar alumno..." value="{{ request('buscar') }}" style="width: 180px;">
                <button type="submit" class="btn btn-sm btn-danger rounded-circle text-white ms-1" style="width:30px; height:30px; padding:0;"><i class="fas fa-search"></i></button>
            </form>

            <form action="{{ route('estudiantes.importar') }}" method="POST" enctype="multipart/form-data" class="import-box m-0">
                @csrf
                <i class="fas fa-file-excel text-success me-2 fs-5"></i>
                <input type="file" name="archivo_excel" class="form-control form-control-sm border-0 bg-transparent p-0 me-2" required accept=".xlsx, .csv" style="max-width: 150px; font-size: 0.85rem;">
                <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-sm">
                    Subir Lista
                </button>
            </form>

            <a href="{{ route('estudiantes.create') }}" class="btn btn-danger rounded-pill shadow-sm fw-bold px-4">
                <i class="fas fa-plus me-1"></i> Manual
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" style="border-radius: 10px;" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if($errors->any())
        <div class="alert alert-danger shadow-sm border-0" style="border-radius: 10px;" role="alert">
            <h6 class="alert-heading fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Error al Importar</h6>
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card soft-card p-4">
        <div class="table-responsive">
            <table class="table table-modern w-100 mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Estudiante</th>
                        <th>Curso / Paralelo</th>
                        <th>Matrícula</th>
                        <th>Código Asignado (QR)</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($estudiantes as $est)
                    <tr>
                        <td class="ps-4 fw-bold text-dark">
                            <div class="d-flex align-items-center">
                                <div class="bg-light border rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 40px; height: 40px;">
                                    <i class="fas fa-user-graduate text-secondary"></i>
                                </div>
                                {{ $est->usuario->nombre_completo ?? 'Sin Nombre' }}
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-white text-dark border shadow-sm px-3 py-2" style="border-radius: 8px;">
                                <i class="fas fa-chalkboard me-1 text-primary"></i> {{ $est->curso->nombre ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="text-muted fw-bold">{{ $est->matricula }}</td>
                        <td>
                            <span class="qr-badge"><i class="fas fa-qrcode text-dark me-2"></i>{{ $est->codigo_qr }}</span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('estudiantes.edit', $est->id) }}" class="btn btn-sm btn-light text-primary shadow-sm rounded-circle me-1" style="width: 32px; height: 32px;" title="Editar">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form action="{{ route('estudiantes.destroy', $est->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Eliminar a este estudiante permanentemente?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-light text-danger shadow-sm rounded-circle" style="width: 32px; height: 32px;" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center p-4 text-muted">No se encontraron estudiantes.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
@extends('layouts.app')

@section('titulo', 'Gestión de Estudiantes')

@section('contenido')
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-secondary fw-bold">Listado de Alumnos</h4>
        
        <div class="d-flex gap-2">
            <form action="{{ route('estudiantes.importar') }}" method="POST" enctype="multipart/form-data" class="d-flex gap-2 bg-white p-1 rounded shadow-sm border">
                @csrf
                <input type="file" name="archivo_excel" class="form-control form-control-sm border-0" required accept=".xlsx, .csv" style="max-width: 200px;">
                <button type="submit" class="btn btn-success btn-sm fw-bold">
                    <i class="fas fa-file-excel"></i> Importar
                </button>
            </form>

            <a href="{{ route('estudiantes.create') }}" class="btn btn-danger shadow-sm">
                <i class="fas fa-plus"></i> Nuevo Manual
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if($errors->any())
        <div class="alert alert-danger shadow-sm border-0" role="alert">
            <h5 class="alert-heading">Error al Importar</h5>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-4">Estudiante</th>
                            <th>Curso / Paralelo</th>
                            <th>Matrícula</th>
                            <th>Código QR</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estudiantes as $est)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $est->usuario->nombre_completo }}</td>
                            <td>
                                <span class="badge bg-light text-dark border border-secondary">
                                    {{ $est->curso->nombre }}
                                </span>
                            </td>
                            <td class="text-muted">{{ $est->matricula }}</td>
                            <td>
                                <span class="badge bg-dark"><i class="fas fa-qrcode me-1"></i> {{ $est->codigo_qr }}</span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('estudiantes.edit', $est->id) }}" class="btn btn-sm btn-light text-primary border" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('estudiantes.destroy', $est->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Eliminar a este estudiante?');">
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
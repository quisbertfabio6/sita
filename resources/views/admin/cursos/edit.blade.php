@extends('layouts.app')

@section('titulo', 'Editar Curso')

@section('contenido')
    
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow border-0">
                <div class="card-header bg-warning text-dark border-bottom py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit"></i> Editar Curso</h5>
                </div>
                <div class="card-body p-4">
                    
                    <form action="{{ route('cursos.update', $curso->id) }}" method="POST">
                        @csrf @method('PUT')

                        <div class="mb-4">
                            <label class="form-label fw-bold">Nombre del Curso</label>
                            <input type="text" name="nombre" class="form-control form-control-lg" value="{{ $curso->nombre }}" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Gestión Académica</label>
                            <input type="text" name="gestion" class="form-control form-control-lg" value="{{ $curso->gestion }}" required>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <a href="{{ route('cursos.index') }}" class="btn btn-light border">Cancelar</a>
                            <button type="submit" class="btn btn-dark fw-bold px-4">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
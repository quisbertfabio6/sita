@extends('layouts.app')

@section('titulo', 'Registrar Nuevo Curso')

@section('contenido')
    
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 text-warning fw-bold"><i class="fas fa-plus-circle"></i> Registrar Nuevo Curso</h5>
                </div>
                <div class="card-body p-4">
                    
                    <form action="{{ route('cursos.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-bold">Nombre del Curso</label>
                            <input type="text" name="nombre" class="form-control form-control-lg" placeholder="Ej: Primero A - Sistemas" required>
                            <div class="form-text">Incluya el paralelo y la carrera para evitar confusión.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Gestión Académica</label>
                            <input type="text" name="gestion" class="form-control form-control-lg" value="1-2025" required>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <a href="{{ route('cursos.index') }}" class="btn btn-light border">Cancelar</a>
                            <button type="submit" class="btn btn-warning fw-bold px-4">Guardar Curso</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
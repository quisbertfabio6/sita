@extends('layouts.app')

@section('titulo', 'Registrar Nueva Materia')

@section('contenido')
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 text-success fw-bold"><i class="fas fa-plus-circle"></i> Registrar Nueva Materia</h5>
                </div>
                <div class="card-body p-4">
                    
                    <form action="{{ route('materias.store') }}" method="POST">
                        @csrf

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Nombre de la Materia</label>
                                <input type="text" name="nombre" class="form-control form-control-lg" placeholder="Ej: Matemáticas Avanzadas" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Sigla (Opcional)</label>
                                <input type="text" name="sigla" class="form-control form-control-lg" placeholder="Ej: MAT-101">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Asignar a Curso</label>
                            <select name="curso_id" class="form-select form-select-lg" required>
                                <option value="">-- Seleccione un Curso --</option>
                                @foreach($cursos as $curso)
                                    <option value="{{ $curso->id }}">{{ $curso->nombre }} ({{ $curso->gestion }})</option>
                                @endforeach
                            </select>
                            <div class="form-text">La materia pertenecerá exclusivamente a este curso.</div>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <a href="{{ route('materias.index') }}" class="btn btn-light border">Cancelar</a>
                            <button type="submit" class="btn btn-success fw-bold px-4">Guardar Materia</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
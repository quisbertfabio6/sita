@extends('layouts.app')

@section('titulo', 'Editar Materia')

@section('contenido')
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-header bg-success text-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit"></i> Editar Materia</h5>
                </div>
                <div class="card-body p-4">
                    
                    <form action="{{ route('materias.update', $materia->id) }}" method="POST">
                        @csrf @method('PUT')

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Nombre</label>
                                <input type="text" name="nombre" class="form-control form-control-lg" value="{{ $materia->nombre }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Sigla</label>
                                <input type="text" name="sigla" class="form-control form-control-lg" value="{{ $materia->sigla }}">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Curso</label>
                            <select name="curso_id" class="form-select form-select-lg" required>
                                @foreach($cursos as $curso)
                                    <option value="{{ $curso->id }}" {{ $materia->curso_id == $curso->id ? 'selected' : '' }}>
                                        {{ $curso->nombre }} ({{ $curso->gestion }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <a href="{{ route('materias.index') }}" class="btn btn-light border">Cancelar</a>
                            <button type="submit" class="btn btn-success fw-bold px-4">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
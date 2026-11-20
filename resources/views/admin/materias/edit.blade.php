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

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Hora de Inicio</label>
                                <input type="time" name="hora_inicio" class="form-control form-control-lg" 
                                       value="{{ $materia->hora_inicio ? \Carbon\Carbon::parse($materia->hora_inicio)->format('H:i') : '08:00' }}" required>
                                <div class="form-text">Hora programada de entrada a clases.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tolerancia (Minutos)</label>
                                <input type="number" name="minutos_tolerancia" class="form-control form-control-lg" 
                                       value="{{ $materia->minutos_tolerancia ?? 15 }}" min="0" max="60" required>
                                <div class="form-text">Minutos permitidos antes de marcar atraso.</div>
                            </div>
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
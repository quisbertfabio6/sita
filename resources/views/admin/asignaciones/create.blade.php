@extends('layouts.app')

@section('titulo', 'Nueva Asignación')

@section('contenido')
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 text-danger fw-bold"><i class="fas fa-link"></i> Asignar Materia a Docente</h5>
                </div>
                <div class="card-body p-4">
                    
                    @if ($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm mb-4">
                            <ul class="mb-0">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('asignaciones.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-bold">1. Seleccione al Docente</label>
                            <select name="docente_id" class="form-select form-select-lg" required>
                                <option value="">-- Buscar en la lista --</option>
                                @foreach($docentes as $docente)
                                    <option value="{{ $docente->id }}">
                                        {{ $docente->usuario->nombre_completo }} 
                                        (Cód: {{ $docente->codigo_docente ?? 'S/N' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">2. Seleccione la Materia y Curso</label>
                            <select name="materia_id" class="form-select form-select-lg" required>
                                <option value="">-- Buscar Materia --</option>
                                @foreach($materias as $materia)
                                    <option value="{{ $materia->id }}">
                                        {{ $materia->nombre }} - [ {{ $materia->curso->nombre }} ]
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted">
                                Asegúrese de elegir la materia correspondiente al curso correcto (Ej: Matemáticas - 1ro A).
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <a href="{{ route('asignaciones.index') }}" class="btn btn-light border">Cancelar</a>
                            <button type="submit" class="btn btn-danger px-4 fw-bold">
                                <i class="fas fa-save me-2"></i> Guardar Asignación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
@extends('layouts.app')

@section('titulo', 'Generador de Reportes')

@section('contenido')
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 text-danger fw-bold"><i class="fas fa-file-pdf me-2"></i> Reporte de Asistencia por Rango</h5>
                </div>
                <div class="card-body p-4">
                    
                    <form action="{{ route('reportes.generar') }}" method="POST" target="_blank">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-bold">1. Seleccione la Materia</label>
                            <select name="materia_id" class="form-select form-select-lg" required>
                                <option value="">-- Buscar Materia --</option>
                                @foreach($materias as $materia)
                                    <option value="{{ $materia->id }}">
                                        {{ $materia->nombre }} - [ {{ $materia->curso->nombre }} ]
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">El reporte se basará en esta materia.</div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">2. Fecha de Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control form-control-lg" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">3. Fecha de Fin</label>
                                <input type="date" name="fecha_fin" class="form-control form-control-lg" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-5">
                            <button type="submit" class="btn btn-danger btn-lg px-5 fw-bold">
                                <i class="fas fa-cogs me-2"></i> Generar Reporte
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
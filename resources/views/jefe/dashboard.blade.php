@extends('layouts.app')

@section('titulo', 'Panel de Jefatura')

@section('contenido')
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-light border shadow-sm d-flex align-items-center" role="alert">
                <i class="fas fa-info-circle text-danger me-2 fa-lg"></i>
                <div>
                    <strong>Carreras bajo supervisión:</strong>
                    {{ Auth::user()->jefeCarrera->carrera_asignada ?? 'Sin asignación' }}
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100 shadow border-0">
                <div class="card-header bg-warning text-dark py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-clock me-2"></i> PENDIENTES</h5>
                </div>
                <div class="card-body p-4 position-relative overflow-hidden">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-3 fw-bold mb-0">{{ $pendientes }}</h1>
                            <p class="text-muted fw-bold">Solicitudes por revisar</p>
                        </div>
                        <i class="fas fa-file-contract fa-5x text-warning opacity-25 position-absolute end-0 bottom-0 me-3 mb-2"></i>
                    </div>
                    <hr>
                    <a href="{{ route('jefe.licencias') }}" class="btn btn-dark w-100 fw-bold">
                        Ir a Gestionar Licencias <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100 shadow border-0">
                <div class="card-body text-center p-4">
                    <div class="icon-box mb-3 text-primary">
                        <i class="fas fa-users fa-3x"></i>
                    </div>
                    <h2 class="fw-bold">{{ $totalAlumnos }}</h2>
                    <p class="text-muted small text-uppercase fw-bold">Estudiantes (en tus carreras)</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 shadow border-0">
                <div class="card-body text-center p-4">
                    <div class="icon-box mb-3 text-success">
                        <i class="fas fa-layer-group fa-3x"></i>
                    </div>
                    <h2 class="fw-bold">{{ $totalCursos }}</h2>
                    <p class="text-muted small text-uppercase fw-bold">Cursos Activos (en tus carreras)</p>
                </div>
            </div>
        </div>
    </div>

@endsection
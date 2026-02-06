@extends('layouts.app')

@section('titulo', 'Mis Asignaturas')

@section('contenido')
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-secondary fw-bold">Cursos Asignados</h4>
        <span class="badge bg-primary">{{ $materiasAsignadas->count() }} Materias</span>
    </div>

    <div class="row g-4">
        @forelse($materiasAsignadas as $materia)
            <div class="col-lg-6"> <div class="card h-100 shadow border-0 card-hover">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h3 class="card-title fw-bold text-dark mb-1">{{ $materia->nombre }}</h3>
                                <div class="mb-3">
                                    <span class="badge bg-primary text-white me-2">
                                        <i class="fas fa-layer-group"></i> {{ $materia->curso->nombre }}
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        <i class="far fa-calendar-alt"></i> {{ $materia->curso->gestion }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-3 bg-primary bg-opacity-10 rounded-circle text-primary">
                                <i class="fas fa-book-open fa-2x"></i>
                            </div>
                        </div>
                        <hr class="my-4 opacity-10">
                        <div class="d-grid">
                            <a href="{{ route('docente.curso.show', $materia->id) }}" class="btn btn-outline-primary btn-lg fw-bold">
                                <i class="fas fa-list-ul me-2"></i> Ver Lista de Estudiantes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="py-5 bg-white rounded shadow-sm">
                    <i class="fas fa-folder-open fa-4x text-muted mb-3 opacity-50"></i>
                    <h4 class="text-muted">No tienes materias asignadas todavía.</h4>
                    <p class="small text-secondary">Comunícate con el administrador para que te asigne cursos.</p>
                </div>
            </div>
        @endforelse
    </div>

@endsection
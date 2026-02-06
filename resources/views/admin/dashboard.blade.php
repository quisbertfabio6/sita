@extends('layouts.app')

@section('titulo', 'Panel de Control')

@section('contenido')
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold border-bottom pb-2">Bienvenido al Sistema</h3>
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 shadow border-0">
                <div class="card-header bg-card-usuarios text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-users-cog me-2"></i> SEGURIDAD</h5>
                </div>
                <div class="card-body p-4">
                    <h4 class="card-title fw-bold mb-0">Usuarios</h4>
                    <small class="text-muted">Accesos y Roles</small>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-dark w-100 fw-bold mt-3">Gestionar</a>
                </div>
            </div>
        </div>
        </div>
@endsection
@extends('layouts.app')

@section('titulo', 'Registrar Nuevo Estudiante')

@section('contenido')
    
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 text-danger fw-bold"><i class="fas fa-user-plus"></i> Registrar Nuevo Estudiante</h5>
                </div>
                <div class="card-body p-4">
                    
                    @if ($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm mb-4">
                            <ul class="mb-0">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('estudiantes.store') }}" method="POST">
                        @csrf

                        <h6 class="text-uppercase text-muted fw-bold mb-3"><i class="fas fa-id-card me-1"></i> Datos de Cuenta</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre Completo</label>
                                <input type="text" name="nombre_completo" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email Institucional</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contraseña Inicial</label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control" id="passwordInput" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr class="text-muted opacity-25">

                        <h6 class="text-uppercase text-muted fw-bold mb-3"><i class="fas fa-graduation-cap me-1"></i> Información Académica</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Asignar Curso</label>
                                <select name="curso_id" class="form-select" required>
                                    <option value="">-- Seleccione --</option>
                                    @foreach($cursos as $curso)
                                        <option value="{{ $curso->id }}">{{ $curso->nombre }} ({{ $curso->gestion }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-danger">Código QR (Tarjeta)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-qrcode"></i></span>
                                    <input type="text" name="codigo_qr" class="form-control" placeholder="Ej: EST-001" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Matrícula</label>
                                <input type="text" name="matricula" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">C.I.</label>
                                <input type="text" name="ci" class="form-control">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <a href="{{ route('estudiantes.index') }}" class="btn btn-light border">Cancelar</a>
                            <button type="submit" class="btn btn-danger fw-bold px-4">Guardar Estudiante</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            var input = document.getElementById("passwordInput");
            var icon = event.currentTarget.querySelector('i');
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
@endsection
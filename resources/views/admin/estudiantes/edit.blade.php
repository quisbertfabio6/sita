@extends('layouts.app')

@section('titulo', 'Editar Estudiante')

@section('contenido')
    
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0">
                <div class="card-header bg-warning text-dark border-bottom py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-user-edit"></i> Editar Estudiante</h5>
                </div>
                <div class="card-body p-4">
                    
                    <form action="{{ route('estudiantes.update', $estudiante->id) }}" method="POST">
                        @csrf @method('PUT')

                        <h6 class="text-uppercase text-muted fw-bold mb-3"><i class="fas fa-id-card me-1"></i> Datos de Cuenta</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre Completo</label>
                                <input type="text" name="nombre_completo" class="form-control" value="{{ $estudiante->usuario->nombre_completo }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ $estudiante->usuario->email }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nueva Contraseña (Opcional)</label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control" id="passwordInputEdit" placeholder="Dejar vacío para no cambiar">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordEdit()">
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
                                    @foreach($cursos as $curso)
                                        <option value="{{ $curso->id }}" {{ $estudiante->curso_id == $curso->id ? 'selected' : '' }}>
                                            {{ $curso->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-danger">Código QR</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-qrcode"></i></span>
                                    <input type="text" name="codigo_qr" class="form-control" value="{{ $estudiante->codigo_qr }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Matrícula</label>
                                <input type="text" name="matricula" class="form-control" value="{{ $estudiante->matricula }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">C.I.</label>
                                <input type="text" name="ci" class="form-control" value="{{ $estudiante->ci }}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <a href="{{ route('estudiantes.index') }}" class="btn btn-light border">Cancelar</a>
                            <button type="submit" class="btn btn-warning text-dark fw-bold px-4">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordEdit() {
            var input = document.getElementById("passwordInputEdit");
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
@extends('layouts.app')

@section('titulo', 'Registrar Nuevo Usuario')

@section('contenido')
    
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 text-danger fw-bold"><i class="fas fa-user-plus"></i> Registrar Nuevo Usuario</h5>
                </div>
                <div class="card-body p-4">
                    
                    @if ($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm mb-4">
                            <ul class="mb-0">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('usuarios.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre Completo</label>
                                <input type="text" name="nombre_completo" class="form-control" placeholder="Ej: Juan Pérez" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" placeholder="ejemplo@ita.edu.bo" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contraseña</label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control" id="passwordInput" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Rol del Usuario</label>
                                <select name="rol_id" id="rolSelect" class="form-select" required onchange="toggleCarreras()">
                                    <option value="">Seleccione un rol...</option>
                                    @foreach($roles as $rol)
                                        <option value="{{ $rol->id }}" data-nombre="{{ strtolower($rol->nombre) }}">
                                            {{ ucfirst($rol->nombre) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="seccionCarreras" class="mt-4 p-3 border rounded bg-light d-none border-danger border-start border-4">
                            <label class="form-label fw-bold text-danger mb-3">Asignar Carreras a Supervisar:</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="carreras[]" value="Sistemas Informáticos"><label class="form-check-label">Sistemas Informáticos</label></div>
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="carreras[]" value="Mecánica Automotriz"><label class="form-check-label">Mecánica Automotriz</label></div>
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="carreras[]" value="Electromecánica"><label class="form-check-label">Electromecánica</label></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="carreras[]" value="Electrónica"><label class="form-check-label">Electrónica</label></div>
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="carreras[]" value="Construcción Civil"><label class="form-check-label">Construcción Civil</label></div>
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="carreras[]" value="Mecánica Industrial"><label class="form-check-label">Mecánica Industrial</label></div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <a href="{{ route('usuarios.index') }}" class="btn btn-light border">Cancelar</a>
                            <button type="submit" class="btn btn-danger px-4 fw-bold">Guardar Usuario</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            var input = document.getElementById("passwordInput");
            input.type = (input.type === "password") ? "text" : "password";
        }

        function toggleCarreras() {
            var select = document.getElementById('rolSelect');
            var seccion = document.getElementById('seccionCarreras');
            var selectedOption = select.options[select.selectedIndex];
            var nombreRol = selectedOption.getAttribute('data-nombre');

            if (nombreRol && (nombreRol.includes('jefe') || nombreRol.includes('carrera'))) {
                seccion.classList.remove('d-none');
            } else {
                seccion.classList.add('d-none');
                var checkboxes = document.querySelectorAll('input[name="carreras[]"]');
                checkboxes.forEach(box => box.checked = false);
            }
        }
    </script>
@endsection
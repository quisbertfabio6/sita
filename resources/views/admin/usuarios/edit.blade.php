@extends('layouts.app')

@section('titulo', 'Editar Usuario')

@section('contenido')
    
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0">
                <div class="card-header bg-warning text-dark border-bottom py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-user-edit"></i> Editar Usuario: {{ $usuario->nombre_completo }}</h5>
                </div>
                <div class="card-body p-4">
                    
                    @if ($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm mb-4">
                            <ul class="mb-0">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('usuarios.update', $usuario->id) }}" method="POST">
                        @csrf 
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre Completo</label>
                                <input type="text" name="nombre_completo" class="form-control" value="{{ $usuario->nombre_completo }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" value="{{ $usuario->email }}" required>
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
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Rol</label>
                                <select name="rol_id" id="rolSelect" class="form-select" required onchange="toggleCarreras()">
                                    @foreach($roles as $rol)
                                        <option value="{{ $rol->id }}" 
                                            data-nombre="{{ strtolower($rol->nombre) }}"
                                            {{ $usuario->rol_id == $rol->id ? 'selected' : '' }}>
                                            {{ ucfirst($rol->nombre) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @php
                            $carrerasAsignadas = [];
                            if($usuario->jefeCarrera && $usuario->jefeCarrera->carrera_asignada) {
                                $carrerasAsignadas = explode(', ', $usuario->jefeCarrera->carrera_asignada);
                            }
                        @endphp

                        <div id="seccionCarreras" class="mt-4 p-3 border rounded bg-light d-none border-warning border-start border-4">
                            <label class="form-label fw-bold text-dark mb-3">Carreras Supervisadas:</label>
                            <div class="row">
                                <div class="col-md-6">
                                    @foreach(['Sistemas Informáticos', 'Mecánica Automotriz', 'Electromecánica'] as $carrera)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="carreras[]" value="{{ $carrera }}"
                                                {{ in_array($carrera, $carrerasAsignadas) ? 'checked' : '' }}>
                                            <label class="form-check-label">{{ $carrera }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="col-md-6">
                                    @foreach(['Electrónica', 'Construcción Civil', 'Mecánica Industrial'] as $carrera)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="carreras[]" value="{{ $carrera }}"
                                                {{ in_array($carrera, $carrerasAsignadas) ? 'checked' : '' }}>
                                            <label class="form-check-label">{{ $carrera }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 mb-3 form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="activo" name="activo" {{ $usuario->activo ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="activo">Cuenta Activa (Puede iniciar sesión)</label>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <a href="{{ route('usuarios.index') }}" class="btn btn-light border">Cancelar</a>
                            <button type="submit" class="btn btn-warning text-dark fw-bold px-4">Actualizar Usuario</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordEdit() {
            var input = document.getElementById("passwordInputEdit");
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
            }
        }
        
        // Ejecutar al cargar la página para que si ya es Jefe, se muestren las opciones
        window.onload = toggleCarreras;
    </script>
@endsection
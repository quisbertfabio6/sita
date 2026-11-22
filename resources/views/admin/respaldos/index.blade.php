@extends('layouts.app')

@section('titulo', 'Copias de seguridad')

@section('contenido')
<div class="container py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fas fa-shield-alt text-danger me-2"></i>Copias de seguridad</h3>
            <p class="text-muted mb-0">Protección de las gestiones académicas y documentos de SITA.</p>
        </div>
        <form method="POST" action="{{ route('respaldos.crear') }}"
              onsubmit="return confirm('Se creará una copia completa de todas las gestiones y archivos adjuntos. ¿Continuar?');">
            @csrf
            <button type="submit" class="btn btn-danger fw-bold">
                <i class="fas fa-database me-2"></i>Generar copia completa
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <div class="alert alert-info">
        <strong>¿Qué incluye?</strong> La base de datos completa (estudiantes, inscripciones,
        gestiones anteriores, materias, asistencias y licencias) y los archivos almacenados
        por la aplicación, incluidos los documentos adjuntos.
        <div class="small mt-2">
            Esta opción <strong>no elimina, cierra ni restaura</strong> gestiones.
            Cada ZIP contiene todas las gestiones existentes al momento de generarlo;
            no es una exportación aislada de un solo año.
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header fw-bold"><i class="fas fa-history me-2"></i>Copias disponibles</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Fecha</th>
                            <th>Archivo</th>
                            <th>Tamaño</th>
                            <th>Integridad (SHA-256)</th>
                            <th class="text-end pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($respaldos as $respaldo)
                            <tr>
                                <td class="ps-3 text-nowrap">{{ date('d/m/Y H:i', $respaldo['fecha']) }}</td>
                                <td><code>{{ $respaldo['nombre'] }}</code></td>
                                <td class="text-nowrap">{{ number_format($respaldo['tamano'] / 1048576, 2) }} MB</td>
                                <td>
                                    @if($respaldo['sha256'])
                                        <code title="{{ $respaldo['sha256'] }}">{{ substr($respaldo['sha256'], 0, 16) }}…</code>
                                    @else
                                        <span class="text-muted">No disponible</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <a class="btn btn-sm btn-outline-danger"
                                       href="{{ route('respaldos.descargar', ['nombre' => $respaldo['nombre']]) }}">
                                        <i class="fas fa-download me-1"></i>Descargar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Todavía no hay copias de seguridad. Genere la primera cuando el sistema esté disponible.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <p class="small text-muted mt-3">
        <i class="fas fa-lock me-1"></i>
        Solo el administrador puede descargar estos archivos. Contienen información personal;
        consérvelos en un medio seguro, preferentemente fuera del equipo del servidor.
        La restauración debe probarse primero en un entorno separado.
    </p>
</div>
@endsection

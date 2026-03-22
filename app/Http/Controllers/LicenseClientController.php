<?php

namespace App\Http\Controllers;

use App\Models\PgConfiguracion;
use App\Services\ExternalLicenseService;
use Illuminate\Http\Request;

class LicenseClientController extends Controller
{
    public function __construct(private ExternalLicenseService $license)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $this->license->ensureSeeded();
        $summary = $this->license->dashboardSummary();
        $configs = [
            'LICENCIA_SERVIDOR_URL' => PgConfiguracion::valor('LICENCIA_SERVIDOR_URL', ''),
            'LICENCIA_CLAVE' => PgConfiguracion::valor('LICENCIA_CLAVE', ''),
            'LICENCIA_PRODUCTO_CODIGO' => PgConfiguracion::valor('LICENCIA_PRODUCTO_CODIGO', ''),
            'LICENCIA_DOMINIO' => PgConfiguracion::valor('LICENCIA_DOMINIO', $this->license->guessDomain($request)),
            'LICENCIA_INSTALACION_ID' => PgConfiguracion::valor('LICENCIA_INSTALACION_ID', $this->license->suggestInstallationId()),
            'LICENCIA_VALIDAR_CADA_MINUTOS' => PgConfiguracion::valor('LICENCIA_VALIDAR_CADA_MINUTOS', '60'),
            'LICENCIA_GRACIA_HORAS' => PgConfiguracion::valor('LICENCIA_GRACIA_HORAS', '24'),
            'LICENCIA_TIMEOUT_SEGUNDOS' => PgConfiguracion::valor('LICENCIA_TIMEOUT_SEGUNDOS', '10'),
            'LICENCIA_BLOQUEAR_SIN_CONFIG' => PgConfiguracion::valor('LICENCIA_BLOQUEAR_SIN_CONFIG', 'N'),
            'LICENCIA_AUTO_ACTIVAR' => PgConfiguracion::valor('LICENCIA_AUTO_ACTIVAR', 'S'),
            'LICENCIA_BLOQUEO_HARDWARE' => PgConfiguracion::valor('LICENCIA_BLOQUEO_HARDWARE', 'S'),
            'LICENCIA_PROTEGER_CLONACION' => PgConfiguracion::valor('LICENCIA_PROTEGER_CLONACION', 'S'),
            'LICENCIA_RSA_PUBLIC_KEY' => PgConfiguracion::valor('LICENCIA_RSA_PUBLIC_KEY', ''),
            'LICENCIA_VERSION_ACTUAL' => PgConfiguracion::valor('LICENCIA_VERSION_ACTUAL', config('app.version', '1.0.0')),
            'LICENCIA_UPDATE_AUTO_ACTIVO' => PgConfiguracion::valor('LICENCIA_UPDATE_AUTO_ACTIVO', 'N'),
            'LICENCIA_UPDATE_ENDPOINT' => PgConfiguracion::valor('LICENCIA_UPDATE_ENDPOINT', '/api/v1/updates/check'),
            'LICENCIA_UPDATE_CHECK_MINUTOS' => PgConfiguracion::valor('LICENCIA_UPDATE_CHECK_MINUTOS', '180'),
            'LICENCIA_UPDATE_AUTO_APLICAR' => PgConfiguracion::valor('LICENCIA_UPDATE_AUTO_APLICAR', 'N'),
        ];

        return view('license_client.index', [
            'configs' => $configs,
            'summary' => $summary,
            'rawResponse' => PgConfiguracion::valor('LICENCIA_RAW_RESPUESTA', ''),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'LICENCIA_SERVIDOR_URL' => ['nullable', 'string', 'max:255'],
            'LICENCIA_CLAVE' => ['nullable', 'string', 'max:255'],
            'LICENCIA_PRODUCTO_CODIGO' => ['nullable', 'string', 'max:100'],
            'LICENCIA_DOMINIO' => ['nullable', 'string', 'max:255'],
            'LICENCIA_INSTALACION_ID' => ['nullable', 'string', 'max:100'],
            'LICENCIA_VALIDAR_CADA_MINUTOS' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'LICENCIA_GRACIA_HORAS' => ['nullable', 'integer', 'min:0', 'max:720'],
            'LICENCIA_TIMEOUT_SEGUNDOS' => ['nullable', 'integer', 'min:3', 'max:60'],
            'LICENCIA_BLOQUEAR_SIN_CONFIG' => ['nullable', 'in:S,N'],
            'LICENCIA_AUTO_ACTIVAR' => ['nullable', 'in:S,N'],
            'LICENCIA_BLOQUEO_HARDWARE' => ['nullable', 'in:S,N'],
            'LICENCIA_PROTEGER_CLONACION' => ['nullable', 'in:S,N'],
            'LICENCIA_RSA_PUBLIC_KEY' => ['nullable', 'string'],
            'LICENCIA_VERSION_ACTUAL' => ['nullable', 'string', 'max:50'],
            'LICENCIA_UPDATE_AUTO_ACTIVO' => ['nullable', 'in:S,N'],
            'LICENCIA_UPDATE_ENDPOINT' => ['nullable', 'string', 'max:255'],
            'LICENCIA_UPDATE_CHECK_MINUTOS' => ['nullable', 'integer', 'min:5', 'max:10080'],
            'LICENCIA_UPDATE_AUTO_APLICAR' => ['nullable', 'in:S,N'],
        ]);

        foreach ($data as $key => $value) {
            $type = str_contains($key, 'MINUTOS') || str_contains($key, 'HORAS') || str_contains($key, 'SEGUNDOS') ? 'numero' : 'texto';
            if (in_array($key, ['LICENCIA_BLOQUEAR_SIN_CONFIG', 'LICENCIA_AUTO_ACTIVAR', 'LICENCIA_BLOQUEO_HARDWARE', 'LICENCIA_PROTEGER_CLONACION', 'LICENCIA_UPDATE_AUTO_ACTIVO', 'LICENCIA_UPDATE_AUTO_APLICAR'], true)) {
                $type = 'booleano';
            }
            PgConfiguracion::setValor($key, $value, $type, $key, 'licencias');
        }

        return redirect()->route('license-client.index')->with('success', 'Configuración de licencia guardada correctamente.');
    }

    public function validateNow(Request $request)
    {
        $result = $this->license->validateLicense(true, $request);
        return redirect()->route('license-client.index')->with(($result['ok'] ?? false) ? 'success' : 'error', $result['message'] ?? 'Resultado de validación actualizado.');
    }

    public function activate(Request $request)
    {
        $result = $this->license->activateInstallation($request);
        return redirect()->route('license-client.index')->with(($result['ok'] ?? false) ? 'success' : 'error', $result['message'] ?? 'Resultado de activación actualizado.');
    }

    public function consult(Request $request)
    {
        $result = $this->license->consultStatus($request);
        return redirect()->route('license-client.index')->with(($result['ok'] ?? false) ? 'success' : 'error', $result['message'] ?? 'Estado consultado.');
    }

    public function deactivate(Request $request)
    {
        $result = $this->license->deactivateInstallation($request);
        return redirect()->route('license-client.index')->with(($result['ok'] ?? false) ? 'success' : 'error', $result['message'] ?? 'Instalación desactivada.');
    }

    public function checkUpdates(Request $request)
    {
        $result = $this->license->checkForUpdates(true, $request);
        return redirect()->route('license-client.index')->with(($result['ok'] ?? false) ? 'success' : 'error', $result['message'] ?? 'Revisión de actualizaciones ejecutada.');
    }

    public function downloadUpdate(Request $request)
    {
        $result = $this->license->downloadLatestUpdate($request);
        return redirect()->route('license-client.index')->with(($result['ok'] ?? false) ? 'success' : 'error', $result['message'] ?? 'Descarga de actualización ejecutada.');
    }

    public function applyUpdate(Request $request)
    {
        $result = $this->license->applyDownloadedUpdate();
        return redirect()->route('license-client.index')->with(($result['ok'] ?? false) ? 'success' : 'error', $result['message'] ?? 'Aplicación de actualización ejecutada.');
    }
}


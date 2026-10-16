<?php
// ============================================================
//  services/NetworkScannerService.php
//  Servicio de escaneo de red con análisis IA
// ============================================================

namespace Services;

class NetworkScannerService
{
    private string $scriptPath;

    public function __construct()
    {
        $this->scriptPath = ROOT_PATH . '/ai/network_scanner.py';
    }

    // Escanear una IP con análisis completo de seguridad
    public function scanIP(string $ip, string $intensidad = 'normal'): array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['error' => 'IP no válida: ' . $ip];
        }

        $ip         = escapeshellarg($ip);
        $intensidad = in_array($intensidad, ['rapido','normal','completo']) ? $intensidad : 'normal';
        $cmd        = "python3 {$this->scriptPath} --ip $ip --intensidad $intensidad 2>&1";
        $output     = shell_exec($cmd);

        return $this->parseOutput($output);
    }

    // Escanear un rango de IPs — solo descubrimiento
    public function scanRange(string $range): array
    {
        // Validar formato CIDR básico
        if (!preg_match('/^[\d.]+\/\d{1,2}$/', $range)) {
            return ['error' => 'Formato de rango no válido. Usar CIDR (ej: 192.168.0.0/24)'];
        }

        $range  = escapeshellarg($range);
        $cmd    = "python3 {$this->scriptPath} --range $range 2>&1";
        $output = shell_exec($cmd);

        return $this->parseOutput($output);
    }

    private function parseOutput(?string $output): array
    {
        if (empty($output)) {
            return ['error' => 'El escáner no retornó resultados. Verificar que nmap está instalado.'];
        }

        $result = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[NetworkScanner] Output inválido: ' . $output);
            return ['error' => 'Error en el escáner: ' . substr($output, 0, 300)];
        }

        return $result;
    }
}

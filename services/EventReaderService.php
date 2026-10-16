<?php
// ============================================================
//  services/EventReaderService.php
//  Servicio de lectura de eventos con análisis IA
// ============================================================

namespace Services;

class EventReaderService
{
    private string $scriptPath;

    public function __construct()
    {
        $this->scriptPath = ROOT_PATH . '/ai/event_reader.py';
    }

    public function readEvents(
        string $ip,
        string $osType,
        string $username,
        string $password,
        string $domain = '',
        int    $port   = 22,
        string $keyPath = ''
    ): array {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['error' => 'IP no válida: ' . $ip];
        }

        if (!file_exists($this->scriptPath)) {
            return ['error' => 'Módulo de lectura de eventos no encontrado.'];
        }

        $osType   = in_array($osType, ['windows','linux']) ? $osType : 'windows';
        $ip       = escapeshellarg($ip);
        $username = escapeshellarg($username);
        $password = escapeshellarg($password);
        $domain   = escapeshellarg($domain);
        $port     = (int)$port;

        $cmd = "python3 {$this->scriptPath} "
             . "--ip $ip "
             . "--os $osType "
             . "--user $username "
             . "--pass $password "
             . "--domain $domain "
             . "--port $port ";

        if ($keyPath && file_exists($keyPath)) {
            $cmd .= '--key ' . escapeshellarg($keyPath) . ' ';
        }

        $cmd .= '2>&1';

        $output = shell_exec($cmd);

        if (empty($output)) {
            return ['error' => 'El lector de eventos no retornó resultados.'];
        }

        $result = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[EventReader] Output inválido: ' . $output);
            return ['error' => 'Error en el lector: ' . substr($output, 0, 300)];
        }

        return $result;
    }
}

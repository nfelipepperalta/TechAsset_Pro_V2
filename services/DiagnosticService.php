<?php
namespace Services;

class DiagnosticService
{
    private string $scriptsPath;

    public function __construct()
    {
        $this->scriptsPath = dirname(__DIR__) . '/ai';
    }

    public function leerEventosWindows(string $host = '192.168.0.143', string $user = 'Administrator', string $passwd = 'kali'): array
    {
        $script = escapeshellarg($this->scriptsPath . '/event_reader.py');
        $host   = escapeshellarg($host);
        $user   = escapeshellarg($user);
        $passwd = escapeshellarg($passwd);

        $cmd    = "python3 {$script} --ip {$host} --os windows --user {$user} --pass {$passwd} 2>&1";
        $output = shell_exec($cmd);
        return $this->parsear($output, 'windows');
    }

    public function leerEventosLinux(int $limit = 50, int $hours = 24): array
    {
        $script = escapeshellarg($this->scriptsPath . '/linux_log_reader.py');
        $cmd    = "python3 {$script} --limit {$limit} --hours {$hours} 2>&1";
        $output = shell_exec($cmd);

        if (empty($output)) {
            return ['analisis'=>['total'=>0,'criticos'=>0,'medios'=>0,'bajos'=>0,'diagnostico'=>[],'resumen_ia'=>'Sin datos Linux.'],'error'=>null];
        }

        $lineas = array_reverse(explode("\n", trim($output)));
        $json = null;
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (str_starts_with($linea, '{')) { $json = $linea; break; }
        }
        if (!$json) return ['analisis'=>['total'=>0,'criticos'=>0,'medios'=>0,'bajos'=>0,'diagnostico'=>[],'resumen_ia'=>'Respuesta inválida.'],'error'=>null];

        $data = json_decode($json, true);
        if (!$data) return ['analisis'=>['total'=>0,'criticos'=>0,'medios'=>0,'bajos'=>0,'diagnostico'=>[],'resumen_ia'=>'JSON malformado.'],'error'=>null];

        // Convertir formato linux_log_reader a formato analisis
        $eventos  = $data['eventos'] ?? [];
        $criticos = count(array_filter($eventos, fn($e) => $e['badge'] === 'danger'));
        $medios   = count(array_filter($eventos, fn($e) => $e['badge'] === 'warning'));

        $diagnostico = array_map(fn($e) => [
            'nivel'      => $e['badge'] === 'danger' ? 'critico' : ($e['badge'] === 'warning' ? 'medio' : 'info'),
            'color'      => $e['badge'],
            'titulo'     => $e['source'] ?? 'Evento Linux',
            'causa'      => $e['message'] ?? '',
            'solucion'   => 'Revisar el log ' . ($e['source'] ?? '') . ' para más detalles.',
            'evento_raw' => $e['message'] ?? '',
            'canal'      => $e['log'] ?? '',
            'fuente'     => $e['source'] ?? '',
            'fecha'      => $e['time'] ?? '',
            'os_type'    => 'linux',
        ], $eventos);

        return [
            'analisis' => [
                'total'      => $data['total'] ?? count($eventos),
                'criticos'   => $criticos,
                'medios'     => $medios,
                'bajos'      => 0,
                'diagnostico'=> $diagnostico,
                'resumen_ia' => "Se encontraron {$criticos} críticos y {$medios} advertencias en los logs de Linux.",
            ],
            'error' => $data['error'] ?? null,
        ];
    }

    public function leerTodo(string $host = '192.168.0.143', string $os = 'windows', int $limit = 50, int $hours = 24): array
    {
        $windows = ($os === 'windows') ? $this->leerEventosWindows($host) : ['analisis'=>['total'=>0,'criticos'=>0,'medios'=>0,'diagnostico'=>[],'resumen_ia'=>''],'error'=>null];
        $linux   = ($os === 'linux')   ? $this->leerEventosLinux()           : ['analisis'=>['total'=>0,'criticos'=>0,'medios'=>0,'diagnostico'=>[],'resumen_ia'=>''],'error'=>null];

        return [
            'generado_en' => date('Y-m-d H:i:s'),
            'windows' => [
                'host'       => $host,
                'total'      => $windows['analisis']['total']    ?? 0,
                'criticos'   => $windows['analisis']['criticos'] ?? 0,
                'medios'     => $windows['analisis']['medios']   ?? 0,
                'error'      => $windows['error'] ?? null,
                'diagnostico'=> $windows['analisis']['diagnostico'] ?? [],
                'resumen_ia' => $windows['analisis']['resumen_ia']  ?? '',
                'eventos'    => $this->diagnosticoAEventos($windows['analisis']['diagnostico'] ?? [], 'windows'),
            ],
            'linux' => [
                'host'       => 'localhost',
                'total'      => $linux['analisis']['total']    ?? 0,
                'criticos'   => $linux['analisis']['criticos'] ?? 0,
                'medios'     => $linux['analisis']['medios']   ?? 0,
                'error'      => $linux['error'] ?? null,
                'diagnostico'=> $linux['analisis']['diagnostico'] ?? [],
                'resumen_ia' => $linux['analisis']['resumen_ia']  ?? '',
                'eventos'    => $this->diagnosticoAEventos($linux['analisis']['diagnostico'] ?? [], 'linux'),
            ],
        ];
    }

    private function diagnosticoAEventos(array $diagnostico, string $origen): array
    {
        return array_map(fn($d) => [
            'time'     => $d['fecha']      ?? '',
            'event_id' => '-',
            'level'    => ucfirst($d['nivel'] ?? 'info'),
            'level_n'  => match($d['nivel'] ?? '') { 'critico' => 2, 'medio' => 3, default => 4 },
            'badge'    => $d['color']      ?? 'info',
            'source'   => $d['fuente']     ?? '',
            'message'  => $d['evento_raw'] ?? '',
            'titulo'   => $d['titulo']     ?? '',
            'causa'    => $d['causa']      ?? '',
            'solucion' => $d['solucion']   ?? '',
            'origin'   => $origen,
            'host'     => $d['canal']      ?? '',
            'log'      => $d['canal']      ?? '',
        ], $diagnostico);
    }

    private function parsear(?string $output, string $origen): array
    {
        if (empty($output)) {
            return ['analisis' => ['total'=>0,'criticos'=>0,'medios'=>0,'bajos'=>0,'diagnostico'=>[],'resumen_ia'=>"Sin datos de {$origen}."], 'error' => null];
        }

        $lineas = array_reverse(explode("\n", trim($output)));
        $json   = null;
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (str_starts_with($linea, '{')) { $json = $linea; break; }
        }

        if (!$json) {
            return ['analisis' => ['total'=>0,'criticos'=>0,'medios'=>0,'bajos'=>0,'diagnostico'=>[],'resumen_ia'=>"Respuesta inválida de {$origen}."], 'error' => substr($output,0,200)];
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['analisis' => ['total'=>0,'criticos'=>0,'medios'=>0,'bajos'=>0,'diagnostico'=>[],'resumen_ia'=>'JSON malformado.'], 'error' => null];
        }

        return $data;
    }
}

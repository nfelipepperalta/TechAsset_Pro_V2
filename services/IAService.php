<?php
// ============================================================
//  services/IAService.php
//  Servicio de IA Predictiva — llama al modelo Python
//  y devuelve los resultados al sistema PHP
// ============================================================

namespace Services;

class IAService
{
    private string $scriptPath;
    private string $cacheFile;
    private int    $cacheTTL = 3600; // 1 hora en segundos

    public function __construct()
    {
        $this->scriptPath = ROOT_PATH . '/ai/modelo_predictivo.py';
        $this->cacheFile  = ROOT_PATH . '/ai/cache_predicciones.json';
    }

    // Obtener predicciones de todos los activos
    public function getPredictions(bool $forceRefresh = false): array
    {
        // Usar cache si está vigente
        if (!$forceRefresh && $this->cacheIsValid()) {
            return $this->readCache();
        }

        // Ejecutar modelo Python
        $result = $this->runModel();

        if (isset($result['error'])) {
            return ['error' => $result['error']];
        }

        // Guardar en cache
        $this->writeCache($result);

        return $result;
    }

    // Obtener predicción de un activo específico
    public function getPredictionByAsset(int $assetId): ?array
    {
        $all = $this->getPredictions();

        if (isset($all['error'])) return null;

        foreach ($all['activos'] ?? [] as $activo) {
            if ((int)$activo['id'] === $assetId) {
                return $activo;
            }
        }

        return null;
    }

    // Obtener top N activos de mayor riesgo
    public function getTopRiesgo(int $n = 10): array
    {
        $all = $this->getPredictions();

        if (isset($all['error'])) return [];

        $activos = $all['activos'] ?? [];

        // Ya vienen ordenados por score desc
        return array_slice($activos, 0, $n);
    }

    // Obtener resumen para el dashboard
    public function getResumen(): array
    {
        $all = $this->getPredictions();

        if (isset($all['error'])) {
            return [
                'error'          => $all['error'],
                'total_activos'  => 0,
                'riesgo_alto'    => 0,
                'riesgo_medio'   => 0,
                'riesgo_bajo'    => 0,
                'score_promedio' => 0,
            ];
        }

        return $all['resumen'] ?? [];
    }

    // Forzar re-entrenamiento del modelo
    public function refresh(): array
    {
        return $this->getPredictions(forceRefresh: true);
    }

    // ── Métodos internos ──────────────────────────────────────

    private function runModel(?int $assetId = null): array
    {
        if (!file_exists($this->scriptPath)) {
            return ['error' => 'Modelo de IA no encontrado en ' . $this->scriptPath];
        }

        $args   = $assetId ? "--asset_id $assetId" : '';
        $cmd    = escapeshellcmd("python3 {$this->scriptPath} $args") . ' 2>&1';
        $output = shell_exec($cmd);

        if (empty($output)) {
            return ['error' => 'El modelo no retornó resultados'];
        }

        $result = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[IAService] Output no es JSON válido: ' . $output);
            return ['error' => 'Error en el modelo de IA: ' . substr($output, 0, 200)];
        }

        return $result;
    }

    private function cacheIsValid(): bool
    {
        if (!file_exists($this->cacheFile)) return false;
        return (time() - filemtime($this->cacheFile)) < $this->cacheTTL;
    }

    private function readCache(): array
    {
        $content = file_get_contents($this->cacheFile);
        return json_decode($content, true) ?? [];
    }

    private function writeCache(array $data): void
    {
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}

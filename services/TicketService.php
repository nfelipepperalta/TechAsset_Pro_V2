<?php

namespace Services;

use Repositories\TicketRepository;
use Repositories\AssetRepository;
use Core\Database;

class TicketService
{
    private TicketRepository $ticketRepo;
    private AssetRepository $assetRepo;

    public function __construct()
    {
        $this->ticketRepo = new TicketRepository();
        $this->assetRepo  = new AssetRepository();
    }

        public function createTicket(array $data, int $userId): array
    {
        $errors = $this->validateTicket($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $titulo = trim((string)($data['titulo'] ?? ''));
        $descripcion = trim((string)($data['descripcion'] ?? ''));
        $categoria = trim((string)($data['categoria'] ?? 'otro'));

        $aiSugerencia = null;
        $aiStatus = 'pendiente';

        // 🧠 FASE 1: Buscar en la Base de Conocimiento Local con filtro de similitud estricto
        $solucionRapida = $this->checkLocalKnowledge($categoria, $titulo, $descripcion);
        
        if ($solucionRapida) {
            $aiSugerencia = "[Autoentrenamiento Local]: " . $solucionRapida;
            $aiStatus = 'resuelto_local';
        } else {
            // 🧠 FASE 2: Si es un caso nuevo o no se parece lo suficiente, va a la IA Local de Ollama
            $ollamaResponse = $this->askOllamaLocal($titulo, $descripcion);
            
            if ($ollamaResponse && strlen(trim($ollamaResponse)) > 15) {
                $aiSugerencia = "[IA Local Ollama]: " . $ollamaResponse;
                $aiStatus = 'analizado_local';
                // 🚀 SE ELIMINÓ EL AUTOENTRENAMIENTO AUTOMÁTICO DE OLLAMA AQUÍ
            } else {
                // 🧠 FASE 3: Si Ollama está apagado o da Timeout, salta directo a la Nube (Groq)
                $cloudResponse = $this->askOpenAICloud($titulo, $descripcion);
                if ($cloudResponse) {
                    $aiSugerencia = "[IA Nube Respaldo]: " . $cloudResponse;
                    $aiStatus = 'analizado_nube';
                    // 🚀 SE ELIMINÓ EL AUTOENTRENAMIENTO AUTOMÁTICO DE LA NUBE AQUÍ
                }
            }
        }

        $payload = [
            'user_id'        => $userId,
            'titulo'         => $titulo,
            'categoria'      => $categoria,
            'descripcion'    => $descripcion,
            'prioridad'      => $data['prioridad'] ?? 'media',
            'estado'         => 'abierto',
            'ia_sugerencia'  => $aiSugerencia ?? '[Soporte] Su requerimiento está en proceso de revisión por fallas en los nodos de IA.',
            'ia_status'      => $aiStatus,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        try {
            $id = $this->ticketRepo->insert($payload);
            return ['success' => true, 'id' => $id];
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'message' => 'No se pudo guardar la solicitud.',
                'errors'  => ['general' => $e->getMessage()],
            ];
        }
    }


        private function checkLocalKnowledge(string $categoria, string $titulo, string $descripcion): ?string
    {
        try {
            $db = Database::getInstance()->getConnection();
            
            // 🚀 ACTUALIZACIÓN DE ORDENAMIENTO: Traemos las soluciones ordenadas por las más votadas primero (clicks_uso DESC)
            $sql = "SELECT id, palabras_clave, solucion FROM dbo.ai_knowledge_base 
                    WHERE categoria = :categoria 
                    ORDER BY clicks_uso DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['categoria' => $categoria]);
            $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $textoEntrante = strtolower(trim($titulo . " " . $descripcion));

            foreach ($records as $row) {
                $palabrasGuardadas = strtolower(trim($row['palabras_clave']));
                
                $similitudPorcentaje = 0;
                similar_text($textoEntrante, $palabrasGuardadas, $similitudPorcentaje);

                // Si la coincidencia es alta, entregamos la solución con mayor reputación histórica
                if ($similitudPorcentaje >= 60.0) {
                    return $row['solucion'];
                }
            }
        } catch (\Exception $e) {
            error_log("[KnowledgeBase Error]: " . $e->getMessage());
        }
        return null;
    }


    public function saveToKnowledgeBase(string $categoria, string $titulo, string $solucion): void
    {
        try {
            // Limpiamos caracteres extraños del título para guardarlo como la clave de búsqueda limpia
            $palabrasClave = strtolower(trim(preg_replace('/[^\w\s]/u', '', $titulo)));
            if (strlen($palabrasClave) < 5 || strlen($solucion) < 20) return;

            $db = Database::getInstance()->getConnection();
            
            // Verificamos si ya existe exactamente el mismo título guardado
            $checkSql = "SELECT COUNT(*) FROM dbo.ai_knowledge_base WHERE palabras_clave = :pc AND categoria = :cat";
            $stmtCheck = $db->prepare($checkSql);
            $stmtCheck->execute(['pc' => $palabrasClave, 'cat' => $categoria]);
            
            if ((int)$stmtCheck->fetchColumn() === 0) {
                $sql = "INSERT INTO dbo.ai_knowledge_base (categoria, palabras_clave, solucion, clicks_uso, created_at) 
                        VALUES (:categoria, :palabras_clave, :solucion, 1, SYSUTCDATETIME())";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    'categoria'     => $categoria,
                    'palabras_clave' => $palabrasClave,
                    'solucion'       => $solucion
                ]);
            }
        } catch (\Exception $e) {
            error_log("[Autoentrenamiento Error]: " . $e->getMessage());
        }
    }

    public function askOllamaLocal(string $titulo, string $descripcion): ?string
    {
        if (!function_exists('curl_init')) return null;

        $prompt = "Eres un técnico de soporte de TI experto. Responde al siguiente problema del usuario, dando una solución paso a paso,  de forma corta, concisa, práctica, en un solo párrafo en lo posible, si es neecesario puedes hacer más, en español y sin usar formato markdown ni viñetas. Título del incidente: {$titulo}. Descripción del problema: {$descripcion}";

        $payload = [
            'model'  => 'qwen2.5:0.5b',
            'prompt' => $prompt,
            'stream' => false
        ];

        $ch = curl_init('http://localhost:11434/api/generate');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 12, // Timeout de 12 segundos para no colgar la experiencia web
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response && $httpCode === 200) {
            $decoded = json_decode($response, true);
            return $decoded['response'] ?? null;
        }
        return null;
    }

    public function askOpenAICloud(string $titulo, string $descripcion): ?string
    {
        $apiKey = trim((string)($_ENV['AI_API_KEY'] ?? ''));
        if ($apiKey === '' || !function_exists('curl_init')) return null;

        $payload = [
            'model' => 'qwen/qwen3.8-27b',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Eres un ingeniero de soporte técnico TI experto. Analiza el incidente y responde con una recomendación breve, en un solo párrafo, en español y sin usar viñetas ni formato markdown.'
                ],
                [
                    'role' => 'user', 
                    'content' => "Título: {$titulo}\nDescripción: {$descripcion}"
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 250,
        ];

        $url = 'https://api.groq.com/openai/v1/chat/completions';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response && $httpCode === 200) {
            $decoded = json_decode($response, true);
            if (isset($decoded['choices'][0]['message']['content'])) {
                return trim($decoded['choices'][0]['message']['content']);
            }
        }
        return null;
    }

    public function getTicketsByUser(int $userId, int $page = 1, int $perPage = DEFAULT_PAGE_SIZE): array
    {
        return $this->ticketRepo->findByUserPaginated($userId, $page, $perPage);
    }

    public function getMyAssets(int $userId): array
    {
        return $this->assetRepo->findByUser($userId);
    }

    private function validateTicket(array $data): array
    {
        $errors = [];
        if (empty(trim((string)($data['titulo'] ?? '')))) $errors['titulo'] = 'El título es obligatorio.';
        if (empty(trim((string)($data['descripcion'] ?? '')))) $errors['descripcion'] = 'Debes describir el problema.';
        return $errors;
    }
}

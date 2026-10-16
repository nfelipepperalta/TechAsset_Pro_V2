<?php

namespace Controllers;

use Core\Auth;
use Core\Response;
use Core\Session;
use Services\TicketService;

class UserDashboardController
{
    private TicketService $ticketService;

    public function __construct()
    {
        // Bloqueo de seguridad: Obliga a iniciar sesión y tener rol de 'usuario'
        Auth::check();
        //Auth::requireRole('usuario');

        $this->ticketService = new TicketService();
    }

    // GET /user-dashboard
    public function index(): void
    {
        Response::redirect('/user-dashboard/tickets');
    }

    // GET /user-dashboard/tickets
    public function tickets(): void
    {
        $user = Auth::user();
        $page = (int)($_GET['page'] ?? 1);

        $result = $this->ticketService->getTicketsByUser((int)$user['id'], $page);

        Response::layout('user_dashboard/tickets', [
            'title'   => 'Mis solicitudes',
            'tickets' => $result['data'],
            'paging'  => $result,
            'user'    => $user,
            'success' => Session::getFlash('success'),
            'error'   => Session::getFlash('error'),
        ]);
    }

    // GET /user-dashboard/tickets/create
    public function createForm(): void
    {
        Response::layout('user_dashboard/ticket_form', [
            'title'    => 'Crear solicitud',
            'user'     => Auth::user(),
            'errors'   => [],
            'old'      => [],
            'success'  => Session::getFlash('success'),
            'error'    => Session::getFlash('error'),
        ]);
    }

    // POST /user-dashboard/tickets/create
           // POST /user-dashboard/tickets/create
    public function store(): void
    {
        // Forzamos la respuesta como JSON limpio para el modal asíncrono
        header('Content-Type: application/json');

        // 🚀 CONEXIÓN NATIVA CON TU FRAMEWORK: Extraemos el usuario real usando tu Auth original
        $user = Auth::user();
        
        if (!$user || !isset($user['id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Error de autenticación: Sesión inválida en Auth.'
            ]);
            return;
        }

        $userId = (int)$user['id'];

    
        // 🚨 SIMULACIÓN DE ERROR CONTROLADO: Forzamos un resultado falso para probar la alerta de JavaScript
        //$result = ['success' => false, 'errors' => ['general' => 'Fallo simulado de inserción']];

        //Procesamos la creación del ticket enviando los datos nativos de $_POST
        $result = $this->ticketService->createTicket($_POST, $userId);

        if (isset($result['success']) && $result['success']) {
            $ticketId = $result['id'] ?? null;
            $iaStatus = 'pendiente';
            $iaSugerencia = '';

            if ($ticketId) {
                try {
                    $db = \Core\Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT ia_status, ia_sugerencia FROM dbo.ticket WHERE id = :id");
                    $stmt->execute(['id' => $ticketId]);
                    $ticketData = $stmt->fetch(\PDO::FETCH_ASSOC);
                    
                    if ($ticketData) {
                        $iaStatus = $ticketData['ia_status'] ?? 'pendiente';
                        $iaSugerencia = $ticketData['ia_sugerencia'] ?? '';
                    }
                } catch (\Exception $e) {
                    error_log("[Controller Sync Error]: " . $e->getMessage());
                }
            }

            echo json_encode([
                'success'       => true,
                'id'            => $ticketId,
                'ia_status'     => $iaStatus,
                'ia_sugerencia' => $iaSugerencia
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo guardar la solicitud en Azure SQL.',
                'errors'  => $result['errors'] ?? []
            ]);
        }
        return;
    }



    // GET /user-dashboard/assets
    public function assets(): void
    {
        $user = Auth::user();
        $assets = $this->ticketService->getMyAssets((int)$user['id']);

        Response::layout('user_dashboard/assets', [
            'title'   => 'Mis activos',
            'assets'  => $assets,
            'user'    => $user,
            'success' => Session::getFlash('success'),
            'error'   => Session::getFlash('error'),
        ]);
    }

        // POST /user-dashboard/tickets/approve-solution
        // POST /user-dashboard/tickets/approve-solution
    public function saveApprovedSolution(): void
    {
        header('Content-Type: application/json');
        
        // Leemos los datos enviados asíncronamente por el chat
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $solucionAprobada = trim((string)($_POST['solucion'] ?? ''));
        
        if ($ticketId === 0 || $solucionAprobada === '') {
            echo json_encode(['success' => false, 'message' => 'Datos insuficientes para el registro.']);
            return;
        }

        try {
            $db = \Core\Database::getInstance()->getConnection();
            
            // 1. Buscamos el registro completo del ticket para indexar y conocer su estado real de IA
            $stmtTicket = $db->prepare("SELECT titulo, categoria, ia_status FROM dbo.ticket WHERE id = :id");
            $stmtTicket->execute(['id' => $ticketId]);
            $ticket = $stmtTicket->fetch(\PDO::FETCH_ASSOC);

            if ($ticket) {
                $categoria = $ticket['categoria'];
                $palabrasClave = strtolower(trim(preg_replace('/[^\w\s]/u', '', $ticket['titulo'])));
                $iaStatusActual = $ticket['ia_status'] ?? 'analizado_nube';

                // 🚀 LIMPIEZA DE ESTRUCTURA: Removemos etiquetas HTML y encabezados duplicados del chat
                $solucionLimpia = preg_replace('/<[^>]*>/', '', $solucionAprobada);
                $solucionLimpia = str_replace('DIAGNÓSTICO INICIAL AUTOMÁTICO:', '', $solucionLimpia);
                $solucionLimpia = trim((string)$solucionLimpia);

                // 2. Buscamos si esta solución exacta ya existía para este problema
                $stmtCheck = $db->prepare("SELECT id FROM dbo.ai_knowledge_base WHERE categoria = :cat AND palabras_clave = :pc AND CAST(solucion AS NVARCHAR(MAX)) = :sol");
                $stmtCheck->execute(['cat' => $categoria, 'pc' => $palabrasClave, 'sol' => $solucionLimpia]);
                $existing = $stmtCheck->fetch();

                if ($existing) {
                    // Si ya existía, le sumamos un voto de éxito (reputación de efectividad)
                    $stmtUpdate = $db->prepare("UPDATE dbo.ai_knowledge_base SET clicks_uso = clicks_uso + 1 WHERE id = :id");
                    $stmtUpdate->execute(['id' => $existing['id']]);
                } else {
                    // Si es una causa/solución nueva para el mismo problema, la insertamos con 1 voto inicial
                    $stmtInsert = $db->prepare("INSERT INTO dbo.ai_knowledge_base (categoria, palabras_clave, solucion, clicks_uso, created_at) VALUES (:cat, :pc, :sol, 1, SYSUTCDATETIME())");
                    $stmtInsert->execute(['cat' => $categoria, 'pc' => $palabrasClave, 'sol' => $solucionLimpia]);
                }

                // 🚀 DETECTOR DE ORIGEN DINÁMICO: Evaluamos qué nodo resolvió realmente para asignar el prefijo correcto
                if ($iaStatusActual === 'resuelto_local') {
                    $prefijoReal = "[Autoentrenamiento Local]: ";
                } elseif ($iaStatusActual === 'analizado_local') {
                    $prefijoReal = "[IA Local Ollama]: ";
                } else {
                    $prefijoReal = "[IA Nube Respaldo]: ";
                }

                $solucionFinalConPrefijo = $prefijoReal . $solucionLimpia;
                
                // 3. Marcamos el ticket actual como cerrado/resuelto inyectando su texto purificado
                $stmtClose = $db->prepare("UPDATE dbo.ticket SET estado = 'cerrado', ia_sugerencia = :sugerencia, updated_at = GETDATE() WHERE id = :id");
                $stmtClose->execute([
                    'sugerencia' => $solucionFinalConPrefijo,
                    'id' => $ticketId
                ]);

                echo json_encode(['success' => true, 'message' => 'Solución retroalimentada y guardada con su prefijo real.']);
                return;
            }
            
            echo json_encode(['success' => false, 'message' => 'Ticket no encontrado.']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error en el servidor de Azure: ' . $e->getMessage()]);
        }
    }


    // POST /user-dashboard/tickets/chat-interact
    public function chatInteract(): void
    {
        header('Content-Type: application/json');
        
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $mensajeUsuario = trim((string)($_POST['mensaje'] ?? ''));
        
        if ($ticketId === 0 || $mensajeUsuario === '') {
            echo json_encode(['success' => false, 'message' => 'Datos de chat insuficientes.']);
            return;
        }

        try {
            $db = \Core\Database::getInstance()->getConnection();
            
            // 1. Consultamos el estado actual del ticket y su contexto
            $stmt = $db->prepare("SELECT titulo, categoria, descripcion, ia_status FROM dbo.ticket WHERE id = :id");
            $stmt->execute(['id' => $ticketId]);
            $ticket = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$ticket) {
                echo json_encode(['success' => false, 'message' => 'Ticket no encontrado.']);
                return;
            }

            $iaStatus = $ticket['ia_status'];
            $categoria = $ticket['categoria'];
            $titulo = $ticket['titulo'];
            
            $respuestaIA = '';
            $nuevoStatus = $iaStatus;
            $forzarEscalamientoHumano = false;

            // 🔁 CONMUTACIÓN INTELIGENTE DE FASES EN EL CHAT
            
            // --- MANEJO DE FASE 1: AUTOENTRENAMIENTO LOCAL ---
            if ($iaStatus === 'resuelto_local') {
                // Buscamos si hay OTRAS soluciones guardadas para la misma categoría que no sean la principal ya usada
                $stmtKB = $db->prepare("SELECT TOP 1 solucion FROM dbo.ai_knowledge_base WHERE categoria = :cat AND palabras_clave LIKE :pc ORDER BY clicks_uso DESC");
                $stmtKB->execute(['cat' => $categoria, 'pc' => '%' . substr($titulo, 0, 5) . '%']);
                $otraSolucion = $stmtKB->fetchColumn();

                // Si el usuario explícitamente dice que no sirvió o ya no hay más datos guardados en la BD
                if (strpos(strtolower($mensajeUsuario), 'no me sirvió') !== false || !$otraSolucion) {
                    $respuestaIA = "No hay más respuestas en la base de conocimientos corporativa. Te pasaré de inmediato con nuestro nodo de IA Local Ollama para un análisis en tiempo real... 🤖";
                    $nuevoStatus = 'analizado_local'; // Escalamos el ticket a Fase 2 para la siguiente interacción
                } else {
                    $respuestaIA = "Encontré otra alternativa registrada en el autoentrenamiento: " . $otraSolucion;
                }
            }
            
            // --- MANEJO DE FASE 2: IA LOCAL OLLAMA ---
            elseif ($nuevoStatus === 'analizado_local') {
                // Si el usuario pide más soporte porque Ollama no dio la talla o viene rebotado de Fase 1
                if (strpos(strtolower($mensajeUsuario), 'no me sirvió') !== false) {
                    $respuestaIA = "El nodo de IA Local no dispone de más alternativas válidas. Escalando soporte de forma definitiva a nuestro nodo de IA en la Nube (Groq)... ☁️";
                    $nuevoStatus = 'analizado_nube'; // Escalamos el ticket a Fase 3
                } else {
                    // Consultamos de forma interactiva a Ollama
                    // 🚀 REPARADO: Pasamos el título del caso original y el mensaje de texto de la conversación actual
                        $respuestaIA = $this->ticketService->askOllamaLocal($titulo, $mensajeUsuario);

                    if (!$respuestaIA) {
                        $respuestaIA = "El nodo de IA Local Ollama no responde (Servicio inactivo). Escalando soporte de forma automática a nuestro nodo de IA en la Nube (Groq)... ☁️";
                        $nuevoStatus = 'analizado_nube';
                    }
                }
            }
            
            // --- MANEJO DE FASE 3: IA EN LA NUBE (GROQ) ---
            if ($nuevoStatus === 'analizado_nube' && $respuestaIA === '') {
                // Consultamos directamente a la infraestructura de Groq usando tu modelo verificado qwen
                $respuestaIA = $this->ticketService->askOpenAICloud($titulo, $mensajeUsuario . " Recuerda entregar exactamente DOS soluciones sencillas paso a paso para un usuario normal.");
                
                if (!$respuestaIA) {
                    $respuestaIA = "Hubo un inconveniente con los canales de comunicación de la nube.";
                }

                // 🚨 DETECTOR DE PALABRAS CLAVE PARA TRASLADAR A SOPORTE HUMANO
                $buscarBloqueos = ['no tengo la solucion', 'debe hacerlo un humano', 'humano', 'tecnico fisico', 'soporte tecnico presencial', 'contacte al administrador'];
                foreach ($buscarBloqueos as $keyword) {
                    if (strpos(strtolower($respuestaIA), $keyword) !== false || strpos(strtolower($mensajeUsuario), 'necesito un humano') !== false) {
                        $forzarEscalamientoHumano = true;
                        break;
                    }
                }

                if ($forzarEscalamientoHumano) {
                    $respuestaIA = "🚨 [SOPORTE HUMANO]: El agente de IA ha agotado sus capacidades de resolución digital para este caso o requiere intervención física. Trasladando soporte de forma inmediata a la cola de un ingeniero de soporte TI humano. El chat automatizado ha sido cerrado.";
                    $nuevoStatus = 'escalado_humano';
                }
            }

            // 3. Si hubo un cambio de fase, actualizamos el estado del ticket en Azure SQL
            if ($nuevoStatus !== $iaStatus) {
                $stmtUpdate = $db->prepare("UPDATE dbo.ticket SET ia_status = :status, updated_at = GETDATE() WHERE id = :id");
                $stmtUpdate->execute(['status' => $nuevoStatus, 'id' => $ticketId]);
            }

            echo json_encode([
                'success' => true,
                'respuesta' => $respuestaIA,
                'nuevo_status' => $nuevoStatus
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error en el orquestador de Azure: ' . $e->getMessage()]);
        }
    }


}

<?php
// ============================================================
//  controllers/DiagnosticController.php
//  Endpoint AJAX para el lector de diagnóstico
// ============================================================

namespace Controllers;

use Services\DiagnosticService;
use Core\Auth;

class DiagnosticController
{
    private DiagnosticService $service;

    public function __construct()
    {
        Auth::requireRole('admin');
        $this->service = new DiagnosticService();
    }

    // GET /diagnostic  — vista principal (panel en el dashboard)
    public function index(): void
    {
        \Core\Response::layout('diagnostic/panel', ['title' => 'Lector de Diagnóstico']);
    }

    // POST /diagnostic/read  — AJAX: leer eventos
    public function read(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $host  = $_POST['host']  ?? '192.168.0.143';
        $limit = (int) ($_POST['limit']  ?? 50);
        $hours = (int) ($_POST['hours']  ?? 24);
        $os    = $_POST['os'] ?? 'windows';

        // Sanitizar
        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            echo json_encode(['error' => 'IP no válida']);
            return;
        }
        $limit = max(10, min(200, $limit));
        $hours = max(1,  min(168, $hours));

        $data = $this->service->leerTodo($host, $os, $limit, $hours);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

<?php
// ============================================================
//  controllers/NetworkScannerController.php
//  Módulo de escaneo de red con análisis IA
// ============================================================

namespace Controllers;

use Services\NetworkScannerService;
use Core\Auth;
use Core\Response;
use Core\Session;

class NetworkScannerController
{
    private NetworkScannerService $scannerService;

    public function __construct()
    {
        Auth::requireRole('ti');
        $this->scannerService = new NetworkScannerService();
    }

    // GET /scanner — formulario principal
    public function index(): void
    {
        Response::layout('scanner/index', [
            'title'   => 'Escáner de Red',
            'user'    => Auth::user(),
            'result'  => null,
            'error'   => Session::getFlash('error'),
        ]);
    }

    // POST /scanner/ip — escanear una IP
    public function scanIP(): void
    {
        $ip         = trim($_POST['ip'] ?? '');
        $intensidad = $_POST['intensidad'] ?? 'normal';

        if (empty($ip)) {
            Session::flash('error', 'Debes ingresar una IP.');
            Response::redirect('/scanner');
        }

        $result = $this->scannerService->scanIP($ip, $intensidad);

        Response::layout('scanner/result', [
            'title'  => 'Análisis de ' . $ip,
            'result' => $result,
            'ip'     => $ip,
            'user'   => Auth::user(),
        ]);
    }

    // POST /scanner/range — escanear rango
    public function scanRange(): void
    {
        $range = trim($_POST['range'] ?? '');

        if (empty($range)) {
            Session::flash('error', 'Debes ingresar un rango CIDR.');
            Response::redirect('/scanner');
        }

        $result = $this->scannerService->scanRange($range);

        Response::layout('scanner/range_result', [
            'title'  => 'Escaneo de rango: ' . $range,
            'result' => $result,
            'range'  => $range,
            'user'   => Auth::user(),
        ]);
    }
}

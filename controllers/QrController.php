<?php
// ============================================================
//  controllers/QrController.php
//  Generación y escaneo de códigos QR
// ============================================================

namespace Controllers;

use Services\QrService;
use Core\Auth;
use Core\Response;
use Core\Session;

class QrController
{
    private QrService $qrService;

    public function __construct()
    {
        $this->qrService = new QrService();
    }

    // GET /qr/{token} — acceso público para escaneo desde móvil
    public function scan(string $token): void
    {
        $asset = $this->qrService->scan($token);

        if (!$asset) {
            http_response_code(404);
            Response::view('errors/qr_invalid', [
                'title' => 'QR no válido',
            ]);
            return;
        }

        Response::view('assets/qr_scan', [
            'title' => $asset['nombre'],
            'asset' => $asset,
        ]);
    }

    // POST /qr/generate/{id} — solo para usuarios ti/admin
    public function generate(int $id): void
    {
        Auth::requireRole('ti');
        $result = $this->qrService->generate($id);

        if (!$result['success']) {
            Session::flash('error', $result['message'] ?? 'No se pudo generar el QR.');
        } else {
            Session::flash('success', 'Código QR generado correctamente.');
        }

        Response::redirect('/assets/' . $id);
    }
}

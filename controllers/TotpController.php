<?php
// ============================================================
//  controllers/TotpController.php
//  Configuración y verificación de 2FA
// ============================================================
namespace Controllers;

use Services\TotpService;
use Core\Auth;
use Core\Response;
use Core\Session;

class TotpController
{
    private TotpService $totpService;

    public function __construct()
    {
        Auth::requireRole('usuario');
        $this->totpService = new TotpService();
    }

    // GET /2fa/setup — mostrar QR para configurar
    public function setup(): void
    {
        $user = Auth::user();
        $data = $this->totpService->generarSecret($user);

        // Guardar secret temporal en sesión
        Session::set('totp_secret_temp', $data['secret']);

        Response::layout('totp/setup', [
            'title'   => 'Configurar 2FA',
            'secret'  => $data['secret'],
            'qr_url'  => $data['qr_url'],
            'user'    => $user,
            'error'   => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
        ]);
    }

    // POST /2fa/setup — activar 2FA
    public function activate(): void
    {
        $user   = Auth::user();
        $secret = Session::get('totp_secret_temp');
        $code   = trim($_POST['code'] ?? '');

        if (!$secret) {
            Session::flash('error', 'Sesión expirada. Intenta de nuevo.');
            Response::redirect('/2fa/setup');
            return;
        }

        if ($this->totpService->activar($user['id'], $secret, $code)) {
            Session::remove('totp_secret_temp');
            Session::flash('success', '2FA activado correctamente.');
            Response::redirect('/dashboard');
        } else {
            Session::flash('error', 'Código incorrecto. Verifica tu app autenticadora.');
            Response::redirect('/2fa/setup');
        }
    }

    // GET /2fa/verify — pedir código después del login
    public function verifyForm(): void
    {
        if (!Session::get('totp_pending_user')) {
            Response::redirect('/auth/login');
            return;
        }
        Response::view('totp/verify', [
            'title' => 'Verificación 2FA',
            'error' => Session::getFlash('error'),
        ]);
    }

    // POST /2fa/verify — verificar código
    public function verify(): void
    {
        $pendingUser = Session::get('totp_pending_user');
        if (!$pendingUser) {
            Response::redirect('/auth/login');
            return;
        }

        $code = trim($_POST['code'] ?? '');
        if ($this->totpService->verificarCodigo($pendingUser['totp_secret'], $code)) {
            Session::remove('totp_pending_user');
            Auth::login($pendingUser);
            Response::redirect('/dashboard');
        } else {
            Session::flash('error', 'Código incorrecto. Intenta de nuevo.');
            Response::redirect('/2fa/verify');
        }
    }

    // POST /2fa/disable — desactivar 2FA
    public function disable(): void
    {
        $user = Auth::user();
        $this->totpService->desactivar($user['id']);
        Session::flash('success', '2FA desactivado.');
        Response::redirect('/dashboard');
    }
}

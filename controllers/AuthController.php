<?php
// ============================================================
//  controllers/AuthController.php
//  Login, logout
// ============================================================

namespace Controllers;

use Services\AuthService;
use Core\Auth;
use Core\Response;
use Core\Session;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    // GET /auth/login
    public function loginForm(): void
    {
        // Si ya está autenticado redirigir al dashboard
        if (Session::isLoggedIn()) {
            Response::redirect('/dashboard');
        }

        Response::view('auth/login', [
            'title' => 'Iniciar Sesión',
            'error' => Session::getFlash('error'),
        ]);
    }

    // POST /auth/login
    public function login(): void
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'login_' . md5($ip);

        if (!Session::checkRateLimit($key, 5, 300)) {
            $intentos = Session::getRateLimitAttempts($key);
            Session::flash('error', "Demasiados intentos fallidos. Espera 5 minutos. (Intento {$intentos}/5)");
            Response::redirect('/auth/login');
            return;
        }

        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $result = $this->authService->login($email, $password);
        if (!$result['success']) {
            Session::incrementRateLimit($key);
            Session::flash('error', $result['message']);
            Response::redirect('/auth/login');
            return;
        }
        Session::clearRateLimit($key);

        // Verificar si tiene 2FA habilitado
        if (!empty($result['user']['totp_enabled'])) {
            Session::set('totp_pending_user', $result['user']);
            Response::redirect('/2fa/verify');
            return;
        }

        Response::redirect('/dashboard');
    }
    // GET /auth/logout
    public function logout(): void
    {
        $this->authService->logout();
    }
}

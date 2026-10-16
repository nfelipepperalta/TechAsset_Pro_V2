<?php
// ============================================================
//  core/Session.php
//  Gestión centralizada de sesiones PHP
// ============================================================

namespace Core;

class Session
{
    private static bool $started = false;

    // Inicia la sesión con configuración segura
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name(SESSION_NAME);

        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => APP_ENV === 'production',
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        session_start();
        self::$started = true;

        // Regenerar ID si la sesión es nueva (previene session fixation)
        if (empty($_SESSION['_initiated'])) {
            session_regenerate_id(true);
            $_SESSION['_initiated'] = true;
        }
    }

    // Guardar un valor en sesión
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    // Obtener un valor de sesión
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    // Verificar si existe una clave en sesión
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    // Eliminar una clave de sesión
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    // Destruir toda la sesión (logout)
    public static function destroy(): void
    {
        session_unset();
        session_destroy();

        // Eliminar cookie de sesión
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        self::$started = false;
    }

    // Mensajes flash (se muestran una sola vez)
    public static function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string
    {
        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }

    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    // Usuario autenticado en sesión
    public static function setUser(array $user): void
    {
        self::set('auth_user', $user);
    }

    public static function getUser(): ?array
    {
        return self::get('auth_user');
    }

    public static function isLoggedIn(): bool
    {
        return self::has('auth_user');
    }

    // ── Rate Limiting ─────────────────────────────────────────
    public static function checkRateLimit(string $key, int $maxAttempts = 5, int $decaySeconds = 300): bool
    {
        $attempts = $_SESSION['rate_' . $key]['attempts'] ?? 0;
        $lastAttempt = $_SESSION['rate_' . $key]['last'] ?? 0;

        // Resetear si pasó el tiempo de decay
        if (time() - $lastAttempt > $decaySeconds) {
            $_SESSION['rate_' . $key] = ['attempts' => 0, 'last' => time()];
            return true;
        }

        return $attempts < $maxAttempts;
    }

    public static function incrementRateLimit(string $key): void
    {
        $_SESSION['rate_' . $key]['attempts'] = ($_SESSION['rate_' . $key]['attempts'] ?? 0) + 1;
        $_SESSION['rate_' . $key]['last'] = time();
    }

    public static function clearRateLimit(string $key): void
    {
        unset($_SESSION['rate_' . $key]);
    }

    public static function getRateLimitAttempts(string $key): int
    {
        return $_SESSION['rate_' . $key]['attempts'] ?? 0;
    }

    // ── CSRF Protection ──────────────────────────────────────
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::csrfToken() . '">';
    }

    public static function verifyCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                http_response_code(403);
                die(json_encode(['error' => 'Token CSRF inválido.']));
            }
        }
    }

}
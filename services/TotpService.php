<?php
// ============================================================
//  services/TotpService.php
//  Servicio de autenticación de dos factores (TOTP)
// ============================================================
namespace Services;

use PragmaRX\Google2FA\Google2FA;
use Repositories\UserRepository;

class TotpService
{
    private Google2FA $google2fa;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
        $this->userRepo  = new UserRepository();
    }

    // Generar secret y QR para configurar 2FA
    public function generarSecret(array $user): array
    {
        $secret = $this->google2fa->generateSecretKey();
        $qrUrl  = $this->google2fa->getQRCodeUrl(
            APP_NAME,
            $user['email'],
            $secret
        );
        return ['secret' => $secret, 'qr_url' => $qrUrl];
    }

    // Verificar código TOTP
    public function verificarCodigo(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    // Activar 2FA para un usuario
    public function activar(int $userId, string $secret, string $code): bool
    {
        if (!$this->verificarCodigo($secret, $code)) {
            return false;
        }
        $this->userRepo->update($userId, [
            'totp_secret'  => $secret,
            'totp_enabled' => 1,
        ]);
        return true;
    }

    // Desactivar 2FA
    public function desactivar(int $userId): void
    {
        $this->userRepo->update($userId, [
            'totp_secret'  => null,
            'totp_enabled' => 0,
        ]);
    }
}

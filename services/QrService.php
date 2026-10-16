<?php
// ============================================================
//  services/QrService.php
//  Lógica de negocio — generación y lectura de códigos QR
// ============================================================

namespace Services;

use Repositories\QrCodeRepository;
use Repositories\AssetRepository;

class QrService
{
    private QrCodeRepository $qrRepo;
    private AssetRepository  $assetRepo;

    public function __construct()
    {
        $this->qrRepo    = new QrCodeRepository();
        $this->assetRepo = new AssetRepository();
    }

    // Generar QR para un activo
    public function generate(int $assetId): array
    {
        $asset = $this->assetRepo->findById($assetId);
        if (!$asset) {
            return ['success' => false, 'message' => 'Activo no encontrado.'];
        }

        // Generar token único
        $token = bin2hex(random_bytes(16));
        $url   = QR_BASE_URL . '/' . $token;

        // Si ya tiene QR, regenerar; si no, crear
        $existing = $this->qrRepo->findByAsset($assetId);

        if ($existing) {
            $this->qrRepo->regenerate($assetId, $token, $url);
        } else {
            $this->qrRepo->insert([
                'asset_id'    => $assetId,
                'token_unico' => $token,
                'url_publica' => $url,
                'activo'      => 1,
            ]);
        }

        return ['success' => true, 'token' => $token, 'url' => $url];
    }

    // Resolver un token QR escaneado — retorna los datos del activo
    public function scan(string $token): ?array
    {
        $qr = $this->qrRepo->findByToken($token);
        if (!$qr) return null;

        return $this->assetRepo->findByIdWithDetails($qr['asset_id']);
    }

    // Obtener QR de un activo
    public function getByAsset(int $assetId): ?array
    {
        return $this->qrRepo->findByAsset($assetId);
    }
}

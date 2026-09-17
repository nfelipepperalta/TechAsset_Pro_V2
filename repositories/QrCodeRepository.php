<?php
// ============================================================
//  repositories/QrCodeRepository.php
//  Acceso a datos — tabla dbo.qr_code
// ============================================================

namespace Repositories;

class QrCodeRepository extends BaseRepository
{
    protected string $table = 'qr_code';

    // Buscar QR por token único (escaneo público)
    public function findByToken(string $token): ?array
    {
        $sql = "SELECT q.*, a.nombre AS activo_nombre, a.serial AS activo_serial
                FROM dbo.qr_code q
                JOIN dbo.asset a ON q.asset_id = a.id
                WHERE q.token_unico = :token AND q.activo = 1";

        return $this->queryOne($sql, ['token' => $token]);
    }

    // Buscar QR por asset_id
    public function findByAsset(int $assetId): ?array
    {
        $sql = "SELECT * FROM dbo.qr_code WHERE asset_id = :asset_id";
        return $this->queryOne($sql, ['asset_id' => $assetId]);
    }

    // Marcar QR anterior como inactivo y registrar regeneración
    public function regenerate(int $assetId, string $newToken, string $newUrl): int
    {
    	$existing = $this->findByAsset($assetId);

    	if ($existing) {
        // Actualizar el registro existente en lugar de insertar uno nuevo
        	$sql = "UPDATE dbo.qr_code
                	SET token_unico   = :token,
                    		url_publica   = :url,
                   		 activo        = 1,
                    	regenerado_at = SYSUTCDATETIME()
                	WHERE asset_id = :asset_id";
        	$this->execute($sql, [
            		'token'    => $newToken,
            		'url'      => $newUrl,
            		'asset_id' => $assetId,
        		]);
        	return $existing['id'];
    	}

    	// Si no existe, insertar nuevo
    	return $this->insert([
        	'asset_id'    => $assetId,
        	'token_unico' => $newToken,
        	'url_publica' => $newUrl,
        	'activo'      => 1,
    		]);
	}
}

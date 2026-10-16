<?php
// ============================================================
//  public/restablecer.php
//  Script temporal para restablecer contraseñas de forma segura
// ============================================================

define('ROOT_PATH', dirname(__DIR__));
require ROOT_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

require ROOT_PATH . '/config/app.php';

use Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    // Generar los hashes perfectos usando la configuración original del proyecto
    $hashAdmin   = password_hash('Admin123456789', PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    $hashTi      = password_hash('Ti123456789', PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    $hashUsuario = password_hash('Usuario123456789', PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

    // 1. Actualizar Administrador (Felipe Paez)
    $stmt1 = $db->prepare("UPDATE dbo.[user] SET password_hash = :hash WHERE id = 1");
    $stmt1->execute(['hash' => $hashAdmin]);

    // 2. Actualizar TI (Laura Jiménez)
    $stmt2 = $db->prepare("UPDATE dbo.[user] SET password_hash = :hash WHERE id = 2");
    $stmt2->execute(['hash' => $hashTi]);

    // 3. Actualizar Usuario Común (Felipe Torres)
    $stmt3 = $db->prepare("UPDATE dbo.[user] SET password_hash = :hash WHERE id = 5");
    $stmt3->execute(['hash' => $hashUsuario]);

    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h2 style='color:#198754;'>¡Contraseñas actualizadas con éxito en Azure SQL de forma 100% segura!</h2>";
    echo "<p style='color:#6c757d;'>Por favor, borra el archivo <b>public/restablecer.php</b> desde tu VS Code de inmediato.</p>";
    echo "</div>";

} catch (Exception $e) {
    echo "Error crítico: " . $e->getMessage();
}

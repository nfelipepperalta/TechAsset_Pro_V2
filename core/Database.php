<?php
// ============================================================
//  core/Database.php
//  Singleton PDO — Conexión a Azure SQL / TECHASSET
// ============================================================

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $config = require CONFIG_PATH . '/database.php';

        try {
            $this->connection = new PDO(
                $config['dsn'],
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            error_log('[TECHASSET DB] ' . $e->getMessage());

            $message = APP_DEBUG
                ? 'Error de conexión a TECHASSET: ' . $e->getMessage()
                : 'No se pudo conectar a la base de datos. Contacte al administrador.';

            http_response_code(500);
            die(json_encode(['error' => $message]));
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    // Prevenir clonación y deserialización del singleton
    private function __clone() {}
    public function __wakeup(): void {}
}

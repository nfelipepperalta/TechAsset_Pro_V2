<?php
// ============================================================
//  config/database.php
//  Configuración de conexión — Azure SQL / DB: TECHASSET
//  Lee todas las credenciales desde .env
// ============================================================

return [
    'driver'     => 'sqlsrv',
    'host'       => $_ENV['DB_HOST']       ?? 'sqlucc-lab.database.windows.net',
    'port'       => $_ENV['DB_PORT']       ?? '1433',
    'dbname'     => $_ENV['DB_NAME']       ?? 'TECHASSET',
    'username'   => $_ENV['DB_USER']       ?? '',
    'password'   => $_ENV['DB_PASS']       ?? '',
    'encrypt'    => $_ENV['DB_ENCRYPT']    ?? 'true',
    'trust_cert' => $_ENV['DB_TRUST_CERT'] ?? 'false',

    // DSN para PDO — driver sqlsrv (Windows/Azure)
    'dsn' => sprintf(
        'sqlsrv:Server=%s,%s;Database=%s;Encrypt=%s;TrustServerCertificate=%s',
        $_ENV['DB_HOST']       ?? 'sqlucc-lab.database.windows.net',
        $_ENV['DB_PORT']       ?? '1433',
        $_ENV['DB_NAME']       ?? 'TECHASSET',
        $_ENV['DB_ENCRYPT']    ?? 'true',
        $_ENV['DB_TRUST_CERT'] ?? 'true'
    ),

    // Opciones PDO
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];

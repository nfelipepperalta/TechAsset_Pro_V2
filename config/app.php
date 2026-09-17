<?php
// ============================================================
//  config/app.php
//  Constantes globales de la aplicación
//  Se carga una sola vez desde public/index.php
// ============================================================

// Nombre y entorno
define('APP_NAME',     $_ENV['APP_NAME']     ?? 'TechAsset Pro');
define('APP_ENV',      $_ENV['APP_ENV']      ?? 'production');
define('APP_URL',      $_ENV['APP_URL']      ?? 'http://localhost');
define('APP_DEBUG',   ($_ENV['APP_DEBUG']    ?? 'false') === 'true');
define('APP_SECRET',   $_ENV['APP_SECRET_KEY'] ?? '');

// Zona horaria
$timezone = $_ENV['APP_TIMEZONE'] ?? 'America/Bogota';
date_default_timezone_set($timezone);
define('APP_TIMEZONE', $timezone);

// Rutas absolutas del proyecto (ROOT_PATH se define en public/index.php)
define('CORE_PATH',        ROOT_PATH . '/core');
define('CONFIG_PATH',      ROOT_PATH . '/config');
define('CONTROLLERS_PATH', ROOT_PATH . '/controllers');
define('SERVICES_PATH',    ROOT_PATH . '/services');
define('REPOSITORIES_PATH',ROOT_PATH . '/repositories');
define('VIEWS_PATH',       ROOT_PATH . '/views');
define('HELPERS_PATH',     ROOT_PATH . '/helpers');
define('PUBLIC_PATH',      ROOT_PATH . '/public');

// Sesión
define('SESSION_NAME',     $_ENV['SESSION_NAME']     ?? 'techasset_session');
define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 7200));

// Seguridad
define('BCRYPT_COST',      (int)($_ENV['BCRYPT_COST'] ?? 12));

// QR
define('QR_BASE_URL',      $_ENV['QR_BASE_URL'] ?? APP_URL . '/qr');

// Roles disponibles (orden de menor a mayor privilegio)
define('ROLES', ['usuario', 'auditor', 'ti', 'admin']);

// Estados de activo
define('ASSET_STATES', ['adquisicion', 'activo', 'mantenimiento', 'baja']);

// Paginación por defecto
define('DEFAULT_PAGE_SIZE', 20);

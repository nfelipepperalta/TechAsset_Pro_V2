<?php
// ============================================================
//  config/routes.php
//  Definición de todas las rutas de TechAsset Pro
//  $router es inyectado desde public/index.php
// ============================================================

// ── Autenticación ─────────────────────────────────────────────
$router->get( '/auth/login',   'AuthController', 'loginForm');
$router->post('/auth/login',   'AuthController', 'login');
$router->get( '/auth/logout',  'AuthController', 'logout');

// ── Dashboard ─────────────────────────────────────────────────
$router->get('/',              'DashboardController', 'index');
$router->get('/dashboard',     'DashboardController', 'index');

// ── Activos ───────────────────────────────────────────────────
$router->get( '/assets',            'AssetController', 'index');
$router->get( '/assets/create',     'AssetController', 'create');
$router->post('/assets/create',     'AssetController', 'store');
$router->get( '/assets/{id}',       'AssetController', 'show');
$router->get( '/assets/{id}/edit',  'AssetController', 'edit');
$router->post('/assets/{id}/edit',  'AssetController', 'update');
$router->post('/assets/{id}/delete','AssetController', 'delete');

// ── Mantenimientos ────────────────────────────────────────────
$router->get( '/maintenance',              'MaintenanceController', 'index');
$router->get( '/maintenance/create',       'MaintenanceController', 'create');
$router->post('/maintenance/create',       'MaintenanceController', 'store');
$router->get( '/maintenance/{id}/edit',    'MaintenanceController', 'edit');
$router->post('/maintenance/{id}/edit',    'MaintenanceController', 'update');
$router->post('/maintenance/{id}/delete',  'MaintenanceController', 'delete');

// ── Alertas ───────────────────────────────────────────────────
$router->get( '/alerts',           'AlertController', 'index');
$router->post('/alerts/{id}/read', 'AlertController', 'markRead');
$router->post('/alerts/{id}/resolve','AlertController','resolve');

// ── Reportes ──────────────────────────────────────────────────
$router->get('/reports',           'ReportController', 'index');
$router->get('/reports/assets',    'ReportController', 'assets');
$router->get('/reports/warranty',  'ReportController', 'warranty');
$router->get('/reports/export',    'ReportController', 'export');

// ── Usuarios ──────────────────────────────────────────────────
$router->get( '/users',            'UserController', 'index');
$router->get( '/users/create',     'UserController', 'create');
$router->post('/users/create',     'UserController', 'store');
$router->get( '/users/{id}/edit',  'UserController', 'edit');
$router->post('/users/{id}/edit',  'UserController', 'update');
$router->post('/users/{id}/delete','UserController', 'delete');

// ── QR ────────────────────────────────────────────────────────
$router->get('/qr/{token}',        'QrController', 'scan');
$router->post('/qr/generate/{id}', 'QrController', 'generate');

// ── IA Predictiva ─────────────────────────────────────────────
$router->get( '/ia',               'IAController', 'index');
$router->get( '/ia/asset/{id}',    'IAController', 'asset');
$router->post('/ia/refresh',       'IAController', 'refresh');

// ── Escáner de Red ────────────────────────────────────────────
$router->get( '/scanner',        'NetworkScannerController', 'index');
$router->post('/scanner/ip',     'NetworkScannerController', 'scanIP');
$router->post('/scanner/range',  'NetworkScannerController', 'scanRange');

// ── Eventos de activos ────────────────────────────────────────
$router->post('/assets/{id}/events', 'AssetController', 'readEvents');
$router->get( '/assets/{id}/rdp',        'AssetController', 'rdp');

// ── Diagnóstico ───────────────────────────────────────────────
$router->get( '/diagnostic',      'DiagnosticController', 'index');
$router->post('/diagnostic/read', 'DiagnosticController', 'read');

// ── 2FA ───────────────────────────────────────────────────────
$router->get( '/2fa/setup',   'TotpController', 'setup');
$router->post('/2fa/setup',   'TotpController', 'activate');
$router->get( '/2fa/verify',  'TotpController', 'verifyForm');
$router->post('/2fa/verify',  'TotpController', 'verify');
$router->post('/2fa/disable', 'TotpController', 'disable');

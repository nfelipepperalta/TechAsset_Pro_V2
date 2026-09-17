<?php
// ============================================================
//  public/index.php
//  Único punto de entrada de TechAsset Pro
//  Todo el tráfico HTTP pasa por aquí
// ============================================================

// Ruta raíz del proyecto (un nivel arriba de /public)
define('ROOT_PATH', dirname(__DIR__));

// Autoload de Composer (clases + dependencias)
require ROOT_PATH . '/vendor/autoload.php';

// Cargar variables de entorno desde .env
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

// Cargar configuración de la aplicación
require ROOT_PATH . '/config/app.php';

// Iniciar sesión segura
\Core\Session::start();

// Crear el router y cargar las rutas
$router = new \Core\Router();
require ROOT_PATH . '/config/routes.php';

// Despachar la petición
$router->dispatch();

<?php
/**
 * Front controller: punto único de entrada (MVC).
 * Redirige a la vista de captura de datos / registro si el usuario no ha iniciado sesión.
 */

session_name('MYFORUM_SESSION');
session_start();

require __DIR__ . '/config/config.php';

// Autoloader de clases (Controllers, Models, Core)
spl_autoload_register(function ($class) {
    $dirs = [APP_PATH . DS . 'Models', APP_PATH . DS . 'Controllers', APP_PATH . DS . 'Core'];
    foreach ($dirs as $dir) {
        $file = $dir . DS . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

// Helpers globales
require_once APP_PATH . DS . 'Helpers' . DS . 'functions.php';

$url    = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $router = new Router();
    require APP_PATH . DS . 'routes.php';
    $router->dispatch($method, $url);
} catch (Throwable $e) {
    http_response_code(500);
    if (DEBUG) {
        echo '<pre>' . e($e->getMessage()) . PHP_EOL . e($e->getTraceAsString()) . '</pre>';
    } else {
        echo 'An unexpected error occurred. Please try again.';
    }
}
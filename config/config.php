<?php
/**
 * Configuración global de la aplicación.
 * Ajusta los datos de conexión a tu entorno XAMPP.
 */

define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . DS . 'app');
define('CONFIG_PATH', ROOT_PATH . DS . 'config');
define('PUBLIC_PATH', ROOT_PATH . DS . 'public');

// Base URL calculada automáticamente (ej. http://localhost/my-forum)
$__scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
define('BASE_URL', $__scriptDir);

// ------------------------------------------------
// Base de datos (MySQLi - LocalHost)
// ------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'my_forum');
define('DB_PORT', 3306);

// ------------------------------------------------
// Base de datos (MySQLi - Production)
// ------------------------------------------------
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'my_forum');
// define('DB_PORT', 3306);

// ------------------------------------------------
// Nombre de la aplicación
// ------------------------------------------------
define('APP_NAME', 'Academic Forum');
define('DEBUG', true);

// --------------------------------------------------
// Seguridad y reglas de negocio del foro
// --------------------------------------------------
// Dominio del correo institucional: xxxx@ecomundo.edu.ec
define('ID_DOMAIN', 'ecomundo.edu.ec');
// Máximo de intentos fallidos de inicio de sesión antes de bloquear
define('MAX_LOGIN_ATTEMPTS', 3);
// Longitud mínima de respuestas / conclusiones
define('MIN_ANSWER_LEN', 10);
// Caracteres permitidos para nombres
define('NAME_PATTERN', "/^[a-zA-Z\u{00C0}-\u{024F}\s'.\-]+$/u");
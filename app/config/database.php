<?php
/**
 * Configuración de Base de Datos
 * ISO 27001 Compliance Platform
 */

// Cargar variables de entorno desde .env
function loadEnv($path) {
    if (!file_exists($path)) {
        die('Archivo .env no encontrado');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

// Cargar .env
loadEnv(__DIR__ . '/../../.env');

// Definir constantes desde .env
define('DB_HOST', getenv('DB_HOST'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('DB_CHARSET', getenv('DB_CHARSET'));

define('APP_NAME', getenv('APP_NAME'));
define('APP_VERSION', getenv('APP_VERSION'));
define('BASE_URL', getenv('BASE_URL'));
define('BASE_PATH', getenv('BASE_PATH'));

define('DEBUG_MODE', getenv('DEBUG_MODE') === 'true');

// Zona horaria
date_default_timezone_set(getenv('TIMEZONE'));

// Errores
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Uploads
define('UPLOAD_MAX_SIZE', (int)getenv('UPLOAD_MAX_SIZE'));
define('UPLOAD_ALLOWED_TYPES', ['pdf', 'docx', 'doc', 'xlsx', 'png', 'jpg', 'jpeg']);
define('UPLOAD_PATH', getenv('UPLOAD_PATH'));

// Crear directorio solo si no existe y tenemos permisos
if (!file_exists(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0755, true);
}
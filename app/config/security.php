<?php
/**
 * Configuración de Seguridad
 * ISO 27001 Compliance Platform
 */

// Configuración de sesiones seguras
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Si estás en HTTPS, descomentar:
// ini_set('session.cookie_secure', 1);

// Timeout de sesión (30 minutos de inactividad)
define('SESSION_TIMEOUT', 1800);

// Regenerar session_id cada 5 minutos
define('SESSION_REGENERATE_INTERVAL', 300);

// Rate limiting - Login
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos

// CSRF Token
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hora

// Validación de archivos
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/png',
    'image/jpeg'
]);

// Extensiones vs MIME types
define('MIME_EXTENSION_MAP', [
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    'image/png' => 'png',
    'image/jpeg' => ['jpg', 'jpeg']
]);

// Headers de seguridad
define('SECURITY_HEADERS', [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'"
]);

// Aplicar headers automáticamente
foreach (SECURITY_HEADERS as $header => $value) {
    header("$header: $value");
}

// Inicializar gestión de sesión segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar timeout de sesión
if (isset($_SESSION['LAST_ACTIVITY'])) {
    if (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
    }
}
$_SESSION['LAST_ACTIVITY'] = time();

// Regenerar session_id periódicamente
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} elseif (time() - $_SESSION['CREATED'] > SESSION_REGENERATE_INTERVAL) {
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}
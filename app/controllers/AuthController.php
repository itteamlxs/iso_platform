<?php
namespace App\Controllers;

use App\Models\Usuario;
use App\Helpers\Security;
use App\Helpers\Logger;
use App\Middleware\RateLimitMiddleware;

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../middleware/RateLimitMiddleware.php';

/**
 * Auth Controller
 * Maneja autenticación y autorización
 * VERSIÓN 4.0 - Full rate limiting + enhanced security
 */
class AuthController {
    
    private $model;
    
    public function __construct() {
        $this->model = new Usuario();
    }
    
    /**
     * Procesar login
     */
    public function login() {
        // Verificar que sea POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // Validar CSRF
        $csrf_token = $_POST[CSRF_TOKEN_NAME] ?? '';
        
        if (!Security::validateCSRFToken($csrf_token)) {
            Logger::security('CSRF validation failed on login', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            $_SESSION['mensaje'] = 'Token de seguridad inválido. Por favor, intente nuevamente.';
            $_SESSION['mensaje_tipo'] = 'error';
            
            // Regenerar token para el próximo intento
            unset($_SESSION[CSRF_TOKEN_NAME]);
            unset($_SESSION[CSRF_TOKEN_NAME . '_time']);
            
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // RATE LIMITING BACKEND
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        if (!RateLimitMiddleware::check('login', $identifier)) {
            $blockInfo = RateLimitMiddleware::isBlocked('login', $identifier);
            
            Logger::security('Login rate limit exceeded', [
                'ip' => $identifier,
                'time_left' => $blockInfo['time_left'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            $_SESSION['mensaje'] = 'Demasiados intentos de login. Por favor, espere ' . $blockInfo['time_left'] . ' minuto(s).';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // Obtener y sanitizar entrada (email o username)
        $identifier = Security::sanitize($_POST['identifier'] ?? '', 'string');
        $password = $_POST['password'] ?? '';
        
        // Validar campos vacíos
        if (empty($identifier) || empty($password)) {
            RateLimitMiddleware::record('login', $identifier);
            
            Logger::warning('Login attempt with empty fields', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $_SESSION['mensaje'] = 'Por favor, complete todos los campos';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // Validación de formato de identificador
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $isUsername = preg_match('/^[a-zA-Z0-9_]{3,}$/', $identifier);
        
        if (!$isEmail && !$isUsername) {
            RateLimitMiddleware::record('login', $identifier);
            
            Logger::warning('Login attempt with invalid identifier format', [
                'identifier' => $identifier,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $_SESSION['mensaje'] = 'Formato de usuario/email inválido';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // Validación estricta de contraseña (backend)
        $passwordErrors = Security::validatePasswordStrength($password);
        
        if (!empty($passwordErrors)) {
            RateLimitMiddleware::record('login', $identifier);
            
            Logger::warning('Login attempt with weak password', [
                'identifier' => $identifier,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'errors' => $passwordErrors
            ]);
            
            $_SESSION['mensaje'] = 'Contraseña no cumple requisitos: ' . implode(', ', $passwordErrors);
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // Timing attack protection: simular tiempo de verificación constante
        $startTime = microtime(true);
        
        // Intentar autenticar (ahora acepta email o username)
        $result = $this->model->authenticate($identifier, $password);
        
        // Asegurar tiempo mínimo de respuesta (prevenir timing attacks)
        $elapsedTime = microtime(true) - $startTime;
        $minTime = 0.5; // 500ms mínimo
        if ($elapsedTime < $minTime) {
            usleep(($minTime - $elapsedTime) * 1000000);
        }
        
        if (!$result['success']) {
            RateLimitMiddleware::record('login', $identifier);
            
            Logger::auth('login', false, [
                'identifier' => $identifier,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'remaining_attempts' => RateLimitMiddleware::getRemaining('login', $identifier)
            ]);
            
            // Mensaje genérico para no revelar si el usuario existe
            $_SESSION['mensaje'] = 'Credenciales incorrectas';
            $_SESSION['mensaje_tipo'] = 'error';
            
            // Mostrar intentos restantes si quedan pocos
            $remaining = RateLimitMiddleware::getRemaining('login', $identifier);
            if ($remaining <= 2 && $remaining > 0) {
                $_SESSION['mensaje'] .= ". Le quedan $remaining intento(s)";
            }
            
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // Login exitoso - RESETEAR rate limit
        RateLimitMiddleware::reset('login', $identifier);
        
        // CRÍTICO: Regenerar session_id por seguridad (prevenir session fixation)
        session_regenerate_id(true);
        
        // Guardar datos en sesión
        $_SESSION['usuario_id'] = $result['usuario']['id'];
        $_SESSION['usuario_nombre'] = $result['usuario']['nombre'];
        $_SESSION['usuario_email'] = $result['usuario']['email'];
        $_SESSION['usuario_rol'] = $result['usuario']['rol'];
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_regeneration'] = time();
        
        // MULTI-EMPRESA: Obtener empresa_id del usuario
        $_SESSION['empresa_id'] = $result['usuario']['empresa_id'] ?? null;
        
        // Fingerprint de sesión para detectar hijacking
        $_SESSION['user_fingerprint'] = hash('sha256', 
            $_SERVER['HTTP_USER_AGENT'] ?? '' . 
            $_SERVER['REMOTE_ADDR'] ?? ''
        );
        
        Logger::auth('login', true, [
            'user_id' => $result['usuario']['id'],
            'email' => $result['usuario']['email'],
            'rol' => $result['usuario']['rol'],
            'empresa_id' => $_SESSION['empresa_id'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // IMPORTANTE: Regenerar token CSRF después de login exitoso
        unset($_SESSION[CSRF_TOKEN_NAME]);
        unset($_SESSION[CSRF_TOKEN_NAME . '_time']);
        
        // Manejar "recordar sesión" si está marcado
        if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
            // Extender tiempo de sesión a 30 días
            ini_set('session.cookie_lifetime', 2592000);
            ini_set('session.gc_maxlifetime', 2592000);
            
            Logger::info('Remember me enabled', [
                'user_id' => $result['usuario']['id']
            ]);
        }
        
        // Actualizar último acceso
        $this->model->actualizarUltimoAcceso($result['usuario']['id']);
        
        // Mensaje de bienvenida
        $_SESSION['mensaje'] = '¡Bienvenido, ' . $result['usuario']['nombre'] . '!';
        $_SESSION['mensaje_tipo'] = 'success';
        
        // Redirigir al dashboard
        header('Location: ' . BASE_URL . '/public/');
        exit;
    }
    
    /**
     * Procesar logout
     */
    public function logout() {
        // Guardar mensaje antes de destruir sesión
        $nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
        $user_id = $_SESSION['usuario_id'] ?? null;
        
        Logger::auth('logout', true, [
            'user_id' => $user_id,
            'nombre' => $nombre,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        // Destruir sesión completamente
        session_unset();
        session_destroy();
        
        // Iniciar nueva sesión limpia
        session_start();
        session_regenerate_id(true);
        
        // Mensaje de despedida
        $_SESSION['mensaje'] = 'Sesión cerrada correctamente. Hasta pronto, ' . $nombre;
        $_SESSION['mensaje_tipo'] = 'success';
        
        // Redirigir al login
        header('Location: ' . BASE_URL . '/public/login');
        exit;
    }
    
    /**
     * Verificar si hay usuario autenticado
     */
    public static function isAuthenticated() {
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            return false;
        }
        
        // Verificar fingerprint para detectar session hijacking
        if (isset($_SESSION['user_fingerprint'])) {
            $currentFingerprint = hash('sha256', 
                $_SERVER['HTTP_USER_AGENT'] ?? '' . 
                $_SERVER['REMOTE_ADDR'] ?? ''
            );
            
            if ($_SESSION['user_fingerprint'] !== $currentFingerprint) {
                Logger::security('Possible session hijacking detected', [
                    'user_id' => $_SESSION['usuario_id'] ?? 'unknown',
                    'expected_fingerprint' => $_SESSION['user_fingerprint'],
                    'current_fingerprint' => $currentFingerprint,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                // Destruir sesión sospechosa
                session_unset();
                session_destroy();
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obtener usuario actual
     */
    public static function getCurrentUser() {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['usuario_id'] ?? null,
            'nombre' => $_SESSION['usuario_nombre'] ?? null,
            'email' => $_SESSION['usuario_email'] ?? null,
            'rol' => $_SESSION['usuario_rol'] ?? null,
            'empresa_id' => $_SESSION['empresa_id'] ?? null
        ];
    }
    
    /**
     * Verificar rol del usuario
     */
    public static function hasRole($roles) {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        $userRole = $_SESSION['usuario_rol'] ?? null;
        
        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }
        
        return $userRole === $roles;
    }
    
    /**
     * Redirigir al login si no está autenticado
     */
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            Logger::warning('Unauthorized access attempt', [
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $_SESSION['mensaje'] = 'Debe iniciar sesión para acceder';
            $_SESSION['mensaje_tipo'] = 'warning';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
    }
    
    /**
     * Redirigir si no tiene el rol requerido
     */
    public static function requireRole($roles) {
        self::requireAuth();
        
        if (!self::hasRole($roles)) {
            Logger::accessDenied('role_check', [
                'required_roles' => is_array($roles) ? implode(',', $roles) : $roles,
                'user_role' => $_SESSION['usuario_rol'] ?? 'none',
                'user_id' => $_SESSION['usuario_id'] ?? 'unknown',
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
            
            $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/');
            exit;
        }
    }
    
    /**
     * Verificar que el usuario tenga empresa asignada
     */
    public static function requireEmpresa() {
        self::requireAuth();
        
        if (empty($_SESSION['empresa_id'])) {
            Logger::warning('User without empresa_id tried to access', [
                'user_id' => $_SESSION['usuario_id'] ?? 'unknown',
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
            
            $_SESSION['mensaje'] = 'No tiene una empresa asignada. Contacte al administrador.';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
    }
}
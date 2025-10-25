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
 * VERSIÓN 3.0 - Con rate limiting backend, logging y multi-empresa
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
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
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
                'time_left' => $blockInfo['time_left']
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
            Logger::warning('Login attempt with empty fields');
            
            $_SESSION['mensaje'] = 'Por favor, complete todos los campos';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // Validación estricta de contraseña
        if (strlen($password) < 8) {
            RateLimitMiddleware::record('login', $identifier);
            $_SESSION['mensaje'] = 'La contraseña debe tener al menos 8 caracteres';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            RateLimitMiddleware::record('login', $identifier);
            $_SESSION['mensaje'] = 'La contraseña debe contener al menos una letra mayúscula';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            RateLimitMiddleware::record('login', $identifier);
            $_SESSION['mensaje'] = 'La contraseña debe contener al menos una letra minúscula';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            RateLimitMiddleware::record('login', $identifier);
            $_SESSION['mensaje'] = 'La contraseña debe contener al menos un carácter especial (!@#$%^&*(),.?":{}|<>)';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // Intentar autenticar (ahora acepta email o username)
        $result = $this->model->authenticate($identifier, $password);
        
        if (!$result['success']) {
            RateLimitMiddleware::record('login', $identifier);
            
            Logger::auth('login', false, [
                'identifier' => $identifier,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $_SESSION['mensaje'] = $result['error'];
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // Login exitoso - RESETEAR rate limit
        RateLimitMiddleware::reset('login', $identifier);
        
        // Regenerar session_id por seguridad
        session_regenerate_id(true);
        
        // Guardar datos en sesión
        $_SESSION['usuario_id'] = $result['usuario']['id'];
        $_SESSION['usuario_nombre'] = $result['usuario']['nombre'];
        $_SESSION['usuario_email'] = $result['usuario']['email'];
        $_SESSION['usuario_rol'] = $result['usuario']['rol'];
        $_SESSION['authenticated'] = true;
        
        // MULTI-EMPRESA: Obtener empresa_id del usuario
        $_SESSION['empresa_id'] = $result['usuario']['empresa_id'] ?? null;
        
        Logger::auth('login', true, [
            'user_id' => $result['usuario']['id'],
            'email' => $result['usuario']['email'],
            'rol' => $result['usuario']['rol'],
            'empresa_id' => $_SESSION['empresa_id']
        ]);
        
        // IMPORTANTE: Regenerar token CSRF después de login exitoso
        unset($_SESSION[CSRF_TOKEN_NAME]);
        unset($_SESSION[CSRF_TOKEN_NAME . '_time']);
        
        // Manejar "recordar sesión" si está marcado
        if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
            // Extender tiempo de sesión a 30 días
            ini_set('session.cookie_lifetime', 2592000);
            ini_set('session.gc_maxlifetime', 2592000);
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
            'nombre' => $nombre
        ]);
        
        // Destruir sesión
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
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
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
                'user_role' => $_SESSION['usuario_rol'] ?? 'none'
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
                'user_id' => $_SESSION['usuario_id'] ?? 'unknown'
            ]);
            
            $_SESSION['mensaje'] = 'No tiene una empresa asignada. Contacte al administrador.';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
    }
}
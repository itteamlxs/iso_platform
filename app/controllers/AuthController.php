<?php
namespace App\Controllers;

use App\Models\Usuario;
use App\Helpers\Security;

require_once __DIR__ . '/../models/Usuario.php';

/**
 * Auth Controller
 * Maneja autenticación y autorización
 * VERSIÓN 2.1 - Validación de contraseña reforzada, rate limiting removido
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
            $_SESSION['mensaje'] = 'Token de seguridad inválido. Por favor, intente nuevamente.';
            $_SESSION['mensaje_tipo'] = 'error';
            
            // Regenerar token para el próximo intento
            unset($_SESSION[CSRF_TOKEN_NAME]);
            unset($_SESSION[CSRF_TOKEN_NAME . '_time']);
            
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // Obtener y sanitizar entrada (email o username)
        $identifier = Security::sanitize($_POST['identifier'] ?? '', 'string');
        $password = $_POST['password'] ?? '';
        
        // Validar campos vacíos
        if (empty($identifier) || empty($password)) {
            $_SESSION['mensaje'] = 'Por favor, complete todos los campos';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // Validación estricta de contraseña
        if (strlen($password) < 8) {
            $_SESSION['mensaje'] = 'La contraseña debe tener al menos 8 caracteres';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $_SESSION['mensaje'] = 'La contraseña debe contener al menos una letra mayúscula';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $_SESSION['mensaje'] = 'La contraseña debe contener al menos una letra minúscula';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $_SESSION['mensaje'] = 'La contraseña debe contener al menos un carácter especial (!@#$%^&*(),.?":{}|<>)';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // Intentar autenticar (ahora acepta email o username)
        $result = $this->model->authenticate($identifier, $password);
        
        if (!$result['success']) {
            $_SESSION['mensaje'] = $result['error'];
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // Login exitoso
        // Regenerar session_id por seguridad
        session_regenerate_id(true);
        
        // Guardar datos en sesión
        $_SESSION['usuario_id'] = $result['usuario']['id'];
        $_SESSION['usuario_nombre'] = $result['usuario']['nombre'];
        $_SESSION['usuario_email'] = $result['usuario']['email'];
        $_SESSION['usuario_rol'] = $result['usuario']['rol'];
        $_SESSION['authenticated'] = true;
        $_SESSION['empresa_id'] = 1; // TODO: Cambiar cuando tengamos multi-empresa
        
        // IMPORTANTE: Regenerar token CSRF después de login exitoso
        unset($_SESSION[CSRF_TOKEN_NAME]);
        unset($_SESSION[CSRF_TOKEN_NAME . '_time']);
        
        // Manejar "recordar sesión" si está marcado
        if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
            // Extender tiempo de sesión a 30 días
            ini_set('session.cookie_lifetime', 2592000); // 30 días en segundos
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
            'rol' => $_SESSION['usuario_rol'] ?? null
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
            $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/');
            exit;
        }
    }
}
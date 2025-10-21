<?php
namespace App\Middleware;

use App\Controllers\AuthController;

/**
 * Auth Middleware
 * Verifica que el usuario esté autenticado
 */
class AuthMiddleware {
    
    /**
     * Rutas públicas (no requieren autenticación)
     */
    private static $publicRoutes = [
        '/login',
        '/logout'
    ];
    
    /**
     * Verificar autenticación
     */
    public static function check($request) {
        // Si es ruta pública, permitir
        if (self::isPublicRoute($request)) {
            return true;
        }
        
        // Verificar si está autenticado
        if (!AuthController::isAuthenticated()) {
            $_SESSION['mensaje'] = 'Debe iniciar sesión para continuar';
            $_SESSION['mensaje_tipo'] = 'warning';
            $_SESSION['redirect_after_login'] = $request;
            
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        return true;
    }
    
    /**
     * Verificar si es ruta pública
     */
    private static function isPublicRoute($request) {
        foreach (self::$publicRoutes as $route) {
            if (strpos($request, $route) === 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Redirigir usuario autenticado fuera del login
     */
    public static function redirectIfAuthenticated() {
        if (AuthController::isAuthenticated()) {
            // Si hay redirect pendiente, ir ahí
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . BASE_URL . '/public' . $redirect);
                exit;
            }
            
            // Sino, al dashboard
            header('Location: ' . BASE_URL . '/public/');
            exit;
        }
    }
}
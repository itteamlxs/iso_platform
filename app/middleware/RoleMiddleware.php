<?php
namespace App\Middleware;

use App\Controllers\AuthController;

/**
 * Role Middleware
 * Verifica permisos por rol
 */
class RoleMiddleware {
    
    /**
     * Permisos por ruta
     * Format: 'ruta' => ['rol1', 'rol2']
     */
    private static $permissions = [
        // Solo admin
        '/usuarios' => ['admin'],
        '/configuracion' => ['admin'],
        
        // Admin y Auditor
        '/controles' => ['admin', 'auditor'],
        '/soa' => ['admin', 'auditor'],
        '/gap' => ['admin', 'auditor'],
        '/evidencias' => ['admin', 'auditor'],
        '/requerimientos' => ['admin', 'auditor'],
        '/reportes' => ['admin', 'auditor'],
        '/auditorias' => ['admin', 'auditor'],
        
        // Todos (admin, auditor, consultor)
        '/' => ['admin', 'auditor', 'consultor'],
        '/dashboard' => ['admin', 'auditor', 'consultor']
    ];
    
    /**
     * Verificar permisos
     */
    public static function check($request) {
        // Buscar permiso requerido
        $requiredRoles = self::getRequiredRoles($request);
        
        // Si no hay restricción específica, permitir (rutas públicas)
        if ($requiredRoles === null) {
            return true;
        }
        
        // Verificar que tenga el rol
        if (!AuthController::hasRole($requiredRoles)) {
            $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección';
            $_SESSION['mensaje_tipo'] = 'error';
            
            header('Location: ' . BASE_URL . '/public/');
            exit;
        }
        
        return true;
    }
    
    /**
     * Obtener roles requeridos para una ruta
     */
    private static function getRequiredRoles($request) {
        // Buscar coincidencia exacta
        if (isset(self::$permissions[$request])) {
            return self::$permissions[$request];
        }
        
        // Buscar coincidencia parcial (ej: /controles/123 → /controles)
        foreach (self::$permissions as $route => $roles) {
            if (strpos($request, $route) === 0) {
                return $roles;
            }
        }
        
        // Si no hay restricción, retornar null (acceso público)
        return null;
    }
    
    /**
     * Verificar si el usuario tiene un rol específico
     */
    public static function hasRole($role) {
        return AuthController::hasRole($role);
    }
    
    /**
     * Verificar si es admin
     */
    public static function isAdmin() {
        return AuthController::hasRole('admin');
    }
    
    /**
     * Verificar si es auditor
     */
    public static function isAuditor() {
        return AuthController::hasRole('auditor');
    }
    
    /**
     * Verificar si es consultor
     */
    public static function isConsultor() {
        return AuthController::hasRole('consultor');
    }
}
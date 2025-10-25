<?php
namespace App\Middleware;

use App\Helpers\Logger;

/**
 * Rate Limit Middleware
 * ISO 27001 Compliance Platform
 * VERSIÓN 1.0
 * 
 * Protección contra fuerza bruta y abuso de endpoints
 */
class RateLimitMiddleware {
    
    private static $limits = [
        'login' => ['max' => 5, 'window' => 900],        // 5 intentos en 15 min
        'form' => ['max' => 20, 'window' => 300],        // 20 envíos en 5 min
        'api' => ['max' => 100, 'window' => 60],         // 100 requests en 1 min
        'upload' => ['max' => 10, 'window' => 3600]      // 10 uploads en 1 hora
    ];
    
    private static $storagePrefix = 'rate_limit_';
    
    /**
     * Verificar rate limit
     * @param string $type - Tipo de límite (login, form, api, upload)
     * @param string $identifier - Identificador único (IP, user_id, etc)
     * @return bool
     */
    public static function check($type = 'form', $identifier = null) {
        if (!isset(self::$limits[$type])) {
            Logger::warning("Rate limit type not found: $type");
            return true;
        }
        
        $identifier = $identifier ?? self::getIdentifier();
        $key = self::$storagePrefix . $type . '_' . $identifier;
        
        $data = self::getData($key);
        $now = time();
        $limit = self::$limits[$type];
        
        // Limpiar intentos expirados
        $data = array_filter($data, function($timestamp) use ($now, $limit) {
            return ($now - $timestamp) < $limit['window'];
        });
        
        // Verificar si excede límite
        if (count($data) >= $limit['max']) {
            $oldestAttempt = min($data);
            $timeLeft = ceil(($oldestAttempt + $limit['window'] - $now) / 60);
            
            Logger::security("Rate limit exceeded: $type", [
                'identifier' => $identifier,
                'attempts' => count($data),
                'limit' => $limit['max'],
                'time_left_minutes' => $timeLeft
            ]);
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Registrar intento
     * @param string $type
     * @param string $identifier
     */
    public static function record($type = 'form', $identifier = null) {
        if (!isset(self::$limits[$type])) {
            return;
        }
        
        $identifier = $identifier ?? self::getIdentifier();
        $key = self::$storagePrefix . $type . '_' . $identifier;
        
        $data = self::getData($key);
        $now = time();
        $limit = self::$limits[$type];
        
        // Limpiar expirados
        $data = array_filter($data, function($timestamp) use ($now, $limit) {
            return ($now - $timestamp) < $limit['window'];
        });
        
        // Agregar nuevo intento
        $data[] = $now;
        
        self::saveData($key, $data);
    }
    
    /**
     * Resetear límite
     * @param string $type
     * @param string $identifier
     */
    public static function reset($type = 'form', $identifier = null) {
        $identifier = $identifier ?? self::getIdentifier();
        $key = self::$storagePrefix . $type . '_' . $identifier;
        
        self::removeData($key);
        
        Logger::info("Rate limit reset: $type", ['identifier' => $identifier]);
    }
    
    /**
     * Obtener intentos restantes
     * @param string $type
     * @param string $identifier
     * @return int
     */
    public static function getRemaining($type = 'form', $identifier = null) {
        if (!isset(self::$limits[$type])) {
            return 0;
        }
        
        $identifier = $identifier ?? self::getIdentifier();
        $key = self::$storagePrefix . $type . '_' . $identifier;
        
        $data = self::getData($key);
        $now = time();
        $limit = self::$limits[$type];
        
        // Contar intentos válidos
        $validAttempts = array_filter($data, function($timestamp) use ($now, $limit) {
            return ($now - $timestamp) < $limit['window'];
        });
        
        return max(0, $limit['max'] - count($validAttempts));
    }
    
    /**
     * Verificar si está bloqueado
     * @param string $type
     * @param string $identifier
     * @return array ['blocked' => bool, 'time_left' => int]
     */
    public static function isBlocked($type = 'form', $identifier = null) {
        if (!isset(self::$limits[$type])) {
            return ['blocked' => false, 'time_left' => 0];
        }
        
        $identifier = $identifier ?? self::getIdentifier();
        $key = self::$storagePrefix . $type . '_' . $identifier;
        
        $data = self::getData($key);
        $now = time();
        $limit = self::$limits[$type];
        
        $validAttempts = array_filter($data, function($timestamp) use ($now, $limit) {
            return ($now - $timestamp) < $limit['window'];
        });
        
        if (count($validAttempts) >= $limit['max']) {
            $oldestAttempt = min($validAttempts);
            $timeLeft = ceil(($oldestAttempt + $limit['window'] - $now) / 60);
            
            return ['blocked' => true, 'time_left' => max(1, $timeLeft)];
        }
        
        return ['blocked' => false, 'time_left' => 0];
    }
    
    /**
     * Obtener identificador único
     */
    private static function getIdentifier() {
        // Prioridad: user_id > email > IP
        if (isset($_SESSION['usuario_id'])) {
            return 'user_' . $_SESSION['usuario_id'];
        }
        
        if (isset($_SESSION['usuario_email'])) {
            return 'email_' . md5($_SESSION['usuario_email']);
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return 'ip_' . md5($ip);
    }
    
    /**
     * Obtener datos de sesión
     */
    private static function getData($key) {
        if (!isset($_SESSION[$key])) {
            return [];
        }
        
        $data = $_SESSION[$key];
        return is_array($data) ? $data : [];
    }
    
    /**
     * Guardar datos en sesión
     */
    private static function saveData($key, $data) {
        $_SESSION[$key] = $data;
    }
    
    /**
     * Eliminar datos de sesión
     */
    private static function removeData($key) {
        unset($_SESSION[$key]);
    }
    
    /**
     * Configurar límites personalizados
     * @param string $type
     * @param int $max
     * @param int $window
     */
    public static function setLimit($type, $max, $window) {
        self::$limits[$type] = ['max' => $max, 'window' => $window];
    }
    
    /**
     * Obtener configuración de límites
     * @param string $type
     * @return array|null
     */
    public static function getLimit($type) {
        return self::$limits[$type] ?? null;
    }
    
    /**
     * Limpiar todos los límites de un tipo
     * @param string $type
     */
    public static function cleanAll($type) {
        $prefix = self::$storagePrefix . $type . '_';
        
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                unset($_SESSION[$key]);
            }
        }
        
        Logger::info("Rate limits cleaned: $type");
    }
}
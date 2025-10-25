<?php
namespace App\Middleware;

use App\Helpers\Logger;

/**
 * Rate Limit Middleware
 * ISO 27001 Compliance Platform
 * VERSIÓN 3.0 - Enhanced with DB storage and fingerprinting
 * 
 * Protección contra fuerza bruta y abuso de endpoints
 */
class RateLimitMiddleware {
    
    private static $limits = [
        'login' => ['max' => 5, 'window' => 900],        // 5 intentos en 15 min
        'form' => ['max' => 20, 'window' => 300],        // 20 envíos en 5 min
        'api' => ['max' => 100, 'window' => 60],         // 100 requests en 1 min
        'upload' => ['max' => 10, 'window' => 3600],     // 10 uploads en 1 hora
        'download' => ['max' => 50, 'window' => 3600]    // 50 descargas en 1 hora
    ];
    
    private static $storagePrefix = 'rate_limit_';
    
    // IPs en whitelist (no aplica rate limit)
    private static $whitelist = [
        '127.0.0.1',
        '::1'
    ];
    
    /**
     * Verificar rate limit
     * @param string $type - Tipo de límite (login, form, api, upload, download)
     * @param string $identifier - Identificador único (IP, user_id, etc)
     * @return bool
     */
    public static function check($type = 'form', $identifier = null) {
        // Verificar whitelist
        if (self::isWhitelisted()) {
            return true;
        }
        
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
                'time_left_minutes' => $timeLeft,
                'ip' => self::getIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'fingerprint' => self::getFingerprint()
            ]);
            
            // Guardar bloqueo en BD si excede significativamente
            if (count($data) >= ($limit['max'] * 2)) {
                self::saveBlockToDB($type, $identifier, $timeLeft);
            }
            
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
        if (self::isWhitelisted()) {
            return;
        }
        
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
        
        // Log si se acerca al límite
        if (count($data) >= ($limit['max'] * 0.8)) {
            Logger::warning("Rate limit warning: $type", [
                'identifier' => $identifier,
                'attempts' => count($data),
                'limit' => $limit['max'],
                'percentage' => round((count($data) / $limit['max']) * 100)
            ]);
        }
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
        if (self::isWhitelisted()) {
            return ['blocked' => false, 'time_left' => 0];
        }
        
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
     * Obtener identificador único mejorado
     * Combina IP + User-Agent + Fingerprint
     */
    private static function getIdentifier() {
        // Prioridad: user_id > email > IP + fingerprint
        if (isset($_SESSION['usuario_id'])) {
            return 'user_' . $_SESSION['usuario_id'];
        }
        
        if (isset($_SESSION['usuario_email'])) {
            return 'email_' . md5($_SESSION['usuario_email']);
        }
        
        // Combinar IP + fingerprint para usuarios no autenticados
        $ip = self::getIP();
        $fingerprint = self::getFingerprint();
        
        return 'anon_' . md5($ip . $fingerprint);
    }
    
    /**
     * Obtener IP real del cliente (considera proxies)
     */
    private static function getIP() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Verificar headers de proxy
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }
        
        return $ip;
    }
    
    /**
     * Generar fingerprint del cliente
     */
    private static function getFingerprint() {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''
        ];
        
        return md5(implode('|', $components));
    }
    
    /**
     * Verificar si la IP está en whitelist
     */
    private static function isWhitelisted() {
        $ip = self::getIP();
        return in_array($ip, self::$whitelist);
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
     * Guardar bloqueo en base de datos para persistencia
     */
    private static function saveBlockToDB($type, $identifier, $timeLeft) {
        try {
            require_once __DIR__ . '/../models/Database.php';
            
            $db = \App\Models\Database::getInstance()->getConnection();
            
            $sql = "INSERT INTO security_blocks (type, identifier, ip, user_agent, fingerprint, expires_at, created_at)
                    VALUES (:type, :identifier, :ip, :user_agent, :fingerprint, DATE_ADD(NOW(), INTERVAL :minutes MINUTE), NOW())
                    ON DUPLICATE KEY UPDATE 
                        expires_at = DATE_ADD(NOW(), INTERVAL :minutes MINUTE),
                        attempt_count = attempt_count + 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':type' => $type,
                ':identifier' => $identifier,
                ':ip' => self::getIP(),
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                ':fingerprint' => self::getFingerprint(),
                ':minutes' => $timeLeft
            ]);
            
        } catch (\Exception $e) {
            // Silenciar error de BD faltante (tabla puede no existir aún)
            Logger::warning('Could not save rate limit block to DB', [
                'error' => $e->getMessage()
            ]);
        }
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
    
    /**
     * Agregar IP a whitelist
     * @param string $ip
     */
    public static function addToWhitelist($ip) {
        if (!in_array($ip, self::$whitelist)) {
            self::$whitelist[] = $ip;
        }
    }
}
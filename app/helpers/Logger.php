<?php
namespace App\Helpers;

/**
 * Logger - Sistema de logging centralizado
 * ISO 27001 Compliance Platform
 * VERSIÓN 1.0
 * 
 * Registra eventos de seguridad y operaciones críticas
 */
class Logger {
    
    private static $logPath = null;
    private static $levels = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL', 'SECURITY'];
    
    /**
     * Inicializar path de logs
     */
    private static function init() {
        if (self::$logPath === null) {
            $baseDir = __DIR__ . '/../../logs';
            
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0755, true);
            }
            
            self::$logPath = $baseDir;
        }
    }
    
    /**
     * Log genérico
     * @param string $level - Nivel del log
     * @param string $message - Mensaje
     * @param array $context - Datos adicionales
     */
    private static function log($level, $message, $context = []) {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user = $_SESSION['usuario_email'] ?? 'guest';
        $url = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'ip' => $ip,
            'user' => $user,
            'url' => $url,
            'message' => $message,
            'context' => $context
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        
        // Log por nivel
        $filename = self::$logPath . '/' . strtolower($level) . '_' . date('Y-m-d') . '.log';
        file_put_contents($filename, $logLine, FILE_APPEND | LOCK_EX);
        
        // Log general
        $generalFile = self::$logPath . '/general_' . date('Y-m-d') . '.log';
        file_put_contents($generalFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log de seguridad
     */
    public static function security($message, $context = []) {
        self::log('SECURITY', $message, $context);
    }
    
    /**
     * Log de error
     */
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }
    
    /**
     * Log de warning
     */
    public static function warning($message, $context = []) {
        self::log('WARNING', $message, $context);
    }
    
    /**
     * Log de info
     */
    public static function info($message, $context = []) {
        self::log('INFO', $message, $context);
    }
    
    /**
     * Log crítico
     */
    public static function critical($message, $context = []) {
        self::log('CRITICAL', $message, $context);
    }
    
    /**
     * Log de autenticación
     */
    public static function auth($action, $success, $context = []) {
        $message = $success ? "Auth success: $action" : "Auth failed: $action";
        self::security($message, array_merge($context, ['action' => $action, 'success' => $success]));
    }
    
    /**
     * Log de cambios en datos
     */
    public static function dataChange($entity, $action, $entityId, $context = []) {
        $message = "Data change: $entity $action (ID: $entityId)";
        self::info($message, array_merge($context, [
            'entity' => $entity,
            'action' => $action,
            'entity_id' => $entityId
        ]));
    }
    
    /**
     * Log de acceso denegado
     */
    public static function accessDenied($resource, $context = []) {
        $message = "Access denied: $resource";
        self::security($message, array_merge($context, ['resource' => $resource]));
    }
    
    /**
     * Log de upload de archivo
     */
    public static function fileUpload($filename, $success, $context = []) {
        $message = $success ? "File uploaded: $filename" : "File upload failed: $filename";
        $level = $success ? 'INFO' : 'WARNING';
        self::log($level, $message, array_merge($context, [
            'filename' => $filename,
            'success' => $success
        ]));
    }
    
    /**
     * Limpiar logs antiguos (>30 días)
     */
    public static function cleanOldLogs($days = 30) {
        self::init();
        
        $files = glob(self::$logPath . '/*.log');
        $cutoff = time() - ($days * 86400);
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Obtener últimos logs
     */
    public static function getRecent($level = 'general', $lines = 100) {
        self::init();
        
        $filename = self::$logPath . '/' . strtolower($level) . '_' . date('Y-m-d') . '.log';
        
        if (!file_exists($filename)) {
            return [];
        }
        
        $file = new \SplFileObject($filename, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $startLine = max(0, $lastLine - $lines);
        
        $logs = [];
        $file->seek($startLine);
        
        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $logs[] = json_decode($line, true);
            }
            $file->next();
        }
        
        return array_reverse($logs);
    }
}
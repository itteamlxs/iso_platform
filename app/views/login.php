<?php
namespace App\Helpers;

/**
 * Security Helper
 * Funciones de seguridad centralizadas
 * ISO 27001 Compliance Platform
 */
class Security {
    
    /**
     * Generar token CSRF
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
            $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Validar token CSRF
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION[CSRF_TOKEN_NAME]) || !isset($_SESSION[CSRF_TOKEN_NAME . '_time'])) {
            return false;
        }
        
        // Verificar expiración
        if (time() - $_SESSION[CSRF_TOKEN_NAME . '_time'] > CSRF_TOKEN_EXPIRE) {
            unset($_SESSION[CSRF_TOKEN_NAME]);
            unset($_SESSION[CSRF_TOKEN_NAME . '_time']);
            return false;
        }
        
        // Comparación segura
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Sanitizar entrada (prevenir XSS)
     */
    public static function sanitize($input, $type = 'string') {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        switch ($type) {
            case 'string':
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
            
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            
            case 'html':
                // Permitir HTML básico (para textarea con formato)
                return strip_tags($input, '<p><br><strong><em><ul><ol><li>');
            
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validar entrada
     */
    public static function validate($input, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $input[$field] ?? null;
            $ruleList = explode('|', $rule);
            
            foreach ($ruleList as $r) {
                // Required
                if ($r === 'required' && empty($value)) {
                    $errors[$field] = "El campo $field es obligatorio";
                    break;
                }
                
                // Email
                if ($r === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "El campo $field debe ser un email válido";
                    break;
                }
                
                // Min length
                if (strpos($r, 'min:') === 0) {
                    $min = (int)substr($r, 4);
                    if (!empty($value) && strlen($value) < $min) {
                        $errors[$field] = "El campo $field debe tener al menos $min caracteres";
                        break;
                    }
                }
                
                // Max length
                if (strpos($r, 'max:') === 0) {
                    $max = (int)substr($r, 4);
                    if (!empty($value) && strlen($value) > $max) {
                        $errors[$field] = "El campo $field debe tener máximo $max caracteres";
                        break;
                    }
                }
                
                // Numeric
                if ($r === 'numeric' && !empty($value) && !is_numeric($value)) {
                    $errors[$field] = "El campo $field debe ser numérico";
                    break;
                }
                
                // Date
                if ($r === 'date' && !empty($value)) {
                    $d = \DateTime::createFromFormat('Y-m-d', $value);
                    if (!$d || $d->format('Y-m-d') !== $value) {
                        $errors[$field] = "El campo $field debe ser una fecha válida (YYYY-MM-DD)";
                        break;
                    }
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validar archivo subido
     */
    public static function validateFile($file) {
        $errors = [];
        
        // Verificar errores de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error al subir el archivo';
            return $errors;
        }
        
        // Verificar tamaño
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            $errors[] = 'El archivo excede el tamaño máximo permitido (' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB)';
            return $errors;
        }
        
        // Obtener MIME type real
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // Verificar MIME type permitido
        if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
            $errors[] = 'Tipo de archivo no permitido';
            return $errors;
        }
        
        // Verificar que la extensión coincida con el MIME
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $expectedExtension = MIME_EXTENSION_MAP[$mimeType] ?? null;
        
        if (is_array($expectedExtension)) {
            if (!in_array($extension, $expectedExtension)) {
                $errors[] = 'La extensión del archivo no coincide con su contenido';
                return $errors;
            }
        } elseif ($extension !== $expectedExtension) {
            $errors[] = 'La extensión del archivo no coincide con su contenido';
            return $errors;
        }
        
        return $errors;
    }
    
    /**
     * Generar nombre de archivo seguro
     */
    public static function generateSecureFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $hash = hash('sha256', random_bytes(32) . time() . $originalName);
        return substr($hash, 0, 40) . '.' . $extension;
    }
    
    /**
     * Verificar contenido malicioso en archivo
     */
    public static function scanFileContent($filepath) {
        $content = file_get_contents($filepath, false, null, 0, 1024 * 100); // Primeros 100KB
        
        // Patrones sospechosos
        $patterns = [
            '/<\?php/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/base64_decode/i',
            '/exec\s*\(/i',
            '/shell_exec/i',
            '/system\s*\(/i',
            '/passthru/i',
            '/`.*`/i' // Backticks
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Hash de contraseña seguro
     */
    public static function hashPassword($password) {
        // Usar ARGON2ID si está disponible, sino BCRYPT
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID);
        }
        
        // Fallback a BCRYPT (más compatible)
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verificar contraseña
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Rate limiting básico (login)
     */
    public static function checkRateLimit($identifier, $maxAttempts = MAX_LOGIN_ATTEMPTS, $lockoutTime = LOGIN_LOCKOUT_TIME) {
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'locked_until' => 0
            ];
        }
        
        // Si está bloqueado
        if ($_SESSION[$key]['locked_until'] > time()) {
            $remainingTime = $_SESSION[$key]['locked_until'] - time();
            return [
                'allowed' => false,
                'message' => 'Demasiados intentos. Bloqueado por ' . ceil($remainingTime / 60) . ' minutos'
            ];
        }
        
        // Si el bloqueo expiró, resetear
        if ($_SESSION[$key]['locked_until'] > 0 && $_SESSION[$key]['locked_until'] <= time()) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'locked_until' => 0
            ];
        }
        
        // Incrementar intentos
        $_SESSION[$key]['attempts']++;
        
        // Si excede el límite
        if ($_SESSION[$key]['attempts'] > $maxAttempts) {
            $_SESSION[$key]['locked_until'] = time() + $lockoutTime;
            return [
                'allowed' => false,
                'message' => 'Demasiados intentos. Bloqueado por ' . ($lockoutTime / 60) . ' minutos'
            ];
        }
        
        return [
            'allowed' => true,
            'attempts' => $_SESSION[$key]['attempts']
        ];
    }
    
    /**
     * Resetear rate limit (tras login exitoso)
     */
    public static function resetRateLimit($identifier) {
        $key = 'rate_limit_' . md5($identifier);
        unset($_SESSION[$key]);
    }
    
    /**
     * Generar campo CSRF oculto para formularios
     */
    public static function csrfField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
    }
}
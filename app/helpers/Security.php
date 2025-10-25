<?php
namespace App\Helpers;

/**
 * Security Helper
 * Funciones de seguridad centralizadas
 * ISO 27001 Compliance Platform
 * VERSIÓN 3.0 - Enhanced Security
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
            return self::sanitizeArray($input, $type);
        }
        
        if ($input === null) {
            return null;
        }
        
        switch ($type) {
            case 'string':
                return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            
            case 'int':
                return (int)filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            
            case 'float':
                return (float)filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            
            case 'html':
                // Permitir HTML básico (para textarea con formato)
                return strip_tags($input, '<p><br><strong><em><ul><ol><li>');
            
            case 'raw':
                // Sin sanitización (usar solo cuando sea absolutamente necesario)
                return $input;
            
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    
    /**
     * Sanitizar array recursivamente
     * 
     * @param array $input
     * @param string $type
     * @return array
     */
    public static function sanitizeArray($input, $type = 'string') {
        $sanitized = [];
        
        foreach ($input as $key => $value) {
            $sanitizedKey = self::sanitize($key, 'string');
            
            if (is_array($value)) {
                $sanitized[$sanitizedKey] = self::sanitizeArray($value, $type);
            } else {
                $sanitized[$sanitizedKey] = self::sanitize($value, $type);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitizar output para vistas (prevenir XSS)
     * 
     * @param mixed $data
     * @return mixed
     */
    public static function sanitizeOutput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeOutput'], $data);
        }
        
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        return $data;
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
                
                // Password strength (8+ chars, 1 uppercase, 1 lowercase, 1 special)
                if ($r === 'strong_password' && !empty($value)) {
                    $passwordErrors = self::validatePasswordStrength($value);
                    if (!empty($passwordErrors)) {
                        $errors[$field] = implode(', ', $passwordErrors);
                        break;
                    }
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validar fortaleza de contraseña
     * Retorna array de errores (vacío si es válida)
     */
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Mínimo 8 caracteres';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Al menos 1 letra mayúscula';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Al menos 1 letra minúscula';
        }
        
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Al menos 1 carácter especial (!@#$%^&*(),.?":{}|<>)';
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
     * MEJORADO: Más patrones y validación estricta
     */
    public static function scanFileContent($filepath) {
        // Verificar que el archivo existe
        if (!file_exists($filepath)) {
            return false;
        }
        
        // Leer primeros 100KB
        $content = file_get_contents($filepath, false, null, 0, 1024 * 100);
        
        if ($content === false) {
            return false;
        }
        
        // Patrones sospechosos expandidos
        $patterns = [
            // PHP malicioso
            '/<\?php/i',
            '/<\?=/i',
            '/<script/i',
            
            // Funciones peligrosas
            '/eval\s*\(/i',
            '/base64_decode/i',
            '/exec\s*\(/i',
            '/shell_exec/i',
            '/system\s*\(/i',
            '/passthru/i',
            '/proc_open/i',
            '/popen/i',
            '/curl_exec/i',
            '/curl_multi_exec/i',
            '/parse_ini_file/i',
            '/show_source/i',
            
            // Backticks
            '/`[^`]*`/i',
            
            // SQL injection attempts
            '/union\s+select/i',
            '/drop\s+table/i',
            '/insert\s+into/i',
            '/delete\s+from/i',
            
            // File inclusion
            '/include\s*\(/i',
            '/require\s*\(/i',
            '/include_once\s*\(/i',
            '/require_once\s*\(/i',
            
            // Comandos shell
            '/wget\s+/i',
            '/curl\s+/i',
            '/nc\s+-/i',
            '/bash\s+-/i',
            '/sh\s+-/i',
            
            // Archivos sospechosos embebidos
            '/\x50\x4B\x03\x04/', // ZIP signature
            '/\x1F\x8B\x08/', // GZIP signature
            '/\x7F\x45\x4C\x46/', // ELF executable
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }
        
        // Verificar NULL bytes
        if (strpos($content, "\0") !== false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Hash de contraseña seguro
     */
    public static function hashPassword($password) {
        // Validar fortaleza antes de hashear
        $errors = self::validatePasswordStrength($password);
        if (!empty($errors)) {
            throw new \Exception('Contraseña no cumple los requisitos: ' . implode(', ', $errors));
        }
        
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
     * Generar campo CSRF oculto para formularios
     */
    public static function csrfField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token, ENT_QUOTES) . '">';
    }
    
    /**
     * Validar ownership de recursos (IDOR protection)
     * 
     * @param int $resource_empresa_id Empresa ID del recurso
     * @param int|null $user_empresa_id Empresa ID del usuario (null = obtener de sesión)
     * @return bool
     */
    public static function validateOwnership($resource_empresa_id, $user_empresa_id = null) {
        if ($user_empresa_id === null) {
            $user_empresa_id = $_SESSION['empresa_id'] ?? null;
        }
        
        if ($user_empresa_id === null) {
            return false;
        }
        
        return (int)$resource_empresa_id === (int)$user_empresa_id;
    }
    
    /**
     * Prevenir path traversal en rutas de archivos
     * 
     * @param string $filename Nombre del archivo
     * @param string $baseDir Directorio base permitido
     * @return string|false Ruta segura o false si es inválido
     */
    public static function sanitizeFilePath($filename, $baseDir) {
        // Remover caracteres peligrosos
        $filename = basename($filename);
        
        // Construir ruta completa
        $fullPath = realpath($baseDir . '/' . $filename);
        $baseDir = realpath($baseDir);
        
        // Verificar que la ruta esté dentro del directorio base
        if ($fullPath === false || strpos($fullPath, $baseDir) !== 0) {
            return false;
        }
        
        return $fullPath;
    }
}
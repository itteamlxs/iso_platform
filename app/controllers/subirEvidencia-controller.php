<?php
/**
 * Procesar upload de evidencia
 * VERSIÓN 4.0 - Enhanced security with full validation
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Evidencia.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../middleware/RateLimitMiddleware.php';
require_once __DIR__ . '/../controllers/EvidenciasController.php';
require_once __DIR__ . '/../controllers/ControlesController.php';

use App\Helpers\Security;
use App\Helpers\Logger;
use App\Middleware\RateLimitMiddleware;

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Logger::warning('subirEvidencia accessed with non-POST method', [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    header('Location: ' . BASE_URL . '/public/evidencias');
    exit;
}

// Validar CSRF - OBLIGATORIO
if (!validateCSRF()) {
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Rate limiting específico para uploads
$identifier = $_SESSION['usuario_id'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

if (!RateLimitMiddleware::check('upload', $identifier)) {
    Logger::security('Upload rate limit exceeded', [
        'user_id' => $_SESSION['usuario_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    $blockInfo = RateLimitMiddleware::isBlocked('upload', $identifier);
    $_SESSION['mensaje'] = 'Demasiados uploads. Espere ' . $blockInfo['time_left'] . ' minuto(s).';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Registrar intento de upload
RateLimitMiddleware::record('upload', $identifier);

$controller = new \App\Controllers\EvidenciasController();

// Validar y sanitizar datos obligatorios
$control_id = Security::sanitize($_POST['control_id'] ?? null, 'int');
$descripcion = Security::sanitize($_POST['descripcion'] ?? '', 'string');
$tipo_evidencia_id = Security::sanitize($_POST['tipo_evidencia_id'] ?? null, 'int');

if (!$control_id || empty($descripcion)) {
    Logger::warning('Subir evidencia: datos incompletos', [
        'control_id' => $control_id,
        'descripcion_length' => strlen($descripcion),
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    
    $_SESSION['mensaje'] = 'Faltan datos obligatorios';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Validar empresa_id en sesión
$empresa_id = $_SESSION['empresa_id'] ?? null;

if (!$empresa_id) {
    Logger::security('subirEvidencia: no empresa_id in session', [
        'user_id' => $_SESSION['usuario_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    $_SESSION['mensaje'] = 'No tiene empresa asignada';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

$empresa_id = Security::sanitize($empresa_id, 'int');

// IDOR Protection: Validar que el control pertenece a la empresa del usuario
try {
    $db = \App\Models\Database::getInstance()->getConnection();
    
    $sql_owner = "SELECT s.empresa_id, s.aplicable, c.codigo, c.nombre
                  FROM soa_entries s
                  INNER JOIN controles c ON s.control_id = c.id
                  WHERE s.control_id = :control_id AND s.empresa_id = :empresa_id
                  LIMIT 1";
    
    $stmt_owner = $db->prepare($sql_owner);
    $stmt_owner->execute([
        ':control_id' => $control_id,
        ':empresa_id' => $empresa_id
    ]);
    
    $control = $stmt_owner->fetch(PDO::FETCH_ASSOC);
    
    if (!$control) {
        Logger::security('IDOR attempt in subirEvidencia: control not found or wrong company', [
            'control_id' => $control_id,
            'user_empresa_id' => $empresa_id,
            'user_id' => $_SESSION['usuario_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        $_SESSION['mensaje'] = 'Control no encontrado o no tiene permisos';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: ' . BASE_URL . '/public/evidencias/subir');
        exit;
    }
    
    // Validar que el control sea aplicable
    if ($control['aplicable'] == 0) {
        Logger::warning('Intento de subir evidencia en control no aplicable', [
            'control_id' => $control_id,
            'codigo' => $control['codigo'],
            'user_id' => $_SESSION['usuario_id'] ?? null
        ]);
        
        $_SESSION['mensaje'] = 'No se puede subir evidencia de un control no aplicable: ' . 
                               Security::sanitizeOutput($control['codigo']) . ' - ' . 
                               Security::sanitizeOutput($control['nombre']);
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: ' . BASE_URL . '/public/evidencias/subir');
        exit;
    }
    
} catch (\Exception $e) {
    Logger::error('Error validating control ownership', [
        'control_id' => $control_id,
        'error' => $e->getMessage(),
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    
    $_SESSION['mensaje'] = 'Error al validar control';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Validar archivo
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    $uploadError = $_FILES['archivo']['error'] ?? 'no_file';
    
    Logger::warning('Error al subir archivo', [
        'control_id' => $control_id,
        'error_code' => $uploadError,
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    
    $_SESSION['mensaje'] = 'Error al subir el archivo';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

$archivo = $_FILES['archivo'];

// Validar tamaño
if ($archivo['size'] > UPLOAD_MAX_SIZE) {
    Logger::warning('Archivo excede tamaño máximo', [
        'control_id' => $control_id,
        'size' => $archivo['size'],
        'max_size' => UPLOAD_MAX_SIZE,
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    
    $_SESSION['mensaje'] = 'El archivo excede el tamaño máximo permitido (' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB)';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Validar extensión
$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

if (!in_array($extension, UPLOAD_ALLOWED_TYPES)) {
    Logger::warning('Tipo de archivo no permitido', [
        'control_id' => $control_id,
        'extension' => $extension,
        'filename' => $archivo['name'],
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    
    $_SESSION['mensaje'] = 'Tipo de archivo no permitido. Solo: ' . implode(', ', UPLOAD_ALLOWED_TYPES);
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Validar archivo (MIME y estructura)
$fileValidation = Security::validateFile($archivo);

if (!empty($fileValidation)) {
    Logger::security('Validación de archivo fallida', [
        'control_id' => $control_id,
        'filename' => $archivo['name'],
        'errors' => $fileValidation,
        'user_id' => $_SESSION['usuario_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    $_SESSION['mensaje'] = 'Archivo inválido: ' . implode(', ', $fileValidation);
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Crear directorio si no existe
if (!file_exists(UPLOAD_PATH)) {
    if (!mkdir(UPLOAD_PATH, 0755, true)) {
        Logger::error('No se pudo crear directorio de uploads', [
            'path' => UPLOAD_PATH,
            'user_id' => $_SESSION['usuario_id'] ?? null
        ]);
        
        $_SESSION['mensaje'] = 'Error al crear directorio de uploads';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: ' . BASE_URL . '/public/evidencias/subir');
        exit;
    }
}

// Generar nombre único y seguro
$nombre_archivo = Security::generateSecureFilename($archivo['name']);
$ruta_destino = UPLOAD_PATH . '/' . $nombre_archivo;

// Mover archivo temporalmente
$temp_path = $archivo['tmp_name'];

// CRÍTICO: Escanear contenido malicioso
if (!Security::scanFileContent($temp_path)) {
    Logger::security('Archivo contiene contenido malicioso', [
        'control_id' => $control_id,
        'original_filename' => $archivo['name'],
        'user_id' => $_SESSION['usuario_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'size' => $archivo['size']
    ]);
    
    $_SESSION['mensaje'] = 'El archivo contiene contenido no permitido o potencialmente malicioso';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Mover archivo a destino final
if (!move_uploaded_file($temp_path, $ruta_destino)) {
    Logger::error('Error al guardar archivo en servidor', [
        'control_id' => $control_id,
        'destination' => $ruta_destino,
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    
    $_SESSION['mensaje'] = 'Error al guardar el archivo en el servidor';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Guardar en BD
$datos = [
    'control_id' => $control_id,
    'descripcion' => $descripcion,
    'tipo_evidencia_id' => $tipo_evidencia_id > 0 ? $tipo_evidencia_id : null,
    'archivo' => $nombre_archivo,
    'empresa_id' => $empresa_id
];

$result = $controller->crear($datos);

if ($result['success']) {
    Logger::fileUpload($nombre_archivo, true, [
        'control_id' => $control_id,
        'control_codigo' => $control['codigo'],
        'evidencia_id' => $result['evidencia_id'],
        'size' => $archivo['size'],
        'original_filename' => $archivo['name'],
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    
    $_SESSION['mensaje'] = 'Evidencia subida correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
    
    // TRIGGER: Verificar completitud automática de requerimientos
    require_once __DIR__ . '/verificarCompletitudRequerimiento.php';
    verificarCompletitudRequerimientos($empresa_id);
    
    header('Location: ' . BASE_URL . '/public/evidencias');
} else {
    // Si falla BD, eliminar archivo
    if (file_exists($ruta_destino)) {
        unlink($ruta_destino);
        
        Logger::warning('Archivo eliminado tras fallo en BD', [
            'archivo' => $nombre_archivo,
            'user_id' => $_SESSION['usuario_id'] ?? null
        ]);
    }
    
    Logger::error('Error al registrar evidencia en BD', [
        'control_id' => $control_id,
        'error' => $result['error'],
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    
    $_SESSION['mensaje'] = 'Error al registrar evidencia: ' . Security::sanitizeOutput($result['error']);
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
}

exit;
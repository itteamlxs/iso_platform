<?php
/**
 * Procesar upload de evidencia
 * VERSIÓN 3.0 - Con validación CSRF, sanitización, scanFile y logging
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Evidencia.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../controllers/EvidenciasController.php';
require_once __DIR__ . '/../controllers/ControlesController.php';

use App\Helpers\Security;
use App\Helpers\Logger;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/evidencias');
    exit;
}

// Validar CSRF
if (!validateCSRF()) {
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

$controller = new \App\Controllers\EvidenciasController();

// Validar y sanitizar datos
$control_id = Security::sanitize($_POST['control_id'] ?? null, 'int');
$descripcion = Security::sanitize($_POST['descripcion'] ?? '', 'string');
$tipo_evidencia_id = Security::sanitize($_POST['tipo_evidencia_id'] ?? null, 'int');

if (!$control_id || empty($descripcion)) {
    Logger::warning('Subir evidencia: datos incompletos', [
        'control_id' => $control_id,
        'descripcion_length' => strlen($descripcion)
    ]);
    $_SESSION['mensaje'] = 'Faltan datos obligatorios';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Validar que el control sea aplicable
$controlesController = new \App\Controllers\ControlesController();
$control = $controlesController->detalle($control_id);

if (!$control || $control['aplicable'] == 0) {
    Logger::warning('Intento de subir evidencia en control no aplicable', [
        'control_id' => $control_id
    ]);
    $_SESSION['mensaje'] = 'No se puede subir evidencia de un control no aplicable';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Validar archivo
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    Logger::warning('Error al subir archivo', [
        'control_id' => $control_id,
        'error_code' => $_FILES['archivo']['error'] ?? 'no_file'
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
        'max_size' => UPLOAD_MAX_SIZE
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
        'extension' => $extension
    ]);
    $_SESSION['mensaje'] = 'Tipo de archivo no permitido. Solo: ' . implode(', ', UPLOAD_ALLOWED_TYPES);
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Validar archivo (MIME y contenido malicioso)
$fileValidation = Security::validateFile($archivo);
if (!empty($fileValidation)) {
    Logger::security('Validación de archivo fallida', [
        'control_id' => $control_id,
        'filename' => $archivo['name'],
        'errors' => $fileValidation
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
            'path' => UPLOAD_PATH
        ]);
        $_SESSION['mensaje'] = 'Error al crear directorio de uploads';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: ' . BASE_URL . '/public/evidencias/subir');
        exit;
    }
}

// Generar nombre único
$nombre_archivo = uniqid() . '_' . time() . '.' . $extension;
$ruta_destino = UPLOAD_PATH . '/' . $nombre_archivo;

// Mover archivo temporalmente para escanear
$temp_path = $archivo['tmp_name'];

// CRÍTICO: Escanear contenido malicioso
if (!Security::scanFileContent($temp_path)) {
    Logger::security('Archivo contiene contenido malicioso', [
        'control_id' => $control_id,
        'original_filename' => $archivo['name']
    ]);
    $_SESSION['mensaje'] = 'El archivo contiene contenido no permitido o potencialmente malicioso';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Mover archivo
if (!move_uploaded_file($temp_path, $ruta_destino)) {
    Logger::error('Error al guardar archivo en servidor', [
        'control_id' => $control_id,
        'destination' => $ruta_destino
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
    'tipo_evidencia_id' => $tipo_evidencia_id,
    'archivo' => $nombre_archivo
];

$result = $controller->crear($datos);

if ($result['success']) {
    Logger::fileUpload($nombre_archivo, true, [
        'control_id' => $control_id,
        'evidencia_id' => $result['evidencia_id'],
        'size' => $archivo['size']
    ]);
    
    $_SESSION['mensaje'] = 'Evidencia subida correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
    
    // TRIGGER: Verificar completitud automática de requerimientos
    require_once __DIR__ . '/verificarCompletitudRequerimiento.php';
    $empresa_id = isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : 1;
    verificarCompletitudRequerimientos($empresa_id);
    
    header('Location: ' . BASE_URL . '/public/evidencias');
} else {
    // Si falla BD, eliminar archivo
    if (file_exists($ruta_destino)) {
        unlink($ruta_destino);
    }
    
    Logger::error('Error al registrar evidencia en BD', [
        'control_id' => $control_id,
        'error' => $result['error']
    ]);
    
    $_SESSION['mensaje'] = 'Error al registrar evidencia: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
}

exit;
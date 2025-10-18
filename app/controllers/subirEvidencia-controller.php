<?php
/**
 * Procesar upload de evidencia
 * VERSIÓN 2.0 - Con verificación automática de requerimientos
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Evidencia.php';
require_once __DIR__ . '/../controllers/EvidenciasController.php';
require_once __DIR__ . '/../controllers/ControlesController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/evidencias');
    exit;
}

$controller = new \App\Controllers\EvidenciasController();

// Validar datos
$control_id = $_POST['control_id'] ?? null;
$descripcion = $_POST['descripcion'] ?? '';
$tipo_evidencia_id = $_POST['tipo_evidencia_id'] ?? null;

if (!$control_id || empty($descripcion)) {
    $_SESSION['mensaje'] = 'Faltan datos obligatorios';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Validar que el control sea aplicable
$controlesController = new \App\Controllers\ControlesController();
$control = $controlesController->detalle($control_id);

if (!$control || $control['aplicable'] == 0) {
    $_SESSION['mensaje'] = 'No se puede subir evidencia de un control no aplicable';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Validar archivo
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['mensaje'] = 'Error al subir el archivo';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

$archivo = $_FILES['archivo'];

// Validar tamaño
if ($archivo['size'] > UPLOAD_MAX_SIZE) {
    $_SESSION['mensaje'] = 'El archivo excede el tamaño máximo permitido (' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB)';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Validar extensión
$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
if (!in_array($extension, UPLOAD_ALLOWED_TYPES)) {
    $_SESSION['mensaje'] = 'Tipo de archivo no permitido. Solo: ' . implode(', ', UPLOAD_ALLOWED_TYPES);
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
    exit;
}

// Crear directorio si no existe
if (!file_exists(UPLOAD_PATH)) {
    if (!mkdir(UPLOAD_PATH, 0755, true)) {
        $_SESSION['mensaje'] = 'Error al crear directorio de uploads';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: ' . BASE_URL . '/public/evidencias/subir');
        exit;
    }
}

// Generar nombre único
$nombre_archivo = uniqid() . '_' . time() . '.' . $extension;
$ruta_destino = UPLOAD_PATH . '/' . $nombre_archivo;

// Mover archivo
if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
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
    
    $_SESSION['mensaje'] = 'Error al registrar evidencia: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias/subir');
}

exit;
<?php
/**
 * Procesar eliminación de evidencia
 * VERSIÓN 2.0 - Con validación CSRF, sanitización y logging
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Evidencia.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../controllers/EvidenciasController.php';

use App\Helpers\Security;
use App\Helpers\Logger;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/evidencias');
    exit;
}

// Validar CSRF
if (!validateCSRF()) {
    header('Location: ' . BASE_URL . '/public/evidencias');
    exit;
}

$controller = new \App\Controllers\EvidenciasController();

// Sanitizar entrada
$evidencia_id = Security::sanitize($_POST['evidencia_id'] ?? null, 'int');

if (!$evidencia_id) {
    Logger::warning('Eliminar evidencia: ID no especificado');
    $_SESSION['mensaje'] = 'Evidencia no identificada';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias');
    exit;
}

// Obtener info antes de eliminar (para logging)
$evidencia = $controller->detalle($evidencia_id);

$result = $controller->eliminar($evidencia_id);

if ($result['success']) {
    Logger::dataChange('evidencia', 'deleted', $evidencia_id, [
        'control_id' => $evidencia['control_id'] ?? 'unknown',
        'archivo' => $evidencia['archivo'] ?? 'unknown'
    ]);
    
    $_SESSION['mensaje'] = 'Evidencia eliminada correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
} else {
    Logger::error('Error al eliminar evidencia', [
        'evidencia_id' => $evidencia_id,
        'error' => $result['error']
    ]);
    $_SESSION['mensaje'] = 'Error al eliminar evidencia: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/evidencias');
exit;
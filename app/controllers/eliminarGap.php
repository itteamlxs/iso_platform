<?php
/**
 * Procesar eliminación de GAP (soft delete)
 * VERSIÓN 2.0 - Con validación CSRF, sanitización y logging
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Gap.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../controllers/GapController.php';

use App\Helpers\Security;
use App\Helpers\Logger;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

// Validar CSRF
if (!validateCSRF()) {
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

// Sanitizar entrada
$gap_id = Security::sanitize($_POST['gap_id'] ?? null, 'int');

if (!$gap_id) {
    Logger::warning('Eliminar GAP: ID no especificado');
    $_SESSION['mensaje'] = 'GAP no identificado';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

// Obtener info antes de eliminar (para logging)
$controller = new \App\Controllers\GapController();
$gap = $controller->detalle($gap_id);

$result = $controller->eliminar($gap_id);

if ($result['success']) {
    Logger::dataChange('gap', 'soft_deleted', $gap_id, [
        'control_id' => $gap['control_id'] ?? 'unknown',
        'prioridad' => $gap['prioridad'] ?? 'unknown'
    ]);
    
    $_SESSION['mensaje'] = 'GAP eliminado correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
    header('Location: ' . BASE_URL . '/public/gap');
} else {
    Logger::error('Error al eliminar GAP', [
        'gap_id' => $gap_id,
        'error' => $result['error']
    ]);
    $_SESSION['mensaje'] = 'Error al eliminar GAP: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id);
}

exit;
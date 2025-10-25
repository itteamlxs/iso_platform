<?php
/**
 * Procesar actualización de GAP
 * VERSIÓN 3.0 - Con validación CSRF, sanitización y logging
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Gap.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/GapController.php';

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

$controller = new \App\Controllers\GapController();

$gap_id = Security::sanitize($_POST['gap_id'] ?? null, 'int');

if (!$gap_id) {
    Logger::warning('Actualizar GAP: ID no especificado');
    $_SESSION['mensaje'] = 'GAP no identificado';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

// Sanitizar datos
$datos = [
    'brecha' => Security::sanitize($_POST['brecha'] ?? '', 'string'),
    'objetivo' => Security::sanitize($_POST['objetivo'] ?? null, 'string'),
    'prioridad' => Security::sanitize($_POST['prioridad'] ?? 'media', 'string'),
    'responsable' => Security::sanitize($_POST['responsable'] ?? null, 'string'),
    'fecha_estimada_cierre' => !empty($_POST['fecha_estimada_cierre']) ? Security::sanitize($_POST['fecha_estimada_cierre'], 'string') : null
];

// Validar datos obligatorios
if (empty($datos['brecha'])) {
    Logger::warning('Actualizar GAP: brecha vacía', ['gap_id' => $gap_id]);
    $_SESSION['mensaje'] = 'La descripción de la brecha es obligatoria';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id . '/editar');
    exit;
}

// Actualizar GAP (el avance se recalcula automáticamente en el modelo)
$result = $controller->actualizar($gap_id, $datos);

if ($result['success']) {
    Logger::dataChange('gap', 'updated', $gap_id, [
        'prioridad' => $datos['prioridad'],
        'responsable' => $datos['responsable']
    ]);
    
    $_SESSION['mensaje'] = 'GAP actualizado correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id);
} else {
    Logger::error('Error al actualizar GAP', [
        'gap_id' => $gap_id,
        'error' => $result['error']
    ]);
    $_SESSION['mensaje'] = 'Error al actualizar GAP: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id . '/editar');
}

exit;
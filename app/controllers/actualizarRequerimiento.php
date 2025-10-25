<?php
/**
 * Procesar actualización de requerimiento
 * VERSIÓN 2.0 - Con validación CSRF, sanitización y logging
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Requerimiento.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../controllers/RequerimientosController.php';

use App\Helpers\Security;
use App\Helpers\Logger;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/requerimientos');
    exit;
}

// Validar CSRF
if (!validateCSRF()) {
    header('Location: ' . BASE_URL . '/public/requerimientos');
    exit;
}

$controller = new \App\Controllers\RequerimientosController();

// Sanitizar entrada
$requerimiento_id = Security::sanitize($_POST['requerimiento_id'] ?? null, 'int');

if (!$requerimiento_id) {
    Logger::warning('Actualizar requerimiento: ID no especificado');
    $_SESSION['mensaje'] = 'Requerimiento no identificado';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/requerimientos');
    exit;
}

// Sanitizar datos
$datos = [
    'estado' => Security::sanitize($_POST['estado'] ?? 'pendiente', 'string'),
    'evidencia_documento' => Security::sanitize($_POST['evidencia_documento'] ?? null, 'string'),
    'fecha_entrega' => !empty($_POST['fecha_entrega']) ? Security::sanitize($_POST['fecha_entrega'], 'string') : null,
    'observaciones' => Security::sanitize($_POST['observaciones'] ?? null, 'string')
];

$result = $controller->actualizar($requerimiento_id, $datos);

if ($result['success']) {
    Logger::dataChange('requerimiento', 'updated', $requerimiento_id, [
        'estado' => $datos['estado'],
        'tiene_evidencia' => !empty($datos['evidencia_documento'])
    ]);
    
    $_SESSION['mensaje'] = 'Requerimiento actualizado correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
} else {
    Logger::error('Error al actualizar requerimiento', [
        'requerimiento_id' => $requerimiento_id,
        'error' => $result['error']
    ]);
    $_SESSION['mensaje'] = 'Error al actualizar: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/requerimientos');
exit;
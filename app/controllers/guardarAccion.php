<?php
/**
 * Procesar creación de acción correctiva
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

$controller = new \App\Controllers\GapController();

// Sanitizar datos
$datos = [
    'gap_id' => Security::sanitize($_POST['gap_id'] ?? null, 'int'),
    'descripcion' => Security::sanitize($_POST['descripcion'] ?? '', 'string'),
    'responsable' => Security::sanitize($_POST['responsable'] ?? null, 'string'),
    'fecha_compromiso' => Security::sanitize($_POST['fecha_compromiso'] ?? null, 'string')
];

$gap_id = $datos['gap_id'];

// Validar
if (empty($gap_id) || empty($datos['descripcion'])) {
    Logger::warning('Crear acción: datos incompletos', [
        'gap_id' => $gap_id,
        'descripcion_length' => strlen($datos['descripcion'])
    ]);
    $_SESSION['mensaje'] = 'Faltan datos obligatorios';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id);
    exit;
}

$result = $controller->crearAccion($datos);

if ($result['success']) {
    Logger::dataChange('accion', 'created', 'new', [
        'gap_id' => $gap_id,
        'responsable' => $datos['responsable'],
        'fecha_compromiso' => $datos['fecha_compromiso']
    ]);
    
    $_SESSION['mensaje'] = 'Acción correctiva creada';
    $_SESSION['mensaje_tipo'] = 'success';
} else {
    Logger::error('Error al crear acción', [
        'gap_id' => $gap_id,
        'error' => $result['error']
    ]);
    $_SESSION['mensaje'] = 'Error al crear acción: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/gap/' . $gap_id);
exit;
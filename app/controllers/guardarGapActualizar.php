<?php
/**
 * Procesar actualización de GAP
 * VERSIÓN 2.0 - Avance 100% automático, sin lógica híbrida
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Gap.php';
require_once __DIR__ . '/GapController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

$controller = new \App\Controllers\GapController();

$gap_id = $_POST['gap_id'] ?? null;

if (!$gap_id) {
    $_SESSION['mensaje'] = 'GAP no identificado';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

$datos = [
    'brecha' => $_POST['brecha'] ?? '',
    'objetivo' => $_POST['objetivo'] ?? null,
    'prioridad' => $_POST['prioridad'] ?? 'media',
    'responsable' => $_POST['responsable'] ?? null,
    'fecha_estimada_cierre' => !empty($_POST['fecha_estimada_cierre']) ? $_POST['fecha_estimada_cierre'] : null
];

// Validar datos obligatorios
if (empty($datos['brecha'])) {
    $_SESSION['mensaje'] = 'La descripción de la brecha es obligatoria';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id . '/editar');
    exit;
}

// Actualizar GAP (el avance se recalcula automáticamente en el modelo)
$result = $controller->actualizar($gap_id, $datos);

if ($result['success']) {
    $_SESSION['mensaje'] = 'GAP actualizado correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id);
} else {
    $_SESSION['mensaje'] = 'Error al actualizar GAP: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id . '/editar');
}

exit;
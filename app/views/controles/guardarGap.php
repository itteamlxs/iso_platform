<?php
/**
 * Procesar creación de GAP
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Gap.php';
require_once __DIR__ . '/../controllers/GapController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

$controller = new \App\Controllers\GapController();

$datos = [
    'soa_id' => $_POST['soa_id'] ?? null,
    'brecha' => $_POST['brecha'] ?? '',
    'objetivo' => $_POST['objetivo'] ?? null,
    'prioridad' => $_POST['prioridad'] ?? 'media',
    'responsable' => $_POST['responsable'] ?? null,
    'fecha_estimada_cierre' => !empty($_POST['fecha_estimada_cierre']) ? $_POST['fecha_estimada_cierre'] : null
];

// Validar datos obligatorios
if (empty($datos['soa_id']) || empty($datos['brecha'])) {
    $_SESSION['mensaje'] = 'Faltan datos obligatorios';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/crear');
    exit;
}

$result = $controller->crear($datos);

if ($result['success']) {
    $_SESSION['mensaje'] = 'GAP creado correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
    header('Location: ' . BASE_URL . '/public/gap/' . $result['gap_id']);
} else {
    $_SESSION['mensaje'] = 'Error al crear GAP: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/crear');
}

exit;
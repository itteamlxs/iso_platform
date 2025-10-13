<?php
/**
 * Procesar creación de acción correctiva
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
    'gap_id' => $_POST['gap_id'] ?? null,
    'descripcion' => $_POST['descripcion'] ?? '',
    'responsable' => $_POST['responsable'] ?? null,
    'fecha_compromiso' => $_POST['fecha_compromiso'] ?? null
];

$gap_id = $datos['gap_id'];

// Validar
if (empty($gap_id) || empty($datos['descripcion'])) {
    $_SESSION['mensaje'] = 'Faltan datos obligatorios';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id);
    exit;
}

$result = $controller->crearAccion($datos);

if ($result['success']) {
    $_SESSION['mensaje'] = 'Acción correctiva creada';
    $_SESSION['mensaje_tipo'] = 'success';
} else {
    $_SESSION['mensaje'] = 'Error al crear acción: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/gap/' . $gap_id);
exit;
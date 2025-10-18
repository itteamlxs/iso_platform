<?php
/**
 * Procesar actualización de requerimiento
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Requerimiento.php';
require_once __DIR__ . '/../controllers/RequerimientosController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/requerimientos');
    exit;
}

$controller = new \App\Controllers\RequerimientosController();

$requerimiento_id = $_POST['requerimiento_id'] ?? null;

if (!$requerimiento_id) {
    $_SESSION['mensaje'] = 'Requerimiento no identificado';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/requerimientos');
    exit;
}

$datos = [
    'estado' => $_POST['estado'] ?? 'pendiente',
    'evidencia_documento' => $_POST['evidencia_documento'] ?? null,
    'fecha_entrega' => !empty($_POST['fecha_entrega']) ? $_POST['fecha_entrega'] : null,
    'observaciones' => $_POST['observaciones'] ?? null
];

$result = $controller->actualizar($requerimiento_id, $datos);

if ($result['success']) {
    $_SESSION['mensaje'] = 'Requerimiento actualizado correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
} else {
    $_SESSION['mensaje'] = 'Error al actualizar: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/requerimientos');
exit;
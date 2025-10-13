<?php
/**
 * Procesar actualización de evaluación de control
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Control.php';
require_once __DIR__ . '/../controllers/ControlesController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/controles');
    exit;
}

$controller = new \App\Controllers\ControlesController();

$datos = [
    'aplicable' => $_POST['aplicable'] ?? 1,
    'estado' => $_POST['estado'] ?? 'no_implementado',
    'justificacion' => $_POST['justificacion'] ?? null,
    'evaluador' => 'Admin User' // Cambiar cuando tengamos sesiones
];

$soa_id = $_POST['soa_id'] ?? null;

if (!$soa_id) {
    header('Location: ' . BASE_URL . '/public/controles');
    exit;
}

$result = $controller->actualizarEvaluacion($soa_id, $datos);

if ($result['success']) {
    $_SESSION['mensaje'] = 'Evaluación actualizada correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
} else {
    $_SESSION['mensaje'] = 'Error al actualizar: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/controles/' . $control_id);
exit;
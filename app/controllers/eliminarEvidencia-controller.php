<?php
/**
 * Procesar eliminación de evidencia
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Evidencia.php';
require_once __DIR__ . '/../controllers/EvidenciasController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/evidencias');
    exit;
}

$controller = new \App\Controllers\EvidenciasController();

$evidencia_id = $_POST['evidencia_id'] ?? null;

if (!$evidencia_id) {
    $_SESSION['mensaje'] = 'Evidencia no identificada';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias');
    exit;
}

$result = $controller->eliminar($evidencia_id);

if ($result['success']) {
    $_SESSION['mensaje'] = 'Evidencia eliminada correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
} else {
    $_SESSION['mensaje'] = 'Error al eliminar evidencia: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/evidencias');
exit;
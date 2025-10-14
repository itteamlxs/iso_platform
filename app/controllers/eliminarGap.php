<?php
/**
 * Procesar eliminación de GAP (soft delete)
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Gap.php';
require_once __DIR__ . '/../controllers/GapController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

$gap_id = $_POST['gap_id'] ?? null;

if (!$gap_id) {
    $_SESSION['mensaje'] = 'GAP no identificado';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

$controller = new \App\Controllers\GapController();
$result = $controller->eliminar($gap_id);

if ($result['success']) {
    $_SESSION['mensaje'] = 'GAP eliminado correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
    header('Location: ' . BASE_URL . '/public/gap');
} else {
    $_SESSION['mensaje'] = 'Error al eliminar GAP: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id);
}

exit;
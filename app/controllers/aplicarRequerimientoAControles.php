<?php
/**
 * Aplicar requerimiento a controles asociados
 * Marca los controles como implementados manualmente
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Requerimiento.php';
require_once __DIR__ . '/RequerimientosController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/requerimientos');
    exit;
}

$requerimiento_base_id = $_POST['requerimiento_base_id'] ?? null;

if (!$requerimiento_base_id) {
    $_SESSION['mensaje'] = 'Requerimiento no identificado';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/requerimientos');
    exit;
}

$controller = new \App\Controllers\RequerimientosController();

// Aplicar requerimiento a controles
$result = $controller->aplicarAControles($requerimiento_base_id);

if ($result['success']) {
    $_SESSION['mensaje'] = 'Requerimiento aplicado correctamente. ' . $result['controles_actualizados'] . ' control(es) marcado(s) como implementado(s)';
    $_SESSION['mensaje_tipo'] = 'success';
} else {
    $_SESSION['mensaje'] = 'Error al aplicar requerimiento: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/requerimientos');
exit;
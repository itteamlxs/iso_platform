<?php
/**
 * Procesar validación de evidencia
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
$estado = $_POST['estado'] ?? null;
$comentarios = $_POST['comentarios'] ?? null;

// Validar
$estados_validos = ['pendiente', 'aprobada', 'rechazada'];
if (!$evidencia_id || !$estado || !in_array($estado, $estados_validos)) {
    $_SESSION['mensaje'] = 'Datos inválidos';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/evidencias');
    exit;
}

$datos = [
    'estado' => $estado,
    'comentarios' => $comentarios,
    'validado_por' => null // TODO: cambiar cuando tengamos usuarios
];

$result = $controller->validar($evidencia_id, $datos);

if ($result['success']) {
    $_SESSION['mensaje'] = 'Evidencia validada correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
} else {
    $_SESSION['mensaje'] = 'Error al validar evidencia: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/evidencias');
exit;
<?php
/**
 * Procesar creación de GAP con acciones correctivas
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
    'control_id' => $_POST['control_id'] ?? null,
    'brecha' => $_POST['brecha'] ?? '',
    'objetivo' => $_POST['objetivo'] ?? null,
    'prioridad' => $_POST['prioridad'] ?? 'media',
    'responsable' => $_POST['responsable'] ?? null,
    'fecha_estimada_cierre' => !empty($_POST['fecha_estimada_cierre']) ? $_POST['fecha_estimada_cierre'] : null,
    'empresa_id' => isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : 1
];

// Decodificar acciones JSON
$acciones_json = $_POST['acciones_json'] ?? '[]';
$acciones = json_decode($acciones_json, true);

// Validar datos obligatorios
if (empty($datos['control_id']) || empty($datos['brecha'])) {
    $_SESSION['mensaje'] = 'Faltan datos obligatorios (control y brecha)';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/crear');
    exit;
}

if (empty($acciones) || count($acciones) === 0) {
    $_SESSION['mensaje'] = 'Debe agregar al menos una acción correctiva';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/crear');
    exit;
}

// Crear GAP con acciones (transacción)
$result = $controller->crearConAcciones($datos, $acciones);

if ($result['success']) {
    $_SESSION['mensaje'] = 'GAP creado correctamente con ' . count($acciones) . ' acción(es)';
    $_SESSION['mensaje_tipo'] = 'success';
    header('Location: ' . BASE_URL . '/public/gap/' . $result['gap_id']);
} else {
    $_SESSION['mensaje'] = 'Error al crear GAP: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/crear');
}

exit;
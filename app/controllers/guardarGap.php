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

// Construir acciones desde arrays POST
$acciones = [];
$descripciones = $_POST['accion_descripcion'] ?? [];
$responsables = $_POST['accion_responsable'] ?? [];
$fechas = $_POST['accion_fecha'] ?? [];

$num_acciones = count($descripciones);

for ($i = 0; $i < $num_acciones; $i++) {
    if (!empty($descripciones[$i]) && !empty($fechas[$i])) {
        $acciones[] = [
            'descripcion' => $descripciones[$i],
            'responsable' => $responsables[$i] ?? null,
            'fecha_compromiso' => $fechas[$i]
        ];
    }
}

// Validar datos obligatorios
if (empty($datos['control_id']) || empty($datos['brecha'])) {
    $_SESSION['mensaje'] = 'Faltan datos obligatorios (control y brecha)';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/crear');
    exit;
}

// Validar que el control sea aplicable
require_once __DIR__ . '/../controllers/ControlesController.php';
$controlesController = new \App\Controllers\ControlesController();
$control = $controlesController->detalle($datos['control_id']);

if (!$control || $control['aplicable'] == 0) {
    $_SESSION['mensaje'] = 'No se puede crear GAP en un control no aplicable';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/crear');
    exit;
}

if (count($acciones) === 0) {
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
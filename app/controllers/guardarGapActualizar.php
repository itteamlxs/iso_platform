<?php
/**
 * Procesar actualización de GAP con lógica híbrida de avance
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Gap.php';
require_once __DIR__ . '/../controllers/GapController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

$controller = new \App\Controllers\GapController();

$gap_id = $_POST['gap_id'] ?? null;
$avance_modificado = $_POST['avance_modificado'] ?? '0';
$avance_manual = (int)($_POST['avance'] ?? 0);

if (!$gap_id) {
    $_SESSION['mensaje'] = 'GAP no identificado';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

// Si NO fue modificado, calcular automáticamente desde acciones
if ($avance_modificado === '0') {
    $acciones = $controller->getAcciones($gap_id);
    $total_acciones = count($acciones);
    $acciones_completadas = count(array_filter($acciones, fn($a) => $a['estado'] === 'completada'));
    $avance = $total_acciones > 0 ? round(($acciones_completadas / $total_acciones) * 100) : 0;
} else {
    // Si fue modificado, usar el valor manual
    $avance = $avance_manual;
}

// Si avance llega a 100%, asignar fecha real de cierre automáticamente
$fecha_real_cierre = null;
if ($avance >= 100) {
    $fecha_real_cierre = date('Y-m-d');
}

$datos = [
    'brecha' => $_POST['brecha'] ?? '',
    'objetivo' => $_POST['objetivo'] ?? null,
    'prioridad' => $_POST['prioridad'] ?? 'media',
    'responsable' => $_POST['responsable'] ?? null,
    'avance' => $avance,
    'fecha_estimada_cierre' => !empty($_POST['fecha_estimada_cierre']) ? $_POST['fecha_estimada_cierre'] : null,
    'fecha_real_cierre' => $fecha_real_cierre ?? ($_POST['fecha_real_cierre'] ?? null)
];

// Validar datos obligatorios
if (empty($datos['brecha'])) {
    $_SESSION['mensaje'] = 'La descripción de la brecha es obligatoria';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id . '/editar');
    exit;
}

$result = $controller->actualizar($gap_id, $datos);

if ($result['success']) {
    $_SESSION['mensaje'] = 'GAP actualizado correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
    
    if ($avance >= 100) {
        $_SESSION['mensaje'] .= ' - GAP marcado como CERRADO';
    }
    
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id);
} else {
    $_SESSION['mensaje'] = 'Error al actualizar GAP: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id . '/editar');
}

exit;
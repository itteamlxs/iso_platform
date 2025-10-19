<?php
/**
 * Procesar creación de GAP con acciones correctivas
 * VERSIÓN 3.0 - Con validación de control aplicable
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Gap.php';
require_once __DIR__ . '/GapController.php';

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

// Validación de datos obligatorios
if (empty($datos['control_id']) || empty($datos['brecha'])) {
    $_SESSION['mensaje'] = 'Faltan datos obligatorios (control y brecha)';
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

// MEJORA: Validar que el control sea aplicable ANTES de crear el GAP
try {
    $db = \App\Models\Database::getInstance()->getConnection();
    
    $sql_check = "SELECT s.aplicable, c.codigo, c.nombre 
                  FROM soa_entries s
                  INNER JOIN controles c ON s.control_id = c.id
                  WHERE s.control_id = :control_id 
                  AND s.empresa_id = :empresa_id";
    
    $stmt_check = $db->prepare($sql_check);
    $stmt_check->bindValue(':control_id', $datos['control_id'], PDO::PARAM_INT);
    $stmt_check->bindValue(':empresa_id', $datos['empresa_id'], PDO::PARAM_INT);
    $stmt_check->execute();
    
    $control_data = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$control_data) {
        $_SESSION['mensaje'] = 'Control no encontrado';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: ' . BASE_URL . '/public/gap/crear');
        exit;
    }
    
    if ($control_data['aplicable'] == 0) {
        $_SESSION['mensaje'] = 'No se puede crear GAP en controles NO APLICABLES. Control: ' . 
                               $control_data['codigo'] . ' - ' . $control_data['nombre'];
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: ' . BASE_URL . '/public/gap/crear');
        exit;
    }
    
} catch (\Exception $e) {
    error_log('Error en validación de control aplicable: ' . $e->getMessage());
    $_SESSION['mensaje'] = 'Error al validar control: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/crear');
    exit;
}

// Validación de aplicabilidad exitosa, proceder a crear GAP
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
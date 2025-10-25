<?php
/**
 * Procesar creación de GAP con acciones correctivas
 * VERSIÓN 4.0 - Con validación CSRF, sanitización y logging
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Gap.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/GapController.php';

use App\Helpers\Security;
use App\Helpers\Logger;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

// Validar CSRF
if (!validateCSRF()) {
    header('Location: ' . BASE_URL . '/public/gap/crear');
    exit;
}

$controller = new \App\Controllers\GapController();

// Sanitizar datos principales
$datos = [
    'control_id' => Security::sanitize($_POST['control_id'] ?? null, 'int'),
    'brecha' => Security::sanitize($_POST['brecha'] ?? '', 'string'),
    'objetivo' => Security::sanitize($_POST['objetivo'] ?? null, 'string'),
    'prioridad' => Security::sanitize($_POST['prioridad'] ?? 'media', 'string'),
    'responsable' => Security::sanitize($_POST['responsable'] ?? null, 'string'),
    'fecha_estimada_cierre' => !empty($_POST['fecha_estimada_cierre']) ? Security::sanitize($_POST['fecha_estimada_cierre'], 'string') : null,
    'empresa_id' => isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : 1
];

// Construir acciones desde arrays POST (sanitizar cada elemento)
$acciones = [];
$descripciones = $_POST['accion_descripcion'] ?? [];
$responsables = $_POST['accion_responsable'] ?? [];
$fechas = $_POST['accion_fecha'] ?? [];

$num_acciones = count($descripciones);

for ($i = 0; $i < $num_acciones; $i++) {
    if (!empty($descripciones[$i]) && !empty($fechas[$i])) {
        $acciones[] = [
            'descripcion' => Security::sanitize($descripciones[$i], 'string'),
            'responsable' => Security::sanitize($responsables[$i] ?? null, 'string'),
            'fecha_compromiso' => Security::sanitize($fechas[$i], 'string')
        ];
    }
}

// Validación de datos obligatorios
if (empty($datos['control_id']) || empty($datos['brecha'])) {
    Logger::warning('Crear GAP: datos obligatorios faltantes', [
        'control_id' => $datos['control_id'],
        'brecha_length' => strlen($datos['brecha'])
    ]);
    $_SESSION['mensaje'] = 'Faltan datos obligatorios (control y brecha)';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/crear');
    exit;
}

if (count($acciones) === 0) {
    Logger::warning('Crear GAP: sin acciones correctivas', [
        'control_id' => $datos['control_id']
    ]);
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
        Logger::error('Crear GAP: control no encontrado', [
            'control_id' => $datos['control_id']
        ]);
        $_SESSION['mensaje'] = 'Control no encontrado';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: ' . BASE_URL . '/public/gap/crear');
        exit;
    }
    
    if ($control_data['aplicable'] == 0) {
        Logger::warning('Intento de crear GAP en control no aplicable', [
            'control_id' => $datos['control_id'],
            'codigo' => $control_data['codigo']
        ]);
        $_SESSION['mensaje'] = 'No se puede crear GAP en controles NO APLICABLES. Control: ' . 
                               $control_data['codigo'] . ' - ' . $control_data['nombre'];
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: ' . BASE_URL . '/public/gap/crear');
        exit;
    }
    
} catch (\Exception $e) {
    Logger::error('Error en validación de control aplicable', [
        'control_id' => $datos['control_id'],
        'error' => $e->getMessage()
    ]);
    $_SESSION['mensaje'] = 'Error al validar control: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/crear');
    exit;
}

// Validación de aplicabilidad exitosa, proceder a crear GAP
$result = $controller->crearConAcciones($datos, $acciones);

if ($result['success']) {
    Logger::dataChange('gap', 'created', $result['gap_id'], [
        'control_id' => $datos['control_id'],
        'prioridad' => $datos['prioridad'],
        'num_acciones' => count($acciones)
    ]);
    
    $_SESSION['mensaje'] = 'GAP creado correctamente con ' . count($acciones) . ' acción(es)';
    $_SESSION['mensaje_tipo'] = 'success';
    header('Location: ' . BASE_URL . '/public/gap/' . $result['gap_id']);
} else {
    Logger::error('Error al crear GAP', [
        'control_id' => $datos['control_id'],
        'error' => $result['error']
    ]);
    $_SESSION['mensaje'] = 'Error al crear GAP: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/crear');
}

exit;
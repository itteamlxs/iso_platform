<?php
/**
 * Procesar validación de evidencia
 * VERSIÓN 3.0 - Con validación CSRF, sanitización y logging
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Evidencia.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../controllers/EvidenciasController.php';

use App\Helpers\Security;
use App\Helpers\Logger;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/evidencias');
    exit;
}

// Validar CSRF
if (!validateCSRF()) {
    header('Location: ' . BASE_URL . '/public/evidencias');
    exit;
}

$controller = new \App\Controllers\EvidenciasController();

// Sanitizar datos
$evidencia_id = Security::sanitize($_POST['evidencia_id'] ?? null, 'int');
$estado = Security::sanitize($_POST['estado'] ?? null, 'string');
$comentarios = Security::sanitize($_POST['comentarios'] ?? null, 'string');

// Validar
$estados_validos = ['pendiente', 'aprobada', 'rechazada'];
if (!$evidencia_id || !$estado || !in_array($estado, $estados_validos)) {
    Logger::warning('Validar evidencia: datos inválidos', [
        'evidencia_id' => $evidencia_id,
        'estado' => $estado
    ]);
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
    Logger::dataChange('evidencia', 'validated', $evidencia_id, [
        'estado' => $estado,
        'tiene_comentarios' => !empty($comentarios)
    ]);
    
    $_SESSION['mensaje'] = 'Evidencia validada correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
    
    // TRIGGER: Solo si fue APROBADA, verificar completitud de requerimientos
    if ($estado === 'aprobada') {
        require_once __DIR__ . '/verificarCompletitudRequerimiento.php';
        $empresa_id = isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : 1;
        verificarCompletitudRequerimientos($empresa_id);
    }
    
} else {
    Logger::error('Error al validar evidencia', [
        'evidencia_id' => $evidencia_id,
        'error' => $result['error']
    ]);
    $_SESSION['mensaje'] = 'Error al validar evidencia: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/evidencias');
exit;
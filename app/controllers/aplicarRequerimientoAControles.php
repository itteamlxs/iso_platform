<?php
/**
 * Aplicar requerimiento a controles asociados
 * Marca los controles como implementados manualmente
 * VERSIÓN 2.0 - Con validación CSRF, sanitización y logging
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Requerimiento.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/RequerimientosController.php';

use App\Helpers\Security;
use App\Helpers\Logger;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/requerimientos');
    exit;
}

// Validar CSRF
if (!validateCSRF()) {
    header('Location: ' . BASE_URL . '/public/requerimientos');
    exit;
}

// Sanitizar entrada
$requerimiento_base_id = Security::sanitize($_POST['requerimiento_base_id'] ?? null, 'int');

if (!$requerimiento_base_id) {
    Logger::warning('Aplicar requerimiento: ID no especificado');
    $_SESSION['mensaje'] = 'Requerimiento no identificado';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/requerimientos');
    exit;
}

$controller = new \App\Controllers\RequerimientosController();

// Aplicar requerimiento a controles
$result = $controller->aplicarAControles($requerimiento_base_id);

if ($result['success']) {
    Logger::dataChange('requerimiento', 'applied_to_controls', $requerimiento_base_id, [
        'controles_actualizados' => $result['controles_actualizados']
    ]);
    
    $_SESSION['mensaje'] = 'Requerimiento aplicado correctamente. ' . $result['controles_actualizados'] . ' control(es) marcado(s) como implementado(s)';
    $_SESSION['mensaje_tipo'] = 'success';
} else {
    Logger::error('Error al aplicar requerimiento a controles', [
        'requerimiento_base_id' => $requerimiento_base_id,
        'error' => $result['error']
    ]);
    $_SESSION['mensaje'] = 'Error al aplicar requerimiento: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/requerimientos');
exit;
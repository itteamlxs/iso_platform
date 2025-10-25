<?php
/**
 * Procesar actualización de evaluación de control
 * VERSIÓN 4.0 - Con validación CSRF, sanitización y logging
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Control.php';
require_once __DIR__ . '/../controllers/ControlesController.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Logger.php';

use App\Helpers\Security;
use App\Helpers\Logger;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/controles');
    exit;
}

// Validar CSRF
if (!validateCSRF()) {
    header('Location: ' . BASE_URL . '/public/controles');
    exit;
}

$controller = new \App\Controllers\ControlesController();

// Sanitizar entrada
$datos = [
    'aplicable' => Security::sanitize($_POST['aplicable'] ?? 1, 'int'),
    'estado' => Security::sanitize($_POST['estado'] ?? 'no_implementado', 'string'),
    'justificacion' => Security::sanitize($_POST['justificacion'] ?? null, 'string'),
    'evaluador' => 'Admin User' // Cambiar cuando tengamos sesiones
];

$soa_id = Security::sanitize($_POST['soa_id'] ?? null, 'int');
$control_id = Security::sanitize($_POST['control_id'] ?? null, 'int');

if (!$soa_id || !$control_id) {
    Logger::warning('Actualizar control: datos incompletos', [
        'soa_id' => $soa_id,
        'control_id' => $control_id
    ]);
    header('Location: ' . BASE_URL . '/public/controles');
    exit;
}

// VALIDACIÓN: Si intenta marcar como NO APLICABLE, verificar requerimientos asociados
if ($datos['aplicable'] == 0) {
    try {
        $db = \App\Models\Database::getInstance()->getConnection();
        $empresa_id = isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : 1;
        
        // Verificar si el control tiene requerimientos asociados
        $sql_check = "SELECT 
                        rb.numero,
                        rb.identificador,
                        er.estado as estado_requerimiento
                      FROM requerimientos_controles rc
                      INNER JOIN requerimientos_base rb ON rc.requerimiento_base_id = rb.id
                      INNER JOIN empresa_requerimientos er ON rb.id = er.requerimiento_id
                      WHERE rc.control_id = :control_id
                      AND er.empresa_id = :empresa_id
                      AND er.estado != 'no_aplica'";
        
        $stmt_check = $db->prepare($sql_check);
        $stmt_check->bindValue(':control_id', $control_id, PDO::PARAM_INT);
        $stmt_check->bindValue(':empresa_id', $empresa_id, PDO::PARAM_INT);
        $stmt_check->execute();
        
        $requerimientos_activos = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
        
        // Si hay requerimientos activos (no en estado "no_aplica"), bloquear
        if (count($requerimientos_activos) > 0) {
            $lista_reqs = array_map(function($r) {
                return "REQ-" . str_pad($r['numero'], 2, '0', STR_PAD_LEFT) . ": " . $r['identificador'];
            }, $requerimientos_activos);
            
            Logger::warning('Intento de marcar control como no aplicable con requerimientos activos', [
                'control_id' => $control_id,
                'requerimientos_activos' => count($requerimientos_activos)
            ]);
            
            $_SESSION['mensaje'] = 'No se puede marcar este control como NO APLICABLE porque está asociado a los siguientes requerimientos activos:<br><br>' . 
                                   implode('<br>', $lista_reqs) . 
                                   '<br><br>Primero debe marcar esos requerimientos como "No Aplica" si realmente no son aplicables a su organización.';
            $_SESSION['mensaje_tipo'] = 'error';
            
            header('Location: ' . BASE_URL . '/public/controles/' . $control_id);
            exit;
        }
        
    } catch (\Exception $e) {
        Logger::error('Error en validación de requerimientos asociados', [
            'control_id' => $control_id,
            'error' => $e->getMessage()
        ]);
        $_SESSION['mensaje'] = 'Error al validar control: ' . $e->getMessage();
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: ' . BASE_URL . '/public/controles/' . $control_id);
        exit;
    }
}

// Si pasa la validación o no intenta marcar como no aplicable, proceder normalmente
$result = $controller->actualizarEvaluacion($soa_id, $datos);

if ($result['success']) {
    Logger::dataChange('control', 'updated', $control_id, [
        'soa_id' => $soa_id,
        'aplicable' => $datos['aplicable'],
        'estado' => $datos['estado']
    ]);
    
    $_SESSION['mensaje'] = 'Evaluación actualizada correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
    
    // TRIGGER: Solo si fue marcado como IMPLEMENTADO, verificar completitud de requerimientos
    if ($datos['estado'] === 'implementado' && $datos['aplicable'] == 1) {
        require_once __DIR__ . '/verificarCompletitudRequerimiento.php';
        $empresa_id = isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : 1;
        verificarCompletitudRequerimientos($empresa_id);
    }
    
} else {
    Logger::error('Error al actualizar control', [
        'control_id' => $control_id,
        'error' => $result['error']
    ]);
    $_SESSION['mensaje'] = 'Error al actualizar: ' . $result['error'];
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/controles/' . $control_id);
exit;
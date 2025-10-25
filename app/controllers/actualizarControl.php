<?php
/**
 * Procesar actualización de evaluación de control
 * VERSIÓN 5.0 - Enhanced security with full validation
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Control.php';
require_once __DIR__ . '/../controllers/ControlesController.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../middleware/RateLimitMiddleware.php';

use App\Helpers\Security;
use App\Helpers\Logger;
use App\Middleware\RateLimitMiddleware;

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Logger::warning('actualizarControl accessed with non-POST method', [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    header('Location: ' . BASE_URL . '/public/controles');
    exit;
}

// Validar CSRF - OBLIGATORIO
if (!validateCSRF()) {
    header('Location: ' . BASE_URL . '/public/controles');
    exit;
}

// Rate limiting para formularios
$identifier = $_SESSION['usuario_id'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

if (!RateLimitMiddleware::check('form', $identifier)) {
    Logger::security('Form submission rate limit exceeded', [
        'form' => 'actualizarControl',
        'user_id' => $_SESSION['usuario_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    $blockInfo = RateLimitMiddleware::isBlocked('form', $identifier);
    $_SESSION['mensaje'] = 'Demasiados envíos. Espere ' . $blockInfo['time_left'] . ' minuto(s).';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/controles');
    exit;
}

// Registrar intento
RateLimitMiddleware::record('form', $identifier);

$controller = new \App\Controllers\ControlesController();

// Sanitizar TODOS los inputs
$aplicable = Security::sanitize($_POST['aplicable'] ?? 1, 'int');
$estado = Security::sanitize($_POST['estado'] ?? 'no_implementado', 'string');
$justificacion = Security::sanitize($_POST['justificacion'] ?? null, 'string');
$soa_id = Security::sanitize($_POST['soa_id'] ?? null, 'int');
$control_id = Security::sanitize($_POST['control_id'] ?? null, 'int');

// Validar datos obligatorios
if (!$soa_id || !$control_id) {
    Logger::warning('actualizarControl: datos incompletos', [
        'soa_id' => $soa_id,
        'control_id' => $control_id,
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    
    $_SESSION['mensaje'] = 'Datos incompletos';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/controles');
    exit;
}

// Validar valores permitidos
if (!in_array($aplicable, [0, 1])) {
    Logger::warning('actualizarControl: aplicable value invalid', [
        'aplicable' => $aplicable,
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    
    $aplicable = 1;
}

$estadosPermitidos = ['implementado', 'parcial', 'no_implementado'];
if (!in_array($estado, $estadosPermitidos)) {
    Logger::warning('actualizarControl: estado value invalid', [
        'estado' => $estado,
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    
    $estado = 'no_implementado';
}

// Preparar datos sanitizados
$datos = [
    'aplicable' => $aplicable,
    'estado' => $estado,
    'justificacion' => $justificacion,
    'evaluador' => Security::sanitize($_SESSION['usuario_nombre'] ?? 'Sistema', 'string')
];

// VALIDACIÓN: Si intenta marcar como NO APLICABLE, verificar requerimientos asociados
if ($datos['aplicable'] == 0) {
    try {
        $db = \App\Models\Database::getInstance()->getConnection();
        $empresa_id = $_SESSION['empresa_id'] ?? null;
        
        if (!$empresa_id) {
            Logger::security('actualizarControl: no empresa_id in session', [
                'user_id' => $_SESSION['usuario_id'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $_SESSION['mensaje'] = 'No tiene empresa asignada';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/controles/' . $control_id);
            exit;
        }
        
        // Sanitizar empresa_id
        $empresa_id = Security::sanitize($empresa_id, 'int');
        
        // IDOR Protection: Verificar que el SOA pertenece a la empresa del usuario
        $sql_owner = "SELECT empresa_id FROM soa_entries WHERE id = :soa_id LIMIT 1";
        $stmt_owner = $db->prepare($sql_owner);
        $stmt_owner->execute([':soa_id' => $soa_id]);
        $soa_owner = $stmt_owner->fetch(PDO::FETCH_ASSOC);
        
        if (!$soa_owner || !Security::validateOwnership($soa_owner['empresa_id'], $empresa_id)) {
            Logger::security('IDOR attempt in actualizarControl', [
                'soa_id' => $soa_id,
                'user_empresa_id' => $empresa_id,
                'soa_empresa_id' => $soa_owner['empresa_id'] ?? 'not_found',
                'user_id' => $_SESSION['usuario_id'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $_SESSION['mensaje'] = 'No tiene permisos para esta operación';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/controles');
            exit;
        }
        
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
        $stmt_check->execute([
            ':control_id' => $control_id,
            ':empresa_id' => $empresa_id
        ]);
        
        $requerimientos_activos = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
        
        // Si hay requerimientos activos (no en estado "no_aplica"), bloquear
        if (count($requerimientos_activos) > 0) {
            $lista_reqs = array_map(function($r) {
                return "REQ-" . str_pad($r['numero'], 2, '0', STR_PAD_LEFT) . ": " . 
                       Security::sanitizeOutput($r['identificador']);
            }, $requerimientos_activos);
            
            Logger::warning('Intento de marcar control como no aplicable con requerimientos activos', [
                'control_id' => $control_id,
                'requerimientos_activos' => count($requerimientos_activos),
                'user_id' => $_SESSION['usuario_id'] ?? null
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
            'error' => $e->getMessage(),
            'user_id' => $_SESSION['usuario_id'] ?? null
        ]);
        
        $_SESSION['mensaje'] = 'Error al validar control: ' . Security::sanitizeOutput($e->getMessage());
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
        'estado' => $datos['estado'],
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    
    $_SESSION['mensaje'] = 'Evaluación actualizada correctamente';
    $_SESSION['mensaje_tipo'] = 'success';
    
    // TRIGGER: Solo si fue marcado como IMPLEMENTADO, verificar completitud de requerimientos
    if ($datos['estado'] === 'implementado' && $datos['aplicable'] == 1) {
        require_once __DIR__ . '/verificarCompletitudRequerimiento.php';
        $empresa_id = $_SESSION['empresa_id'] ?? 1;
        verificarCompletitudRequerimientos($empresa_id);
    }
    
} else {
    Logger::error('Error al actualizar control', [
        'control_id' => $control_id,
        'error' => $result['error'],
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    
    $_SESSION['mensaje'] = 'Error al actualizar: ' . Security::sanitizeOutput($result['error']);
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/controles/' . $control_id);
exit;
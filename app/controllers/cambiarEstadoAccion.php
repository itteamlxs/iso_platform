<?php
/**
 * Cambiar estado de acción correctiva
 * VERSIÓN 4.0 - Con validación CSRF, sanitización y logging
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Gap.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../controllers/GapController.php';

use App\Helpers\Security;
use App\Helpers\Logger;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

// Validar CSRF
if (!validateCSRF()) {
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

// Sanitizar datos
$accion_id = Security::sanitize($_POST['accion_id'] ?? null, 'int');
$gap_id = Security::sanitize($_POST['gap_id'] ?? null, 'int');
$nuevo_estado = Security::sanitize($_POST['nuevo_estado'] ?? null, 'string');

// Validar estados válidos (sin 'inactiva')
$estados_validos = ['pendiente', 'en_progreso', 'completada'];
if (!$accion_id || !$gap_id || !$nuevo_estado || !in_array($nuevo_estado, $estados_validos)) {
    Logger::warning('Cambiar estado acción: datos inválidos', [
        'accion_id' => $accion_id,
        'gap_id' => $gap_id,
        'nuevo_estado' => $nuevo_estado
    ]);
    $_SESSION['mensaje'] = 'Datos incompletos o inválidos';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id);
    exit;
}

try {
    $db = \App\Models\Database::getInstance()->getConnection();
    
    // Verificar que la acción pertenece al GAP y está activa
    $sql_check = "SELECT COUNT(*) as existe FROM acciones a 
                  INNER JOIN gap_items g ON a.gap_id = g.id 
                  WHERE a.id = :accion_id 
                  AND g.id = :gap_id 
                  AND a.estado_accion = 'activo'";
    
    $stmt_check = $db->prepare($sql_check);
    $stmt_check->bindParam(':accion_id', $accion_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':gap_id', $gap_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if ($result['existe'] == 0) {
        Logger::warning('Cambiar estado acción: acción no válida', [
            'accion_id' => $accion_id,
            'gap_id' => $gap_id
        ]);
        throw new Exception('Acción no encontrada, no pertenece a este GAP o está eliminada');
    }
    
    // Actualizar estado de la acción
    $sql = "UPDATE acciones SET estado = :estado WHERE id = :accion_id AND estado_accion = 'activo'";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':estado', $nuevo_estado, PDO::PARAM_STR);
    $stmt->bindParam(':accion_id', $accion_id, PDO::PARAM_INT);
    $stmt->execute();
    
    Logger::dataChange('accion', 'estado_changed', $accion_id, [
        'gap_id' => $gap_id,
        'nuevo_estado' => $nuevo_estado
    ]);
    
    // Recalcular avance del GAP automáticamente
    $controller = new \App\Controllers\GapController();
    $avance_result = $controller->actualizarAvance($gap_id);
    
    $_SESSION['mensaje'] = 'Estado de acción actualizado a: ' . ucfirst(str_replace('_', ' ', $nuevo_estado));
    
    if ($avance_result['success'] && $avance_result['avance'] >= 100) {
        $_SESSION['mensaje'] .= ' - GAP marcado como CERRADO (100%)';
        Logger::info('GAP cerrado automáticamente', [
            'gap_id' => $gap_id,
            'avance' => $avance_result['avance']
        ]);
    }
    
    $_SESSION['mensaje_tipo'] = 'success';
    
} catch (\Exception $e) {
    Logger::error('Error al cambiar estado de acción', [
        'accion_id' => $accion_id,
        'gap_id' => $gap_id,
        'error' => $e->getMessage()
    ]);
    $_SESSION['mensaje'] = 'Error al actualizar estado: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/gap/' . $gap_id);
exit;
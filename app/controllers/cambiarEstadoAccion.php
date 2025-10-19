<?php
/**
 * Cambiar estado de acción correctiva
 * VERSIÓN 3.0 - Con validación de estado_accion = 'activo'
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Gap.php';
require_once __DIR__ . '/GapController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

$accion_id = $_POST['accion_id'] ?? null;
$gap_id = $_POST['gap_id'] ?? null;
$nuevo_estado = $_POST['nuevo_estado'] ?? null;

// Validar estados válidos (sin 'inactiva')
$estados_validos = ['pendiente', 'en_progreso', 'completada'];
if (!$accion_id || !$gap_id || !$nuevo_estado || !in_array($nuevo_estado, $estados_validos)) {
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
        throw new Exception('Acción no encontrada, no pertenece a este GAP o está eliminada');
    }
    
    // Actualizar estado de la acción
    $sql = "UPDATE acciones SET estado = :estado WHERE id = :accion_id AND estado_accion = 'activo'";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':estado', $nuevo_estado, PDO::PARAM_STR);
    $stmt->bindParam(':accion_id', $accion_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Recalcular avance del GAP automáticamente
    $controller = new \App\Controllers\GapController();
    $avance_result = $controller->actualizarAvance($gap_id);
    
    $_SESSION['mensaje'] = 'Estado de acción actualizado a: ' . ucfirst(str_replace('_', ' ', $nuevo_estado));
    
    if ($avance_result['success'] && $avance_result['avance'] >= 100) {
        $_SESSION['mensaje'] .= ' - GAP marcado como CERRADO (100%)';
    }
    
    $_SESSION['mensaje_tipo'] = 'success';
    
} catch (\Exception $e) {
    error_log('Error al cambiar estado de acción: ' . $e->getMessage());
    $_SESSION['mensaje'] = 'Error al actualizar estado: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/gap/' . $gap_id);
exit;
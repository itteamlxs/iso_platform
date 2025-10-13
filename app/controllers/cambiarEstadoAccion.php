<?php
/**
 * Cambiar estado de acción correctiva
 */

require_once __DIR__ . '/../models/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

$accion_id = $_POST['accion_id'] ?? null;
$gap_id = $_POST['gap_id'] ?? null;
$nuevo_estado = $_POST['nuevo_estado'] ?? null;

// Validar estados válidos
$estados_validos = ['pendiente', 'en_progreso', 'completada'];
if (!$accion_id || !$gap_id || !$nuevo_estado || !in_array($nuevo_estado, $estados_validos)) {
    $_SESSION['mensaje'] = 'Datos incompletos o inválidos';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ' . BASE_URL . '/public/gap/' . $gap_id);
    exit;
}

try {
    $db = \App\Models\Database::getInstance()->getConnection();
    
    // Verificar que la acción pertenece al GAP
    $sql_check = "SELECT COUNT(*) as existe FROM acciones a 
                  INNER JOIN gap_items g ON a.gap_id = g.id 
                  WHERE a.id = :accion_id AND g.id = :gap_id";
    $stmt_check = $db->prepare($sql_check);
    $stmt_check->bindParam(':accion_id', $accion_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':gap_id', $gap_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if ($result['existe'] == 0) {
        throw new Exception('Acción no encontrada o no pertenece a este GAP');
    }
    
    // Actualizar estado
    $sql = "UPDATE acciones SET estado = :estado WHERE id = :accion_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':estado', $nuevo_estado, PDO::PARAM_STR);
    $stmt->bindParam(':accion_id', $accion_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $_SESSION['mensaje'] = 'Estado de acción actualizado a: ' . ucfirst(str_replace('_', ' ', $nuevo_estado));
    $_SESSION['mensaje_tipo'] = 'success';
    
} catch (\Exception $e) {
    error_log('Error al cambiar estado de acción: ' . $e->getMessage());
    $_SESSION['mensaje'] = 'Error al actualizar estado: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . BASE_URL . '/public/gap/' . $gap_id);
exit;
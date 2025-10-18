<?php
/**
 * Verificar completitud automática de requerimientos
 * Se ejecuta después de: subir evidencia, validar evidencia, actualizar control
 * 
 * Lógica: Si TODOS los controles asociados están implementados + tienen evidencias aprobadas
 *         → Marcar requerimiento como "completado" automáticamente
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Requerimiento.php';
require_once __DIR__ . '/RequerimientosController.php';

/**
 * Función principal: Verificar todos los requerimientos de una empresa
 */
function verificarCompletitudRequerimientos($empresa_id) {
    try {
        $controller = new \App\Controllers\RequerimientosController();
        $db = \App\Models\Database::getInstance()->getConnection();
        
        // Obtener todos los requerimientos NO completados de la empresa
        $sql = "SELECT er.id, er.requerimiento_id 
                FROM empresa_requerimientos er
                WHERE er.empresa_id = :empresa_id 
                AND er.estado != 'completado'";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':empresa_id', $empresa_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $requerimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $requerimientos_completados = 0;
        
        foreach ($requerimientos as $req) {
            // Verificar si cumple condiciones para completitud automática
            $es_completo = $controller->verificarCompletitudAutomatica($req['requerimiento_id']);
            
            if ($es_completo) {
                // Marcar como completado automáticamente
                $sql_update = "UPDATE empresa_requerimientos 
                              SET estado = 'completado',
                                  fecha_entrega = NOW(),
                                  observaciones = CONCAT(COALESCE(observaciones, ''), '\n[Sistema] Completado automáticamente al tener todos los controles implementados con evidencias.')
                              WHERE id = :req_id";
                
                $stmt_update = $db->prepare($sql_update);
                $stmt_update->bindValue(':req_id', $req['id'], PDO::PARAM_INT);
                $stmt_update->execute();
                
                $requerimientos_completados++;
            }
        }
        
        return [
            'success' => true,
            'requerimientos_completados' => $requerimientos_completados
        ];
        
    } catch (\Exception $e) {
        error_log('Error en verificarCompletitudRequerimientos: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Si se ejecuta directamente (no como include)
 */
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    
    // Verificar si viene de un proceso válido
    $empresa_id = isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : 1;
    
    $result = verificarCompletitudRequerimientos($empresa_id);
    
    if ($result['success'] && $result['requerimientos_completados'] > 0) {
        error_log('Verificación automática: ' . $result['requerimientos_completados'] . ' requerimiento(s) completado(s)');
    }
    
    // No hacer redirect, este script se ejecuta en segundo plano
    exit;
}
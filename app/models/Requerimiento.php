<?php

namespace App\Models;

use PDO;

require_once __DIR__ . '/Database.php';

/**
 * Requerimiento Model
 * Gestión de requerimientos base ISO 27001
 */
class Requerimiento {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todos los requerimientos de una empresa
     */
    public function getAll($empresa_id) {
        try {
            $sql = "SELECT 
                        er.id,
                        er.estado,
                        er.evidencia_documento,
                        er.fecha_entrega,
                        er.observaciones,
                        rb.numero,
                        rb.identificador,
                        rb.descripcion,
                        rb.objetivo
                    FROM empresa_requerimientos er
                    INNER JOIN requerimientos_base rb ON er.requerimiento_id = rb.id
                    WHERE er.empresa_id = :empresa_id
                    ORDER BY rb.numero ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresa_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log('Error en Requerimiento::getAll: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener requerimiento por ID
     */
    public function getById($requerimiento_id) {
        try {
            $sql = "SELECT 
                        er.*,
                        rb.numero,
                        rb.identificador,
                        rb.descripcion,
                        rb.objetivo
                    FROM empresa_requerimientos er
                    INNER JOIN requerimientos_base rb ON er.requerimiento_id = rb.id
                    WHERE er.id = :requerimiento_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':requerimiento_id', $requerimiento_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log('Error en Requerimiento::getById: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Actualizar estado de requerimiento
     */
    public function actualizar($requerimiento_id, $datos) {
        try {
            $sql = "UPDATE empresa_requerimientos SET 
                        estado = :estado,
                        evidencia_documento = :evidencia,
                        fecha_entrega = :fecha,
                        observaciones = :observaciones
                    WHERE id = :requerimiento_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':estado', $datos['estado'], PDO::PARAM_STR);
            $stmt->bindValue(':evidencia', $datos['evidencia_documento'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha', $datos['fecha_entrega'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':observaciones', $datos['observaciones'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':requerimiento_id', $requerimiento_id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            error_log('Error en Requerimiento::actualizar: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener estadísticas de requerimientos
     */
    public function getEstadisticas($empresa_id) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                        SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                        SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
                        SUM(CASE WHEN estado = 'no_aplica' THEN 1 ELSE 0 END) as no_aplica
                    FROM empresa_requerimientos 
                    WHERE empresa_id = :empresa_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresa_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log('Error en Requerimiento::getEstadisticas: ' . $e->getMessage());
            return [];
        }
    }
}
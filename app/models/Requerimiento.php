<?php

namespace App\Models;

use PDO;

require_once __DIR__ . '/Database.php';

/**
 * Requerimiento Model
 * Gestión de requerimientos base ISO 27001
 * VERSIÓN 3.0 - Con validación de evidencias APROBADAS para completitud automática
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
                        er.requerimiento_id as requerimiento_base_id,
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
                        er.requerimiento_id as requerimiento_base_id,
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
     * Obtener controles asociados a un requerimiento
     */
    public function getControlesAsociados($requerimiento_base_id, $empresa_id) {
        try {
            $sql = "SELECT 
                        c.id,
                        c.codigo,
                        c.nombre,
                        c.descripcion,
                        cd.nombre as dominio,
                        COALESCE(s.aplicable, 1) as aplicable,
                        COALESCE(s.estado, 'no_implementado') as estado,
                        (SELECT COUNT(*) 
                         FROM evidencias e 
                         WHERE e.control_id = c.id 
                         AND e.empresa_id = :empresa_id_ev
                         AND e.estado_validacion = 'aprobada') as total_evidencias
                    FROM requerimientos_controles rc
                    INNER JOIN controles c ON rc.control_id = c.id
                    INNER JOIN controles_dominio cd ON c.dominio_id = cd.id
                    LEFT JOIN soa_entries s ON c.id = s.control_id AND s.empresa_id = :empresa_id_soa
                    WHERE rc.requerimiento_base_id = :req_id
                    ORDER BY c.codigo ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':req_id', $requerimiento_base_id, PDO::PARAM_INT);
            $stmt->bindValue(':empresa_id_soa', $empresa_id, PDO::PARAM_INT);
            $stmt->bindValue(':empresa_id_ev', $empresa_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log('Error en Requerimiento::getControlesAsociados: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener evidencias de controles asociados
     */
    public function getEvidenciasDeControles($requerimiento_base_id, $empresa_id) {
        try {
            $sql = "SELECT 
                        e.id,
                        e.descripcion,
                        e.archivo,
                        e.fecha_subida,
                        e.estado_validacion,
                        c.codigo,
                        c.nombre as control
                    FROM requerimientos_controles rc
                    INNER JOIN controles c ON rc.control_id = c.id
                    INNER JOIN evidencias e ON c.id = e.control_id
                    WHERE rc.requerimiento_base_id = :req_id 
                    AND e.empresa_id = :empresa_id
                    AND e.estado_validacion = 'aprobada'
                    ORDER BY e.fecha_subida DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':req_id', $requerimiento_base_id, PDO::PARAM_INT);
            $stmt->bindValue(':empresa_id', $empresa_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log('Error en Requerimiento::getEvidenciasDeControles: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Aplicar requerimiento a controles (marcar como implementado)
     */
    public function aplicarAControles($requerimiento_base_id, $empresa_id) {
        try {
            $this->db->beginTransaction();
            
            $sql = "SELECT c.id 
                    FROM requerimientos_controles rc
                    INNER JOIN controles c ON rc.control_id = c.id
                    INNER JOIN soa_entries s ON c.id = s.control_id
                    WHERE rc.requerimiento_base_id = :req_id 
                    AND s.empresa_id = :empresa_id
                    AND s.aplicable = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':req_id', $requerimiento_base_id, PDO::PARAM_INT);
            $stmt->bindValue(':empresa_id', $empresa_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $controles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($controles) == 0) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'No hay controles aplicables asociados'];
            }
            
            $sql_update = "UPDATE soa_entries 
                          SET estado = 'implementado', 
                              fecha_evaluacion = NOW(),
                              evaluador = 'Sistema - Requerimiento aplicado'
                          WHERE control_id = :control_id 
                          AND empresa_id = :empresa_id
                          AND aplicable = 1";
            
            $stmt_update = $this->db->prepare($sql_update);
            $controles_actualizados = 0;
            
            foreach ($controles as $control_id) {
                $stmt_update->bindValue(':control_id', $control_id, PDO::PARAM_INT);
                $stmt_update->bindValue(':empresa_id', $empresa_id, PDO::PARAM_INT);
                $stmt_update->execute();
                $controles_actualizados++;
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'controles_actualizados' => $controles_actualizados
            ];
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('Error en Requerimiento::aplicarAControles: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Verificar si todos los controles asociados están implementados con evidencias APROBADAS
     * MEJORA: Ahora valida que las evidencias estén en estado 'aprobada'
     */
    public function verificarCompletitudAutomatica($requerimiento_base_id, $empresa_id) {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT c.id) as total_controles,
                        SUM(CASE WHEN s.estado = 'implementado' AND s.aplicable = 1 THEN 1 ELSE 0 END) as implementados,
                        COUNT(DISTINCT CASE 
                            WHEN e.estado_validacion = 'aprobada' AND s.aplicable = 1 
                            THEN c.id 
                            ELSE NULL 
                        END) as con_evidencias_aprobadas
                    FROM requerimientos_controles rc
                    INNER JOIN controles c ON rc.control_id = c.id
                    INNER JOIN soa_entries s ON c.id = s.control_id
                    LEFT JOIN evidencias e ON c.id = e.control_id AND e.empresa_id = :empresa_id_ev
                    WHERE rc.requerimiento_base_id = :req_id 
                    AND s.empresa_id = :empresa_id_soa
                    AND s.aplicable = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':req_id', $requerimiento_base_id, PDO::PARAM_INT);
            $stmt->bindValue(':empresa_id_soa', $empresa_id, PDO::PARAM_INT);
            $stmt->bindValue(':empresa_id_ev', $empresa_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // CONDICIÓN MEJORADA: 
            // 1. Todos los controles aplicables deben estar implementados
            // 2. Todos los controles aplicables deben tener AL MENOS UNA evidencia aprobada
            if ($result['total_controles'] > 0 && 
                $result['implementados'] == $result['total_controles'] &&
                $result['con_evidencias_aprobadas'] == $result['total_controles']) {
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            error_log('Error en Requerimiento::verificarCompletitudAutomatica: ' . $e->getMessage());
            return false;
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
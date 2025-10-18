<?php

namespace App\Models;

use PDO;

/**
 * Gap Model
 * Gestión de análisis de brechas
 * VERSIÓN 2.0 - Optimizado con avance automático y soft delete en cascada
 */
class Gap {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todos los GAPs de una empresa (solo activos y aplicables)
     */
    public function getAll($empresa_id, $filtros = []) {
        try {
            $sql = "SELECT 
                        g.id,
                        g.brecha,
                        g.objetivo,
                        g.prioridad,
                        g.avance,
                        g.fecha_estimada_cierre,
                        g.fecha_real_cierre,
                        g.responsable,
                        c.codigo,
                        c.nombre as control,
                        cd.nombre as dominio,
                        s.id as soa_id,
                        (SELECT COUNT(*) FROM acciones WHERE gap_id = g.id AND estado != 'inactiva') as total_acciones,
                        (SELECT COUNT(*) FROM acciones WHERE gap_id = g.id AND estado = 'completada') as acciones_completadas
                    FROM gap_items g
                    INNER JOIN soa_entries s ON g.soa_id = s.id
                    INNER JOIN controles c ON s.control_id = c.id
                    INNER JOIN controles_dominio cd ON c.dominio_id = cd.id
                    WHERE s.empresa_id = :empresa_id AND g.estado_gap = 'activo' AND s.aplicable = 1";
            
            if (!empty($filtros['prioridad'])) {
                $sql .= " AND g.prioridad = :prioridad";
            }
            
            if (isset($filtros['estado'])) {
                if ($filtros['estado'] == 'pendiente') {
                    $sql .= " AND g.avance < 100 AND g.fecha_real_cierre IS NULL";
                } elseif ($filtros['estado'] == 'cerrado') {
                    $sql .= " AND (g.avance = 100 OR g.fecha_real_cierre IS NOT NULL)";
                }
            }
            
            $sql .= " ORDER BY 
                        FIELD(g.prioridad, 'alta', 'media', 'baja'),
                        g.avance ASC,
                        g.fecha_estimada_cierre ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);
            
            if (!empty($filtros['prioridad'])) {
                $stmt->bindParam(':prioridad', $filtros['prioridad'], PDO::PARAM_STR);
            }
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log('Error en Gap::getAll: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener GAP por ID (solo si es aplicable)
     */
    public function getById($gap_id) {
        try {
            $sql = "SELECT 
                        g.*,
                        c.codigo,
                        c.nombre as control,
                        cd.nombre as dominio
                    FROM gap_items g
                    INNER JOIN soa_entries s ON g.soa_id = s.id
                    INNER JOIN controles c ON s.control_id = c.id
                    INNER JOIN controles_dominio cd ON c.dominio_id = cd.id
                    WHERE g.id = :gap_id AND g.estado_gap = 'activo' AND s.aplicable = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':gap_id', $gap_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log('Error en Gap::getById: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Método centralizado: Obtener soa_id desde control_id
     */
    private function getSoaIdFromControl($control_id, $empresa_id) {
        try {
            $sql = "SELECT id FROM soa_entries 
                    WHERE control_id = :control_id 
                    AND empresa_id = :empresa_id 
                    AND aplicable = 1
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':control_id', $control_id, PDO::PARAM_INT);
            $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['id'] : null;
            
        } catch (\Exception $e) {
            error_log('Error en Gap::getSoaIdFromControl: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calcular avance automático basado en acciones
     */
    private function calcularAvanceAutomatico($gap_id) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas
                    FROM acciones 
                    WHERE gap_id = :gap_id AND estado != 'inactiva'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':gap_id', $gap_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total'] > 0) {
                return round(($result['completadas'] / $result['total']) * 100);
            }
            
            return 0;
            
        } catch (\Exception $e) {
            error_log('Error en Gap::calcularAvanceAutomatico: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Actualizar avance de un GAP (llamado desde cambios de acciones)
     */
    public function actualizarAvance($gap_id) {
        try {
            $avance = $this->calcularAvanceAutomatico($gap_id);
            
            $fecha_real_cierre = null;
            if ($avance >= 100) {
                $fecha_real_cierre = date('Y-m-d');
            }
            
            $sql = "UPDATE gap_items 
                    SET avance = :avance, 
                        fecha_real_cierre = :fecha_cierre
                    WHERE id = :gap_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':avance', $avance, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_cierre', $fecha_real_cierre, PDO::PARAM_STR);
            $stmt->bindParam(':gap_id', $gap_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return ['success' => true, 'avance' => $avance];
            
        } catch (\Exception $e) {
            error_log('Error en Gap::actualizarAvance: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Crear GAP con acciones correctivas (transacción)
     */
    public function crearConAcciones($datos, $acciones) {
        try {
            $this->db->beginTransaction();
            
            $empresa_id = $datos['empresa_id'] ?? 1;
            
            // Obtener soa_id usando método centralizado
            $soa_id = $this->getSoaIdFromControl($datos['control_id'], $empresa_id);
            
            if (!$soa_id) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Control no aplicable o no encontrado'];
            }
            
            // Crear GAP con avance inicial en 0
            $sql_gap = "INSERT INTO gap_items 
                        (soa_id, brecha, objetivo, prioridad, avance, fecha_estimada_cierre, responsable, estado_gap) 
                        VALUES (:soa_id, :brecha, :objetivo, :prioridad, 0, :fecha_estimada_cierre, :responsable, 'activo')";
            
            $stmt_gap = $this->db->prepare($sql_gap);
            $stmt_gap->bindValue(':soa_id', $soa_id, PDO::PARAM_INT);
            $stmt_gap->bindValue(':brecha', $datos['brecha'], PDO::PARAM_STR);
            $stmt_gap->bindValue(':objetivo', $datos['objetivo'] ?? null, PDO::PARAM_STR);
            $stmt_gap->bindValue(':prioridad', $datos['prioridad'] ?? 'media', PDO::PARAM_STR);
            $stmt_gap->bindValue(':fecha_estimada_cierre', $datos['fecha_estimada_cierre'] ?? null, PDO::PARAM_STR);
            $stmt_gap->bindValue(':responsable', $datos['responsable'] ?? null, PDO::PARAM_STR);
            
            $stmt_gap->execute();
            $gap_id = $this->db->lastInsertId();
            
            // Crear acciones
            $sql_accion = "INSERT INTO acciones 
                           (gap_id, descripcion, responsable, fecha_compromiso, fecha_inicio, estado) 
                           VALUES (:gap_id, :descripcion, :responsable, :fecha_compromiso, :fecha_inicio, 'pendiente')";
            
            $fecha_inicio = date('Y-m-d');
            
            foreach ($acciones as $accion) {
                $stmt_accion = $this->db->prepare($sql_accion);
                $stmt_accion->bindValue(':gap_id', $gap_id, PDO::PARAM_INT);
                $stmt_accion->bindValue(':descripcion', $accion['descripcion'], PDO::PARAM_STR);
                $stmt_accion->bindValue(':responsable', $accion['responsable'] ?? null, PDO::PARAM_STR);
                $stmt_accion->bindValue(':fecha_compromiso', $accion['fecha_compromiso'], PDO::PARAM_STR);
                $stmt_accion->bindValue(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
                
                $stmt_accion->execute();
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'gap_id' => $gap_id
            ];
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('Error en Gap::crearConAcciones: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Actualizar GAP (sin modificar avance manualmente)
     */
    public function actualizar($gap_id, $datos) {
        try {
            $sql = "UPDATE gap_items SET 
                        brecha = :brecha,
                        objetivo = :objetivo,
                        prioridad = :prioridad,
                        fecha_estimada_cierre = :fecha_estimada_cierre,
                        responsable = :responsable
                    WHERE id = :gap_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':brecha', $datos['brecha'], PDO::PARAM_STR);
            $stmt->bindValue(':objetivo', $datos['objetivo'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':prioridad', $datos['prioridad'], PDO::PARAM_STR);
            $stmt->bindValue(':fecha_estimada_cierre', $datos['fecha_estimada_cierre'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':responsable', $datos['responsable'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':gap_id', $gap_id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Recalcular avance automáticamente
            $this->actualizarAvance($gap_id);
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            error_log('Error en Gap::actualizar: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Eliminar GAP (soft delete en cascada)
     */
    public function eliminar($gap_id) {
        try {
            $this->db->beginTransaction();
            
            // Soft delete del GAP
            $sql_gap = "UPDATE gap_items SET estado_gap = 'eliminado' WHERE id = :gap_id";
            $stmt_gap = $this->db->prepare($sql_gap);
            $stmt_gap->bindValue(':gap_id', $gap_id, PDO::PARAM_INT);
            $stmt_gap->execute();
            
            // Soft delete en cascada de acciones
            $sql_acciones = "UPDATE acciones SET estado = 'inactiva' WHERE gap_id = :gap_id";
            $stmt_acciones = $this->db->prepare($sql_acciones);
            $stmt_acciones->bindValue(':gap_id', $gap_id, PDO::PARAM_INT);
            $stmt_acciones->execute();
            
            $this->db->commit();
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('Error en Gap::eliminar: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener acciones de un GAP (excluye inactivas)
     */
    public function getAcciones($gap_id) {
        try {
            $sql = "SELECT * FROM acciones 
                    WHERE gap_id = :gap_id AND estado != 'inactiva'
                    ORDER BY fecha_compromiso ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':gap_id', $gap_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log('Error en Gap::getAcciones: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Crear acción correctiva
     */
    public function crearAccion($datos) {
        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO acciones 
                    (gap_id, descripcion, responsable, fecha_compromiso, fecha_inicio, estado) 
                    VALUES (:gap_id, :descripcion, :responsable, :fecha_compromiso, :fecha_inicio, 'pendiente')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':gap_id', $datos['gap_id'], PDO::PARAM_INT);
            $stmt->bindValue(':descripcion', $datos['descripcion'], PDO::PARAM_STR);
            $stmt->bindValue(':responsable', $datos['responsable'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_compromiso', $datos['fecha_compromiso'], PDO::PARAM_STR);
            
            $fecha_inicio = date('Y-m-d');
            $stmt->bindValue(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
            
            $stmt->execute();
            
            // Recalcular avance del GAP
            $this->actualizarAvance($datos['gap_id']);
            
            $this->db->commit();
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('Error en Gap::crearAccion: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener estadísticas de GAPs
     */
    public function getEstadisticas($empresa_id) {
        try {
            $gaps = $this->getAll($empresa_id);
            
            $stats = [
                'total' => count($gaps),
                'alta' => 0,
                'media' => 0,
                'baja' => 0,
                'cerrados' => 0,
                'avance_promedio' => 0
            ];
            
            $suma_avance = 0;
            
            foreach ($gaps as $gap) {
                $stats[$gap['prioridad']]++;
                
                if ($gap['avance'] >= 100 || $gap['fecha_real_cierre']) {
                    $stats['cerrados']++;
                }
                
                $suma_avance += $gap['avance'];
            }
            
            $stats['avance_promedio'] = $stats['total'] > 0 ? round($suma_avance / $stats['total'], 1) : 0;
            
            return $stats;
            
        } catch (\Exception $e) {
            error_log('Error en Gap::getEstadisticas: ' . $e->getMessage());
            return [];
        }
    }
}
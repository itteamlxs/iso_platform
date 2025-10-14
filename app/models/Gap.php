<?php

namespace App\Models;


use PDO;

/**
 * Gap Model
 * Gestión de análisis de brechas
 */
class Gap {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todos los GAPs de una empresa (solo activos)
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
                        (SELECT COUNT(*) FROM acciones WHERE gap_id = g.id) as total_acciones,
                        (SELECT COUNT(*) FROM acciones WHERE gap_id = g.id AND estado = 'completada') as acciones_completadas
                    FROM gap_items g
                    INNER JOIN soa_entries s ON g.soa_id = s.id
                    INNER JOIN controles c ON s.control_id = c.id
                    INNER JOIN controles_dominio cd ON c.dominio_id = cd.id
                    WHERE s.empresa_id = :empresa_id AND g.estado_gap = 'activo'";
            
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
     * Obtener GAP por ID
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
                    WHERE g.id = :gap_id AND g.estado_gap = 'activo'";
            
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
     * Crear nuevo GAP
     * Recibe control_id, busca el soa_id correspondiente
     */
    public function crear($datos) {
        try {
            $empresa_id = $datos['empresa_id'] ?? 1;
            
            $sql_soa = "SELECT id FROM soa_entries 
                        WHERE control_id = :control_id AND empresa_id = :empresa_id 
                        LIMIT 1";
            
            $stmt_soa = $this->db->prepare($sql_soa);
            $stmt_soa->bindParam(':control_id', $datos['control_id'], PDO::PARAM_INT);
            $stmt_soa->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);
            $stmt_soa->execute();
            
            $soa_result = $stmt_soa->fetch(PDO::FETCH_ASSOC);
            
            if (!$soa_result) {
                return ['success' => false, 'error' => 'No se encontró el registro SOA para este control'];
            }
            
            $soa_id = $soa_result['id'];
            
            $sql = "INSERT INTO gap_items 
                    (soa_id, brecha, objetivo, prioridad, avance, fecha_estimada_cierre, responsable, estado_gap) 
                    VALUES (:soa_id, :brecha, :objetivo, :prioridad, :avance, :fecha_estimada_cierre, :responsable, 'activo')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':soa_id', $soa_id, PDO::PARAM_INT);
            $stmt->bindParam(':brecha', $datos['brecha'], PDO::PARAM_STR);
            $stmt->bindParam(':objetivo', $datos['objetivo'], PDO::PARAM_STR);
            $stmt->bindParam(':prioridad', $datos['prioridad'] ?? 'media', PDO::PARAM_STR);
            $stmt->bindParam(':avance', 0, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_estimada_cierre', $datos['fecha_estimada_cierre'], PDO::PARAM_STR);
            $stmt->bindParam(':responsable', $datos['responsable'], PDO::PARAM_STR);
            
            $stmt->execute();
            
            return [
                'success' => true,
                'gap_id' => $this->db->lastInsertId()
            ];
            
        } catch (\Exception $e) {
            error_log('Error en Gap::crear: ' . $e->getMessage());
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
            
            $sql_soa = "SELECT id FROM soa_entries 
                        WHERE control_id = :control_id AND empresa_id = :empresa_id 
                        LIMIT 1";
            
            $stmt_soa = $this->db->prepare($sql_soa);
            $stmt_soa->bindParam(':control_id', $datos['control_id'], PDO::PARAM_INT);
            $stmt_soa->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);
            $stmt_soa->execute();
            
            $soa_result = $stmt_soa->fetch(PDO::FETCH_ASSOC);
            
            if (!$soa_result) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'No se encontró el registro SOA para este control'];
            }
            
            $soa_id = $soa_result['id'];
            
            $sql_gap = "INSERT INTO gap_items 
                        (soa_id, brecha, objetivo, prioridad, avance, fecha_estimada_cierre, responsable, estado_gap) 
                        VALUES (:soa_id, :brecha, :objetivo, :prioridad, :avance, :fecha_estimada_cierre, :responsable, 'activo')";
            
            $stmt_gap = $this->db->prepare($sql_gap);
            $stmt_gap->bindValue(':soa_id', $soa_id, PDO::PARAM_INT);
            $stmt_gap->bindValue(':brecha', $datos['brecha'], PDO::PARAM_STR);
            $stmt_gap->bindValue(':objetivo', $datos['objetivo'] ?? null, PDO::PARAM_STR);
            $stmt_gap->bindValue(':prioridad', $datos['prioridad'] ?? 'media', PDO::PARAM_STR);
            $stmt_gap->bindValue(':avance', 0, PDO::PARAM_INT);
            $stmt_gap->bindValue(':fecha_estimada_cierre', $datos['fecha_estimada_cierre'] ?? null, PDO::PARAM_STR);
            $stmt_gap->bindValue(':responsable', $datos['responsable'] ?? null, PDO::PARAM_STR);
            
            $stmt_gap->execute();
            $gap_id = $this->db->lastInsertId();
            
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
     * Actualizar GAP con lógica híbrida de avance
     */
    public function actualizar($gap_id, $datos) {
        try {
            $sql = "UPDATE gap_items SET 
                        brecha = :brecha,
                        objetivo = :objetivo,
                        prioridad = :prioridad,
                        avance = :avance,
                        fecha_estimada_cierre = :fecha_estimada_cierre,
                        fecha_real_cierre = :fecha_real_cierre,
                        responsable = :responsable
                    WHERE id = :gap_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':brecha', $datos['brecha'], PDO::PARAM_STR);
            $stmt->bindValue(':objetivo', $datos['objetivo'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':prioridad', $datos['prioridad'], PDO::PARAM_STR);
            $stmt->bindValue(':avance', $datos['avance'], PDO::PARAM_INT);
            $stmt->bindValue(':fecha_estimada_cierre', $datos['fecha_estimada_cierre'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_real_cierre', $datos['fecha_real_cierre'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':gap_id', $gap_id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            error_log('Error en Gap::actualizar: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Eliminar GAP (soft delete - cambiar estado a eliminado)
     */
    public function eliminar($gap_id) {
        try {
            $sql = "UPDATE gap_items SET estado_gap = 'eliminado' WHERE id = :gap_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':gap_id', $gap_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            error_log('Error en Gap::eliminar: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener acciones de un GAP
     */
    public function getAcciones($gap_id) {
        try {
            $sql = "SELECT * FROM acciones WHERE gap_id = :gap_id ORDER BY fecha_compromiso ASC";
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
            
            return ['success' => true];
            
        } catch (\Exception $e) {
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
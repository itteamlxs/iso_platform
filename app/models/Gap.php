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
     * Obtener todos los GAPs de una empresa
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
                        s.soa_id,
                        (SELECT COUNT(*) FROM acciones WHERE gap_id = g.id) as total_acciones,
                        (SELECT COUNT(*) FROM acciones WHERE gap_id = g.id AND estado = 'completada') as acciones_completadas
                    FROM gap_items g
                    INNER JOIN soa_entries s ON g.soa_id = s.id
                    INNER JOIN controles c ON s.control_id = c.id
                    INNER JOIN controles_dominio cd ON c.dominio_id = cd.id
                    WHERE s.empresa_id = ?";
            
            $params = [$empresa_id];
            
            // Filtro por prioridad
            if (!empty($filtros['prioridad'])) {
                $sql .= " AND g.prioridad = ?";
                $params[] = $filtros['prioridad'];
            }
            
            // Filtro por estado (según avance)
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
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
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
                    WHERE g.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$gap_id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Crear nuevo GAP
     */
    public function crear($datos) {
        try {
            $sql = "INSERT INTO gap_items 
                    (soa_id, brecha, objetivo, prioridad, avance, fecha_estimada_cierre, responsable) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $datos['soa_id'],
                $datos['brecha'],
                $datos['objetivo'] ?? null,
                $datos['prioridad'] ?? 'media',
                0,
                $datos['fecha_estimada_cierre'] ?? null,
                $datos['responsable'] ?? null
            ]);
            
            return [
                'success' => true,
                'gap_id' => $this->db->lastInsertId()
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Actualizar GAP
     */
    public function actualizar($gap_id, $datos) {
        try {
            $sql = "UPDATE gap_items SET 
                        brecha = ?,
                        objetivo = ?,
                        prioridad = ?,
                        avance = ?,
                        fecha_estimada_cierre = ?,
                        fecha_real_cierre = ?,
                        responsable = ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $datos['brecha'],
                $datos['objetivo'] ?? null,
                $datos['prioridad'],
                $datos['avance'],
                $datos['fecha_estimada_cierre'] ?? null,
                $datos['fecha_real_cierre'] ?? null,
                $datos['responsable'] ?? null,
                $gap_id
            ]);
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener acciones de un GAP
     */
    public function getAcciones($gap_id) {
        try {
            $sql = "SELECT * FROM acciones WHERE gap_id = ? ORDER BY fecha_compromiso ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$gap_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
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
                    VALUES (?, ?, ?, ?, ?, 'pendiente')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $datos['gap_id'],
                $datos['descripcion'],
                $datos['responsable'] ?? null,
                $datos['fecha_compromiso'] ?? null,
                date('Y-m-d')
            ]);
            
            return ['success' => true];
            
        } catch (\Exception $e) {
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
            return [];
        }
    }
    
}
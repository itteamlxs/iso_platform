<?php
namespace App\Models;

use PDO;

/**
 * Control Model
 * Gestión de controles ISO 27001
 */
class Control {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todos los controles con estado de SOA
     */
    public function getAll($empresa_id, $filtros = []) {
        try {
            $sql = "SELECT 
                        c.id,
                        c.codigo,
                        c.nombre,
                        c.descripcion,
                        cd.nombre as dominio,
                        cd.codigo as dominio_codigo,
                        COALESCE(s.aplicable, 1) as aplicable,
                        COALESCE(s.estado, 'no_implementado') as estado,
                        s.justificacion,
                        (SELECT COUNT(*) FROM evidencias e WHERE e.control_id = c.id AND e.empresa_id = ?) as total_evidencias
                    FROM controles c
                    INNER JOIN controles_dominio cd ON c.dominio_id = cd.id
                    LEFT JOIN soa_entries s ON c.id = s.control_id AND s.empresa_id = ?
                    WHERE 1=1";
            
            $params = [$empresa_id, $empresa_id];
            
            // Filtro por dominio
            if (!empty($filtros['dominio'])) {
                $sql .= " AND c.dominio_id = ?";
                $params[] = $filtros['dominio'];
            }
            
            // Filtro por estado - SOLO si tiene valor real
            if (isset($filtros['estado']) && $filtros['estado'] !== '' && $filtros['estado'] !== null) {
                $sql .= " AND COALESCE(s.estado, 'no_implementado') = ?";
                $params[] = $filtros['estado'];
            }
            
            // Filtro por aplicabilidad - SOLO si tiene valor real
            if (isset($filtros['aplicable']) && $filtros['aplicable'] !== '' && $filtros['aplicable'] !== null) {
                $sql .= " AND COALESCE(s.aplicable, 1) = ?";
                $params[] = $filtros['aplicable'];
            }
            
            $sql .= " ORDER BY c.codigo ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Obtener control por ID con su SOA
     */
    public function getById($control_id, $empresa_id) {
        try {
            $sql = "SELECT 
                        c.*,
                        cd.nombre as dominio,
                        cd.codigo as dominio_codigo,
                        s.id as soa_id,
                        s.aplicable,
                        s.estado,
                        s.justificacion,
                        s.fecha_evaluacion,
                        s.evaluador
                    FROM controles c
                    INNER JOIN controles_dominio cd ON c.dominio_id = cd.id
                    LEFT JOIN soa_entries s ON c.id = s.control_id AND s.empresa_id = ?
                    WHERE c.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$empresa_id, $control_id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Actualizar estado de SOA para un control
     */
    public function updateSoa($soa_id, $datos) {
        try {
            $sql = "UPDATE soa_entries SET 
                        aplicable = ?,
                        estado = ?,
                        justificacion = ?,
                        fecha_evaluacion = NOW(),
                        evaluador = ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $datos['aplicable'],
                $datos['estado'],
                $datos['justificacion'] ?? null,
                $datos['evaluador'] ?? 'Sistema',
                $soa_id
            ]);
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener todos los dominios
     */
    public function getDominios() {
        try {
            $sql = "SELECT id, codigo, nombre FROM controles_dominio ORDER BY codigo";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }
    
}
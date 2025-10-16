<?php

namespace App\Models;

use PDO;

require_once __DIR__ . '/Database.php';

/**
 * Evidencia Model
 * Gestión de evidencias documentales
 */
class Evidencia {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todas las evidencias de una empresa
     */
    public function getAll($empresa_id, $filtros = []) {
        try {
            $sql = "SELECT 
                        e.id,
                        e.descripcion,
                        e.archivo,
                        e.fecha_subida,
                        e.estado_validacion,
                        e.comentarios_validacion,
                        e.fecha_validacion,
                        c.codigo,
                        c.nombre as control,
                        te.nombre as tipo_evidencia
                    FROM evidencias e
                    INNER JOIN controles c ON e.control_id = c.id
                    LEFT JOIN tipos_evidencia te ON e.tipo_evidencia_id = te.id
                    WHERE e.empresa_id = :empresa_id";
            
            // Filtro por control
            if (!empty($filtros['control_id'])) {
                $sql .= " AND e.control_id = :control_id";
            }
            
            // Filtro por estado
            if (!empty($filtros['estado'])) {
                $sql .= " AND e.estado_validacion = :estado";
            }
            
            // Filtro por tipo
            if (!empty($filtros['tipo'])) {
                $sql .= " AND e.tipo_evidencia_id = :tipo";
            }
            
            $sql .= " ORDER BY e.fecha_subida DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresa_id, PDO::PARAM_INT);
            
            if (!empty($filtros['control_id'])) {
                $stmt->bindValue(':control_id', $filtros['control_id'], PDO::PARAM_INT);
            }
            
            if (!empty($filtros['estado'])) {
                $stmt->bindValue(':estado', $filtros['estado'], PDO::PARAM_STR);
            }
            
            if (!empty($filtros['tipo'])) {
                $stmt->bindValue(':tipo', $filtros['tipo'], PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log('Error en Evidencia::getAll: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener evidencia por ID
     */
    public function getById($evidencia_id) {
        try {
            $sql = "SELECT 
                        e.*,
                        c.codigo,
                        c.nombre as control,
                        te.nombre as tipo_evidencia
                    FROM evidencias e
                    INNER JOIN controles c ON e.control_id = c.id
                    LEFT JOIN tipos_evidencia te ON e.tipo_evidencia_id = te.id
                    WHERE e.id = :evidencia_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':evidencia_id', $evidencia_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log('Error en Evidencia::getById: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Crear nueva evidencia
     */
    public function crear($datos) {
        try {
            $sql = "INSERT INTO evidencias 
                    (empresa_id, control_id, descripcion, tipo_evidencia_id, archivo, fecha_subida, estado_validacion) 
                    VALUES (:empresa_id, :control_id, :descripcion, :tipo_evidencia_id, :archivo, NOW(), 'pendiente')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':empresa_id', $datos['empresa_id'], PDO::PARAM_INT);
            $stmt->bindValue(':control_id', $datos['control_id'], PDO::PARAM_INT);
            $stmt->bindValue(':descripcion', $datos['descripcion'], PDO::PARAM_STR);
            $stmt->bindValue(':tipo_evidencia_id', $datos['tipo_evidencia_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':archivo', $datos['archivo'], PDO::PARAM_STR);
            
            $stmt->execute();
            
            return [
                'success' => true,
                'evidencia_id' => $this->db->lastInsertId()
            ];
            
        } catch (\Exception $e) {
            error_log('Error en Evidencia::crear: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Actualizar validación de evidencia
     */
    public function validar($evidencia_id, $datos) {
        try {
            $sql = "UPDATE evidencias SET 
                        estado_validacion = :estado,
                        comentarios_validacion = :comentarios,
                        validado_por = :validado_por,
                        fecha_validacion = NOW()
                    WHERE id = :evidencia_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':estado', $datos['estado'], PDO::PARAM_STR);
            $stmt->bindValue(':comentarios', $datos['comentarios'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':validado_por', $datos['validado_por'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':evidencia_id', $evidencia_id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            error_log('Error en Evidencia::validar: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Eliminar evidencia (físicamente)
     */
    public function eliminar($evidencia_id) {
        try {
            // Obtener archivo antes de eliminar para borrarlo del servidor
            $evidencia = $this->getById($evidencia_id);
            
            if ($evidencia && file_exists(UPLOAD_PATH . '/' . $evidencia['archivo'])) {
                unlink(UPLOAD_PATH . '/' . $evidencia['archivo']);
            }
            
            $sql = "DELETE FROM evidencias WHERE id = :evidencia_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':evidencia_id', $evidencia_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            error_log('Error en Evidencia::eliminar: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener tipos de evidencia activos
     */
    public function getTipos() {
        try {
            $sql = "SELECT id, nombre, descripcion FROM tipos_evidencia WHERE activo = 1 ORDER BY nombre";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Error en Evidencia::getTipos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener estadísticas de evidencias
     */
    public function getEstadisticas($empresa_id) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN estado_validacion = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                        SUM(CASE WHEN estado_validacion = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
                        SUM(CASE WHEN estado_validacion = 'rechazada' THEN 1 ELSE 0 END) as rechazadas
                    FROM evidencias 
                    WHERE empresa_id = :empresa_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresa_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log('Error en Evidencia::getEstadisticas: ' . $e->getMessage());
            return [];
        }
    }
}
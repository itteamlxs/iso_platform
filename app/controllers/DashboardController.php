<?php
namespace App\Controllers;

use App\Models\Database;
use PDO;

/**
 * Dashboard Controller
 * Maneja la lógica del dashboard principal
 */
class DashboardController {
    
    private $db;
    private $empresa_id;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        // Empresa por defecto (ID 1) - cambiar cuando tengamos sesiones
        $this->empresa_id = isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : 1;
    }
    
    /**
     * Obtener métricas generales de cumplimiento
     */
    public function getMetricas() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_controles,
                        SUM(CASE WHEN estado = 'implementado' THEN 1 ELSE 0 END) as implementados,
                        SUM(CASE WHEN estado = 'parcial' THEN 1 ELSE 0 END) as parciales,
                        SUM(CASE WHEN estado = 'no_implementado' THEN 1 ELSE 0 END) as no_implementados,
                        SUM(CASE WHEN aplicable = FALSE THEN 1 ELSE 0 END) as no_aplicables
                    FROM soa_entries 
                    WHERE empresa_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->empresa_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular porcentaje
            $aplicables = $data['total_controles'] - $data['no_aplicables'];
            $porcentaje = $aplicables > 0 ? round(($data['implementados'] / $aplicables) * 100, 2) : 0;
            
            return [
                'success' => true,
                'data' => $data,
                'porcentaje' => $porcentaje
            ];
            
        } catch (\Exception $e) {
            log_message('Error en getMetricas: ' . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => 'Error al obtener métricas'
            ];
        }
    }
    
    /**
     * Obtener controles agrupados por dominio
     */
    public function getControlsPorDominio() {
        try {
            $sql = "SELECT 
                        cd.nombre as dominio,
                        cd.codigo,
                        COUNT(c.id) as total,
                        SUM(CASE WHEN s.estado = 'implementado' THEN 1 ELSE 0 END) as implementados
                    FROM controles_dominio cd
                    LEFT JOIN controles c ON cd.id = c.dominio_id
                    LEFT JOIN soa_entries s ON c.id = s.control_id AND s.empresa_id = ?
                    GROUP BY cd.id
                    ORDER BY cd.codigo";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->empresa_id]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $data
            ];
            
        } catch (\Exception $e) {
            log_message('Error en getControlsPorDominio: ' . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => 'Error al obtener controles por dominio'
            ];
        }
    }
    
    /**
     * Obtener resumen de requerimientos base
     */
    public function getRequerimientos() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
                        SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes
                    FROM empresa_requerimientos 
                    WHERE empresa_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->empresa_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $data
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener requerimientos'
            ];
        }
    }
    
    /**
     * Obtener GAPs de alta prioridad
     */
    public function getGapsPrioritarios() {
        try {
            $sql = "SELECT 
                        g.id,
                        c.codigo,
                        c.nombre as control,
                        g.brecha,
                        g.avance
                    FROM gap_items g
                    JOIN soa_entries s ON g.soa_id = s.id
                    JOIN controles c ON s.control_id = c.id
                    WHERE s.empresa_id = ? AND g.prioridad = 'alta'
                    ORDER BY g.avance ASC
                    LIMIT 5";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->empresa_id]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $data
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener GAPs'
            ];
        }
    }
    
    /**
     * Obtener últimas evidencias
     */
    public function getUltimasEvidencias() {
        try {
            $sql = "SELECT 
                        e.id,
                        e.descripcion,
                        c.codigo,
                        c.nombre as control,
                        e.fecha_subida,
                        e.estado_validacion
                    FROM evidencias e
                    JOIN controles c ON e.control_id = c.id
                    WHERE e.empresa_id = ?
                    ORDER BY e.fecha_subida DESC
                    LIMIT 5";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->empresa_id]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $data
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener evidencias'
            ];
        }
    }
    
    /**
     * Obtener acciones pendientes
     */
    public function getAccionesPendientes() {
        try {
            $sql = "SELECT 
                        a.id,
                        a.descripcion,
                        a.responsable,
                        a.fecha_compromiso,
                        c.codigo,
                        c.nombre as control
                    FROM acciones a
                    JOIN gap_items g ON a.gap_id = g.id
                    JOIN soa_entries s ON g.soa_id = s.id
                    JOIN controles c ON s.control_id = c.id
                    WHERE s.empresa_id = ? AND a.estado IN ('pendiente', 'en_progreso')
                    ORDER BY a.fecha_compromiso ASC
                    LIMIT 5";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->empresa_id]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $data
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener acciones'
            ];
        }
    }
    
}
<?php
namespace App\Controllers;

use App\Models\Requerimiento;

require_once __DIR__ . '/../models/Requerimiento.php';

/**
 * Requerimientos Controller
 * Maneja la lógica de requerimientos base ISO 27001
 * VERSIÓN 2.0 - Con gestión de relaciones bidireccionales
 */
class RequerimientosController {
    
    private $model;
    private $empresa_id;
    
    public function __construct() {
        $this->model = new Requerimiento();
        $this->empresa_id = isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : 1;
    }
    
    /**
     * Obtener lista de requerimientos
     */
    public function listar() {
        $requerimientos = $this->model->getAll($this->empresa_id);
        
        // Debug: verificar estructura
        foreach ($requerimientos as &$req) {
            if (!isset($req['requerimiento_base_id']) && isset($req['requerimiento_id'])) {
                $req['requerimiento_base_id'] = $req['requerimiento_id'];
            }
        }
        
        return $requerimientos;
    }
    
    /**
     * Obtener detalle de un requerimiento
     */
    public function detalle($requerimiento_id) {
        return $this->model->getById($requerimiento_id);
    }
    
    /**
     * Obtener controles asociados a un requerimiento
     */
    public function getControlesAsociados($requerimiento_base_id) {
        error_log("RequerimientosController::getControlesAsociados - requerimiento_base_id: $requerimiento_base_id, empresa_id: {$this->empresa_id}");
        
        $controles = $this->model->getControlesAsociados($requerimiento_base_id, $this->empresa_id);
        
        error_log("RequerimientosController::getControlesAsociados - Controles encontrados: " . count($controles));
        
        return $controles;
    }
    
    /**
     * Obtener evidencias de controles asociados
     */
    public function getEvidenciasDeControles($requerimiento_base_id) {
        return $this->model->getEvidenciasDeControles($requerimiento_base_id, $this->empresa_id);
    }
    
    /**
     * Aplicar requerimiento a controles (marcar como implementado)
     */
    public function aplicarAControles($requerimiento_base_id) {
        return $this->model->aplicarAControles($requerimiento_base_id, $this->empresa_id);
    }
    
    /**
     * Verificar si debe marcarse como completado automáticamente
     */
    public function verificarCompletitudAutomatica($requerimiento_base_id) {
        return $this->model->verificarCompletitudAutomatica($requerimiento_base_id, $this->empresa_id);
    }
    
    /**
     * Actualizar requerimiento
     */
    public function actualizar($requerimiento_id, $datos) {
        return $this->model->actualizar($requerimiento_id, $datos);
    }
    
    /**
     * Obtener estadísticas
     */
    public function getEstadisticas() {
        return $this->model->getEstadisticas($this->empresa_id);
    }
}
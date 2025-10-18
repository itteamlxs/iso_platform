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
        return $this->model->getAll($this->empresa_id);
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
        return $this->model->getControlesAsociados($requerimiento_base_id, $this->empresa_id);
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
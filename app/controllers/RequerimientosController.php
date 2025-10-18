<?php
namespace App\Controllers;

use App\Models\Requerimiento;

require_once __DIR__ . '/../models/Requerimiento.php';

/**
 * Requerimientos Controller
 * Maneja la lógica de requerimientos base ISO 27001
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
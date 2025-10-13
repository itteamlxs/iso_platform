<?php
namespace App\Controllers;

use App\Models\Database;
use App\Models\Gap;

/**
 * Gap Controller
 * Maneja la lógica de análisis de brechas
 */
class GapController {
    
    private $model;
    private $empresa_id;
    
    public function __construct() {
        $this->model = new Gap();
        $this->empresa_id = isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : 1;
    }
    
    /**
     * Listar GAPs con filtros
     */
    public function listar($filtros = []) {
        return $this->model->getAll($this->empresa_id, $filtros);
    }
    
    /**
     * Obtener detalle de GAP
     */
    public function detalle($gap_id) {
        return $this->model->getById($gap_id);
    }
    
    /**
     * Crear nuevo GAP
     */
    public function crear($datos) {
        return $this->model->crear($datos);
    }
    
    /**
     * Actualizar GAP
     */
    public function actualizar($gap_id, $datos) {
        return $this->model->actualizar($gap_id, $datos);
    }
    
    /**
     * Obtener acciones de un GAP
     */
    public function getAcciones($gap_id) {
        return $this->model->getAcciones($gap_id);
    }
    
    /**
     * Crear acción correctiva
     */
    public function crearAccion($datos) {
        return $this->model->crearAccion($datos);
    }
    
    /**
     * Obtener estadísticas
     */
    public function getEstadisticas() {
        return $this->model->getEstadisticas($this->empresa_id);
    }
    
}
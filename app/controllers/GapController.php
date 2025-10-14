<?php
namespace App\Controllers;

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
    
    public function listar($filtros = []) {
        return $this->model->getAll($this->empresa_id, $filtros);
    }
    
    public function detalle($gap_id) {
        return $this->model->getById($gap_id);
    }
    
    public function crear($datos) {
        return $this->model->crear($datos);
    }
    
    public function crearConAcciones($datos, $acciones) {
        return $this->model->crearConAcciones($datos, $acciones);
    }
    
    public function actualizar($gap_id, $datos) {
        return $this->model->actualizar($gap_id, $datos);
    }
    
    public function eliminar($gap_id) {
        return $this->model->eliminar($gap_id);
    }
    
    public function getAcciones($gap_id) {
        return $this->model->getAcciones($gap_id);
    }
    
    public function crearAccion($datos) {
        return $this->model->crearAccion($datos);
    }
    
    public function getEstadisticas() {
        return $this->model->getEstadisticas($this->empresa_id);
    }
}
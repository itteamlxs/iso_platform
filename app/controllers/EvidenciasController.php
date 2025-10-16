<?php
namespace App\Controllers;

use App\Models\Evidencia;

/**
 * Evidencias Controller
 * Maneja la lógica de evidencias documentales
 */
class EvidenciasController {
    
    private $model;
    private $empresa_id;
    
    public function __construct() {
        $this->model = new Evidencia();
        $this->empresa_id = isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : 1;
    }
    
    public function listar($filtros = []) {
        return $this->model->getAll($this->empresa_id, $filtros);
    }
    
    public function detalle($evidencia_id) {
        return $this->model->getById($evidencia_id);
    }
    
    public function crear($datos) {
        $datos['empresa_id'] = $this->empresa_id;
        return $this->model->crear($datos);
    }
    
    public function validar($evidencia_id, $datos) {
        return $this->model->validar($evidencia_id, $datos);
    }
    
    public function eliminar($evidencia_id) {
        return $this->model->eliminar($evidencia_id);
    }
    
    public function getTipos() {
        return $this->model->getTipos();
    }
    
    public function getEstadisticas() {
        return $this->model->getEstadisticas($this->empresa_id);
    }
}
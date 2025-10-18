<?php
namespace App\Controllers;

use App\Models\Gap;

/**
 * Gap Controller
 * Maneja la lógica de análisis de brechas
 * VERSIÓN 2.0 - Optimizado con delegación de lógica al modelo
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
     * Obtener detalle de un GAP
     */
    public function detalle($gap_id) {
        return $this->model->getById($gap_id);
    }
    
    /**
     * Crear GAP con acciones correctivas
     */
    public function crearConAcciones($datos, $acciones) {
        $datos['empresa_id'] = $this->empresa_id;
        return $this->model->crearConAcciones($datos, $acciones);
    }
    
    /**
     * Actualizar GAP (sin avance manual)
     */
    public function actualizar($gap_id, $datos) {
        return $this->model->actualizar($gap_id, $datos);
    }
    
    /**
     * Eliminar GAP (soft delete en cascada)
     */
    public function eliminar($gap_id) {
        return $this->model->eliminar($gap_id);
    }
    
    /**
     * Obtener acciones de un GAP
     */
    public function getAcciones($gap_id) {
        return $this->model->getAcciones($gap_id);
    }
    
    /**
     * Crear nueva acción correctiva
     */
    public function crearAccion($datos) {
        return $this->model->crearAccion($datos);
    }
    
    /**
     * Actualizar avance de GAP (llamado desde cambios de acciones)
     */
    public function actualizarAvance($gap_id) {
        return $this->model->actualizarAvance($gap_id);
    }
    
    /**
     * Obtener estadísticas de GAPs
     */
    public function getEstadisticas() {
        return $this->model->getEstadisticas($this->empresa_id);
    }
}
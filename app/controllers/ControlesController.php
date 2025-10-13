<?php
namespace App\Controllers;

use App\Models\Database;
use App\Models\Control;

/**
 * Controles Controller
 * Maneja la lógica de los controles ISO 27001
 */
class ControlesController {
    
    private $model;
    private $empresa_id;
    
    public function __construct() {
        $this->model = new Control();
        $this->empresa_id = isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : 1;
    }
    
    /**
     * Obtener lista de controles con filtros
     */
    public function listar($filtros = []) {
        return $this->model->getAll($this->empresa_id, $filtros);
    }
    
    /**
     * Obtener detalle de un control
     */
    public function detalle($control_id) {
        return $this->model->getById($control_id, $this->empresa_id);
    }
    
    /**
     * Actualizar evaluación de control
     */
    public function actualizarEvaluacion($soa_id, $datos) {
        return $this->model->updateSoa($soa_id, $datos);
    }
    
    /**
     * Obtener dominios para filtros
     */
    public function getDominios() {
        return $this->model->getDominios();
    }
    
    /**
     * Obtener estadísticas de controles
     */
    public function getEstadisticas() {
        $controles = $this->listar();
        
        $stats = [
            'total' => count($controles),
            'implementados' => 0,
            'parciales' => 0,
            'no_implementados' => 0,
            'no_aplicables' => 0
        ];
        
        foreach ($controles as $control) {
            if ($control['aplicable'] == 0) {
                $stats['no_aplicables']++;
            } else {
                switch ($control['estado']) {
                    case 'implementado':
                        $stats['implementados']++;
                        break;
                    case 'parcial':
                        $stats['parciales']++;
                        break;
                    case 'no_implementado':
                        $stats['no_implementados']++;
                        break;
                }
            }
        }
        
        return $stats;
    }
    
}
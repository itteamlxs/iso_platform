<?php
namespace App\Controllers;

use App\Models\Database;
use App\Models\Control;
use App\Helpers\Security;
use App\Helpers\Logger;

require_once __DIR__ . '/../models/Control.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Logger.php';

/**
 * Controles Controller
 * Maneja la lógica de los controles ISO 27001
 * VERSIÓN 3.0 - Enhanced security with IDOR protection
 */
class ControlesController {
    
    private $model;
    private $empresa_id;
    
    public function __construct() {
        $this->model = new Control();
        
        // Obtener empresa_id de sesión y validar
        $this->empresa_id = $_SESSION['empresa_id'] ?? null;
        
        if ($this->empresa_id === null) {
            Logger::security('ControlesController accessed without empresa_id', [
                'user_id' => $_SESSION['usuario_id'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $_SESSION['mensaje'] = 'No tiene una empresa asignada';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
        
        // Validar que empresa_id sea un entero positivo
        $this->empresa_id = Security::sanitize($this->empresa_id, 'int');
        
        if ($this->empresa_id <= 0) {
            Logger::security('Invalid empresa_id in ControlesController', [
                'empresa_id' => $this->empresa_id,
                'user_id' => $_SESSION['usuario_id'] ?? null
            ]);
            
            $_SESSION['mensaje'] = 'ID de empresa inválido';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
    }
    
    /**
     * Obtener lista de controles con filtros
     * Con sanitización de filtros
     */
    public function listar($filtros = []) {
        try {
            // Sanitizar filtros
            $filtrosSanitizados = [];
            
            if (isset($filtros['dominio'])) {
                $filtrosSanitizados['dominio'] = Security::sanitize($filtros['dominio'], 'int');
            }
            
            if (isset($filtros['estado']) && $filtros['estado'] !== '') {
                $estadosPermitidos = ['implementado', 'parcial', 'no_implementado'];
                $estado = Security::sanitize($filtros['estado'], 'string');
                
                if (in_array($estado, $estadosPermitidos)) {
                    $filtrosSanitizados['estado'] = $estado;
                } else {
                    Logger::warning('Invalid estado filter attempted', [
                        'estado' => $filtros['estado'],
                        'user_id' => $_SESSION['usuario_id'] ?? null
                    ]);
                }
            }
            
            if (isset($filtros['aplicable']) && $filtros['aplicable'] !== '') {
                $filtrosSanitizados['aplicable'] = Security::sanitize($filtros['aplicable'], 'int');
                
                // Solo permitir 0 o 1
                if (!in_array($filtrosSanitizados['aplicable'], [0, 1])) {
                    unset($filtrosSanitizados['aplicable']);
                }
            }
            
            // Pasar empresa_id validada y filtros sanitizados
            return $this->model->getAll($this->empresa_id, $filtrosSanitizados);
            
        } catch (\Exception $e) {
            Logger::error('Error listing controls', [
                'empresa_id' => $this->empresa_id,
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['usuario_id'] ?? null
            ]);
            
            return [];
        }
    }
    
    /**
     * Obtener detalle de un control
     * Con validación de ownership
     */
    public function detalle($control_id) {
        try {
            // Sanitizar control_id
            $control_id = Security::sanitize($control_id, 'int');
            
            if ($control_id <= 0) {
                Logger::warning('Invalid control_id in detalle', [
                    'control_id' => $control_id,
                    'user_id' => $_SESSION['usuario_id'] ?? null
                ]);
                return null;
            }
            
            // Obtener control con empresa_id validada
            $control = $this->model->getById($control_id, $this->empresa_id);
            
            if (!$control) {
                Logger::warning('Control not found or access denied', [
                    'control_id' => $control_id,
                    'empresa_id' => $this->empresa_id,
                    'user_id' => $_SESSION['usuario_id'] ?? null
                ]);
                return null;
            }
            
            // IDOR Protection: Verificar que el SOA pertenece a la empresa del usuario
            // Nota: el modelo ya filtra por empresa_id, pero validamos adicionalmente
            if (isset($control['soa_id'])) {
                $db = Database::getInstance()->getConnection();
                
                $sql = "SELECT empresa_id FROM soa_entries WHERE id = :soa_id LIMIT 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([':soa_id' => $control['soa_id']]);
                $soa = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($soa && !Security::validateOwnership($soa['empresa_id'], $this->empresa_id)) {
                    Logger::security('IDOR attempt: user tried to access control from another company', [
                        'control_id' => $control_id,
                        'user_empresa_id' => $this->empresa_id,
                        'soa_empresa_id' => $soa['empresa_id'],
                        'user_id' => $_SESSION['usuario_id'] ?? null,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    
                    return null;
                }
            }
            
            return $control;
            
        } catch (\Exception $e) {
            Logger::error('Error getting control detail', [
                'control_id' => $control_id,
                'empresa_id' => $this->empresa_id,
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['usuario_id'] ?? null
            ]);
            
            return null;
        }
    }
    
    /**
     * Actualizar evaluación de control
     * Con sanitización y validación de ownership
     */
    public function actualizarEvaluacion($soa_id, $datos) {
        try {
            // Sanitizar soa_id
            $soa_id = Security::sanitize($soa_id, 'int');
            
            if ($soa_id <= 0) {
                Logger::warning('Invalid soa_id in actualizarEvaluacion', [
                    'soa_id' => $soa_id,
                    'user_id' => $_SESSION['usuario_id'] ?? null
                ]);
                
                return ['success' => false, 'error' => 'ID inválido'];
            }
            
            // IDOR Protection: Verificar ownership del SOA entry
            $db = Database::getInstance()->getConnection();
            
            $sql = "SELECT empresa_id, control_id FROM soa_entries WHERE id = :soa_id LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':soa_id' => $soa_id]);
            $soa = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$soa) {
                Logger::warning('SOA entry not found', [
                    'soa_id' => $soa_id,
                    'user_id' => $_SESSION['usuario_id'] ?? null
                ]);
                
                return ['success' => false, 'error' => 'Registro no encontrado'];
            }
            
            if (!Security::validateOwnership($soa['empresa_id'], $this->empresa_id)) {
                Logger::security('IDOR attempt: user tried to update SOA from another company', [
                    'soa_id' => $soa_id,
                    'user_empresa_id' => $this->empresa_id,
                    'soa_empresa_id' => $soa['empresa_id'],
                    'user_id' => $_SESSION['usuario_id'] ?? null,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                return ['success' => false, 'error' => 'No tiene permisos para esta operación'];
            }
            
            // Sanitizar datos de entrada
            $datosSanitizados = [
                'aplicable' => Security::sanitize($datos['aplicable'] ?? 1, 'int'),
                'estado' => Security::sanitize($datos['estado'] ?? 'no_implementado', 'string'),
                'justificacion' => Security::sanitize($datos['justificacion'] ?? null, 'string'),
                'evaluador' => Security::sanitize($_SESSION['usuario_nombre'] ?? 'Sistema', 'string')
            ];
            
            // Validar valores permitidos
            if (!in_array($datosSanitizados['aplicable'], [0, 1])) {
                $datosSanitizados['aplicable'] = 1;
            }
            
            $estadosPermitidos = ['implementado', 'parcial', 'no_implementado'];
            if (!in_array($datosSanitizados['estado'], $estadosPermitidos)) {
                Logger::warning('Invalid estado value in actualizarEvaluacion', [
                    'estado' => $datos['estado'] ?? 'none',
                    'user_id' => $_SESSION['usuario_id'] ?? null
                ]);
                
                $datosSanitizados['estado'] = 'no_implementado';
            }
            
            // Actualizar
            $result = $this->model->updateSoa($soa_id, $datosSanitizados);
            
            if ($result['success']) {
                Logger::info('Control evaluation updated', [
                    'soa_id' => $soa_id,
                    'control_id' => $soa['control_id'],
                    'aplicable' => $datosSanitizados['aplicable'],
                    'estado' => $datosSanitizados['estado'],
                    'user_id' => $_SESSION['usuario_id'] ?? null
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Error updating control evaluation', [
                'soa_id' => $soa_id,
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['usuario_id'] ?? null
            ]);
            
            return ['success' => false, 'error' => 'Error al actualizar evaluación'];
        }
    }
    
    /**
     * Obtener dominios para filtros
     */
    public function getDominios() {
        try {
            return $this->model->getDominios();
        } catch (\Exception $e) {
            Logger::error('Error getting dominios', [
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['usuario_id'] ?? null
            ]);
            
            return [];
        }
    }
    
    /**
     * Obtener requerimientos asociados a un control
     */
    public function getRequerimientosAsociados($control_id) {
        try {
            // Sanitizar control_id
            $control_id = Security::sanitize($control_id, 'int');
            
            if ($control_id <= 0) {
                return [];
            }
            
            return $this->model->getRequerimientosAsociados($control_id);
            
        } catch (\Exception $e) {
            Logger::error('Error getting requerimientos asociados', [
                'control_id' => $control_id,
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['usuario_id'] ?? null
            ]);
            
            return [];
        }
    }
    
    /**
     * Obtener estadísticas de controles
     * Solo para la empresa del usuario
     */
    public function getEstadisticas() {
        try {
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
            
        } catch (\Exception $e) {
            Logger::error('Error getting estadisticas', [
                'empresa_id' => $this->empresa_id,
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['usuario_id'] ?? null
            ]);
            
            return [
                'total' => 0,
                'implementados' => 0,
                'parciales' => 0,
                'no_implementados' => 0,
                'no_aplicables' => 0
            ];
        }
    }
}
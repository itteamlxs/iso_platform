<?php
namespace App\Models;

use PDO;

require_once __DIR__ . '/Database.php';

/**
 * Empresa Model
 * Gestión de empresas
 */
class Empresa {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Crear nueva empresa
     */
    public function crear($datos) {
        try {
            $sql = "INSERT INTO empresas (nombre, ruc, direccion, telefono, email_contacto, sector) 
                    VALUES (:nombre, :ruc, :direccion, :telefono, :email, :sector)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':ruc' => $datos['ruc'] ?? null,
                ':direccion' => $datos['direccion'] ?? null,
                ':telefono' => $datos['telefono'] ?? null,
                ':email' => $datos['email'] ?? null,
                ':sector' => $datos['sector'] ?? null
            ]);
            
            return [
                'success' => true,
                'empresa_id' => $this->db->lastInsertId()
            ];
            
        } catch (\Exception $e) {
            log_message('Error al crear empresa: ' . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => 'Error al crear empresa'
            ];
        }
    }
    
    /**
     * Obtener empresa por ID
     */
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM empresas WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            log_message('Error al obtener empresa: ' . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    /**
     * Verificar si existe al menos una empresa
     */
    public function existeEmpresa() {
        try {
            $sql = "SELECT COUNT(*) as total FROM empresas";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] > 0;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
}
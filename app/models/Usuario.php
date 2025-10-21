<?php
namespace App\Models;

use PDO;
use App\Helpers\Security;

require_once __DIR__ . '/Database.php';

/**
 * Usuario Model
 * Gestión de usuarios y autenticación
 */
class Usuario {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Autenticar usuario
     */
    public function authenticate($email, $password) {
        try {
            $sql = "SELECT id, nombre, email, contrasena_hash, rol 
                    FROM usuarios 
                    WHERE email = :email 
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                return [
                    'success' => false,
                    'error' => 'Credenciales inválidas'
                ];
            }
            
            // Verificar contraseña
            if (!Security::verifyPassword($password, $usuario['contrasena_hash'])) {
                return [
                    'success' => false,
                    'error' => 'Credenciales inválidas'
                ];
            }
            
            // Autenticación exitosa
            return [
                'success' => true,
                'usuario' => [
                    'id' => $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'email' => $usuario['email'],
                    'rol' => $usuario['rol']
                ]
            ];
            
        } catch (\Exception $e) {
            error_log('Error en Usuario::authenticate: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error del servidor'
            ];
        }
    }
    
    /**
     * Obtener usuario por ID
     */
    public function getById($usuario_id) {
        try {
            $sql = "SELECT id, nombre, email, rol, creado_en 
                    FROM usuarios 
                    WHERE id = :id 
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log('Error en Usuario::getById: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Crear usuario (solo para instalación inicial)
     */
    public function crear($datos) {
        try {
            $sql = "INSERT INTO usuarios (nombre, email, contrasena_hash, rol) 
                    VALUES (:nombre, :email, :password_hash, :rol)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $datos['email'], PDO::PARAM_STR);
            $stmt->bindValue(':password_hash', Security::hashPassword($datos['password']), PDO::PARAM_STR);
            $stmt->bindValue(':rol', $datos['rol'] ?? 'auditor', PDO::PARAM_STR);
            
            $stmt->execute();
            
            return [
                'success' => true,
                'usuario_id' => $this->db->lastInsertId()
            ];
            
        } catch (\Exception $e) {
            error_log('Error en Usuario::crear: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al crear usuario'
            ];
        }
    }
    
    /**
     * Verificar si existe al menos un usuario admin
     */
    public function existeAdmin() {
        try {
            $sql = "SELECT COUNT(*) as total FROM usuarios WHERE rol = 'admin'";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] > 0;
            
        } catch (\Exception $e) {
            error_log('Error en Usuario::existeAdmin: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todos los usuarios
     */
    public function getAll() {
        try {
            $sql = "SELECT id, nombre, email, rol, creado_en 
                    FROM usuarios 
                    ORDER BY creado_en DESC";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log('Error en Usuario::getAll: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar último acceso
     */
    public function actualizarUltimoAcceso($usuario_id) {
        try {
            $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return true;
            
        } catch (\Exception $e) {
            error_log('Error en Usuario::actualizarUltimoAcceso: ' . $e->getMessage());
            return false;
        }
    }
}
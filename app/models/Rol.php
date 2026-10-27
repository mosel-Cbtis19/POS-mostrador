<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/models/Rol.php
// DESCRIPCIÓN: Modelo para consulta de roles y perfiles de autorización.
// =============================================================================

require_once __DIR__ . '/BaseModel.php';

class Rol extends BaseModel {

    /**
     * Obtiene todos los roles disponibles en el sistema
     */
    public function obtenerTodos(): array {
        $sql = "SELECT id, nombre, descripcion, created_at FROM roles ORDER BY id ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Busca un rol por su identificador primario
     */
    public function findById(int $id): ?array {
        $sql = "SELECT id, nombre, descripcion FROM roles WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $rol = $stmt->fetch();
        return $rol ?: null;
    }
}

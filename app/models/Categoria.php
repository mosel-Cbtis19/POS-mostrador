<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/models/Categoria.php
// DESCRIPCIÓN: Modelo para la administración del catálogo de categorías/departamentos.
// =============================================================================

require_once __DIR__ . '/BaseModel.php';

class Categoria extends BaseModel {

    /**
     * Obtiene todas las categorías activas ordenadas alfabéticamente
     */
    public function obtenerTodas(bool $soloActivas = true): array {
        $sql = "SELECT id, nombre, descripcion, estado, created_at 
                FROM categorias";
        if ($soloActivas) {
            $sql .= " WHERE estado = 'ACTIVO'";
        }
        $sql .= " ORDER BY nombre ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene una categoría por su ID
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT id, nombre, descripcion, estado, created_at 
                FROM categorias 
                WHERE id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $cat = $stmt->fetch();
        return $cat ?: null;
    }

    /**
     * Registra una nueva categoría en la base de datos
     */
    public function crear(string $nombre, string $descripcion = ''): int {
        $sql = "INSERT INTO categorias (nombre, descripcion, estado) 
                VALUES (:nombre, :descripcion, 'ACTIVO')";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nombre', trim($nombre), PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', trim($descripcion), PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->lastInsertId();
    }

    /**
     * Actualiza los datos de una categoría existente
     */
    public function actualizar(int $id, string $nombre, string $descripcion = ''): bool {
        $sql = "UPDATE categorias 
                SET nombre = :nombre, descripcion = :descripcion 
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nombre', trim($nombre), PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', trim($descripcion), PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Realiza una baja lógica (soft-delete) de la categoría
     */
    public function eliminar(int $id): bool {
        $sql = "UPDATE categorias SET estado = 'INACTIVO' WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

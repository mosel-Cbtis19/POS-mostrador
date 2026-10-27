<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/models/BaseModel.php
// DESCRIPCIÓN: Modelo base del patrón MVC para acceso a la base de datos PDO.
// =============================================================================

require_once __DIR__ . '/../config/database.php';

abstract class BaseModel {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Inicia una transacción SQL (ACID)
     */
    public function beginTransaction(): bool {
        return $this->db->beginTransaction();
    }

    /**
     * Confirma los cambios de una transacción
     */
    public function commit(): bool {
        return $this->db->commit();
    }

    /**
     * Revierte cualquier cambio en caso de error durante una transacción
     */
    public function rollBack(): bool {
        return $this->db->rollBack();
    }

    /**
     * Retorna el último ID insertado por AUTO_INCREMENT
     */
    public function lastInsertId(): string {
        return $this->db->lastInsertId();
    }
}

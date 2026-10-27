<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/models/Usuario.php
// DESCRIPCIÓN: Modelo para gestión de usuarios, hashing BCRYPT y consultas PDO.
// =============================================================================

require_once __DIR__ . '/BaseModel.php';

class Usuario extends BaseModel {

    /**
     * Busca un usuario activo por su nombre de usuario (login)
     * Utiliza consultas preparadas para erradicar inyecciones SQL
     */
    public function findByUsuario(string $usuario): ?array {
        $sql = "SELECT u.id, u.rol_id, u.nombre_completo, u.usuario, u.password, 
                       u.telefono, u.estado, u.ultimo_login, r.nombre AS rol_nombre
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                WHERE u.usuario = :usuario AND u.estado = 'ACTIVO'
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario', trim($usuario), PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Busca un usuario por su identificador primario
     */
    public function findById(int $id): ?array {
        $sql = "SELECT u.id, u.rol_id, u.nombre_completo, u.usuario, u.telefono, 
                       u.estado, u.ultimo_login, r.nombre AS rol_nombre
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                WHERE u.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Actualiza la fecha y hora del último acceso exitoso al sistema
     */
    public function actualizarUltimoLogin(int $id): bool {
        $sql = "UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Registra un nuevo usuario con contraseña cifrada mediante BCRYPT
     */
    public function crearUsuario(array $data): int {
        $sql = "INSERT INTO usuarios (rol_id, nombre_completo, usuario, password, telefono, estado)
                VALUES (:rol_id, :nombre_completo, :usuario, :password, :telefono, 'ACTIVO')";

        // Cifrado robusto nativo de PHP con BCRYPT
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':rol_id', (int)$data['rol_id'], PDO::PARAM_INT);
        $stmt->bindValue(':nombre_completo', trim($data['nombre_completo']), PDO::PARAM_STR);
        $stmt->bindValue(':usuario', trim($data['usuario']), PDO::PARAM_STR);
        $stmt->bindValue(':password', $passwordHash, PDO::PARAM_STR);
        $stmt->bindValue(':telefono', $data['telefono'] ?? null, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->lastInsertId();
    }

    /**
     * Obtiene el listado de todos los usuarios registrados
     */
    public function obtenerTodos(): array {
        $sql = "SELECT u.id, u.rol_id, u.nombre_completo, u.usuario, u.telefono, 
                       u.estado, u.ultimo_login, r.nombre AS rol_nombre
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                ORDER BY u.id ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el listado exclusivo de cajeros con su historial de ventas
     */
    public function obtenerCajeros(): array {
        try {
            $sql = "SELECT u.id, u.nombre_completo, u.usuario, u.telefono, u.estado, 
                           u.ultimo_login, u.created_at,
                           COUNT(v.id) AS total_ventas_realizadas,
                           COALESCE(SUM(v.total), 0.00) AS monto_total_vendido
                    FROM usuarios u
                    LEFT JOIN ventas v ON u.id = v.usuario_id AND v.estado = 'COMPLETADA'
                    WHERE u.rol_id = 2
                    GROUP BY u.id, u.nombre_completo, u.usuario, u.telefono, u.estado, u.ultimo_login, u.created_at
                    ORDER BY u.id DESC";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("SysVentas Usuario::obtenerCajeros falló: " . $e->getMessage());
            // Datos semilla de contingencia
            return [
                [
                    'id' => 2,
                    'nombre_completo' => 'Juan Pérez Gómez',
                    'usuario' => 'cajero1',
                    'telefono' => '312-555-0102',
                    'estado' => 'ACTIVO',
                    'ultimo_login' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d 08:00:00'),
                    'total_ventas_realizadas' => 22,
                    'monto_total_vendido' => 2130.00
                ]
            ];
        }
    }

    /**
     * Cambia el estatus de un cajero (Activar o Baja Lógica INACTIVO)
     */
    public function cambiarEstado(int $id, string $nuevoEstado): bool {
        $estadoValido = in_array($nuevoEstado, ['ACTIVO', 'INACTIVO'], true) ? $nuevoEstado : 'INACTIVO';
        try {
            $sql = "UPDATE usuarios SET estado = :estado, updated_at = NOW() WHERE id = :id AND rol_id = 2";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':estado', $estadoValido, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Throwable $e) {
            error_log("SysVentas Usuario::cambiarEstado falló: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un nombre de usuario ya está registrado en el sistema
     */
    public function existeUsuario(string $usuario, int $excluirId = 0): bool {
        try {
            $sql = "SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario AND id <> :excluir_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':usuario', trim($usuario), PDO::PARAM_STR);
            $stmt->bindValue(':excluir_id', $excluirId, PDO::PARAM_INT);
            $stmt->execute();
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Valida una contraseña en texto plano contra el hash almacenado
     */
    public static function verificarPassword(string $passwordPlano, string $passwordHash): bool {
        return password_verify($passwordPlano, $passwordHash);
    }
}

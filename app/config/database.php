<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/config/database.php
// DESCRIPCIÓN: Clase de conexión Singleton con PDO a MariaDB / MySQL.
// =============================================================================

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    /**
     * Constructor privado para impedir instanciación directa (Patrón Singleton)
     */
    private function __construct() {}

    /**
     * Impedir clonación de la instancia
     */
    private function __clone() {}

    /**
     * Obtiene la única instancia activa de la conexión PDO
     * @return PDO
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, // Consultas preparadas reales nativas
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
                ];

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Registro seguro de error sin exponer credenciales al cliente
                error_log("Error de conexión a la Base de Datos SysVentas: " . $e->getMessage());
                die("<h3>Error crítico del sistema:</h3><p>No se pudo conectar con la base de datos de SysVentas. Verifique que el servicio MariaDB/MySQL esté activo en XAMPP.</p>");
            }
        }

        return self::$instance;
    }
}

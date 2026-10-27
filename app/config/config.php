<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/config/config.php
// DESCRIPCIÓN: Constantes de configuración global del sistema y base de datos.
// =============================================================================

// Zona horaria estándar de Colima / Centro de México
date_default_timezone_set('America/Mexico_City');

// Parámetros de la Aplicación
define('APP_NAME', 'SysVentas');
define('APP_VERSION', '1.0');
define('APP_SUBTITLE', 'Punto de Venta de Mostrador');
define('APP_SCHOOL', 'CBTis No. 19 "Miguel Hidalgo y Costilla"');
define('APP_CITY', 'Colima, Col., México');

// URL Base del proyecto (Ajustable según el entorno local XAMPP)
define('BASE_URL', 'http://localhost/punto_de_venta/public/');

// Parámetros de Conexión a MariaDB / MySQL (XAMPP por defecto)
define('DB_HOST', 'localhost');
define('DB_NAME', 'sysventas_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Rutas del Servidor (Paths absolutos)
define('ROOT_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
define('APP_PATH', ROOT_PATH . 'app' . DIRECTORY_SEPARATOR);
define('VIEWS_PATH', APP_PATH . 'views' . DIRECTORY_SEPARATOR);
define('PUBLIC_PATH', ROOT_PATH . 'public' . DIRECTORY_SEPARATOR);

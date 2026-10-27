<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: public/index.php
// DESCRIPCIÓN: Front Controller con blindaje HTTP, cookies seguras y CSRF (Fase 11).
// =============================================================================

// 1. Configuración de Cookies de Sesión Seguras (Mitigación de XSS y Robo de Sesión)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true, // Impide acceso a cookies de sesión desde JavaScript
        'samesite' => 'Lax' // Protección preventiva contra ataques CSRF
    ]);
    @session_start();
}

// 2. Cabeceras HTTP de Seguridad Integral
header("X-Frame-Options: SAMEORIGIN"); // Previene ataques de Clickjacking
header("X-Content-Type-Options: nosniff"); // Previene confusión de tipos MIME
header("X-XSS-Protection: 1; mode=block"); // Filtro de protección XSS en navegadores
header("Referrer-Policy: strict-origin-when-cross-origin"); // Control de fuga de URLs

// 3. Inicialización del Token Global CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 4. Cargar configuración global y componentes base
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/models/BaseModel.php';

// 5. Autocargador seguro de clases (Controllers y Models)
spl_autoload_register(function ($className) {
    $sanitizedClass = preg_replace('/[^a-zA-Z0-9_]/', '', $className);
    $paths = [
        APP_PATH . 'controllers' . DIRECTORY_SEPARATOR . $sanitizedClass . '.php',
        APP_PATH . 'models' . DIRECTORY_SEPARATOR . $sanitizedClass . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// 6. Determinación y sanitización estricta del Controlador y Acción (Prevención LFI)
$controllerName = 'auth';
$actionName = 'login';

if (isset($_GET['url']) && !empty(trim($_GET['url']))) {
    $urlParts = explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
    $controllerName = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $urlParts[0] ?? 'auth'));
    $actionName = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $urlParts[1] ?? 'index'));
} elseif (isset($_GET['c'])) {
    $controllerName = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['c']));
    $actionName = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['a'] ?? 'index'));
} else {
    // Redirección por defecto según el estado de la sesión
    if (isset($_SESSION['usuario_id'])) {
        if ((int)($_SESSION['rol_id'] ?? 2) === 1) {
            $controllerName = 'reporte';
            $actionName = 'index';
        } else {
            $controllerName = 'pos';
            $actionName = 'index';
        }
    }
}

// 7. Mapeo controlado a la clase del Controlador
$allowedControllers = ['auth', 'caja', 'pos', 'producto', 'reporte', 'usuario'];

if (!in_array($controllerName, $allowedControllers, true)) {
    http_response_code(404);
    die("<h3>Error 404 - Módulo no encontrado</h3><p>El módulo solicitado no está disponible o ha sido restringido por directivas de seguridad.</p>");
}

$controllerClass = ucfirst($controllerName) . 'Controller';

if (class_exists($controllerClass)) {
    $controllerInstance = new $controllerClass();

    if (method_exists($controllerInstance, $actionName)) {
        // Ejecutar la acción solicitada
        $controllerInstance->$actionName();
    } else {
        http_response_code(404);
        die("<h3>Error 404 - Acción no encontrada</h3><p>La acción '{$actionName}' no existe en el controlador '{$controllerClass}'.</p>");
    }
} else {
    http_response_code(404);
    die("<h3>Error 404 - Módulo no encontrado</h3><p>El controlador '{$controllerClass}' no existe en el sistema.</p>");
}

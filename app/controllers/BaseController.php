<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/controllers/BaseController.php
// DESCRIPCIÓN: Controlador base MVC con blindaje de seguridad, CSRF y sanitización (Fase 11).
// =============================================================================

abstract class BaseController {

    /**
     * Renderiza una vista pasando un arreglo asociativo de variables e inyecta el token CSRF
     * @param string $view Ruta relativa de la vista dentro de app/views/ (ej. 'pos/index')
     * @param array $data Variables disponibles para la vista
     */
    protected function view(string $view, array $data = []): void {
        // Inyectar automáticamente el token CSRF activo en todas las vistas
        $data['csrf_token'] = $this->getCsrfToken();
        extract($data);
        $viewFile = VIEWS_PATH . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $view) . '.php';

        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            http_response_code(404);
            die("<h3>Error 404: Vista no encontrada</h3><p>El recurso solicitado '{$viewFile}' no existe en el servidor.</p>");
        }
    }

    /**
     * Responde una petición asíncrona (Fetch API) con formato JSON seguro
     * @param mixed $data Datos a serializar
     * @param int $statusCode Código de estado HTTP
     */
    protected function jsonResponse($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Redirige a otra ruta dentro del sistema
     * @param string $path Ruta interna (ej. '?c=pos&a=index')
     */
    protected function redirect(string $path): void {
        header("Location: " . BASE_URL . $path);
        exit;
    }

    /**
     * Obtiene o genera el token criptográfico CSRF de la sesión activa
     */
    protected function getCsrfToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida la autenticidad del token CSRF recibido por POST o Header HTTP
     */
    protected function validateCsrf(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Renderiza una vista visual estilizada para el error HTTP 403 Forbidden
     */
    protected function renderForbidden(string $mensaje = '', string $titulo = '403 - Acceso Denegado'): void {
        http_response_code(403);
        
        // Si la petición espera respuesta JSON (Fetch API / AJAX)
        $isJson = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                  (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        
        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => strip_tags($mensaje ?: 'Acceso restringido por directivas de seguridad.'),
                'code' => 403
            ]);
            exit();
        }

        $pageTitle = $titulo;
        $errorMessage = !empty($mensaje) ? $mensaje : 'Su rol de usuario no tiene los privilegios necesarios para acceder a este módulo reservado.';
        
        $errorView = __DIR__ . '/../views/errors/403.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo "<h3>{$titulo}</h3><p>{$errorMessage}</p><p><a href='" . BASE_URL . "'>Volver al inicio</a></p>";
        }
        exit();
    }

    /**
     * Exige un token CSRF válido para peticiones POST críticas (Prevención CSRF)
     */
    protected function requireCsrf(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validateCsrf()) {
                $this->renderForbidden(
                    "La petición fue rechazada por falta de autenticidad del token de sesión (CSRF). Recargue la página y vuelva a intentarlo.",
                    "403 - Fallo de Seguridad CSRF"
                );
            }
        }
    }

    /**
     * Verifica que el usuario tenga una sesión autenticada en el sistema
     */
    protected function requireAuth(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect('?c=auth&a=login');
        }
    }

    /**
     * Verifica que el usuario tenga el rol requerido (Control de Acceso RBAC)
     * @param int $requiredRoleId 1: Administrador, 2: Cajero
     */
    protected function requireRole(int $requiredRoleId): void {
        $this->requireAuth();
        if ((int)$_SESSION['rol_id'] !== $requiredRoleId) {
            $rolActual = $_SESSION['rol_nombre'] ?? ((int)$_SESSION['rol_id'] === 1 ? 'Administrador' : 'Cajero');
            $rolRequerido = $requiredRoleId === 1 ? 'Administrador' : 'Cajero';
            $this->renderForbidden(
                "Su rol actual es <strong>{$rolActual}</strong>, pero este módulo o acción requiere privilegios exclusivos de <strong>{$rolRequerido}</strong>.",
                "403 - Acceso Denegado (RBAC)"
            );
        }
    }

    /**
     * Exige que el usuario sea Administrador (Rol 1)
     */
    protected function requireAdmin(): void {
        $this->requireRole(1);
    }

    /**
     * Exige que el usuario sea Cajero (Rol 2)
     */
    protected function requireCajero(): void {
        $this->requireRole(2);
    }

    /**
     * Exige que exista una caja con estatus ABIERTA para operar ventas (Regla RN-01)
     */
    protected function requireCajaAbierta(): void {
        $this->requireAuth();
        if (!isset($_SESSION['caja_estado']) || $_SESSION['caja_estado'] !== 'ABIERTA') {
            $this->redirect('?c=caja&a=apertura');
        }
    }

    /**
     * Sanitiza cadenas de texto para prevenir ataques XSS (Cross-Site Scripting)
     */
    protected function sanitize(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Valida y filtra números enteros positivos (ej. IDs de catálogo)
     */
    protected function validateInt($value, int $min = 1): int {
        $filtered = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min]]);
        return ($filtered !== false) ? $filtered : 0;
    }

    /**
     * Valida y filtra valores flotantes y monetarios (evita inyecciones numéricas)
     */
    protected function validateFloat($value, float $min = 0.0): float {
        $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);
        return ($filtered !== false && $filtered >= $min) ? round($filtered, 2) : $min;
    }
}

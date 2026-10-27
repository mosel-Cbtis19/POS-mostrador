<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/views/errors/403.php
// DESCRIPCIÓN: Vista visual estilizada para error HTTP 403 (Acceso Denegado / RBAC).
// =============================================================================

$pageTitle = $pageTitle ?? '403 - Acceso Denegado | SysVentas';
$errorMessage = $errorMessage ?? 'Su rol de usuario no cuenta con los permisos necesarios para acceder a este módulo reservado.';
$userName = $_SESSION['nombre_completo'] ?? 'Usuario del Sistema';
$userRole = $_SESSION['rol_nombre'] ?? ((int)($_SESSION['rol_id'] ?? 2) === 1 ? 'Administrador' : 'Cajero');
$userNick = $_SESSION['usuario'] ?? 'sesión_activa';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #1e293b;
        }
        .error-card {
            background: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            max-width: 620px;
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .error-header-banner {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            padding: 2.25rem 2rem 1.75rem;
            text-align: center;
            color: #ffffff;
            position: relative;
        }
        .icon-circle {
            width: 90px;
            height: 90px;
            background: rgba(255, 255, 255, 0.15);
            border: 3px solid rgba(255, 255, 255, 0.35);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(4px);
        }
        .icon-circle i {
            font-size: 3rem;
            color: #ffffff;
        }
        .code-pill {
            display: inline-block;
            background: rgba(0, 0, 0, 0.25);
            color: #fecaca;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            padding: 0.35rem 1rem;
            border-radius: 50rem;
            margin-bottom: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .user-badge-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
        }
        .btn-action-primary {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            border: none;
            color: #ffffff;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.6rem;
            transition: all 0.2s ease;
        }
        .btn-action-primary:hover {
            background: linear-gradient(135deg, #172554 0%, #1d4ed8 100%);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(37, 99, 235, 0.3);
        }
    </style>
</head>
<body>

<div class="error-card">
    <!-- Banner de Encabezado de Error -->
    <div class="error-header-banner">
        <div class="icon-circle">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <br>
        <span class="code-pill">CÓDIGO DE SEGURIDAD 403</span>
        <h2 class="fw-bold mb-1">Acceso Denegado</h2>
        <p class="text-white-50 mb-0 small">Control de Acceso Basado en Roles (RBAC) • SysVentas POS</p>
    </div>

    <!-- Cuerpo del Mensaje y Contexto -->
    <div class="p-4 p-md-5">
        <div class="text-center mb-4">
            <p class="fs-5 text-dark mb-2">
                <?= $errorMessage ?>
            </p>
            <p class="text-muted small mb-0">
                Esta directiva de seguridad protege la integridad financiera, los costos de inventario y los reportes ejecutivos del comercio contra accesos no autorizados.
            </p>
        </div>

        <!-- Tarjeta de Información de la Sesión Activa -->
        <div class="user-badge-box mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-person-fill fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($userName) ?></div>
                        <div class="text-muted small">Usuario: <code>@<?= htmlspecialchars($userNick) ?></code></div>
                    </div>
                </div>
                <div>
                    <span class="badge <?= (int)($_SESSION['rol_id'] ?? 2) === 1 ? 'bg-primary' : 'bg-warning text-dark' ?> px-3 py-2 rounded-pill fw-semibold">
                        <i class="bi <?= (int)($_SESSION['rol_id'] ?? 2) === 1 ? 'bi-shield-check' : 'bi-person-badge' ?> me-1"></i>
                        Rol: <?= htmlspecialchars($userRole) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Botones de Acción y Navegación Segura -->
        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
            <?php if ((int)($_SESSION['rol_id'] ?? 2) === 1): ?>
                <a href="<?= BASE_URL ?>?c=reporte&a=index" class="btn btn-action-primary d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-speedometer2 me-2"></i> Ir al Dashboard Principal
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>?c=pos&a=index" class="btn btn-action-primary d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-upc-scan me-2"></i> Volver a Terminal de Cobro POS
                </a>
                <a href="<?= BASE_URL ?>?c=caja&a=corte" class="btn btn-outline-secondary fw-semibold d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-cash-stack me-2"></i> Cortes de Caja
                </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>?c=auth&a=logout" class="btn btn-outline-danger fw-semibold d-inline-flex align-items-center justify-content-center">
                <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
            </a>
        </div>

        <!-- Pie Institucional -->
        <div class="text-center mt-4 pt-3 border-top">
            <span class="text-muted small">
                <strong>SysVentas POS</strong> — CBTis No. 19 "Miguel Hidalgo y Costilla" (Colima, Col., México)
            </span>
        </div>
    </div>
</div>

</body>
</html>

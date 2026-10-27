<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/views/layouts/navbar.php
// DESCRIPCIÓN: Barra de navegación superior con información de caja y usuario.
// =============================================================================
$activeModule = $activeModule ?? 'pos';
$userName = $_SESSION['nombre_completo'] ?? 'Juan Pérez (Cajero)';
$userRole = $_SESSION['rol_nombre'] ?? 'Cajero';
$cajaStatus = $_SESSION['caja_estado'] ?? 'ABIERTA';
$cajaNumero = $_SESSION['caja_id'] ?? '1';
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-sysventas sticky-top">
    <div class="container-fluid px-3">
        <!-- Marca / Logotipo -->
        <a class="navbar-brand d-flex align-items-center fw-bold fs-4 me-3" href="<?= BASE_URL ?>?c=pos&a=index">
            <i class="bi bi-shop me-2 text-warning fs-3"></i>
            <span>Sys<span class="text-warning">Ventas</span></span>
        </a>

        <!-- Botón Toggle Responsive -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Navegación">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <!-- Menú de Módulos -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= $activeModule === 'pos' ? 'active fw-bold text-white' : 'text-white-50' ?> px-2" href="<?= BASE_URL ?>?c=pos&a=index">
                        <i class="bi bi-upc-scan me-1"></i> Terminal POS
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeModule === 'productos' ? 'active fw-bold text-white' : 'text-white-50' ?> px-2" href="<?= BASE_URL ?>?c=producto&a=index">
                        <i class="bi bi-box-seam me-1"></i> Productos & Stock
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeModule === 'caja' ? 'active fw-bold text-white' : 'text-white-50' ?> px-2" href="<?= BASE_URL ?>?c=caja&a=corte">
                        <i class="bi bi-cash-stack me-1"></i> Cortes de Caja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeModule === 'reportes' ? 'active fw-bold text-white' : 'text-white-50' ?> px-2" href="<?= BASE_URL ?>?c=reporte&a=index">
                        <i class="bi bi-graph-up-arrow me-1"></i> Dashboard & Reportes
                    </a>
                </li>
                <?php if ((int)($_SESSION['rol_id'] ?? 2) === 1): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeModule === 'usuarios' ? 'active fw-bold text-white' : 'text-white-50' ?> px-2" href="<?= BASE_URL ?>?c=usuario&a=index">
                        <i class="bi bi-people me-1"></i> Cajeros
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Información de Turno y Usuario -->
            <div class="d-flex align-items-center gap-3">
                <!-- Badge de Estatus de Caja -->
                <div class="d-none d-md-flex align-items-center bg-white bg-opacity-10 rounded-pill px-3 py-1 text-white border border-white border-opacity-25">
                    <span class="badge <?= $cajaStatus === 'ABIERTA' ? 'bg-success' : 'bg-danger' ?> rounded-circle p-1 me-2"> </span>
                    <span class="small fw-semibold">Caja #<?= htmlspecialchars($cajaNumero) ?>: <?= htmlspecialchars($cajaStatus) ?></span>
                </div>

                <!-- Usuario y Menú Desplegable -->
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle rounded-pill px-3 d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-2 fs-6"></i>
                        <span class="fw-semibold me-1"><?= htmlspecialchars($userName) ?></span>
                        <small class="badge bg-warning text-dark ms-1"><?= htmlspecialchars($userRole) ?></small>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li>
                            <h6 class="dropdown-header text-muted">Sesión Activa</h6>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>?c=caja&a=apertura">
                                <i class="bi bi-door-open me-2 text-primary"></i> Apertura de Turno
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>?c=caja&a=corte">
                                <i class="bi bi-calculator me-2 text-warning"></i> Arqueo / Corte Z
                            </a>
                        </li>
                        <?php if ((int)($_SESSION['rol_id'] ?? 2) === 1): ?>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>?c=usuario&a=index">
                                <i class="bi bi-people me-2 text-primary"></i> Gestión de Cajeros
                            </a>
                        </li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger fw-semibold" href="<?= BASE_URL ?>?c=auth&a=logout">
                                <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

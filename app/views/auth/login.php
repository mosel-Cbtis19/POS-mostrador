<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/views/auth/login.php
// DESCRIPCIÓN: Pantalla de inicio de sesión centrada, limpia y responsiva.
// =============================================================================
$pageTitle = "Iniciar Sesión - SysVentas (CBTis 19)";
require_once VIEWS_PATH . 'layouts/header.php';
?>

<div class="container min-vh-100 d-flex justify-content-center align-items-center py-5">
    <div class="row w-100 justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
            <!-- Tarjeta Principal de Login -->
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <!-- Encabezado con degradado azul marino institucional -->
                <div class="card-header bg-primary text-white text-center py-4" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important;">
                    <div class="mb-2">
                        <i class="bi bi-shop-window display-5 text-warning"></i>
                    </div>
                    <h3 class="fw-bold mb-0">Sys<span class="text-warning">Ventas</span></h3>
                    <p class="small text-white-50 mb-0">Punto de Venta de Mostrador</p>
                    <div class="mt-2">
                        <span class="badge bg-white bg-opacity-25 text-white fw-normal px-2 py-1 small">
                            CBTis No. 19 "Miguel Hidalgo y Costilla"
                        </span>
                    </div>
                </div>

                <!-- Cuerpo del Formulario -->
                <div class="card-body p-4 p-sm-5">
                    <?php if (isset($errorMessage)): ?>
                        <div class="alert alert-danger d-flex align-items-center py-2 px-3 mb-3 small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div><?= htmlspecialchars($errorMessage) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>?c=auth&a=authenticate" method="POST">
                        <!-- Campo Usuario -->
                        <div class="mb-3">
                            <label for="usuario" class="form-label fw-semibold small text-secondary">
                                <i class="bi bi-person-fill text-primary me-1"></i> Usuario del Sistema
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" class="form-control bg-light border-start-0 ps-0" id="usuario" name="usuario" placeholder="Ej. cajero1 o admin" required autofocus autocomplete="username">
                            </div>
                        </div>

                        <!-- Campo Contraseña -->
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold small text-secondary">
                                <i class="bi bi-lock-fill text-primary me-1"></i> Contraseña
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-shield-lock"></i>
                                </span>
                                <input type="password" class="form-control bg-light border-start-0 ps-0" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                            </div>
                        </div>

                        <!-- Botón de Ingreso -->
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm d-flex justify-content-center align-items-center" style="background-color: #1e3a8a; border-color: #1e3a8a;">
                            <i class="bi bi-box-arrow-in-right me-2 fs-5"></i> Ingresar al Sistema
                        </button>
                    </form>

                    <!-- Credenciales de Prueba sugeridas (Entorno Educativo) -->
                    <div class="mt-4 pt-3 border-top text-center">
                        <small class="text-muted d-block mb-2">Cuentas registradas para demostración:</small>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" onclick="document.getElementById('usuario').value='admin'; document.getElementById('password').value='admin123';">
                                <i class="bi bi-shield-check text-primary"></i> <strong>admin</strong>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" onclick="document.getElementById('usuario').value='cajero1'; document.getElementById('password').value='cajero123';">
                                <i class="bi bi-person-check text-success"></i> <strong>cajero1</strong>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pie de la Tarjeta -->
                <div class="card-footer bg-light text-center py-2 border-0">
                    <small class="text-muted" style="font-size: 0.75rem;">
                        Módulo IV: Desarrolla Sistemas Web • Colima, Col., México
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . 'layouts/footer.php'; ?>

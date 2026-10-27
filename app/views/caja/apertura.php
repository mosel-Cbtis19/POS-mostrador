<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/views/caja/apertura.php
// DESCRIPCIÓN: Registro de fondo inicial para apertura de turno de caja.
// =============================================================================
$pageTitle = "Apertura de Turno de Caja - SysVentas";
$activeModule = "caja";

require_once VIEWS_PATH . 'layouts/header.php';
require_once VIEWS_PATH . 'layouts/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white text-center py-4" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%) !important;">
                    <div class="mb-2">
                        <i class="bi bi-door-open-fill display-5 text-warning"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Apertura de Turno de Caja</h4>
                    <p class="small text-white-50 mb-0">Declaración obligatoria de fondo inicial en efectivo</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <div class="alert alert-info border-0 d-flex align-items-center small py-2 px-3 mb-4">
                        <i class="bi bi-info-circle-fill me-2 fs-5 text-info"></i>
                        <div>El fondo de caja es el dinero en monedas y billetes entregado para dar cambio a los clientes.</div>
                    </div>

                    <form action="<?= BASE_URL ?>?c=caja&a=guardarApertura" method="POST">
                        <div class="mb-3">
                            <label for="monto_inicial" class="form-label fw-bold text-dark">
                                Monto Inicial en Efectivo ($ MXN):
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light text-success fw-bold fs-4">$</span>
                                <input type="number" step="0.50" min="0" class="form-control form-control-lg fw-bold text-primary fs-3" id="monto_inicial" name="monto_inicial" value="500.00" required autofocus>
                            </div>
                        </div>

                        <!-- Botones de Montos Frecuentes -->
                        <div class="mb-4">
                            <label class="form-label small text-muted">Montos Frecuentes:</label>
                            <div class="d-flex justify-content-between gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm flex-fill py-2" onclick="document.getElementById('monto_inicial').value='200.00';">$200</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm flex-fill py-2" onclick="document.getElementById('monto_inicial').value='300.00';">$300</button>
                                <button type="button" class="btn btn-outline-primary btn-sm flex-fill py-2 active fw-bold" onclick="document.getElementById('monto_inicial').value='500.00';">$500</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm flex-fill py-2" onclick="document.getElementById('monto_inicial').value='1000.00';">$1,000</button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="observaciones" class="form-label small text-muted">Observaciones Iniciales (Opcional):</label>
                            <input type="text" class="form-control" id="observaciones" name="observaciones" placeholder="Ej. Inicio de turno matutino">
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-5 shadow">
                            <i class="bi bi-check-circle me-1"></i> Abrir Caja e Ir al Mostrador
                        </button>
                    </form>
                </div>
                <div class="card-footer bg-light text-center py-2 text-muted small">
                    CBTis No. 19 "Miguel Hidalgo y Costilla" • SysVentas POS
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . 'layouts/footer.php'; ?>

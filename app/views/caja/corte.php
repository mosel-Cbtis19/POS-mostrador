<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/views/caja/corte.php
// DESCRIPCIÓN: Arqueo de efectivo en gaveta y cierre de turno (Corte X y Z) - Fase 10.
// =============================================================================
$pageTitle = "Arqueo y Corte de Turno - SysVentas";
$activeModule = "caja";

require_once VIEWS_PATH . 'layouts/header.php';
require_once VIEWS_PATH . 'layouts/navbar.php';

$fondoInicial = (float)($detalle['monto_inicial'] ?? 0.0);
$ventasEfectivo = (float)($detalle['ventas_efectivo'] ?? 0.0);
$ventasTarjeta = (float)($detalle['ventas_tarjeta'] ?? 0.0);
$totalEsperado = (float)($detalle['total_esperado'] ?? ($fondoInicial + $ventasEfectivo));
$totalTickets = (int)($detalle['total_tickets'] ?? 0);
$cajaId = (int)($detalle['id'] ?? 1);
$cajeroNombre = htmlspecialchars($detalle['cajero_nombre'] ?? 'Cajero en Turno');
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <!-- Encabezado del Arqueo -->
                <div class="card-header bg-primary text-white p-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%) !important;">
                    <div>
                        <h4 class="fw-bold mb-1">
                            <i class="bi bi-cash-stack me-2 text-warning"></i> Arqueo y Cierre de Turno de Caja
                        </h4>
                        <p class="small text-white-50 mb-0">
                            Cajero: <strong><?= $cajeroNombre ?></strong> • Apertura: <?= date('d/m/Y H:i', strtotime($detalle['fecha_apertura'])) ?>
                        </p>
                    </div>
                    <div class="text-sm-end">
                        <span class="badge bg-warning text-dark px-3 py-2 fw-bold">Turno Activo #<?= $cajaId ?></span>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Tarjetas de Resumen Financiero en Vivo -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light border-0 p-3 rounded-3 text-center h-100">
                                <small class="text-muted fw-semibold text-uppercase">Fondo Inicial Asignado</small>
                                <h4 class="fw-bold text-dark mt-1 mb-0">$<?= number_format($fondoInicial, 2) ?></h4>
                                <small class="text-muted">Apertura de turno</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0 p-3 rounded-3 text-center h-100">
                                <small class="text-muted fw-semibold text-uppercase">Ventas en Efectivo</small>
                                <h4 class="fw-bold text-success mt-1 mb-0">+$<?= number_format($ventasEfectivo, 2) ?></h4>
                                <small class="text-success fw-semibold">(<?= (int)$detalle['tickets_efectivo'] ?> tickets emitidos)</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0 p-3 rounded-3 text-center h-100">
                                <small class="text-muted fw-semibold text-uppercase">Ventas con Tarjeta</small>
                                <h4 class="fw-bold text-primary mt-1 mb-0">+$<?= number_format($ventasTarjeta, 2) ?></h4>
                                <small class="text-primary fw-semibold">(<?= (int)$detalle['tickets_tarjeta'] ?> vouchers banco)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Total Esperado en Efectivo en Gaveta -->
                    <div class="alert alert-secondary p-3 rounded-3 d-flex justify-content-between align-items-center mb-4 border">
                        <div>
                            <strong class="fs-6 text-dark d-block">Efectivo Teórico Esperado en Gaveta:</strong>
                            <small class="text-muted">
                                Fondo Inicial ($<?= number_format($fondoInicial, 2) ?>) + Ventas Efectivo ($<?= number_format($ventasEfectivo, 2) ?>)
                            </small>
                        </div>
                        <span class="fs-3 fw-bold text-primary" id="total-esperado-val">
                            $<?= number_format($totalEsperado, 2) ?>
                        </span>
                    </div>

                    <!-- Formulario de Conteo Físico para Corte Z -->
                    <form action="<?= BASE_URL ?>?c=caja&a=procesarCorte" method="POST" id="form-corte-caja" onsubmit="return confirm('¿Está seguro de realizar el Cierre Definitivo (Corte Z)? El turno quedará formalmente cerrado y se imprimirá el ticket de balance.');">
                        <div class="mb-4">
                            <label for="monto_contado" class="form-label fw-bold text-dark fs-5">
                                Efectivo Físico Contado en Gaveta ($):
                            </label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text bg-light fw-bold text-success fs-3">$</span>
                                <input type="number" step="0.50" min="0" class="form-control fw-bold text-dark fs-2" 
                                       id="monto_contado" name="monto_contado" 
                                       value="<?= number_format($totalEsperado, 2, '.', '') ?>" required>
                            </div>
                            <small class="text-muted">Capture la suma real de billetes y monedas presentes en la gaveta física.</small>
                        </div>

                        <!-- Indicador Dinámico de Cuadratura / Sobrante / Faltante -->
                        <div class="card p-3 text-center rounded-3 mb-4 border-success bg-success bg-opacity-10" id="card-diferencia">
                            <small class="text-success fw-bold text-uppercase" id="title-diferencia">Resultado del Arqueo</small>
                            <h3 class="fw-bold text-success mb-0" id="lbl-diferencia">Cuadratura Exacta ($0.00)</h3>
                        </div>

                        <div class="mb-4">
                            <label for="obs" class="form-label fw-semibold text-secondary">Observaciones o Justificación de Cierre:</label>
                            <textarea class="form-control" id="obs" name="observaciones" rows="2" placeholder="Describa cualquier incidencia, retiro o justificación de diferencias en el turno..."></textarea>
                        </div>

                        <!-- Botones de Acción: Corte X (Parcial) y Corte Z (Final) -->
                        <div class="d-flex flex-column flex-sm-row justify-content-between gap-3 pt-2">
                            <a href="<?= BASE_URL ?>?c=caja&a=ticketCorte&id=<?= $cajaId ?>&tipo=X" target="_blank" class="btn btn-outline-secondary py-2 px-3 fw-semibold">
                                <i class="bi bi-printer me-1"></i> Imprimir Arqueo X (Parcial)
                            </a>
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>?c=pos&a=index" class="btn btn-light border py-2 px-3 fw-semibold">
                                    <i class="bi bi-arrow-left me-1"></i> Volver al POS
                                </a>
                                <button type="submit" class="btn btn-danger py-2 px-4 fw-bold shadow">
                                    <i class="bi bi-lock-fill me-1"></i> Finalizar Turno y Corte Z
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const totalEsperado = <?= (float)$totalEsperado ?>;
    const inputContado = document.getElementById('monto_contado');
    const cardDiferencia = document.getElementById('card-diferencia');
    const titleDiferencia = document.getElementById('title-diferencia');
    const lblDiferencia = document.getElementById('lbl-diferencia');

    function evaluarDiferencia() {
        const contado = parseFloat(inputContado ? inputContado.value : 0) || 0;
        const diff = Math.round((contado - totalEsperado) * 100) / 100;

        if (Math.abs(diff) < 0.01) {
            cardDiferencia.className = "card p-3 text-center rounded-3 mb-4 border-success bg-success bg-opacity-10";
            titleDiferencia.className = "text-success fw-bold text-uppercase";
            lblDiferencia.className = "fw-bold text-success mb-0";
            lblDiferencia.textContent = "Cuadratura Exacta ($0.00)";
        } else if (diff > 0) {
            cardDiferencia.className = "card p-3 text-center rounded-3 mb-4 border-warning bg-warning bg-opacity-10";
            titleDiferencia.className = "text-warning fw-bold text-uppercase";
            lblDiferencia.className = "fw-bold text-warning mb-0";
            lblDiferencia.textContent = `Sobrante en Caja: +$${diff.toFixed(2)}`;
        } else {
            cardDiferencia.className = "card p-3 text-center rounded-3 mb-4 border-danger bg-danger bg-opacity-10";
            titleDiferencia.className = "text-danger fw-bold text-uppercase";
            lblDiferencia.className = "fw-bold text-danger mb-0";
            lblDiferencia.textContent = `Faltante en Caja: -$${Math.abs(diff).toFixed(2)}`;
        }
    }

    if (inputContado) {
        inputContado.addEventListener('input', evaluarDiferencia);
        evaluarDiferencia();
    }
});
</script>

<?php require_once VIEWS_PATH . 'layouts/footer.php'; ?>

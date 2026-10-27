<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/views/reportes/index.php
// DESCRIPCIÓN: Panel de control administrativo y analítica avanzada de ventas (Fase 9).
// =============================================================================
$pageTitle = "Dashboard y Analítica SQL - SysVentas";
$activeModule = "reportes";

require_once VIEWS_PATH . 'layouts/header.php';
require_once VIEWS_PATH . 'layouts/navbar.php';

$margenPromedio = ($kpis['total_ventas'] > 0) 
    ? round(($kpis['ganancia_bruta'] / $kpis['total_ventas']) * 100, 1) 
    : 0.0;
?>

<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Encabezado del Dashboard -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div>
            <h2 class="fw-bold text-primary mb-1">
                <i class="bi bi-speedometer2 me-2"></i> Panel de Control y Analítica SQL
            </h2>
            <p class="text-muted mb-0">Métricas comerciales consolidadas al <?= date('d/m/Y') ?> con consultas avanzadas (Módulo IV).</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" onclick="window.print();">
                <i class="bi bi-printer me-1"></i> Imprimir Informe
            </button>
            <a href="<?= BASE_URL ?>?c=pos&a=index" class="btn btn-success fw-bold shadow-sm">
                <i class="bi bi-cart-plus me-1"></i> Abrir Mostrador POS
            </a>
        </div>
    </div>

    <!-- Tarjetas de Métricas Clave (KPIs calculados con Subconsultas SQL) -->
    <div class="row g-3 mb-4">
        <!-- Tarjeta 1: Total Ventas Hoy -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-primary border-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Ventas del Día</small>
                        <h3 class="fw-bold text-primary mt-1 mb-0">$<?= number_format($kpis['total_ventas'], 2) ?></h3>
                        <small class="text-success"><i class="bi bi-calendar-check me-1"></i> Transacciones de hoy</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                        <i class="bi bi-currency-dollar fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta 2: Utilidad Bruta Estimada -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-success border-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Ganancia Bruta Real</small>
                        <h3 class="fw-bold text-success mt-1 mb-0">$<?= number_format($kpis['ganancia_bruta'], 2) ?></h3>
                        <small class="text-muted">Margen promedio: <strong><?= $margenPromedio ?>%</strong></small>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                        <i class="bi bi-graph-up-arrow fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta 3: Tickets Emitidos y Ticket Promedio -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-warning border-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Tickets Emitidos</small>
                        <h3 class="fw-bold text-dark mt-1 mb-0"><?= $kpis['tickets_emitidos'] ?> Tickets</h3>
                        <small class="text-muted">Ticket Promedio: <strong>$<?= number_format($kpis['ticket_promedio'], 2) ?></strong></small>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                        <i class="bi bi-receipt fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta 4: Alertas de Stock Crítico -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-danger border-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Alertas de Inventario</small>
                        <h3 class="fw-bold text-danger mt-1 mb-0"><?= $kpis['alertas_stock'] ?> Artículos</h3>
                        <small class="text-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i> Stock &le; Mínimo requerido
                        </small>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                        <i class="bi bi-exclamation-octagon fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Tablas y Desgloses Analíticos -->
    <div class="row g-4 mb-4">
        <!-- Top 5 Productos más Vendidos (INNER JOIN + GROUP BY) -->
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-trophy-fill text-warning me-2"></i> Top Productos Más Vendidos
                    </h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary small">INNER JOIN + GROUP BY</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small">
                            <tr>
                                <th class="text-center" style="width: 40px;">#</th>
                                <th>Producto / Categoría</th>
                                <th class="text-center">Unidades</th>
                                <th class="text-end">Ingresos</th>
                                <th class="text-end">Utilidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topProductos)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No se registran ventas acumuladas en el período.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topProductos as $idx => $prod): ?>
                                    <tr>
                                        <td class="text-center fw-bold text-primary"><?= $idx + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($prod['nombre']) ?></div>
                                            <span class="badge bg-light text-secondary border small">
                                                <?= htmlspecialchars($prod['categoria'] ?? 'General') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                                                <?= (int)$prod['unidades_vendidas'] ?> pzas
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            $<?= number_format((float)$prod['total_ingresos'], 2) ?>
                                        </td>
                                        <td class="text-end fw-semibold text-success">
                                            +$<?= number_format((float)($prod['ganancia_bruta'] ?? 0), 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Desglose por Método de Pago y Arqueo de Caja -->
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-wallet2 text-primary me-2"></i> Balance de Formas de Pago
                    </h5>
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i> Caja #<?= $estadoCaja['id'] ?> Abierta
                    </span>
                </div>
                <div class="card-body p-4">
                    <!-- Progreso Efectivo -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold text-dark">
                                <i class="bi bi-cash me-1 text-success"></i> Efectivo en Caja (<?= $balancePagos['porc_efectivo'] ?>%)
                            </span>
                            <span class="fw-bold text-success">$<?= number_format($balancePagos['efectivo'], 2) ?></span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?= $balancePagos['porc_efectivo'] ?>%;" 
                                 aria-valuenow="<?= $balancePagos['porc_efectivo'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    <!-- Progreso Tarjeta -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold text-dark">
                                <i class="bi bi-credit-card me-1 text-primary"></i> Tarjeta Bancaria (<?= $balancePagos['porc_tarjeta'] ?>%)
                            </span>
                            <span class="fw-bold text-primary">$<?= number_format($balancePagos['tarjeta'], 2) ?></span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" 
                                 style="width: <?= $balancePagos['porc_tarjeta'] ?>%;" 
                                 aria-valuenow="<?= $balancePagos['porc_tarjeta'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    <!-- Cuadro de Arqueo en Gaveta -->
                    <div class="p-3 bg-light rounded-4 mt-4 border">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="d-block text-dark">Fondo Inicial Asignado:</strong>
                                <small class="text-muted">Apertura: <?= date('d/m/Y H:i', strtotime($estadoCaja['fecha_apertura'])) ?></small>
                            </div>
                            <span class="fs-5 fw-bold text-secondary">$<?= number_format($estadoCaja['fondo_inicial'], 2) ?></span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="d-block text-dark">Efectivo en Gaveta al Momento:</strong>
                                <small class="text-muted">Fondo Inicial + Ventas Efectivo</small>
                            </div>
                            <span class="fs-4 fw-bold text-primary">$<?= number_format($estadoCaja['total_gaveta'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Ventas por Categoría (GROUP BY + HAVING) -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-tags-fill text-info me-2"></i> Recaudación por Categoría de Productos
                    </h5>
                    <span class="badge bg-info bg-opacity-10 text-info border border-info small">GROUP BY + HAVING</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <?php foreach ($ventasCategoria as $cat): ?>
                            <div class="col-12 col-sm-6 col-md-3">
                                <div class="p-3 border rounded-3 bg-light h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($cat['categoria']) ?></h6>
                                        <span class="badge bg-secondary small"><?= (int)$cat['unidades'] ?> pzas</span>
                                    </div>
                                    <h4 class="fw-bold text-primary mb-1">$<?= number_format((float)$cat['total'], 2) ?></h4>
                                    <small class="text-muted"><?= (int)$cat['total_productos'] ?> productos en catálogo</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . 'layouts/footer.php'; ?>

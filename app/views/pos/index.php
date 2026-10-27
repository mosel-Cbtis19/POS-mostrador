<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/views/pos/index.php
// DESCRIPCIÓN: Terminal de cobro de mostrador interactiva (Dinamismo JS y validaciones).
// =============================================================================
$pageTitle = "Terminal de Cobro POS - SysVentas";
$activeModule = "pos";
$extraScripts = ['pos.js'];

require_once VIEWS_PATH . 'layouts/header.php';
require_once VIEWS_PATH . 'layouts/navbar.php';
?>

<div class="container-fluid py-2">
    <div class="row g-2">
        <!-- =============================================================== -->
        <!-- COLUMNA IZQUIERDA: BÚSQUEDA Y CATÁLOGO DE PRODUCTOS (60%)       -->
        <!-- =============================================================== -->
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0 h-100 rounded-3">
                <div class="card-body p-3 d-flex flex-column">
                    <!-- Barra de Entrada Rápida de Código / Búsqueda -->
                    <div class="mb-3">
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text bg-primary text-white border-primary">
                                <i class="bi bi-upc-scan fs-4"></i>
                            </span>
                            <input type="text" id="barcode-input" class="form-control border-primary fw-semibold" 
                                   placeholder="Escanear código de barras o escribir nombre del producto (F3)..." 
                                   autofocus autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" id="btn-clear-search" title="Limpiar búsqueda">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Filtros por Categoría Dinámicos (Pills) -->
                    <div class="d-flex gap-2 overflow-x-auto pb-2 mb-3 category-pills">
                        <button class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold active" data-category="ALL">
                            <i class="bi bi-grid-fill me-1"></i> Todos
                        </button>
                        <?php foreach ($categorias as $cat): ?>
                            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-category="<?= htmlspecialchars($cat['nombre']) ?>">
                                <i class="bi bi-tag-fill me-1"></i> <?= htmlspecialchars($cat['nombre']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Cuadrícula de Tarjetas de Productos Rápidos -->
                    <div class="flex-grow-1 overflow-y-auto" style="max-height: calc(100vh - 250px);">
                        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-2" id="products-grid">
                            <?php foreach ($productos as $p): ?>
                                <?php
                                    $stock = (int)$p['stock_actual'];
                                    $min = (int)($p['stock_minimo'] ?? 5);
                                    $isOut = ($stock <= 0);
                                    $isCrit = (!$isOut && $stock <= $min);
                                    $badgeClass = $isOut ? 'badge-stock-out' : ($isCrit ? 'badge-stock-critical' : 'badge-stock-normal');
                                    $badgeText = $isOut ? 'Agotado (0)' : ($isCrit ? 'Bajo (' . $stock . ')' : 'Stock: ' . $stock);
                                ?>
                                <div class="col product-item" 
                                     data-id="<?= $p['id'] ?>" 
                                     data-barcode="<?= htmlspecialchars($p['codigo_barras']) ?>" 
                                     data-name="<?= htmlspecialchars($p['nombre']) ?>" 
                                     data-price="<?= $p['precio_venta'] ?>" 
                                     data-stock="<?= $stock ?>" 
                                     data-category="<?= htmlspecialchars($p['categoria_nombre'] ?? 'General') ?>">
                                    <div class="card h-100 product-card rounded-3 <?= $isOut ? 'opacity-50' : '' ?>" style="cursor: pointer;">
                                        <div class="card-body p-2 d-flex flex-column text-center justify-content-between">
                                            <div class="text-secondary my-1">
                                                <i class="bi bi-box-seam fs-2 text-primary"></i>
                                            </div>
                                            <h6 class="card-title fw-bold text-truncate-2 small mb-1" 
                                                title="<?= htmlspecialchars($p['nombre']) ?>" 
                                                style="height: 38px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                                <?= htmlspecialchars($p['nombre']) ?>
                                            </h6>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <span class="badge <?= $badgeClass ?> small">
                                                    <?= $badgeText ?>
                                                </span>
                                                <span class="fw-bold text-primary fs-6 font-monospace">
                                                    $<?= number_format((float)$p['precio_venta'], 2) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- COLUMNA DERECHA: TICKET DE VENTA EN CURSO Y TOTALIZADOR (40%)    -->
        <!-- =============================================================== -->
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm border-0 h-100 rounded-3 d-flex flex-column">
                <!-- Encabezado del Ticket -->
                <div class="card-header bg-primary text-white py-2 px-3 d-flex justify-content-between align-items-center rounded-top-3">
                    <span class="fw-bold">
                        <i class="bi bi-receipt me-1"></i> Cuenta en Mostrador
                    </span>
                    <span class="badge bg-white text-primary fw-bold" id="total-articulos">0 piezas</span>
                </div>

                <!-- Tabla de Artículos Seleccionados -->
                <div class="table-responsive flex-grow-1 p-0 m-0" style="min-height: 260px; max-height: calc(100vh - 420px); overflow-y: auto;">
                    <table class="table table-sm table-striped table-hover align-middle mb-0">
                        <thead class="table-light sticky-top small text-secondary">
                            <tr>
                                <th style="width: 45%;">Producto</th>
                                <th class="text-center" style="width: 25%;">Cant.</th>
                                <th class="text-end" style="width: 20%;">Total</th>
                                <th class="text-center" style="width: 10%;"></th>
                            </tr>
                        </thead>
                        <tbody id="ticket-items-body">
                            <!-- Los renglones se agregan y modifican reactivamente con pos.js -->
                        </tbody>
                    </table>
                </div>

                <!-- Panel de Totales y Liquidación -->
                <div class="card-footer bg-light p-3 border-top rounded-bottom-3 mt-auto">
                    <!-- Resumen Subtotal e Impuestos -->
                    <div class="d-flex justify-content-between text-secondary small mb-1">
                        <span>Subtotal (Base):</span>
                        <span class="fw-semibold font-monospace" id="lbl-subtotal">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between text-secondary small mb-2">
                        <span>IVA Trasladado (16%):</span>
                        <span class="fw-semibold font-monospace" id="lbl-iva">$0.00</span>
                    </div>

                    <!-- Total Gigante de Alta Visibilidad -->
                    <div class="card bg-primary text-white text-center py-2 px-3 mb-3 shadow-sm rounded-3">
                        <span class="small text-uppercase opacity-75 fw-semibold">Total a Liquidar</span>
                        <h2 class="display-5 fw-bold mb-0 font-monospace" id="lbl-gran-total">$0.00</h2>
                    </div>

                    <!-- Botones de Acción Operativa -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success btn-lg fw-bold shadow-sm py-2" id="btn-abrir-cobro" data-bs-toggle="modal" data-bs-target="#modalCobro" disabled>
                            <i class="bi bi-cash-stack me-2 fs-5 align-middle"></i> Proceder al Cobro (F10)
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm fw-semibold" id="btn-cancelar-venta">
                            <i class="bi bi-trash3 me-1"></i> Limpiar Ticket (F2)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =================================================================== -->
<!-- MODAL DE COBRO INTERACTIVO MULTIMÉTODO                             -->
<!-- =================================================================== -->
<div class="modal fade" id="modalCobro" tabindex="-1" aria-labelledby="modalCobroLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow border-0">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold" id="modalCobroLabel">
                    <i class="bi bi-calculator me-2"></i> Cobro de Mostrador
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Total a Pagar en Modal -->
                <div class="text-center mb-3">
                    <span class="text-muted text-uppercase small fw-bold">Importe Total</span>
                    <h1 class="display-4 fw-bold text-dark font-monospace mb-0" id="modal-total-display">$0.00</h1>
                </div>

                <!-- Método de Pago -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small text-uppercase">Método de Liquidación</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="metodo_pago" id="pago_efectivo" value="EFECTIVO" checked>
                        <label class="btn btn-outline-success fw-bold" for="pago_efectivo"><i class="bi bi-cash me-1"></i> Efectivo</label>

                        <input type="radio" class="btn-check" name="metodo_pago" id="pago_tarjeta" value="TARJETA">
                        <label class="btn btn-outline-primary fw-bold" for="pago_tarjeta"><i class="bi bi-credit-card me-1"></i> Tarjeta</label>

                        <input type="radio" class="btn-check" name="metodo_pago" id="pago_transferencia" value="TRANSFERENCIA">
                        <label class="btn btn-outline-info fw-bold" for="pago_transferencia"><i class="bi bi-phone me-1"></i> Transfer</label>
                    </div>
                </div>

                <!-- Billetes Rápidos -->
                <div class="mb-3" id="quick-cash-container">
                    <label class="form-label fw-bold text-secondary small text-uppercase">Billetes Rápidos</label>
                    <div class="d-flex gap-2 flex-wrap justify-content-center">
                        <button type="button" class="btn btn-outline-secondary btn-quick-cash fw-bold" data-cash="50">$50</button>
                        <button type="button" class="btn btn-outline-secondary btn-quick-cash fw-bold" data-cash="100">$100</button>
                        <button type="button" class="btn btn-outline-secondary btn-quick-cash fw-bold" data-cash="200">$200</button>
                        <button type="button" class="btn btn-outline-secondary btn-quick-cash fw-bold" data-cash="500">$500</button>
                        <button type="button" class="btn btn-outline-primary btn-quick-cash fw-bold" data-cash="exacto">Monto Exacto</button>
                    </div>
                </div>

                <!-- Input Dinámico de Pago Recibido -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small text-uppercase">Efectivo Recibido ($)</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light fw-bold text-muted">$</span>
                        <input type="number" step="0.50" id="pago-con-input" class="form-control text-center fw-bold fs-3 text-dark" placeholder="0.00" autocomplete="off">
                    </div>
                </div>

                <!-- Visualizador de Cambio o Faltante -->
                <div class="card bg-light border text-center py-2 mb-2" id="box-cambio">
                    <small class="text-uppercase fw-bold text-muted" id="label-cambio-titulo">Cambio para el Cliente</small>
                    <h3 class="display-6 fw-bold text-muted font-monospace mb-0" id="modal-cambio-display">$0.00</h3>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Regresar</button>
                <button type="button" class="btn btn-success fw-bold px-4" id="btn-confirmar-venta" disabled>
                    <i class="bi bi-printer me-1"></i> Confirmar y Emitir Ticket
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =================================================================== -->
<!-- MODAL DE TICKET TÉRMICO IMPRIMIBLE                                  -->
<!-- =================================================================== -->
<div class="modal fade" id="modalTicketImpresion" tabindex="-1" aria-labelledby="modalTicketLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 shadow border-0">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title fw-bold" id="modalTicketLabel">
                    <i class="bi bi-receipt me-1"></i> Comprobante de Venta
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 font-monospace small bg-white text-dark" id="ticket-termico-area" style="font-size: 11px;">
                <div class="text-center mb-2">
                    <h6 class="fw-bold mb-0">SYSVENTAS MOSTRADOR</h6>
                    <small class="d-block text-muted">CBTis No. 19 Colima, Col., México</small>
                    <small class="d-block text-muted">RFC: XAXX010101000</small>
                    <small class="d-block text-muted">Tel: 312-312-0000</small>
                </div>
                <hr class="my-1 border-dark border-dashed">
                <div class="d-flex justify-content-between">
                    <span>Folio:</span>
                    <strong id="ticket-folio">VTA-0000</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Fecha:</span>
                    <span id="ticket-fecha"><?= date('d/m/Y H:i') ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Cajero:</span>
                    <span><?= htmlspecialchars($_SESSION['nombre_completo'] ?? 'Cajero') ?></span>
                </div>
                <hr class="my-1 border-dark border-dashed">
                <table class="w-100 mb-1" id="ticket-tabla-articulos">
                    <thead>
                        <tr class="border-bottom border-dark">
                            <th class="text-start">Cant.</th>
                            <th class="text-start">Desc.</th>
                            <th class="text-end">Imp.</th>
                        </tr>
                    </thead>
                    <tbody id="ticket-body-articulos">
                        <!-- Artículos inyectados dinámicamente -->
                    </tbody>
                </table>
                <hr class="my-1 border-dark border-dashed">
                <div class="d-flex justify-content-between">
                    <span>Subtotal:</span>
                    <span id="ticket-subtotal">$0.00</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>IVA (16%):</span>
                    <span id="ticket-iva">$0.00</span>
                </div>
                <div class="d-flex justify-content-between fw-bold fs-6 border-top border-bottom border-dark py-1 my-1">
                    <span>TOTAL:</span>
                    <span id="ticket-total">$0.00</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Pagó con:</span>
                    <span id="ticket-pago-con">$0.00</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Cambio:</span>
                    <span id="ticket-cambio">$0.00</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Método:</span>
                    <span id="ticket-metodo">EFECTIVO</span>
                </div>
                <hr class="my-1 border-dark border-dashed">
                <div class="text-center mt-2">
                    <p class="mb-0 fw-bold">¡GRACIAS POR SU COMPRA!</p>
                    <small class="text-muted">Conserve este comprobante</small>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary btn-sm fw-bold px-3" onclick="window.print();">
                    <i class="bi bi-printer me-1"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . 'layouts/footer.php'; ?>

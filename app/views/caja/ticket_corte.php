<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de <?= ($tipo === 'Z') ? 'Corte Z (Cierre Definitivo)' : 'Corte X (Arqueo Parcial)' ?> - SysVentas</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Consolas', 'Courier New', monospace;
            color: #000;
        }

        .ticket-thermal {
            width: 80mm;
            max-width: 100%;
            margin: 20px auto;
            background: #fff;
            padding: 15px 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 4px;
            font-size: 12px;
            line-height: 1.35;
        }

        .ticket-divider {
            border-top: 1px dashed #444;
            margin: 8px 0;
        }

        .ticket-divider-double {
            border-top: 2px dashed #000;
            margin: 10px 0;
        }

        .text-mono {
            font-family: 'Consolas', 'Courier New', monospace;
        }

        @media print {
            body {
                background: none;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .ticket-thermal {
                width: 100% !important;
                margin: 0 !important;
                padding: 5px 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
        }
    </style>
</head>
<body>

<!-- Barra de Controles en Pantalla -->
<div class="container py-3 no-print">
    <div class="d-flex justify-content-center gap-2">
        <button onclick="window.print();" class="btn btn-primary fw-bold shadow-sm">
            <i class="bi bi-printer-fill me-1"></i> Imprimir Ticket
        </button>
        <?php if ((int)($_SESSION['rol_id'] ?? 2) === 1): ?>
            <a href="<?= BASE_URL ?>?c=reporte&a=index" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver a Reportes
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>?c=caja&a=apertura" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Apertura de Caja
            </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>?c=pos&a=index" class="btn btn-outline-success">
            <i class="bi bi-cart me-1"></i> Mostrador POS
        </a>
    </div>
</div>

<!-- Estructura del Ticket Térmico -->
<div class="ticket-thermal">
    <!-- Encabezado Fiscal / Institucional -->
    <div class="text-center">
        <strong class="d-block text-uppercase fw-bold" style="font-size: 13px;">
            <?= htmlspecialchars($negocio['nombre'] ?? 'SysVentas Mostrador') ?>
        </strong>
        <small class="d-block text-muted">RFC: <?= htmlspecialchars($negocio['rfc'] ?? 'XAXX010101000') ?></small>
        <small class="d-block"><?= htmlspecialchars($negocio['direccion'] ?? 'Colima, Col., México') ?></small>
        <small class="d-block">Tel: <?= htmlspecialchars($negocio['telefono'] ?? '312-312-0000') ?></small>
        <div class="ticket-divider"></div>
        <strong class="d-block fs-6 text-uppercase my-1">
            *** <?= ($tipo === 'Z') ? 'CORTE Z (CIERRE DEFINITIVO)' : 'CORTE X (ARQUEO PARCIAL)' ?> ***
        </strong>
        <div class="ticket-divider"></div>
    </div>

    <!-- Metadatos de la Sesión de Caja -->
    <table class="w-100 mb-2">
        <tr>
            <td><strong>TURNO CAJA:</strong></td>
            <td class="text-end">#<?= (int)($detalle['id'] ?? 1) ?></td>
        </tr>
        <tr>
            <td><strong>CAJERO:</strong></td>
            <td class="text-end text-truncate" style="max-width: 140px;">
                <?= htmlspecialchars($detalle['cajero_nombre'] ?? 'Cajero') ?>
            </td>
        </tr>
        <tr>
            <td><strong>APERTURA:</strong></td>
            <td class="text-end"><?= date('d/m/Y H:i', strtotime($detalle['fecha_apertura'])) ?></td>
        </tr>
        <tr>
            <td><strong><?= ($tipo === 'Z') ? 'CIERRE:' : 'EMISIÓN:' ?></strong></td>
            <td class="text-end">
                <?= ($tipo === 'Z' && !empty($detalle['fecha_cierre'])) ? date('d/m/Y H:i', strtotime($detalle['fecha_cierre'])) : date('d/m/Y H:i') ?>
            </td>
        </tr>
        <tr>
            <td><strong>ESTADO:</strong></td>
            <td class="text-end">
                <span class="badge <?= ($detalle['estado'] === 'CERRADA') ? 'bg-secondary' : 'bg-success' ?>">
                    <?= $detalle['estado'] ?>
                </span>
            </td>
        </tr>
    </table>

    <div class="ticket-divider"></div>
    <div class="text-center fw-bold">RESUMEN DE OPERACIONES</div>
    <div class="ticket-divider"></div>

    <!-- Desglose por Formas de Cobro -->
    <table class="w-100">
        <tr>
            <td>(+) Fondo Inicial:</td>
            <td class="text-end fw-bold">$<?= number_format((float)$detalle['monto_inicial'], 2) ?></td>
        </tr>
        <tr>
            <td>(+) Ventas Efectivo:</td>
            <td class="text-end fw-bold">$<?= number_format((float)$detalle['ventas_efectivo'], 2) ?></td>
        </tr>
        <tr>
            <td class="ps-2 text-muted"><small>» Tickets Efectivo:</small></td>
            <td class="text-end text-muted"><small><?= (int)$detalle['tickets_efectivo'] ?> tck</small></td>
        </tr>
        <tr>
            <td>(+) Ventas Tarjeta:</td>
            <td class="text-end fw-bold">$<?= number_format((float)$detalle['ventas_tarjeta'], 2) ?></td>
        </tr>
        <tr>
            <td class="ps-2 text-muted"><small>» Vouchers Tarjeta:</small></td>
            <td class="text-end text-muted"><small><?= (int)$detalle['tickets_tarjeta'] ?> op</small></td>
        </tr>
    </table>

    <div class="ticket-divider"></div>

    <!-- Total de Ventas Facturadas e Impuestos -->
    <table class="w-100">
        <tr class="fw-bold fs-6">
            <td>TOTAL VENTAS:</td>
            <td class="text-end">$<?= number_format((float)$detalle['total_ventas'], 2) ?></td>
        </tr>
        <tr class="text-muted">
            <td><small>Subtotal (Base):</small></td>
            <td class="text-end"><small>$<?= number_format((float)($detalle['acumulado_subtotal'] ?? 0), 2) ?></small></td>
        </tr>
        <tr class="text-muted">
            <td><small>IVA Trasladado (16%):</small></td>
            <td class="text-end"><small>$<?= number_format((float)($detalle['acumulado_iva'] ?? 0), 2) ?></small></td>
        </tr>
        <tr class="text-muted">
            <td><small>Total Transacciones:</small></td>
            <td class="text-end"><small><?= (int)$detalle['total_tickets'] ?> trans.</small></td>
        </tr>
    </table>

    <div class="ticket-divider-double"></div>
    <div class="text-center fw-bold">ARQUEO DE GAVETA EN EFECTIVO</div>
    <div class="ticket-divider"></div>

    <!-- Cuadratura de Efectivo -->
    <table class="w-100">
        <tr>
            <td>Fondo + Efectivo:</td>
            <td class="text-end fw-bold">$<?= number_format((float)$detalle['total_esperado'], 2) ?></td>
        </tr>
        <tr class="fw-bold">
            <td>Efectivo Esperado:</td>
            <td class="text-end">$<?= number_format((float)$detalle['total_esperado'], 2) ?></td>
        </tr>
        <?php if ($tipo === 'Z'): ?>
            <tr class="fw-bold fs-6">
                <td>Efectivo Contado:</td>
                <td class="text-end">$<?= number_format((float)($detalle['monto_final_efectivo'] ?? $detalle['total_esperado']), 2) ?></td>
            </tr>
            <tr class="fw-bold">
                <td>DIFERENCIA:</td>
                <td class="text-end">
                    <?php 
                        $diff = (float)($detalle['diferencia'] ?? 0.0);
                        if (abs($diff) < 0.01) {
                            echo '<span class="text-success">$0.00 (EXACTO)</span>';
                        } elseif ($diff > 0) {
                            echo '<span class="text-warning">+$' . number_format($diff, 2) . ' (SOBRANTE)</span>';
                        } else {
                            echo '<span class="text-danger">-$' . number_format(abs($diff), 2) . ' (FALTANTE)</span>';
                        }
                    ?>
                </td>
            </tr>
        <?php else: ?>
            <tr class="text-muted fst-italic">
                <td colspan="2"><small>* Nota: Arqueo parcial preliminar en curso. No asienta cierre en base de datos.</small></td>
            </tr>
        <?php endif; ?>
    </table>

    <?php if (!empty($detalle['observaciones'])): ?>
        <div class="ticket-divider"></div>
        <small class="d-block">
            <strong>Observaciones:</strong><br>
            <?= nl2br(htmlspecialchars($detalle['observaciones'])) ?>
        </small>
    <?php endif; ?>

    <!-- Firmas de Conformidad -->
    <div class="mt-4 pt-3 text-center">
        <div style="border-top: 1px solid #000; width: 75%; margin: 0 auto 4px auto;"></div>
        <small class="d-block">Firma del Cajero</small>
        <small class="d-block text-muted"><?= htmlspecialchars($detalle['cajero_nombre'] ?? 'Cajero') ?></small>

        <div style="border-top: 1px solid #000; width: 75%; margin: 25px auto 4px auto;"></div>
        <small class="d-block">Firma de Auditoría / Administrador</small>
        <small class="d-block text-muted">SysVentas - CBTis No. 19</small>
    </div>

    <div class="ticket-divider-double"></div>
    <div class="text-center text-muted" style="font-size: 10px;">
        SysVentas Mostrador POS • Software Educativo<br>
        Módulo IV: Desarrolla Sistemas Web en un Sistema de Información<br>
        <?= date('d/m/Y H:i:s') ?>
    </div>
</div>

</body>
</html>

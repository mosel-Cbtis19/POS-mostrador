<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/views/productos/index.php
// DESCRIPCIÓN: Catálogo de productos, existencias y control de inventario (CRUD).
// =============================================================================
$pageTitle = "Control de Inventario y Productos - SysVentas";
$activeModule = "productos";

require_once VIEWS_PATH . 'layouts/header.php';
require_once VIEWS_PATH . 'layouts/navbar.php';
?>

<div class="container-fluid py-4 px-3 px-md-4">

    <!-- Notificaciones de Éxito / Error -->
    <?php if (!empty($mensajeExito)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>
            <?= htmlspecialchars($mensajeExito) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensajeError)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3" role="alert">
            <i class="bi bi-exclamation-octagon-fill me-2 fs-5 align-middle"></i>
            <?= htmlspecialchars($mensajeError) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Encabezado de Sección y Botones de Acción -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
        <div>
            <h2 class="fw-bold text-primary mb-1">
                <i class="bi bi-boxes me-2"></i> Catálogo de Productos y Existencias
            </h2>
            <p class="text-muted mb-0">Administración integral del inventario, control de márgenes de ganancia y stock crítico.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-secondary" onclick="window.print();">
                <i class="bi bi-printer me-1"></i> Imprimir Reporte
            </button>
            <button type="button" class="btn btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria">
                <i class="bi bi-tag me-1"></i> + Nueva Categoría
            </button>
            <button type="button" class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto">
                <i class="bi bi-plus-circle me-1"></i> + Nuevo Producto
            </button>
        </div>
    </div>

    <!-- Tarjetas de Resumen KPI de Inventario -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 border-start border-primary border-4">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Artículos Registrados</small>
                        <h4 class="fw-bold mb-0 text-dark"><?= (int)($kpis['total_productos'] ?? 0) ?> Productos</h4>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                        <i class="bi bi-boxes fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 border-start border-info border-4">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Unidades en Existencia</small>
                        <h4 class="fw-bold mb-0 text-info"><?= number_format((int)($kpis['total_unidades'] ?? 0)) ?> pzas</h4>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
                        <i class="bi bi-stack fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 border-start border-warning border-4">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Stock Bajo (Mínimo)</small>
                        <h4 class="fw-bold mb-0 text-warning"><?= (int)($kpis['stock_bajo'] ?? 0) ?> Productos</h4>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                        <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 border-start border-danger border-4">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Agotados (Stock 0)</small>
                        <h4 class="fw-bold mb-0 text-danger"><?= (int)($kpis['agotados'] ?? 0) ?> Productos</h4>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                        <i class="bi bi-x-circle-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros y Búsqueda -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <input type="hidden" name="c" value="producto">
                <input type="hidden" name="a" value="index">
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control bg-light border-start-0" 
                               placeholder="Buscar por código de barras o nombre..." 
                               value="<?= htmlspecialchars($busqueda ?? '') ?>">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <select name="cat" class="form-select bg-light">
                        <option value="">Todas las Categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (isset($categoriaSeleccionada) && (int)$categoriaSeleccionada === (int)$cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-funnel-fill me-1"></i> Filtrar
                    </button>
                    <a href="?c=producto&a=index" class="btn btn-outline-secondary" title="Limpiar Filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla Principal de Productos -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="bi bi-card-checklist text-primary me-2"></i>Artículos Disponibles (<?= count($productos) ?>)
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th class="ps-3">Código / SKU</th>
                        <th>Descripción del Producto</th>
                        <th>Categoría</th>
                        <th class="text-end">Costo Compra</th>
                        <th class="text-end">Precio Venta</th>
                        <th class="text-end">Margen %</th>
                        <th class="text-center">Existencias</th>
                        <th class="text-center pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                No se encontraron productos registrados con los criterios seleccionados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                            <?php 
                                $stock = (int)$p['stock_actual'];
                                $min = (int)$p['stock_minimo'];
                                $isOut = ($stock <= 0);
                                $isCrit = (!$isOut && $stock <= $min);
                                $badgeClass = $isOut ? 'bg-danger' : ($isCrit ? 'bg-warning text-dark' : 'bg-success');
                                $badgeText = $isOut ? 'Agotado (0)' : ($isCrit ? 'Bajo (' . $stock . ')' : $stock . ' pzas');

                                // Cálculo de margen porcentual: ((Precio Venta - Costo) / Precio Venta) * 100
                                $costo = (float)$p['precio_compra'];
                                $venta = (float)$p['precio_venta'];
                                $margen = $venta > 0 ? (($venta - $costo) / $venta) * 100 : 0;
                            ?>
                            <tr>
                                <td class="ps-3 font-monospace fw-semibold text-primary">
                                    <i class="bi bi-upc me-1 text-muted"></i><?= htmlspecialchars($p['codigo_barras']) ?>
                                </td>
                                <td>
                                    <strong class="d-block text-dark"><?= htmlspecialchars($p['nombre']) ?></strong>
                                    <?php if (!empty($p['descripcion'])): ?>
                                        <small class="text-muted d-block text-truncate" style="max-width: 250px;"><?= htmlspecialchars($p['descripcion']) ?></small>
                                    <?php endif; ?>
                                    <small class="text-muted">Stock Mínimo: <?= $min ?> pzas</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        <?= htmlspecialchars($p['categoria_nombre'] ?? 'General') ?>
                                    </span>
                                </td>
                                <td class="text-end text-muted font-monospace">$<?= number_format($costo, 2) ?></td>
                                <td class="text-end fw-bold text-primary font-monospace fs-6">$<?= number_format($venta, 2) ?></td>
                                <td class="text-end">
                                    <span class="badge bg-light text-success border border-success border-opacity-25 font-monospace">
                                        +<?= number_format($margen, 1) ?>%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $badgeClass ?> px-2 py-1"><?= $badgeText ?></span>
                                </td>
                                <td class="text-center pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-editar"
                                                data-id="<?= $p['id'] ?>"
                                                data-codigo="<?= htmlspecialchars($p['codigo_barras']) ?>"
                                                data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                                                data-cat="<?= $p['categoria_id'] ?>"
                                                data-desc="<?= htmlspecialchars($p['descripcion'] ?? '') ?>"
                                                data-costo="<?= $p['precio_compra'] ?>"
                                                data-venta="<?= $p['precio_venta'] ?>"
                                                data-stock="<?= $p['stock_actual'] ?>"
                                                data-min="<?= $p['stock_minimo'] ?>"
                                                title="Editar Producto">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <a href="?c=producto&a=eliminar&id=<?= $p['id'] ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('¿Está seguro de dar de baja este producto del catálogo?');"
                                           title="Desactivar Producto">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal 1: Registrar Nuevo Producto -->
<div class="modal fade" id="modalNuevoProducto" tabindex="-1" aria-labelledby="modalNuevoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 shadow border-0">
            <form action="?c=producto&a=guardar" method="POST">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="modalNuevoLabel">
                        <i class="bi bi-plus-circle me-2 text-warning"></i> Registrar Nuevo Producto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Código de Barras / SKU *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-upc-scan"></i></span>
                                <input type="text" name="codigo_barras" class="form-control" placeholder="Ej. 75010000001" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Categoría *</label>
                            <select name="categoria_id" class="form-select" required>
                                <option value="">Seleccione Categoría...</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary">Nombre Comercial del Producto *</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej. Aceite Vegetal 1L" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary">Descripción Detallada (Opcional)</label>
                            <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalles de marca, presentación o notas..."></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-secondary">Costo Compra ($) *</label>
                            <input type="number" step="0.01" min="0" name="precio_compra" class="form-control" placeholder="0.00" required id="np_compra">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-secondary">Precio Venta ($) *</label>
                            <input type="number" step="0.01" min="0" name="precio_venta" class="form-control fw-bold text-primary" placeholder="0.00" required id="np_venta">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-secondary">Stock Inicial *</label>
                            <input type="number" min="0" name="stock_actual" class="form-control" value="10" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-secondary">Stock Mínimo *</label>
                            <input type="number" min="1" name="stock_minimo" class="form-control" value="5" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="bi bi-save me-1"></i> Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Editar Producto -->
<div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 shadow border-0">
            <form action="?c=producto&a=guardar" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold" id="modalEditarLabel">
                        <i class="bi bi-pencil-square me-2 text-warning"></i> Modificar Producto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Código de Barras / SKU *</label>
                            <input type="text" name="codigo_barras" id="edit_codigo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Categoría *</label>
                            <select name="categoria_id" id="edit_cat" class="form-select" required>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary">Nombre Comercial *</label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary">Descripción</label>
                            <textarea name="descripcion" id="edit_desc" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-secondary">Costo Compra ($) *</label>
                            <input type="number" step="0.01" min="0" name="precio_compra" id="edit_compra" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-secondary">Precio Venta ($) *</label>
                            <input type="number" step="0.01" min="0" name="precio_venta" id="edit_venta" class="form-control fw-bold text-primary" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-secondary">Stock Actual *</label>
                            <input type="number" min="0" name="stock_actual" id="edit_stock" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-secondary">Stock Mínimo *</label>
                            <input type="number" min="1" name="stock_minimo" id="edit_min" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <i class="bi bi-check-lg me-1"></i> Actualizar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Registrar Nueva Categoría -->
<div class="modal fade" id="modalNuevaCategoria" tabindex="-1" aria-labelledby="modalCatLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow border-0">
            <form action="?c=producto&a=guardarCategoria" method="POST">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="modalCatLabel">
                        <i class="bi bi-tag-fill me-2 text-warning"></i> Nueva Categoría / Departamento
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Nombre de la Categoría *</label>
                        <input type="text" name="cat_nombre" class="form-control" placeholder="Ej. Frutas y Verduras" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Descripción (Opcional)</label>
                        <textarea name="cat_descripcion" class="form-control" rows="2" placeholder="Descripción breve..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="bi bi-save me-1"></i> Guardar Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Script para cargar dinámicamente los datos en el modal de edición
document.addEventListener('DOMContentLoaded', function () {
    const editButtons = document.querySelectorAll('.btn-editar');
    const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarProducto'));

    editButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_codigo').value = this.dataset.codigo;
            document.getElementById('edit_nombre').value = this.dataset.nombre;
            document.getElementById('edit_cat').value = this.dataset.cat;
            document.getElementById('edit_desc').value = this.dataset.desc;
            document.getElementById('edit_compra').value = this.dataset.costo;
            document.getElementById('edit_venta').value = this.dataset.venta;
            document.getElementById('edit_stock').value = this.dataset.stock;
            document.getElementById('edit_min').value = this.dataset.min;

            modalEditar.show();
        });
    });
});
</script>

<?php require_once VIEWS_PATH . 'layouts/footer.php'; ?>

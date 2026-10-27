<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/views/usuarios/index.php
// DESCRIPCIÓN: Vista de gestión de cajeros, registro y baja lógica para Administradores.
// =============================================================================
$pageTitle = "Gestión de Cajeros - SysVentas";
$activeModule = "usuarios";

require_once VIEWS_PATH . 'layouts/header.php';
require_once VIEWS_PATH . 'layouts/navbar.php';
?>

<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Encabezado del Módulo -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div>
            <h2 class="fw-bold text-primary mb-1">
                <i class="bi bi-people-fill me-2"></i> Gestión de Cajeros y Personal
            </h2>
            <p class="text-muted mb-0">Alta, control operativo y baja lógica de usuarios con rol Cajero en SysVentas.</p>
        </div>
        <div class="mt-3 mt-md-0">
            <button type="button" class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoCajero">
                <i class="bi bi-person-plus-fill me-1"></i> Registrar Nuevo Cajero
            </button>
        </div>
    </div>

    <!-- Alertas del Sistema -->
    <?php if (!empty($mensaje)): ?>
        <div class="alert <?= strpos($mensaje, 'Error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi <?= strpos($mensaje, 'Error') !== false ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' ?> me-2"></i>
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <!-- Tabla de Cajeros Registrados -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-person-badge text-primary me-2"></i> Plantilla de Cajeros
            </h5>
            <span class="badge bg-light text-secondary border"><?= count($cajeros) ?> cajeros registrados</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small text-uppercase">
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th>Nombre Completo</th>
                        <th>Usuario (Login)</th>
                        <th>Teléfono</th>
                        <th class="text-center">Transacciones</th>
                        <th class="text-end">Total Cobrado</th>
                        <th>Último Acceso</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cajeros)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                No hay cajeros registrados en el sistema.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cajeros as $idx => $cajero): ?>
                            <tr class="<?= $cajero['estado'] === 'INACTIVO' ? 'table-light opacity-75' : '' ?>">
                                <td class="text-center fw-bold text-muted"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($cajero['nombre_completo']) ?></div>
                                    <small class="text-muted">Alta: <?= date('d/m/Y', strtotime($cajero['created_at'] ?? 'now')) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                        <?= htmlspecialchars($cajero['usuario']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($cajero['telefono'] ?: 'No registrado') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                                        <?= (int)($cajero['total_ventas_realizadas'] ?? 0) ?> ventas
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    $<?= number_format((float)($cajero['monto_total_vendido'] ?? 0), 2) ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= !empty($cajero['ultimo_login']) ? date('d/m/Y H:i', strtotime($cajero['ultimo_login'])) : 'Sin ingresos' ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?php if ($cajero['estado'] === 'ACTIVO'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                            <i class="bi bi-check-circle me-1"></i> Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                                            <i class="bi bi-slash-circle me-1"></i> Inactivo (Baja)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if ($cajero['estado'] === 'ACTIVO'): ?>
                                        <a href="<?= BASE_URL ?>?c=usuario&a=cambiarEstado&id=<?= $cajero['id'] ?>&estado=INACTIVO" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('¿Está seguro de dar de BAJA LÓGICA al cajero <?= htmlspecialchars(addslashes($cajero['nombre_completo'])) ?>? El usuario no podrá iniciar sesión en la terminal.');">
                                            <i class="bi bi-person-x me-1"></i> Dar de Baja
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>?c=usuario&a=cambiarEstado&id=<?= $cajero['id'] ?>&estado=ACTIVO" 
                                           class="btn btn-sm btn-outline-success"
                                           onclick="return confirm('¿Desea REACTIVAR al cajero <?= htmlspecialchars(addslashes($cajero['nombre_completo'])) ?> para que pueda volver a operar el mostrador?');">
                                            <i class="bi bi-person-check me-1"></i> Reactivar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Registro de Nuevo Cajero -->
<div class="modal fade" id="modalNuevoCajero" tabindex="-1" aria-labelledby="modalNuevoCajeroLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white p-3">
                <h5 class="modal-title fw-bold" id="modalNuevoCajeroLabel">
                    <i class="bi bi-person-plus-fill me-2"></i> Registrar Nuevo Cajero
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="<?= BASE_URL ?>?c=usuario&a=guardar" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="nombre_completo" class="form-label fw-semibold">Nombre Completo del Cajero:</label>
                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" placeholder="Ej. Ana Laura Mendoza Díaz" required>
                    </div>
                    <div class="mb-3">
                        <label for="usuario" class="form-label fw-semibold">Nombre de Usuario (Login):</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Ej. cajero2" pattern="[a-zA-Z0-9_]{3,30}" title="Letras, números o guiones bajos (3-30 caracteres)" required>
                        </div>
                        <small class="text-muted">Se utilizará para ingresar a la terminal.</small>
                    </div>
                    <div class="mb-3">
                        <label for="telefono" class="form-label fw-semibold">Teléfono de Contacto (Opcional):</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="Ej. 312-123-4567">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Contraseña Inicial:</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" minlength="6" placeholder="Mínimo 6 caracteres" required>
                        </div>
                        <small class="text-muted">Se encriptará de forma irreversible con el algoritmo BCRYPT (cost 12).</small>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="bi bi-save me-1"></i> Guardar Cajero
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . 'layouts/footer.php'; ?>

<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/views/layouts/footer.php
// DESCRIPCIÓN: Pie de página común con scripts Bootstrap 5 y créditos.
// =============================================================================
?>
    <!-- Pie de página Institucional -->
    <footer class="footer mt-auto py-2 bg-white border-top text-center text-muted small d-print-none">
        <div class="container-fluid px-3 d-flex flex-column flex-md-row justify-content-between align-items-center">
            <span>
                <strong>SysVentas POS v1.0</strong> • CBTis No. 19 "Miguel Hidalgo y Costilla" (Colima, Col., México)
            </span>
            <span class="mt-1 mt-md-0 text-secondary">
                Módulo IV: Desarrolla Sistemas Web en un Sistema de Información • 5to Semestre Programación
            </span>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS Bundle con Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script Base SysVentas -->
    <script src="<?= BASE_URL ?>assets/js/main.js"></script>

    <?php if (isset($extraScripts) && is_array($extraScripts)): ?>
        <?php foreach ($extraScripts as $script): ?>
            <script src="<?= BASE_URL ?>assets/js/<?= htmlspecialchars($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>

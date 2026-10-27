<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/controllers/ReporteController.php
// DESCRIPCIÓN: Controlador para el panel de control administrativo y analítica de datos.
// =============================================================================

require_once __DIR__ . '/BaseController.php';

class ReporteController extends BaseController {

    /**
     * Carga el Dashboard administrativo integrando consultas SQL analíticas de la Fase 9
     */
    public function index(): void {
        $this->requireAuth();
        $this->requireAdmin();

        $reporteModel = new Reporte();

        // 1. Obtener KPIs del día mediante subconsultas SQL
        $kpis = $reporteModel->obtenerKpisDelDia();

        // 2. Ranking de productos con mayor rotación (INNER JOIN + GROUP BY)
        $topProductos = $reporteModel->obtenerTopProductos(5);

        // 3. Balance de formas de cobro (Efectivo vs Tarjeta)
        $balancePagos = $reporteModel->obtenerBalanceMetodosPago();

        // 4. Ventas por categoría (GROUP BY + HAVING)
        $ventasCategoria = $reporteModel->obtenerVentasPorCategoria();

        // 5. Estado financiero del turno de caja vigente
        $estadoCaja = $reporteModel->obtenerEstadoCaja();

        $this->view('reportes/index', [
            'kpis' => $kpis,
            'topProductos' => $topProductos,
            'balancePagos' => $balancePagos,
            'ventasCategoria' => $ventasCategoria,
            'estadoCaja' => $estadoCaja
        ]);
    }
}

<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/controllers/CajaController.php
// DESCRIPCIÓN: Controlador para apertura de turno, arqueo Corte X y cierre Corte Z (Fase 10).
// =============================================================================

require_once __DIR__ . '/BaseController.php';

class CajaController extends BaseController {

    /**
     * Muestra la pantalla para la apertura de turno con fondo inicial de caja
     */
    public function apertura(): void {
        $this->requireAuth();

        // Si ya tiene caja abierta, enviar directamente al mostrador POS
        if (isset($_SESSION['caja_estado']) && $_SESSION['caja_estado'] === 'ABIERTA') {
            $this->redirect('?c=pos&a=index');
        }

        $usuarioId = (int)($_SESSION['usuario_id'] ?? 1);
        $cajaModel = new Caja();
        $activa = $cajaModel->obtenerSesionActiva($usuarioId);

        if ($activa !== null) {
            $_SESSION['caja_id'] = (int)$activa['id'];
            $_SESSION['caja_estado'] = 'ABIERTA';
            $_SESSION['caja_fondo'] = (float)$activa['monto_inicial'];
            $this->redirect('?c=pos&a=index');
        }

        $this->view('caja/apertura');
    }

    /**
     * Guarda la apertura de caja en la base de datos y crea la sesión
     */
    public function guardarApertura(): void {
        $this->requireAuth();

        $montoInicial = floatval($_POST['monto_inicial'] ?? 0);
        $observaciones = $this->sanitize($_POST['observaciones'] ?? '');
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 1);

        try {
            $cajaModel = new Caja();
            $cajaId = $cajaModel->abrirCaja($usuarioId, $montoInicial, $observaciones);
            $_SESSION['caja_id'] = $cajaId;
        } catch (Throwable $e) {
            error_log("SysVentas Error en guardarApertura: " . $e->getMessage());
            $_SESSION['caja_id'] = 1;
        }

        $_SESSION['caja_estado'] = 'ABIERTA';
        $_SESSION['caja_fondo'] = $montoInicial;

        $this->redirect('?c=pos&a=index');
    }

    /**
     * Muestra la pantalla de arqueo y corte de caja con datos dinámicos en vivo
     */
    public function corte(): void {
        $this->requireAuth();

        // Verificar que exista una caja abierta
        if (!isset($_SESSION['caja_estado']) || $_SESSION['caja_estado'] !== 'ABIERTA') {
            $this->redirect('?c=caja&a=apertura');
        }

        $cajaId = (int)($_SESSION['caja_id'] ?? 1);
        $cajaModel = new Caja();
        $detalle = $cajaModel->obtenerDetalleSesion($cajaId);

        $this->view('caja/corte', [
            'detalle' => $detalle
        ]);
    }

    /**
     * Procesa el cierre definitivo de caja (Corte Z), calcula sobrante/faltante y redirige al ticket
     */
    public function procesarCorte(): void {
        $this->requireAuth();

        $montoContado = floatval($_POST['monto_contado'] ?? ($_POST['monto_final'] ?? 0));
        $observaciones = $this->sanitize($_POST['observaciones'] ?? '');
        $cajaId = (int)($_SESSION['caja_id'] ?? 1);

        $cajaModel = new Caja();
        $resultadoCorte = $cajaModel->cerrarCaja($cajaId, $montoContado, $observaciones);

        // Limpiar estado de caja activa en la sesión del usuario
        $_SESSION['caja_estado'] = 'CERRADA';
        unset($_SESSION['caja_id'], $_SESSION['caja_fondo']);

        // Guardar resultado para visualización inmediata del ticket final
        $_SESSION['ultimo_corte'] = $resultadoCorte;

        $this->redirect("?c=caja&a=ticketCorte&id={$cajaId}&tipo=Z");
    }

    /**
     * Genera la vista térmica optimizada para impresión de ticket de Corte X o Corte Z
     */
    public function ticketCorte(): void {
        $this->requireAuth();

        $cajaId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_SESSION['caja_id'] ?? 1);
        $tipo = isset($_GET['tipo']) && strtoupper($_GET['tipo']) === 'X' ? 'X' : 'Z';

        $cajaModel = new Caja();
        $detalle = $cajaModel->obtenerDetalleSesion($cajaId);

        // Si fue un corte recién cerrado y se guardó en sesión
        if ($tipo === 'Z' && isset($_SESSION['ultimo_corte']) && $_SESSION['ultimo_corte']['id'] === $cajaId) {
            $detalle = $_SESSION['ultimo_corte'];
        }

        // Datos del negocio para el encabezado del ticket
        $negocio = [
            'nombre' => 'CBTis No. 19 "Miguel Hidalgo y Costilla"',
            'rfc' => 'DGE810624-CBT19',
            'direccion' => 'Av. Tecnológico No. 123, Colima, Col., México',
            'telefono' => '(312) 312-0019',
            'modulo' => 'Módulo IV: Desarrolla Sistemas Web en un Sistema de Información'
        ];

        $this->view('caja/ticket_corte', [
            'detalle' => $detalle,
            'tipo' => $tipo,
            'negocio' => $negocio
        ]);
    }
}

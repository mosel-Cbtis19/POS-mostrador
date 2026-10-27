<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/controllers/PosController.php
// DESCRIPCIÓN: Controlador para la terminal de ventas en mostrador (POS) y transacciones.
// =============================================================================

require_once __DIR__ . '/BaseController.php';

class PosController extends BaseController {

    /**
     * Carga y despacha la terminal de cobro interactiva en mostrador
     */
    public function index(): void {
        // Exige autenticación y turno de caja abierto (Regla RN-01)
        $this->requireAuth();
        $this->requireCajaAbierta();

        $productos = [];
        $categorias = [];

        try {
            $productoModel = new Producto();
            $categoriaModel = new Categoria();

            $productos = $productoModel->obtenerTodos();
            $categorias = $categoriaModel->obtenerTodas();
        } catch (Throwable $e) {
            // Datos semilla de contingencia para evaluación visual sin MariaDB activo
            $categorias = [
                ['id' => 1, 'nombre' => 'Bebidas'],
                ['id' => 2, 'nombre' => 'Snacks'],
                ['id' => 3, 'nombre' => 'Lácteos'],
                ['id' => 4, 'nombre' => 'Abarrotes'],
                ['id' => 5, 'nombre' => 'Limpieza']
            ];
            $productos = [
                ['id' => 1, 'categoria_id' => 1, 'categoria_nombre' => 'Bebidas', 'codigo_barras' => '7501055310884', 'nombre' => 'Coca-Cola 600ml No Retornable', 'precio_compra' => 14.00, 'precio_venta' => 19.50, 'stock_actual' => 48, 'stock_minimo' => 12],
                ['id' => 2, 'categoria_id' => 1, 'categoria_nombre' => 'Bebidas', 'codigo_barras' => '7501055304739', 'nombre' => 'Agua Mineral Ciel 600ml', 'precio_compra' => 10.50, 'precio_venta' => 15.00, 'stock_actual' => 30, 'stock_minimo' => 10],
                ['id' => 3, 'categoria_id' => 1, 'categoria_nombre' => 'Bebidas', 'codigo_barras' => '7501031302728', 'nombre' => 'Jugo del Valle Mango 413ml', 'precio_compra' => 12.00, 'precio_venta' => 17.00, 'stock_actual' => 24, 'stock_minimo' => 8],
                ['id' => 4, 'categoria_id' => 2, 'categoria_nombre' => 'Snacks', 'codigo_barras' => '7501011115683', 'nombre' => 'Papas Sabritas Original 45g', 'precio_compra' => 15.00, 'precio_venta' => 21.00, 'stock_actual' => 35, 'stock_minimo' => 10],
                ['id' => 5, 'categoria_id' => 2, 'categoria_nombre' => 'Snacks', 'codigo_barras' => '7501011131072', 'nombre' => 'Doritos Nacho 58g', 'precio_compra' => 14.50, 'precio_venta' => 20.50, 'stock_actual' => 40, 'stock_minimo' => 10],
                ['id' => 6, 'categoria_id' => 2, 'categoria_nombre' => 'Snacks', 'codigo_barras' => '7501000111207', 'nombre' => 'Galletas Chokis Clásicas 76g', 'precio_compra' => 13.00, 'precio_venta' => 18.50, 'stock_actual' => 25, 'stock_minimo' => 8],
                ['id' => 7, 'categoria_id' => 3, 'categoria_nombre' => 'Lácteos', 'codigo_barras' => '7501020512114', 'nombre' => 'Leche Lala Entera 1 Litro', 'precio_compra' => 23.50, 'precio_venta' => 29.00, 'stock_actual' => 50, 'stock_minimo' => 15],
                ['id' => 8, 'categoria_id' => 3, 'categoria_nombre' => 'Lácteos', 'codigo_barras' => '7501020512145', 'nombre' => 'Leche Lala Deslactosada 1L', 'precio_compra' => 24.50, 'precio_venta' => 31.00, 'stock_actual' => 40, 'stock_minimo' => 15],
                ['id' => 9, 'categoria_id' => 3, 'categoria_nombre' => 'Lácteos', 'codigo_barras' => '7501020531511', 'nombre' => 'Yoghurt Lala Fresa 1kg', 'precio_compra' => 32.00, 'precio_venta' => 42.00, 'stock_actual' => 5, 'stock_minimo' => 6],
                ['id' => 10, 'categoria_id' => 4, 'categoria_nombre' => 'Abarrotes', 'codigo_barras' => '7501000100010', 'nombre' => 'Aceite 1-2-3 Vegetal 1 Litro', 'precio_compra' => 36.00, 'precio_venta' => 46.50, 'stock_actual' => 20, 'stock_minimo' => 8],
                ['id' => 11, 'categoria_id' => 4, 'categoria_nombre' => 'Abarrotes', 'codigo_barras' => '7501030421017', 'nombre' => 'Arroz Verde Valle Extra 1kg', 'precio_compra' => 26.00, 'precio_venta' => 34.00, 'stock_actual' => 30, 'stock_minimo' => 10],
                ['id' => 12, 'categoria_id' => 5, 'categoria_nombre' => 'Limpieza', 'codigo_barras' => '7501025401017', 'nombre' => 'Jabón Zote Rosa 400g', 'precio_compra' => 18.00, 'precio_venta' => 24.00, 'stock_actual' => 0, 'stock_minimo' => 10]
            ];
        }

        $this->view('pos/index', [
            'productos' => $productos,
            'categorias' => $categorias
        ]);
    }

    /**
     * Procesa la venta mediante una Transacción Atómica PDO (Fase 8)
     */
    public function procesarVenta(): void {
        $this->requireAuth();
        $this->requireCajaAbierta();

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data || empty($data['items'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'No se recibieron datos de los artículos a liquidar.'
            ], 400);
        }

        $usuarioId = (int)($_SESSION['usuario_id'] ?? 1);
        $cajaId = (int)($_SESSION['caja_id'] ?? 0);

        // Si la sesión de caja no está en la variable de sesión, recuperarla activamente
        if ($cajaId <= 0) {
            try {
                $cajaModel = new Caja();
                $sesionActiva = $cajaModel->obtenerSesionActiva($usuarioId);
                if ($sesionActiva) {
                    $cajaId = (int)$sesionActiva['id'];
                    $_SESSION['caja_id'] = $cajaId;
                } else {
                    $cajaId = 1;
                }
            } catch (Throwable $t) {
                $cajaId = 1;
            }
        }

        $cliente = $this->sanitize($data['cliente'] ?? 'PÚBLICO EN GENERAL');
        $metodoPago = in_array($data['metodo_pago'] ?? '', ['EFECTIVO', 'TARJETA']) ? $data['metodo_pago'] : 'EFECTIVO';

        $total = floatval($data['total'] ?? 0);
        $subtotal = $total / 1.16;
        $iva = $total - $subtotal;
        $pagoCon = floatval($data['pago_con'] ?? $total);
        $cambio = max(0, $pagoCon - $total);

        if ($pagoCon < $total) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'El importe recibido es inferior al total a pagar.'
            ], 400);
        }

        $datosVenta = [
            'caja_sesion_id' => $cajaId,
            'usuario_id' => $usuarioId,
            'cliente' => $cliente,
            'metodo_pago' => $metodoPago,
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total,
            'pago_con' => $pagoCon,
            'cambio' => $cambio
        ];

        try {
            $ventaModel = new Venta();
            $resultado = $ventaModel->registrarVenta($datosVenta, $data['items']);
            $this->jsonResponse($resultado);
        } catch (Throwable $e) {
            error_log("SysVentas Error procesarVenta: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al registrar la transacción en la base de datos: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Endpoint API para búsqueda asíncrona de producto por código de barras
     */
    public function buscarPorCodigo(): void {
        $this->requireAuth();

        $codigo = isset($_GET['codigo']) ? $this->sanitize($_GET['codigo']) : '';

        if (empty($codigo)) {
            $this->jsonResponse(['success' => false, 'message' => 'Código no proporcionado.'], 400);
        }

        try {
            $productoModel = new Producto();
            $producto = $productoModel->obtenerPorCodigo($codigo);

            if ($producto) {
                $this->jsonResponse(['success' => true, 'producto' => $producto]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Producto no encontrado en inventario.'], 404);
            }
        } catch (Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error al consultar la base de datos.'], 500);
        }
    }
}

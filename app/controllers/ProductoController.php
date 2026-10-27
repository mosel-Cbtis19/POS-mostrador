<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/controllers/ProductoController.php
// DESCRIPCIÓN: Controlador para la administración del catálogo de artículos e inventario.
// =============================================================================

require_once __DIR__ . '/BaseController.php';

class ProductoController extends BaseController {

    /**
     * Muestra la vista principal del catálogo de productos con métricas e inventario
     */
    public function index(): void {
        $this->requireAuth();
        $this->requireAdmin();

        $busqueda = isset($_GET['q']) ? $this->sanitize($_GET['q']) : '';
        $categoriaId = isset($_GET['cat']) && is_numeric($_GET['cat']) ? (int)$_GET['cat'] : null;

        $productos = [];
        $categorias = [];
        $kpis = [
            'total_productos' => 0,
            'total_unidades' => 0,
            'stock_bajo' => 0,
            'agotados' => 0
        ];

        try {
            $productoModel = new Producto();
            $categoriaModel = new Categoria();

            $productos = $productoModel->obtenerTodos($busqueda, $categoriaId);
            $categorias = $categoriaModel->obtenerTodas();
            $kpis = $productoModel->obtenerEstadisticas();
        } catch (Throwable $e) {
            // Datos semilla demostrativos para contingencia local
            $categorias = [
                ['id' => 1, 'nombre' => 'Abarrotes'],
                ['id' => 2, 'nombre' => 'Bebidas'],
                ['id' => 3, 'nombre' => 'Lácteos'],
                ['id' => 4, 'nombre' => 'Botanas'],
                ['id' => 5, 'nombre' => 'Limpieza']
            ];
            $productos = [
                ['id' => 1, 'categoria_id' => 1, 'categoria_nombre' => 'Abarrotes', 'codigo_barras' => '75010001', 'nombre' => 'Arroz Grano Grueso 1kg', 'precio_compra' => 18.50, 'precio_venta' => 24.50, 'stock_actual' => 45, 'stock_minimo' => 10],
                ['id' => 2, 'categoria_id' => 2, 'categoria_nombre' => 'Bebidas', 'codigo_barras' => '75010002', 'nombre' => 'Refresco Cola 600ml', 'precio_compra' => 12.00, 'precio_venta' => 18.00, 'stock_actual' => 8, 'stock_minimo' => 15],
                ['id' => 3, 'categoria_id' => 3, 'categoria_nombre' => 'Lácteos', 'codigo_barras' => '75010003', 'nombre' => 'Leche Entera 1L', 'precio_compra' => 21.00, 'precio_venta' => 27.50, 'stock_actual' => 0, 'stock_minimo' => 10]
            ];
            $kpis = [
                'total_productos' => 3,
                'total_unidades' => 53,
                'stock_bajo' => 1,
                'agotados' => 1
            ];
        }

        $this->view('productos/index', [
            'productos' => $productos,
            'categorias' => $categorias,
            'kpis' => $kpis,
            'busqueda' => $busqueda,
            'categoriaSeleccionada' => $categoriaId,
            'mensajeExito' => isset($_GET['msg']) ? $this->sanitize($_GET['msg']) : null,
            'mensajeError' => isset($_GET['err']) ? $this->sanitize($_GET['err']) : null
        ]);
    }

    /**
     * Guarda un producto nuevo o actualiza uno existente
     */
    public function guardar(): void {
        $this->requireAuth();
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?c=producto&a=index');
        }

        $id = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : 0;
        $codigoBarras = $this->sanitize($_POST['codigo_barras'] ?? '');
        $nombre = $this->sanitize($_POST['nombre'] ?? '');
        $categoriaId = (int)($_POST['categoria_id'] ?? 0);
        $descripcion = $this->sanitize($_POST['descripcion'] ?? '');
        $precioCompra = floatval($_POST['precio_compra'] ?? 0);
        $precioVenta = floatval($_POST['precio_venta'] ?? 0);
        $stockActual = intval($_POST['stock_actual'] ?? 0);
        $stockMinimo = intval($_POST['stock_minimo'] ?? 5);

        // Validaciones de negocio (RN-06: precio de venta >= precio de compra)
        if (empty($codigoBarras) || empty($nombre) || $categoriaId <= 0) {
            $this->redirect('?c=producto&a=index&err=' . urlencode('Todos los campos obligatorios deben ser completados.'));
        }

        if ($precioCompra < 0 || $precioVenta < 0) {
            $this->redirect('?c=producto&a=index&err=' . urlencode('Los precios no pueden ser negativos.'));
        }

        if ($precioVenta < $precioCompra) {
            $this->redirect('?c=producto&a=index&err=' . urlencode('El precio de venta no puede ser inferior al precio de compra (Regla RN-06).'));
        }

        if ($stockActual < 0 || $stockMinimo < 0) {
            $this->redirect('?c=producto&a=index&err=' . urlencode('Los niveles de stock no pueden ser negativos.'));
        }

        $datos = [
            'categoria_id' => $categoriaId,
            'codigo_barras' => $codigoBarras,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio_compra' => $precioCompra,
            'precio_venta' => $precioVenta,
            'stock_actual' => $stockActual,
            'stock_minimo' => $stockMinimo
        ];

        try {
            $productoModel = new Producto();

            if ($id > 0) {
                $productoModel->actualizar($id, $datos);
                $this->redirect('?c=producto&a=index&msg=' . urlencode('Producto actualizado exitosamente.'));
            } else {
                $productoModel->crear($datos);
                $this->redirect('?c=producto&a=index&msg=' . urlencode('Producto registrado exitosamente en el catálogo.'));
            }
        } catch (Throwable $e) {
            $this->redirect('?c=producto&a=index&err=' . urlencode('Error al guardar el producto. Verifique que el código de barras no esté duplicado.'));
        }
    }

    /**
     * Da de baja lógica un producto del inventario
     */
    public function eliminar(): void {
        $this->requireAuth();
        $this->requireAdmin();

        $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id > 0) {
            try {
                $productoModel = new Producto();
                $productoModel->eliminar($id);
                $this->redirect('?c=producto&a=index&msg=' . urlencode('Producto dado de baja correctamente.'));
            } catch (Throwable $e) {
                $this->redirect('?c=producto&a=index&err=' . urlencode('No se pudo dar de baja el producto.'));
            }
        }

        $this->redirect('?c=producto&a=index');
    }

    /**
     * Registra una nueva categoría desde el modal rápido
     */
    public function guardarCategoria(): void {
        $this->requireAuth();
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?c=producto&a=index');
        }

        $nombre = $this->sanitize($_POST['cat_nombre'] ?? '');
        $descripcion = $this->sanitize($_POST['cat_descripcion'] ?? '');

        if (empty($nombre)) {
            $this->redirect('?c=producto&a=index&err=' . urlencode('El nombre de la categoría es obligatorio.'));
        }

        try {
            $categoriaModel = new Categoria();
            $categoriaModel->crear($nombre, $descripcion);
            $this->redirect('?c=producto&a=index&msg=' . urlencode('Categoría registrada exitosamente.'));
        } catch (Throwable $e) {
            $this->redirect('?c=producto&a=index&err=' . urlencode('Error al registrar la categoría. Puede que ya exista.'));
        }
    }
}

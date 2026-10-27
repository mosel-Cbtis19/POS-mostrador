<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/models/Producto.php
// DESCRIPCIÓN: Modelo para gestión del catálogo de artículos y control de existencias.
// =============================================================================

require_once __DIR__ . '/BaseModel.php';

class Producto extends BaseModel {

    /**
     * Obtiene el listado de productos activos con su categoría asociada
     */
    public function obtenerTodos(?string $busqueda = null, ?int $categoriaId = null): array {
        $sql = "SELECT p.id, p.categoria_id, p.codigo_barras, p.nombre, p.descripcion,
                       p.precio_compra, p.precio_venta, p.stock_actual, p.stock_minimo,
                       p.imagen, p.estado, p.created_at,
                       c.nombre AS categoria_nombre
                FROM productos p
                INNER JOIN categorias c ON p.categoria_id = c.id
                WHERE p.estado = 'ACTIVO'";

        $params = [];

        if (!empty($busqueda)) {
            $sql .= " AND (p.nombre LIKE :busqueda OR p.codigo_barras LIKE :busqueda)";
            $params[':busqueda'] = '%' . trim($busqueda) . '%';
        }

        if (!empty($categoriaId)) {
            $sql .= " AND p.categoria_id = :categoria_id";
            $params[':categoria_id'] = $categoriaId;
        }

        $sql .= " ORDER BY p.id DESC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Obtiene un producto por su clave primaria ID
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre
                FROM productos p
                INNER JOIN categorias c ON p.categoria_id = c.id
                WHERE p.id = :id AND p.estado = 'ACTIVO'
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $prod = $stmt->fetch();
        return $prod ?: null;
    }

    /**
     * Busca un producto por su código de barras único (EAN/UPC o SKU)
     */
    public function obtenerPorCodigo(string $codigo): ?array {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre
                FROM productos p
                INNER JOIN categorias c ON p.categoria_id = c.id
                WHERE p.codigo_barras = :codigo AND p.estado = 'ACTIVO'
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        $stmt->execute();

        $prod = $stmt->fetch();
        return $prod ?: null;
    }

    /**
     * Registra un nuevo producto en el catálogo
     */
    public function crear(array $datos): int {
        $sql = "INSERT INTO productos (categoria_id, codigo_barras, nombre, descripcion,
                                       precio_compra, precio_venta, stock_actual, stock_minimo,
                                       estado)
                VALUES (:categoria_id, :codigo_barras, :nombre, :descripcion,
                        :precio_compra, :precio_venta, :stock_actual, :stock_minimo,
                        'ACTIVO')";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':categoria_id', (int)$datos['categoria_id'], PDO::PARAM_INT);
        $stmt->bindValue(':codigo_barras', trim($datos['codigo_barras']), PDO::PARAM_STR);
        $stmt->bindValue(':nombre', trim($datos['nombre']), PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', trim($datos['descripcion'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':precio_compra', (float)$datos['precio_compra']);
        $stmt->bindValue(':precio_venta', (float)$datos['precio_venta']);
        $stmt->bindValue(':stock_actual', (int)($datos['stock_actual'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':stock_minimo', (int)($datos['stock_minimo'] ?? 5), PDO::PARAM_INT);
        $stmt->execute();

        return (int)$this->lastInsertId();
    }

    /**
     * Actualiza los datos y niveles de existencias de un producto
     */
    public function actualizar(int $id, array $datos): bool {
        $sql = "UPDATE productos 
                SET categoria_id = :categoria_id,
                    codigo_barras = :codigo_barras,
                    nombre = :nombre,
                    descripcion = :descripcion,
                    precio_compra = :precio_compra,
                    precio_venta = :precio_venta,
                    stock_actual = :stock_actual,
                    stock_minimo = :stock_minimo
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':categoria_id', (int)$datos['categoria_id'], PDO::PARAM_INT);
        $stmt->bindValue(':codigo_barras', trim($datos['codigo_barras']), PDO::PARAM_STR);
        $stmt->bindValue(':nombre', trim($datos['nombre']), PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', trim($datos['descripcion'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':precio_compra', (float)$datos['precio_compra']);
        $stmt->bindValue(':precio_venta', (float)$datos['precio_venta']);
        $stmt->bindValue(':stock_actual', (int)$datos['stock_actual'], PDO::PARAM_INT);
        $stmt->bindValue(':stock_minimo', (int)$datos['stock_minimo'], PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Ejecuta una baja lógica del producto en el catálogo
     */
    public function eliminar(int $id): bool {
        $sql = "UPDATE productos SET estado = 'INACTIVO' WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Obtiene métricas agregadas del inventario para los KPI Cards
     */
    public function obtenerEstadisticas(): array {
        $sql = "SELECT 
                    COUNT(*) AS total_productos,
                    COALESCE(SUM(stock_actual), 0) AS total_unidades,
                    COALESCE(SUM(CASE WHEN stock_actual <= stock_minimo AND stock_actual > 0 THEN 1 ELSE 0 END), 0) AS stock_bajo,
                    COALESCE(SUM(CASE WHEN stock_actual <= 0 THEN 1 ELSE 0 END), 0) AS agotados
                FROM productos 
                WHERE estado = 'ACTIVO'";

        $stmt = $this->db->query($sql);
        $res = $stmt->fetch();
        return $res ?: [
            'total_productos' => 0,
            'total_unidades' => 0,
            'stock_bajo' => 0,
            'agotados' => 0
        ];
    }
}

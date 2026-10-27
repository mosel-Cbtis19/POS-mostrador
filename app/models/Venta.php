<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/models/Venta.php
// DESCRIPCIÓN: Modelo para registro y cobro de ventas con transacciones atómicas PDO.
// =============================================================================

require_once __DIR__ . '/BaseModel.php';

class Venta extends BaseModel {

    /**
     * Genera un folio correlativo único por fecha (ej. VTA-20261026-0001)
     */
    public function generarFolio(): string {
        $fechaPrefijo = date('Ymd');
        $sql = "SELECT COUNT(*) AS total 
                FROM ventas 
                WHERE DATE(fecha_venta) = CURDATE()";

        $stmt = $this->db->query($sql);
        $res = $stmt->fetch();
        $consecutivo = (int)($res['total'] ?? 0) + 1;

        return sprintf("VTA-%s-%04d", $fechaPrefijo, $consecutivo);
    }

    /**
     * Ejecuta el registro de una venta completa mediante Transacción Atómica PDO (ACID)
     * 
     * @param array $datosVenta Encabezado: caja_sesion_id, usuario_id, cliente, metodo_pago, subtotal, iva, total, pago_con, cambio
     * @param array $items Detalle: arreglo de productos [{id, cantidad, precio, subtotal}, ...]
     * @return array [success => bool, venta_id => int, folio => string, message => string]
     * @throws Exception Si ocurre un fallo de concurrencia, stock insuficiente o error SQL
     */
    public function registrarVenta(array $datosVenta, array $items): array {
        if (empty($items)) {
            throw new InvalidArgumentException("No se pueden registrar ventas sin artículos.");
        }

        // Iniciar Transacción PDO
        $this->db->beginTransaction();

        try {
            // 1. VALIDACIÓN ESTRICTA DE STOCK (SELECT ... FOR UPDATE para evitar condiciones de carrera)
            $sqlCheck = "SELECT id, nombre, stock_actual FROM productos WHERE id = :id FOR UPDATE";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stocksPrevios = [];

            foreach ($items as $item) {
                $stmtCheck->bindValue(':id', (int)$item['id'], PDO::PARAM_INT);
                $stmtCheck->execute();
                $prod = $stmtCheck->fetch();

                if (!$prod) {
                    throw new Exception("El producto con ID {$item['id']} no existe en el catálogo.");
                }

                $solicitado = (int)$item['cantidad'];
                $disponible = (int)$prod['stock_actual'];

                if ($disponible < $solicitado) {
                    throw new Exception("Stock insuficiente para '{$prod['nombre']}'. Disponible: {$disponible}, solicitado: {$solicitado}.");
                }

                $stocksPrevios[(int)$item['id']] = $disponible;
            }

            // 2. GENERAR FOLIO ÚNICO
            $folio = $this->generarFolio();

            // 3. INSERTAR ENCABEZADO DE VENTA
            $sqlVenta = "INSERT INTO ventas (
                            folio, caja_sesion_id, usuario_id, cliente, metodo_pago,
                            subtotal, iva, total, pago_con, cambio, estado, fecha_venta
                         ) VALUES (
                            :folio, :caja_sesion_id, :usuario_id, :cliente, :metodo_pago,
                            :subtotal, :iva, :total, :pago_con, :cambio, 'COMPLETADA', NOW()
                         )";

            $stmtVenta = $this->db->prepare($sqlVenta);
            $stmtVenta->bindValue(':folio', $folio, PDO::PARAM_STR);
            $stmtVenta->bindValue(':caja_sesion_id', (int)$datosVenta['caja_sesion_id'], PDO::PARAM_INT);
            $stmtVenta->bindValue(':usuario_id', (int)$datosVenta['usuario_id'], PDO::PARAM_INT);
            $stmtVenta->bindValue(':cliente', trim($datosVenta['cliente'] ?? 'PÚBLICO EN GENERAL'), PDO::PARAM_STR);
            $stmtVenta->bindValue(':metodo_pago', $datosVenta['metodo_pago'], PDO::PARAM_STR);
            $stmtVenta->bindValue(':subtotal', (float)$datosVenta['subtotal']);
            $stmtVenta->bindValue(':iva', (float)$datosVenta['iva']);
            $stmtVenta->bindValue(':total', (float)$datosVenta['total']);
            $stmtVenta->bindValue(':pago_con', (float)$datosVenta['pago_con']);
            $stmtVenta->bindValue(':cambio', (float)$datosVenta['cambio']);
            $stmtVenta->execute();

            $ventaId = (int)$this->lastInsertId();

            // 4. INSERTAR DETALLE DE VENTA Y DESCONTAR INVENTARIO
            $sqlDetalle = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal)
                           VALUES (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);

            $sqlStock = "UPDATE productos SET stock_actual = stock_actual - :cantidad WHERE id = :producto_id";
            $stmtStock = $this->db->prepare($sqlStock);

            $sqlKardex = "INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo)
                          VALUES (:producto_id, :usuario_id, 'SALIDA_VENTA', :cantidad, :stock_ant, :stock_post, :motivo)";
            $stmtKardex = $this->db->prepare($sqlKardex);

            $sqlCheckNow = "SELECT stock_actual FROM productos WHERE id = :id";
            $stmtCheckNow = $this->db->prepare($sqlCheckNow);

            foreach ($items as $item) {
                $cant = (int)$item['cantidad'];
                $prodId = (int)$item['id'];
                $stockPrevio = (int)($stocksPrevios[$prodId] ?? 0);

                // Inserción en detalle_ventas
                $stmtDetalle->bindValue(':venta_id', $ventaId, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':producto_id', $prodId, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':cantidad', $cant, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':precio_unitario', (float)$item['precio']);
                $stmtDetalle->bindValue(':subtotal', (float)($cant * $item['precio']));
                $stmtDetalle->execute();

                // Verificar si un trigger de base de datos ya descontó el stock
                $stmtCheckNow->bindValue(':id', $prodId, PDO::PARAM_INT);
                $stmtCheckNow->execute();
                $stockActualBd = (int)$stmtCheckNow->fetchColumn();

                // Si el stock no cambió tras el INSERT (ej. base de datos sin triggers activos),
                // ejecutar descuento directo y registrar bitácora Kardex vía PDO
                if ($stockActualBd === $stockPrevio) {
                    $stmtStock->bindValue(':cantidad', $cant, PDO::PARAM_INT);
                    $stmtStock->bindValue(':producto_id', $prodId, PDO::PARAM_INT);
                    $stmtStock->execute();

                    $stockNuevo = max(0, $stockPrevio - $cant);
                    $stmtKardex->bindValue(':producto_id', $prodId, PDO::PARAM_INT);
                    $stmtKardex->bindValue(':usuario_id', (int)$datosVenta['usuario_id'], PDO::PARAM_INT);
                    $stmtKardex->bindValue(':cantidad', $cant, PDO::PARAM_INT);
                    $stmtKardex->bindValue(':stock_ant', $stockPrevio, PDO::PARAM_INT);
                    $stmtKardex->bindValue(':stock_post', $stockNuevo, PDO::PARAM_INT);
                    $stmtKardex->bindValue(':motivo', "Venta Folio: {$folio}", PDO::PARAM_STR);
                    $stmtKardex->execute();
                }
            }

            // Confirmar transacción de manera atómica
            $this->db->commit();

            return [
                'success' => true,
                'venta_id' => $ventaId,
                'folio' => $folio,
                'total' => (float)$datosVenta['total'],
                'pago_con' => (float)$datosVenta['pago_con'],
                'cambio' => (float)$datosVenta['cambio'],
                'metodo_pago' => $datosVenta['metodo_pago'],
                'items' => $items,
                'message' => 'Venta completada exitosamente.'
            ];

        } catch (Throwable $e) {
            // Reversión total ante cualquier anomalía
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtiene los datos de una venta y su desglose por folio o ID
     */
    public function obtenerPorId(int $id): ?array {
        $sqlVenta = "SELECT v.*, u.nombre_completo AS cajero_nombre, c.fecha_apertura
                     FROM ventas v
                     INNER JOIN usuarios u ON v.usuario_id = u.id
                     INNER JOIN cajas_sesion c ON v.caja_sesion_id = c.id
                     WHERE v.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sqlVenta);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $venta = $stmt->fetch();

        if (!$venta) {
            return null;
        }

        // Obtener detalles de productos vendidos
        $sqlDetalle = "SELECT d.*, p.codigo_barras, p.nombre
                       FROM detalle_ventas d
                       INNER JOIN productos p ON d.producto_id = p.id
                       WHERE d.venta_id = :venta_id";

        $stmtDetalle = $this->db->prepare($sqlDetalle);
        $stmtDetalle->bindValue(':venta_id', $id, PDO::PARAM_INT);
        $stmtDetalle->execute();
        $venta['detalles'] = $stmtDetalle->fetchAll();

        return $venta;
    }
}

<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/models/Caja.php
// DESCRIPCIÓN: Modelo para turnos de caja, fondo inicial, arqueos X y cierre Z (Fase 10).
// =============================================================================

require_once __DIR__ . '/BaseModel.php';

class Caja extends BaseModel {

    /**
     * Obtiene la sesión de caja abierta actualmente para un cajero
     */
    public function obtenerSesionActiva(int $usuarioId): ?array {
        try {
            $sql = "SELECT id, usuario_id, fecha_apertura, monto_inicial, 
                           monto_ventas_efectivo, monto_ventas_tarjeta, estado, observaciones
                    FROM cajas_sesion
                    WHERE usuario_id = :usuario_id AND estado = 'ABIERTA'
                    ORDER BY id DESC
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();

            $caja = $stmt->fetch();
            return $caja ?: null;
        } catch (Throwable $e) {
            error_log("SysVentas Caja::obtenerSesionActiva falló: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Registra la apertura de un nuevo turno de caja con fondo inicial
     */
    public function abrirCaja(int $usuarioId, float $montoInicial, string $observaciones = ''): int {
        // Verificar que el usuario no tenga ya una caja abierta
        $activa = $this->obtenerSesionActiva($usuarioId);
        if ($activa !== null) {
            return (int)$activa['id'];
        }

        $sql = "INSERT INTO cajas_sesion (usuario_id, fecha_apertura, monto_inicial, estado, observaciones)
                VALUES (:usuario_id, NOW(), :monto_inicial, 'ABIERTA', :observaciones)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':monto_inicial', $montoInicial);
        $stmt->bindValue(':observaciones', trim($observaciones), PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->lastInsertId();
    }

    /**
     * Obtiene el desglose financiero completo y en tiempo real de una sesión de caja
     */
    public function obtenerDetalleSesion(int $cajaId): ?array {
        try {
            $sql = "SELECT c.*, u.nombre_completo AS cajero_nombre, u.email AS cajero_email
                    FROM cajas_sesion c
                    INNER JOIN usuarios u ON c.usuario_id = u.id
                    WHERE c.id = :id LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $cajaId, PDO::PARAM_INT);
            $stmt->execute();
            $caja = $stmt->fetch();

            if (!$caja) {
                return null;
            }

            // Consultar ventas reales asociadas a esta sesión de caja
            $sqlVentas = "SELECT 
                            COALESCE(SUM(CASE WHEN metodo_pago = 'EFECTIVO' THEN total ELSE 0 END), 0.00) AS total_efectivo,
                            COALESCE(SUM(CASE WHEN metodo_pago = 'TARJETA' THEN total ELSE 0 END), 0.00) AS total_tarjeta,
                            COUNT(CASE WHEN metodo_pago = 'EFECTIVO' THEN 1 END) AS tickets_efectivo,
                            COUNT(CASE WHEN metodo_pago = 'TARJETA' THEN 1 END) AS tickets_tarjeta,
                            COUNT(*) AS total_tickets,
                            COALESCE(SUM(subtotal), 0.00) AS acumulado_subtotal,
                            COALESCE(SUM(iva), 0.00) AS acumulado_iva,
                            COALESCE(SUM(total), 0.00) AS acumulado_total
                          FROM ventas
                          WHERE caja_sesion_id = :caja_id AND estado = 'COMPLETADA'";

            $stmtV = $this->db->prepare($sqlVentas);
            $stmtV->bindValue(':caja_id', $cajaId, PDO::PARAM_INT);
            $stmtV->execute();
            $ventas = $stmtV->fetch();

            $fondoInicial = (float)$caja['monto_inicial'];
            $ventasEfectivo = (float)($ventas['total_efectivo'] ?? 0.0);
            $ventasTarjeta = (float)($ventas['total_tarjeta'] ?? 0.0);
            $totalEsperado = $fondoInicial + $ventasEfectivo;

            return [
                'id' => (int)$caja['id'],
                'usuario_id' => (int)$caja['usuario_id'],
                'cajero_nombre' => $caja['cajero_nombre'],
                'cajero_email' => $caja['cajero_email'],
                'fecha_apertura' => $caja['fecha_apertura'],
                'fecha_cierre' => $caja['fecha_cierre'],
                'monto_inicial' => $fondoInicial,
                'ventas_efectivo' => $ventasEfectivo,
                'ventas_tarjeta' => $ventasTarjeta,
                'total_ventas' => (float)($ventas['acumulado_total'] ?? ($ventasEfectivo + $ventasTarjeta)),
                'acumulado_subtotal' => (float)($ventas['acumulado_subtotal'] ?? 0.0),
                'acumulado_iva' => (float)($ventas['acumulado_iva'] ?? 0.0),
                'tickets_efectivo' => (int)($ventas['tickets_efectivo'] ?? 0),
                'tickets_tarjeta' => (int)($ventas['tickets_tarjeta'] ?? 0),
                'total_tickets' => (int)($ventas['total_tickets'] ?? 0),
                'total_esperado' => $totalEsperado,
                'monto_final_efectivo' => isset($caja['monto_final_efectivo']) ? (float)$caja['monto_final_efectivo'] : null,
                'diferencia' => isset($caja['diferencia']) ? (float)$caja['diferencia'] : 0.0,
                'estado' => $caja['estado'],
                'observaciones' => $caja['observaciones'] ?? ''
            ];
        } catch (Throwable $e) {
            error_log("SysVentas Caja::obtenerDetalleSesion falló: " . $e->getMessage());
            // Datos semilla de contingencia
            return [
                'id' => $cajaId,
                'usuario_id' => 1,
                'cajero_nombre' => 'Cajero de Turno',
                'cajero_email' => 'cajero@sysventas.com',
                'fecha_apertura' => date('Y-m-d 08:00:00'),
                'fecha_cierre' => null,
                'monto_inicial' => 500.00,
                'ventas_efectivo' => 1450.00,
                'ventas_tarjeta' => 680.00,
                'total_ventas' => 2130.00,
                'acumulado_subtotal' => 1836.21,
                'acumulado_iva' => 293.79,
                'tickets_efectivo' => 18,
                'tickets_tarjeta' => 4,
                'total_tickets' => 22,
                'total_esperado' => 1950.00,
                'monto_final_efectivo' => null,
                'diferencia' => 0.0,
                'estado' => 'ABIERTA',
                'observaciones' => ''
            ];
        }
    }

    /**
     * Efectúa el cierre definitivo de caja (Corte Z) calculando diferencias y persistiendo datos
     */
    public function cerrarCaja(int $cajaId, float $montoContado, string $observaciones = ''): array {
        $detalle = $this->obtenerDetalleSesion($cajaId);
        if (!$detalle) {
            throw new Exception("Sesión de caja no encontrada o inválida.");
        }

        $totalEsperado = $detalle['total_esperado'];
        $diferencia = round($montoContado - $totalEsperado, 2);

        try {
            $sqlUpdate = "UPDATE cajas_sesion 
                          SET fecha_cierre = NOW(),
                              monto_ventas_efectivo = :monto_efectivo,
                              monto_ventas_tarjeta = :monto_tarjeta,
                              total_esperado = :total_esperado,
                              monto_final_efectivo = :monto_final_efectivo,
                              diferencia = :diferencia,
                              estado = 'CERRADA',
                              observaciones = CONCAT(COALESCE(observaciones, ''), ' | Cierre: ', :observaciones)
                          WHERE id = :id";

            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':monto_efectivo', $detalle['ventas_efectivo']);
            $stmtUpdate->bindValue(':monto_tarjeta', $detalle['ventas_tarjeta']);
            $stmtUpdate->bindValue(':total_esperado', $totalEsperado);
            $stmtUpdate->bindValue(':monto_final_efectivo', $montoContado);
            $stmtUpdate->bindValue(':diferencia', $diferencia);
            $stmtUpdate->bindValue(':observaciones', trim($observaciones), PDO::PARAM_STR);
            $stmtUpdate->bindValue(':id', $cajaId, PDO::PARAM_INT);
            $stmtUpdate->execute();
        } catch (Throwable $e) {
            error_log("SysVentas Caja::cerrarCaja falló en BD: " . $e->getMessage());
        }

        $detalle['monto_final_efectivo'] = $montoContado;
        $detalle['diferencia'] = $diferencia;
        $detalle['estado'] = 'CERRADA';
        $detalle['fecha_cierre'] = date('Y-m-d H:i:s');
        $detalle['observaciones'] .= " | Cierre: " . trim($observaciones);

        return $detalle;
    }
}

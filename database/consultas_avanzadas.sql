-- =============================================================================
-- PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
-- INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
-- MÓDULO IV: Desarrolla Sistemas Web en un Sistema de Información
-- SUBMÓDULOS: Construye Aplicaciones Web / Implementa Aplicaciones Web
-- CICLO ESCOLAR: 2026-2027 | 5to Semestre
-- ARCHIVO: database/consultas_avanzadas.sql
-- DESCRIPCIÓN: Consultas SQL analíticas (JOIN, GROUP BY, HAVING, Subconsultas),
--              Vistas del Negocio (Views) y Disparadores (Triggers) de Auditoría.
-- =============================================================================

USE sysventas;

-- =============================================================================
-- SECCIÓN 1: VISTAS DEL SISTEMA (VIEWS PARA DASHBOARD Y REPORTES)
-- =============================================================================

-- Vista 1: Resumen de ventas diarias consolidando cajero, sesión de caja y totales
CREATE OR REPLACE VIEW vista_resumen_ventas_diarias AS
SELECT 
    DATE(v.fecha_venta) AS fecha,
    COUNT(v.id) AS total_transacciones,
    SUM(v.total) AS ingreso_bruto,
    SUM(v.subtotal) AS ingreso_sin_iva,
    SUM(v.iva) AS iva_acumulado,
    ROUND(AVG(v.total), 2) AS ticket_promedio,
    SUM(CASE WHEN v.metodo_pago = 'EFECTIVO' THEN v.total ELSE 0 END) AS total_efectivo,
    SUM(CASE WHEN v.metodo_pago = 'TARJETA' THEN v.total ELSE 0 END) AS total_tarjeta
FROM ventas v
WHERE v.estado = 'COMPLETADA'
GROUP BY DATE(v.fecha_venta);

-- Vista 2: Ranking de productos con mayor volumen y utilidad bruta generada
CREATE OR REPLACE VIEW vista_top_productos AS
SELECT 
    p.id AS producto_id,
    p.codigo_barras,
    p.nombre AS producto_nombre,
    c.nombre AS categoria_nombre,
    p.precio_compra,
    p.precio_venta,
    SUM(dv.cantidad) AS unidades_vendidas,
    SUM(dv.subtotal) AS ingresos_totales,
    SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)) AS ganancia_bruta_real,
    ROUND(
        (SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)) / NULLIF(SUM(dv.subtotal), 0)) * 100, 
        2
    ) AS margen_porcentual
FROM detalle_ventas dv
INNER JOIN productos p ON dv.producto_id = p.id
INNER JOIN categorias c ON p.categoria_id = c.id
INNER JOIN ventas v ON dv.venta_id = v.id
WHERE v.estado = 'COMPLETADA'
GROUP BY p.id, p.codigo_barras, p.nombre, c.nombre, p.precio_compra, p.precio_venta;

-- Vista 3: Desempeño y volumen de transacciones por usuario cajero
CREATE OR REPLACE VIEW vista_rendimiento_cajeros AS
SELECT 
    u.id AS usuario_id,
    u.nombre_completo AS cajero,
    u.email,
    COUNT(v.id) AS transacciones_atendidas,
    COALESCE(SUM(v.total), 0.00) AS total_cobrado,
    COALESCE(ROUND(AVG(v.total), 2), 0.00) AS ticket_promedio
FROM usuarios u
LEFT JOIN ventas v ON u.id = v.usuario_id AND v.estado = 'COMPLETADA'
WHERE u.rol_id = 2 -- Solo usuarios Cajeros
GROUP BY u.id, u.nombre_completo, u.email;

-- Vista 4: Auditoría y trazabilidad detallada del Kardex de inventario
CREATE OR REPLACE VIEW vista_auditoria_kardex AS
SELECT 
    m.id AS movimiento_id,
    m.fecha,
    p.codigo_barras,
    p.nombre AS producto_nombre,
    m.tipo,
    m.cantidad,
    m.stock_anterior,
    m.stock_nuevo,
    m.motivo,
    u.nombre_completo AS responsable
FROM movimientos_inventario m
INNER JOIN productos p ON m.producto_id = p.id
INNER JOIN usuarios u ON m.usuario_id = u.id;


-- =============================================================================
-- SECCIÓN 2: CONSULTAS ANALÍTICAS AVANZADAS (COMPETENCIAS MÓDULO IV)
-- =============================================================================

-- Consulta 1: INNER JOIN múltiple para desglose completo de tickets de venta
-- Permite auditar el encabezado, cajero que cobró, sesión de caja y artículo vendido.
SELECT 
    v.folio,
    v.fecha_venta,
    u.nombre_completo AS cajero,
    v.cliente,
    v.metodo_pago,
    p.nombre AS producto,
    c.nombre AS categoria,
    dv.cantidad,
    dv.precio_unitario,
    dv.subtotal,
    v.total AS total_ticket
FROM ventas v
INNER JOIN usuarios u ON v.usuario_id = u.id
INNER JOIN detalle_ventas dv ON v.id = dv.venta_id
INNER JOIN productos p ON dv.producto_id = p.id
INNER JOIN categorias c ON p.categoria_id = c.id
WHERE v.estado = 'COMPLETADA'
ORDER BY v.fecha_venta DESC, dv.id ASC
LIMIT 50;

-- Consulta 2: LEFT JOIN para auditoría de productos sin movimiento (Stock Inmovilizado)
-- Identifica artículos existentes en inventario que no se han vendido en el ciclo.
SELECT 
    p.id,
    p.codigo_barras,
    p.nombre,
    c.nombre AS categoria,
    p.stock_actual,
    p.precio_compra,
    (p.stock_actual * p.precio_compra) AS capital_inmovilizado,
    COALESCE(SUM(dv.cantidad), 0) AS unidades_vendidas
FROM productos p
INNER JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN detalle_ventas dv ON p.id = dv.producto_id
GROUP BY p.id, p.codigo_barras, p.nombre, c.nombre, p.stock_actual, p.precio_compra
HAVING unidades_vendidas = 0
ORDER BY capital_inmovilizado DESC;

-- Consulta 3: GROUP BY con HAVING para detectar categorías de alta demanda comercial
-- Filtra aquellas categorías cuya recaudación total supere el promedio general.
SELECT 
    c.nombre AS categoria,
    COUNT(DISTINCT p.id) AS total_productos_categoria,
    SUM(dv.cantidad) AS piezas_vendidas,
    SUM(dv.subtotal) AS ingreso_recaudado
FROM categorias c
INNER JOIN productos p ON c.id = p.categoria_id
INNER JOIN detalle_ventas dv ON p.id = dv.producto_id
INNER JOIN ventas v ON dv.venta_id = v.id
WHERE v.estado = 'COMPLETADA'
GROUP BY c.id, c.nombre
HAVING ingreso_recaudado > 100.00
ORDER BY ingreso_recaudado DESC;

-- Consulta 4: Subconsultas para cálculo de KPIs del día (Métricas Financieras del Dashboard)
-- Obtiene en un solo registro atómico: total ventas, ganancia neta, ticket promedio y alertas de stock.
SELECT 
    (SELECT COALESCE(SUM(total), 0.00) FROM ventas WHERE DATE(fecha_venta) = CURDATE() AND estado = 'COMPLETADA') AS total_ventas_hoy,
    (SELECT COALESCE(SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)), 0.00)
     FROM detalle_ventas dv
     INNER JOIN productos p ON dv.producto_id = p.id
     INNER JOIN ventas v ON dv.venta_id = v.id
     WHERE DATE(v.fecha_venta) = CURDATE() AND v.estado = 'COMPLETADA') AS ganancia_bruta_hoy,
    (SELECT COUNT(*) FROM ventas WHERE DATE(fecha_venta) = CURDATE() AND estado = 'COMPLETADA') AS tickets_emitidos_hoy,
    (SELECT COALESCE(ROUND(AVG(total), 2), 0.00) FROM ventas WHERE DATE(fecha_venta) = CURDATE() AND estado = 'COMPLETADA') AS ticket_promedio_hoy,
    (SELECT COUNT(*) FROM productos WHERE stock_actual <= stock_minimo AND estado = 'ACTIVO') AS productos_bajo_stock;

-- Consulta 5: Subconsulta correlacionada para comparar ticket actual contra el ticket promedio global
SELECT 
    v.folio,
    v.fecha_venta,
    v.total,
    (SELECT ROUND(AVG(total), 2) FROM ventas WHERE estado = 'COMPLETADA') AS promedio_historico,
    ROUND(v.total - (SELECT AVG(total) FROM ventas WHERE estado = 'COMPLETADA'), 2) AS desviacion_vs_promedio
FROM ventas v
WHERE v.estado = 'COMPLETADA'
ORDER BY v.fecha_venta DESC
LIMIT 20;


-- =============================================================================
-- SECCIÓN 3: DISPARADORES ADICIONALES (TRIGGERS DE AUDITORÍA Y SEGURIDAD)
-- =============================================================================

DELIMITER $$

-- Disparador 3: Bloquear preventivamente cualquier intento de fijar stock negativo
DROP TRIGGER IF EXISTS tr_prevenir_stock_negativo$$
CREATE TRIGGER tr_prevenir_stock_negativo
BEFORE UPDATE ON productos
FOR EACH ROW
BEGIN
    IF NEW.stock_actual < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error de Negocio (RN-02): El stock actual del producto no puede ser inferior a 0.';
    END IF;
END$$

-- Disparador 4: Auditoría automática ante cambios de precio de venta o compra
DROP TRIGGER IF EXISTS tr_auditoria_cambio_precio$$
CREATE TRIGGER tr_auditoria_cambio_precio
AFTER UPDATE ON productos
FOR EACH ROW
BEGIN
    IF OLD.precio_venta <> NEW.precio_venta OR OLD.precio_compra <> NEW.precio_compra THEN
        INSERT INTO movimientos_inventario (
            producto_id, usuario_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo, fecha
        ) VALUES (
            NEW.id,
            1, -- Administrador del Sistema
            'AJUSTE_INVENTARIO',
            0,
            NEW.stock_actual,
            NEW.stock_actual,
            CONCAT('Ajuste de Precios: Compra ($', OLD.precio_compra, ' -> $', NEW.precio_compra, '), Venta ($', OLD.precio_venta, ' -> $', NEW.precio_venta, ')'),
            NOW()
        );
    END IF;
END$$

DELIMITER ;

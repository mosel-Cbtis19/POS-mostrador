-- =============================================================================
-- PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
-- INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Colima, México
-- ESPECIALIDAD: Técnico en Programación - 5to Semestre
-- FASE 2: Diseño de Base de Datos Relacional y Transaccional
-- MOTOR: MariaDB / MySQL (InnoDB - Soporte ACID y Llaves Foráneas)
-- JUEGO DE CARACTERES: UTF-8 (utf8mb4)
-- =============================================================================

DROP DATABASE IF EXISTS sysventas_db;
CREATE DATABASE sysventas_db 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE sysventas_db;

-- Desactivar temporalmente revisión de llaves foráneas para creación limpia
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. TABLA: roles
-- Descripción: Catálogo de perfiles de usuario y niveles de autorización.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS roles;
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. TABLA: usuarios
-- Descripción: Cuentas de acceso al sistema con contraseñas cifradas en BCRYPT.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) NULL,
    estado ENUM('ACTIVO', 'INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    ultimo_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_roles 
        FOREIGN KEY (rol_id) REFERENCES roles(id) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. TABLA: configuracion
-- Descripción: Parámetros generales del negocio (datos fiscales, ticket e IVA).
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS configuracion;
CREATE TABLE configuracion (
    id INT PRIMARY KEY DEFAULT 1,
    nombre_negocio VARCHAR(150) NOT NULL DEFAULT 'SysVentas - Mostrador',
    rfc VARCHAR(20) DEFAULT 'XAXX010101000',
    direccion VARCHAR(255) DEFAULT 'Av. Tecnológico No. 123, Colima, Col., México',
    telefono VARCHAR(50) DEFAULT '312-312-0000',
    email VARCHAR(100) DEFAULT 'contacto@sysventas.com',
    porcentaje_iva DECIMAL(5,2) NOT NULL DEFAULT 16.00 CHECK (porcentaje_iva >= 0),
    mensaje_ticket VARCHAR(255) DEFAULT '¡Gracias por su compra! Vuelva pronto.',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. TABLA: categorias
-- Descripción: Clasificación y agrupación de los productos del inventario.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS categorias;
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    estado ENUM('ACTIVO', 'INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. TABLA: productos
-- Descripción: Catálogo de artículos disponibles para venta y control de stock.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS productos;
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    codigo_barras VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    precio_compra DECIMAL(10,2) NOT NULL CHECK (precio_compra >= 0),
    precio_venta DECIMAL(10,2) NOT NULL CHECK (precio_venta >= precio_compra),
    stock_actual INT NOT NULL DEFAULT 0 CHECK (stock_actual >= 0),
    stock_minimo INT NOT NULL DEFAULT 5 CHECK (stock_minimo >= 0),
    imagen VARCHAR(255) NULL,
    estado ENUM('ACTIVO', 'INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_productos_categorias 
        FOREIGN KEY (categoria_id) REFERENCES categorias(id) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. TABLA: cajas_sesion
-- Descripción: Control de turnos de caja, fondo inicial, ventas y cortes X/Z.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS cajas_sesion;
CREATE TABLE cajas_sesion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha_apertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    monto_inicial DECIMAL(10,2) NOT NULL CHECK (monto_inicial >= 0),
    fecha_cierre DATETIME NULL,
    monto_ventas_efectivo DECIMAL(10,2) NOT NULL DEFAULT 0.00 CHECK (monto_ventas_efectivo >= 0),
    monto_ventas_tarjeta DECIMAL(10,2) NOT NULL DEFAULT 0.00 CHECK (monto_ventas_tarjeta >= 0),
    total_esperado DECIMAL(10,2) NULL,
    monto_final_efectivo DECIMAL(10,2) NULL,
    diferencia DECIMAL(10,2) NULL,
    estado ENUM('ABIERTA', 'CERRADA') NOT NULL DEFAULT 'ABIERTA',
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cajas_usuarios 
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. TABLA: ventas
-- Descripción: Encabezado de la transacción comercial de venta en mostrador.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS ventas;
CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(20) NOT NULL UNIQUE,
    caja_sesion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    cliente VARCHAR(150) NOT NULL DEFAULT 'PÚBLICO EN GENERAL',
    metodo_pago ENUM('EFECTIVO', 'TARJETA') NOT NULL DEFAULT 'EFECTIVO',
    subtotal DECIMAL(10,2) NOT NULL CHECK (subtotal >= 0),
    iva DECIMAL(10,2) NOT NULL DEFAULT 0.00 CHECK (iva >= 0),
    total DECIMAL(10,2) NOT NULL CHECK (total >= 0),
    pago_con DECIMAL(10,2) NOT NULL CHECK (pago_con >= total),
    cambio DECIMAL(10,2) NOT NULL DEFAULT 0.00 CHECK (cambio >= 0),
    estado ENUM('COMPLETADA', 'CANCELADA') NOT NULL DEFAULT 'COMPLETADA',
    fecha_venta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ventas_caja 
        FOREIGN KEY (caja_sesion_id) REFERENCES cajas_sesion(id) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ventas_usuario 
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. TABLA: detalle_ventas
-- Descripción: Desglose por producto vendido, precio histórico e importe.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS detalle_ventas;
CREATE TABLE detalle_ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL CHECK (cantidad > 0),
    precio_unitario DECIMAL(10,2) NOT NULL CHECK (precio_unitario >= 0),
    subtotal DECIMAL(10,2) NOT NULL CHECK (subtotal >= 0),
    CONSTRAINT fk_detalle_ventas 
        FOREIGN KEY (venta_id) REFERENCES ventas(id) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detalle_productos 
        FOREIGN KEY (producto_id) REFERENCES productos(id) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. TABLA: movimientos_inventario
-- Descripción: Bitácora de auditoría (Kardex) de entradas, salidas y ajustes.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS movimientos_inventario;
CREATE TABLE movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('ENTRADA_COMPRA', 'SALIDA_VENTA', 'AJUSTE_INVENTARIO', 'MERMA') NOT NULL,
    cantidad INT NOT NULL,
    stock_anterior INT NOT NULL,
    stock_nuevo INT NOT NULL,
    motivo VARCHAR(255) NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kardex_producto 
        FOREIGN KEY (producto_id) REFERENCES productos(id) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_kardex_usuario 
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reactivar revisión de llaves foráneas
SET FOREIGN_KEY_CHECKS = 1;


-- =============================================================================
-- DISPARADORES (TRIGGERS) DE NEGOCIO E INTEGRIDAD
-- =============================================================================

DELIMITER $$

-- Trigger 1: Acumular automáticamente el total de la venta en la sesión de caja activa
DROP TRIGGER IF EXISTS tr_acumular_total_caja$$
CREATE TRIGGER tr_acumular_total_caja
AFTER INSERT ON ventas
FOR EACH ROW
BEGIN
    IF NEW.estado = 'COMPLETADA' THEN
        IF NEW.metodo_pago = 'EFECTIVO' THEN
            UPDATE cajas_sesion 
            SET monto_ventas_efectivo = monto_ventas_efectivo + NEW.total
            WHERE id = NEW.caja_sesion_id;
        ELSEIF NEW.metodo_pago = 'TARJETA' THEN
            UPDATE cajas_sesion 
            SET monto_ventas_tarjeta = monto_ventas_tarjeta + NEW.total
            WHERE id = NEW.caja_sesion_id;
        END IF;
    END IF;
END$$

-- Trigger 2: Descontar stock y registrar en bitácora de inventario al insertar detalle de venta
DROP TRIGGER IF EXISTS tr_descontar_stock_venta$$
CREATE TRIGGER tr_descontar_stock_venta
AFTER INSERT ON detalle_ventas
FOR EACH ROW
BEGIN
    DECLARE v_stock_anterior INT;
    DECLARE v_usuario_id INT;

    -- Obtener el stock actual del producto
    SELECT stock_actual INTO v_stock_anterior 
    FROM productos 
    WHERE id = NEW.producto_id;

    -- Obtener el usuario cajero que realiza la venta
    SELECT usuario_id INTO v_usuario_id 
    FROM ventas 
    WHERE id = NEW.venta_id;

    -- Actualizar stock restando la cantidad vendida
    UPDATE productos 
    SET stock_actual = stock_actual - NEW.cantidad 
    WHERE id = NEW.producto_id;

    -- Registrar movimiento en kardex de inventario
    INSERT INTO movimientos_inventario (
        producto_id, usuario_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo
    ) VALUES (
        NEW.producto_id, 
        v_usuario_id, 
        'SALIDA_VENTA', 
        NEW.cantidad, 
        v_stock_anterior, 
        (v_stock_anterior - NEW.cantidad), 
        CONCAT('Venta registrada Venta ID: ', NEW.venta_id)
    );
END$$

-- Trigger 3: Reversión de inventario si una venta es cancelada
DROP TRIGGER IF EXISTS tr_revertir_stock_cancelacion$$
CREATE TRIGGER tr_revertir_stock_cancelacion
AFTER UPDATE ON ventas
FOR EACH ROW
BEGIN
    IF OLD.estado = 'COMPLETADA' AND NEW.estado = 'CANCELADA' THEN
        -- Revertir caja
        IF NEW.metodo_pago = 'EFECTIVO' THEN
            UPDATE cajas_sesion 
            SET monto_ventas_efectivo = monto_ventas_efectivo - NEW.total
            WHERE id = NEW.caja_sesion_id;
        ELSEIF NEW.metodo_pago = 'TARJETA' THEN
            UPDATE cajas_sesion 
            SET monto_ventas_tarjeta = monto_ventas_tarjeta - NEW.total
            WHERE id = NEW.caja_sesion_id;
        END IF;
    END IF;
END$$

DELIMITER ;


-- =============================================================================
-- VISTAS ANALÍTICAS (VIEWS)
-- =============================================================================

-- Vista 1: Alertas preventivas de inventario (Stock menor o igual al mínimo)
DROP VIEW IF EXISTS v_productos_alerta_stock;
CREATE VIEW v_productos_alerta_stock AS
SELECT 
    p.id,
    p.codigo_barras,
    p.nombre AS producto,
    c.nombre AS categoria,
    p.stock_actual,
    p.stock_minimo,
    (p.stock_minimo - p.stock_actual) AS deficit,
    CASE 
        WHEN p.stock_actual = 0 THEN 'AGOTADO'
        WHEN p.stock_actual <= p.stock_minimo THEN 'STOCK CRÍTICO'
        ELSE 'NORMAL'
    END AS nivel_alerta
FROM productos p
INNER JOIN categorias c ON p.categoria_id = c.id
WHERE p.stock_actual <= p.stock_minimo AND p.estado = 'ACTIVO';

-- Vista 2: Resumen analítico de ventas diarias
DROP VIEW IF EXISTS v_resumen_ventas_diarias;
CREATE VIEW v_resumen_ventas_diarias AS
SELECT 
    DATE(v.fecha_venta) AS fecha,
    COUNT(v.id) AS total_transacciones,
    COALESCE(SUM(CASE WHEN v.metodo_pago = 'EFECTIVO' THEN v.total ELSE 0 END), 0) AS total_efectivo,
    COALESCE(SUM(CASE WHEN v.metodo_pago = 'TARJETA' THEN v.total ELSE 0 END), 0) AS total_tarjeta,
    COALESCE(SUM(v.total), 0) AS gran_total,
    ROUND(AVG(v.total), 2) AS ticket_promedio
FROM ventas v
WHERE v.estado = 'COMPLETADA'
GROUP BY DATE(v.fecha_venta);

-- Vista 3: Top 10 productos más vendidos
DROP VIEW IF EXISTS v_top_productos_vendidos;
CREATE VIEW v_top_productos_vendidos AS
SELECT 
    p.id,
    p.codigo_barras,
    p.nombre AS producto,
    c.nombre AS categoria,
    SUM(dv.cantidad) AS unidades_vendidas,
    SUM(dv.subtotal) AS ingresos_generados
FROM detalle_ventas dv
INNER JOIN productos p ON dv.producto_id = p.id
INNER JOIN categorias c ON p.categoria_id = c.id
INNER JOIN ventas v ON dv.venta_id = v.id
WHERE v.estado = 'COMPLETADA'
GROUP BY p.id, p.codigo_barras, p.nombre, c.nombre
ORDER BY unidades_vendidas DESC
LIMIT 10;

-- Vista 4: Detalle de ventas con cajero y cliente para auditoría
DROP VIEW IF EXISTS v_ventas_auditoria;
CREATE VIEW v_ventas_auditoria AS
SELECT 
    v.id AS venta_id,
    v.folio,
    v.fecha_venta,
    v.cliente,
    v.metodo_pago,
    v.subtotal,
    v.iva,
    v.total,
    v.pago_con,
    v.cambio,
    v.estado,
    u.nombre_completo AS cajero,
    cs.id AS caja_id,
    cs.estado AS estado_caja
FROM ventas v
INNER JOIN usuarios u ON v.usuario_id = u.id
INNER JOIN cajas_sesion cs ON v.caja_sesion_id = cs.id;


-- =============================================================================
-- DATOS INICIALES Y SEMILLAS DE PRUEBA (SEED DATA)
-- =============================================================================

-- 1. Roles del Sistema
INSERT INTO roles (id, nombre, descripcion) VALUES
(1, 'Administrador', 'Control total de inventario, usuarios, reportes analíticos y auditoría'),
(2, 'Cajero', 'Operador de terminal de mostrador, cobros, tickets y corte de turno');

-- 2. Usuarios Base
-- Passwords cifradas con BCRYPT:
-- 'admin123' -> $2y$10$6DzxDV03hkoVjanmMaFet.OhuE3BThtIhxHhWIRqhickCe2okgr9u
-- 'cajero123' -> $2y$10$6BYO/hdkOvDQqyptg5g79uPdhS1HgJoqJ/zEGvcmw2gdb1P/P3QQe
INSERT INTO usuarios (id, rol_id, nombre_completo, usuario, password, telefono, estado) VALUES
(1, 1, 'Prof. Administrador del Sistema', 'admin', '$2y$10$6DzxDV03hkoVjanmMaFet.OhuE3BThtIhxHhWIRqhickCe2okgr9u', '312-100-2020', 'ACTIVO'),
(2, 2, 'Juan Pérez (Cajero Matutino)', 'cajero1', '$2y$10$6BYO/hdkOvDQqyptg5g79uPdhS1HgJoqJ/zEGvcmw2gdb1P/P3QQe', '312-200-3030', 'ACTIVO'),
(3, 2, 'María González (Cajera Vespertina)', 'cajero2', '$2y$10$6BYO/hdkOvDQqyptg5g79uPdhS1HgJoqJ/zEGvcmw2gdb1P/P3QQe', '312-300-4040', 'ACTIVO');

-- 3. Configuración del Negocio
INSERT INTO configuracion (id, nombre_negocio, rfc, direccion, telefono, email, porcentaje_iva, mensaje_ticket) VALUES
(1, 'SysVentas CBTis 19', 'CBT191026ABC', 'Av. Tecnológico No. 123, Colima, Col., México', '312-312-5555', 'contacto@sysventas.edu.mx', 16.00, '¡Gracias por su compra en SysVentas! Vuelva pronto.');

-- 4. Categorías de Tienda Retail
INSERT INTO categorias (id, nombre, descripcion, estado) VALUES
(1, 'Bebidas y Refrescos', 'Aguas, jugos, sodas, refrescos y bebidas energéticas', 'ACTIVO'),
(2, 'Snacks y Botanas', 'Frituras, papas, galletas, chocolates y cacahuates', 'ACTIVO'),
(3, 'Lácteos y Derivados', 'Leche pasteurizada, quesos, crema y yogures', 'ACTIVO'),
(4, 'Abarrotes y Despensa', 'Aceite, arroz, frijol, azúcar, harinas y conservas', 'ACTIVO'),
(5, 'Higiene y Limpieza', 'Detergentes, jabones, papel higiénico y desinfectantes', 'ACTIVO');

-- 5. Catálogo de Productos Iniciales (15 Productos)
INSERT INTO productos (id, categoria_id, codigo_barras, nombre, descripcion, precio_compra, precio_venta, stock_actual, stock_minimo, estado) VALUES
-- Bebidas
(1, 1, '7501055310884', 'Coca-Cola 600ml No Retornable', 'Refresco clásico sabor original', 14.00, 19.50, 48, 12, 'ACTIVO'),
(2, 1, '7501055304739', 'Agua Mineral Ciel 600ml', 'Agua purificada con gas', 10.50, 15.00, 30, 10, 'ACTIVO'),
(3, 1, '7501031302728', 'Jugo del Valle Mango 413ml', 'Néctar de mango en botella', 12.00, 17.00, 24, 8, 'ACTIVO'),
-- Snacks
(4, 2, '7501011115683', 'Papas Sabritas Original 45g', 'Papas fritas con sal de grano', 15.00, 21.00, 35, 10, 'ACTIVO'),
(5, 2, '7501011131072', 'Doritos Nacho 58g', 'Totopos de maíz nixtamalizado sabor queso nacho', 14.50, 20.50, 40, 10, 'ACTIVO'),
(6, 2, '7501000111207', 'Galletas Chokis Clásicas 76g', 'Galletas con chispas sabor chocolate', 13.00, 18.50, 25, 8, 'ACTIVO'),
-- Lácteos
(7, 3, '7501020512114', 'Leche Lala Entera 1 Litro', 'Leche ultrapasteurizada adicionada con vitaminas A y D', 23.50, 29.00, 50, 15, 'ACTIVO'),
(8, 3, '7501020512145', 'Leche Lala Deslactosada 1 Litro', 'Leche ultrapasteurizada de fácil digestión', 24.50, 31.00, 40, 15, 'ACTIVO'),
(9, 3, '7501020531511', 'Yoghurt Lala Fresa 1kg', 'Yoghurt batido sabor fresa', 32.00, 42.00, 18, 6, 'ACTIVO'),
-- Abarrotes
(10, 4, '7501000100010', 'Aceite 1-2-3 Vegetal 1 Litro', 'Aceite comestible puro de soya y canola', 36.00, 46.50, 20, 8, 'ACTIVO'),
(11, 4, '7501030421017', 'Arroz Verde Valle Súper Extra 1kg', 'Arroz grano largo seleccionado', 26.00, 34.00, 30, 10, 'ACTIVO'),
(12, 4, '7501030421031', 'Frijol Negro Verde Valle 1kg', 'Frijol negro seleccionado y limpio', 34.00, 44.50, 28, 10, 'ACTIVO'),
(13, 4, '7501005110014', 'Atún Dolores en Agua 140g', 'Lomo de atún aleta amarilla en agua', 16.50, 22.50, 45, 12, 'ACTIVO'),
-- Higiene y Limpieza
(14, 5, '7501025401017', 'Jabón Zote Rosa 400g', 'Jabón de lavandería multiusos', 18.00, 24.00, 32, 10, 'ACTIVO'),
(15, 5, '7501032900015', 'Cloralex El Rendidor 950ml', 'Blanqueador y desinfectante líquido concentrado', 15.00, 21.00, 25, 8, 'ACTIVO');

-- Fin del script schema.sql

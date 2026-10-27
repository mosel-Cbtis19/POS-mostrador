# SysVentas - Sistema de Punto de Venta de Mostrador (POS)

<div align="center">

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MariaDB%2FMySQL-InnoDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6%2B%20Fetch%20API-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Architecture](https://img.shields.io/badge/Architecture-MVC%20Vanilla-green?style=for-the-badge)
![Security](https://img.shields.io/badge/Security-BCRYPT%20%7C%20CSRF%20%7C%20PDO-red?style=for-the-badge)
![Status](https://img.shields.io/badge/Status-Completado%20100%25-success?style=for-the-badge)

</div>

---

### Datos Institucionales y Académicos
* **Institución:** Centro de Bachillerato Tecnológico Industrial y de Servicios No. 19 *"Miguel Hidalgo y Costilla"*
* **Ubicación:** Colima, Colima, México
* **Dirección General:** Dirección General de Educación Tecnológica Industrial y de Servicios (DGETI)
* **Módulo Profesional:** Módulo IV: Desarrolla Sistemas Web en un Sistema de Información
* **Submódulos:** 
  - Submódulo 1: Construye Aplicaciones Web
  - Submódulo 2: Implementa Aplicaciones Web
* **Nivel y Ciclo:** 5to Semestre de Bachillerato Técnico en Programación — Ciclo Escolar 2026-2027

---

## 1. Descripción del Proyecto

**SysVentas** es una solución integral de software web diseñada específicamente para el entorno de **Punto de Venta de Mostrador (POS)** en comercios minoristas, abarrotes, ferreterías y tiendas de conveniencia. El sistema centraliza la operación diaria comercial garantizando:

* **Cobro ultra-ágil en mostrador:** Búsqueda predictiva instantánea, soporte directo para lectores de códigos de barras mediante evento `Enter`, y atajos de teclado (`F2`, `F3`, `F10`).
* **Integridad transaccional ACID:** Operaciones atómicas con MariaDB/MySQL mediante `BEGIN`, `COMMIT` y `ROLLBACK`, con bloqueo pesimista `SELECT ... FOR UPDATE` para evitar sobreventas concurrentes.
* **Control estricto de caja y turnos:** Apertura de turno con fondo inicial en gaveta, arqueo parcial en tiempo real (**Corte X**) y cierre definitivo auditado con cálculo automático de sobrante/faltante (**Corte Z**).
* **Mantenimiento y semáforo de inventarios:** Control de existencias físicas, stock mínimo de seguridad, cálculo de rentabilidad y prevención de precios por debajo del costo.
* **Gestión administrativa de personal:** Control de acceso basado en roles (**RBAC**), registro de nuevos cajeros con cifrado **BCRYPT** y módulo de **baja lógica** (`ACTIVO` / `INACTIVO`).
* **Formatos de comprobante térmico:** Emisión de tickets de mostrador y tickets de corte de caja compatibles con impresoras térmicas de 58mm y 80mm (`window.print()`).

---

## 2. Arquitectura de Software

SysVentas está construido bajo el patrón arquitectónico **Modelo-Vista-Controlador (MVC)** desacoplado en PHP nativo, eliminando dependencias pesadas y garantizando un alto rendimiento:

```text
punto_de_venta/
├── app/
│   ├── config/              # Configuración global y credenciales
│   │   ├── config.php       # Constantes del sistema, URLs base y directorios
│   │   └── database.php     # Conexión Singleton PDO segura (MySQL/MariaDB)
│   ├── controllers/         # Lógica de negocio y controladores HTTP
│   │   ├── AuthController.php      # Autenticación, control de intentos y sesiones
│   │   ├── BaseController.php      # Guardias RBAC, sanitización y protección CSRF
│   │   ├── CajaController.php      # Apertura de turno, Arqueo X y Corte Z
│   │   ├── PosController.php       # Terminal de ventas y cobro asíncrono
│   │   ├── ProductoController.php  # Catálogo de productos, existencias y costos
│   │   ├── ReporteController.php   # Dashboard estadístico, Top productos y ventas
│   │   └── UsuarioController.php   # Gestión y baja lógica de cajeros (Admin)
│   ├── models/              # Modelos de datos con sentencias preparadas PDO
│   │   ├── BaseModel.php           # Abstracción PDO para todos los modelos
│   │   ├── Caja.php                # Consultas y estados de sesiones de caja
│   │   ├── Categoria.php           # Departamentos y categorías de productos
│   │   ├── Producto.php            # Inventario, códigos de barra y semáforo
│   │   ├── Reporte.php             # Métricas financieras avanzadas y agregaciones
│   │   ├── Rol.php                 # Roles y permisos de usuario
│   │   ├── Usuario.php             # Usuarios, verificación BCRYPT y bajas lógicas
│   │   └── Venta.php               # Transacciones atómicas, folios y detalles
│   └── views/               # Plantillas visuales en Bootstrap 5
│       ├── auth/                   # Inicio de sesión (Login)
│       ├── caja/                   # Apertura, arqueo y corte Z
│       ├── layouts/                # Header, Navbar con rol y Footer
│       ├── pos/                    # Terminal de mostrador POS y tickets
│       ├── productos/              # Inventario y control de artículos
│       ├── reportes/               # Dashboard administrativo y gráficas
│       └── usuarios/               # Administración y baja de personal
├── database/                # Scripts SQL y diseño relacional
│   ├── schema.sql           # Tablas InnoDB 3FN, llaves foráneas, triggers y semillas
│   └── consultas_avanzadas.sql # Consultas complejas (JOIN, GROUP BY, HAVING, Vistas)
├── public/                  # Directorio público expuesto al servidor web
│   ├── assets/
│   │   ├── css/styles.css   # Estilos personalizados para POS de mostrador
│   │   └── js/
│   │       ├── main.js      # Atajos de teclado e inicialización global
│   │       └── pos.js       # Estado reactivo en memoria (Map) y cobro Fetch API
│   ├── index.php            # Front Controller centralizado con lista blanca
│   └── .htaccess            # Enrutamiento semántico y protección perimetral
├── tests/                   # Banco de pruebas automatizadas
│   ├── TestCase.php         # Micro-framework de aserciones para CLI
│   └── run_tests.php        # Suite ejecutable con 25 aserciones automatizadas
├── .gitignore               # Exclusiones de archivos locales y del entorno
├── .htaccess                # Redirección transparente de raíz a /public
├── index.php                # Front controller raíz de compatibilidad
└── README.md                # Manual del sistema y guía de instalación
```

---

## 3. Requisitos del Sistema

* **Servidor Web:** Apache 2.4+ (incluido en XAMPP, WampServer o LAMP).
* **Manejador de Base de Datos:** MySQL 5.7+ o MariaDB 10.4+ con motor **InnoDB**.
* **Lenguaje:** PHP 8.0, 8.1 o 8.2 con extensiones habilitadas:
  - `pdo_mysql` (Conexión segura y transaccional)
  - `session` (Gestión de sesiones)
  - `json` (Comunicación asíncrona API)
* **Navegador Web Moderno:** Google Chrome, Microsoft Edge, Mozilla Firefox o Safari.

---

## 4. Instalación y Despliegue en XAMPP

1. **Clonar o copiar el proyecto:**
   Ubicar la carpeta del proyecto dentro del directorio `htdocs` de XAMPP:
   ```text
   C:\xampp\htdocs\punto_de_venta
   ```

2. **Iniciar servicios en el Panel de Control de XAMPP:**
   - Iniciar **Apache** (Puerto 80 / 443).
   - Iniciar **MySQL** (Puerto 3306).

3. **Importar la Base de Datos:**
   - Abrir **phpMyAdmin** en el navegador: `http://localhost/phpmyadmin/`.
   - Crear una nueva base de datos denominada: `sysventas_db` con cotejamiento `utf8mb4_unicode_ci`.
   - Ir a la pestaña **Importar** y seleccionar el archivo del proyecto:
     [`database/schema.sql`](database/schema.sql)
   - Opcionalmente, importar las consultas analíticas y vistas de:
     [`database/consultas_avanzadas.sql`](database/consultas_avanzadas.sql).

4. **Acceder a la aplicación:**
   Abrir el navegador web e ingresar a la URL:
   ```text
   http://localhost/punto_de_venta/
   ```
   *(El sistema redirigirá de manera automática y transparente al Front Controller seguro en `public/index.php`).*

---

## 5. Credenciales de Acceso Predeterminadas

El script de inicialización de la base de datos provee las siguientes cuentas iniciales con contraseñas encriptadas mediante **BCRYPT** (cost 12):

| Rol de Usuario | Nombre Completo | Usuario | Contraseña | Permisos y Acceso |
| :--- | :--- | :--- | :--- | :--- |
| **Administrador** | Prof. Administrador del Sistema | `admin` | `admin123` | **Acceso Total:** Dashboard, Catálogo de Productos, Gestión de Cajeros, Cortes y Reportes. |
| **Cajero 1** | Juan Pérez (Cajero Matutino) | `cajero1` | `cajero123` | **Operativo:** Apertura de caja, Terminal POS mostrador, emisión de tickets y Arqueo / Corte Z. |
| **Cajero 2** | María González (Cajera Vespertina) | `cajero2` | `cajero123` | **Operativo:** Turno vespertino, cobros en mostrador y cierre de turno. |

> **Nota:** Las contraseñas iniciales para pruebas en entorno local son `admin123` y `cajero123`. El administrador puede registrar nuevos cajeros desde el módulo de personal.

---

## 6. Módulos y Flujos de Operación

### 6.1. Flujo Operativo del Cajero
1. **Inicio de Sesión:** El cajero ingresa sus credenciales en `views/auth/login.php`.
2. **Apertura de Caja:** Si no tiene una sesión abierta, el sistema exige ingresar el fondo inicial de gaveta (ej. `$500.00`) para desbloquear la terminal (Regla RN-01).
3. **Terminal POS Mostrador:** 
   - El cajero busca productos por nombre/departamento o escanea códigos de barras con pistola (evento `Enter`).
   - El carrito valida las existencias en tiempo real impidiendo sobreventas (Regla RN-02).
   - Se recalculan automáticamente Subtotal base, IVA (16%) y Total en pesos mexicanos.
4. **Cobro y Liquidación:**
   - Presionar `F10` o clic en **Cobrar Cuenta**.
   - Ingresar el dinero recibido en efectivo o elegir botones de billetes rápidos (`$100`, `$200`, `$500`).
   - El sistema calcula el cambio exacto o previene el cobro si el dinero es insuficiente.
   - Al confirmar, se genera la transacción ACID, se descuenta el stock y se despliega el ticket térmico imprimible.
5. **Cierre de Turno (Arqueo X y Corte Z):**
   - El cajero accede a **Cortes de Caja**.
   - Puede consultar el arqueo en tiempo real (**Corte X**).
   - Para cerrar, ingresa el dinero físico contado en gaveta. El sistema calcula si hay descuadre (**Sobrante**, **Faltante** o **Cuadratura Exacta**) y genera el ticket de Corte Z.

### 6.2. Flujo Administrativo
1. **Dashboard & Reportes:** Métricas en tiempo real de ventas del día, utilidad estimada, transacciones y top 5 artículos más vendidos.
2. **Productos & Stock:** Alta, edición y semáforo visual de existencias de artículos (Verde = Normal, Amarillo = Stock Bajo, Rojo = Agotado).
3. **Gestión de Cajeros:** Registro de nuevo personal con encriptación BCRYPT y módulo de baja lógica (`ACTIVO`/`INACTIVO`) que inhabilita el acceso de inmediato sin perder el historial de ventas previas.

---

## 7. Ejecución de Pruebas Automatizadas

El sistema incluye un micro-framework de pruebas unitarias y de integración para consola CLI que valida 25 aserciones de seguridad, finanzas, inventarios y BCRYPT.

Para ejecutar la suite de pruebas desde la terminal:
```bash
php tests/run_tests.php
```
*(O utilizando la ruta completa en XAMPP: `C:\xampp\php\php.exe tests/run_tests.php`)*

**Resultado esperado:**
```text
==========================================================
  BANCO DE PRUEBAS AUTOMATIZADAS - SYSVENTAS (CBTIS 19)   
==========================================================
Total de Aserciones: 25
Aprobadas (PASS):    25
Fallidas (FAIL):     0
Tiempo de Ejecución: ~1050 ms
Tasa de Éxito:       100%
==========================================================
```

---

## 8. Guion Rápido para Defensa y Demostración Académica

Para presentar el sistema ante el docente o jurado evaluador, siga este recorrido de 5 minutos:

1. **Presentación de Arquitectura:** Mostrar la estructura de carpetas MVC desacoplada y la conexión PDO Singleton.
2. **Prueba de Seguridad (RBAC y Login):**
   - Iniciar sesión como `cajero1` e intentar navegar a `?c=reporte&a=index` (Se activará el guardia HTTP 403 de acceso denegado).
3. **Flujo de Venta en Vivo:**
   - Realizar la apertura de turno con `$500.00` de fondo inicial.
   - En la Terminal POS, escanear o buscar "Galletas Emperador" y "Coca Cola".
   - Demostrar el bloqueo reactivo al intentar vender más unidades de las disponibles.
   - Cobrar con `$200.00`, validar el cambio y mostrar la ventana modal del comprobante térmico con folio `VTA-YYYYMMDD-XXXX`.
4. **Verificación de Inventario y Base de Datos:**
   - Comprobar cómo el stock disminuyó en MariaDB y en la interfaz.
5. **Corte Z:**
   - Realizar el arqueo, ingresar el efectivo contado y generar el comprobante de cierre de turno.
6. **Gestión de Personal (Admin):**
   - Iniciar sesión como `admin`, mostrar el Dashboard de ventas y dar de baja lógica al cajero demostrando que ya no puede autenticarse.

---

## 9. Historial y Matriz de Fases del Proyecto

| Fase | Título del Módulo | Entregables Principales | Estatus |
| :---: | :--- | :--- | :---: |
| **1** | Análisis y Requerimientos del POS | Matriz de requerimientos, reglas de negocio y actores. | Aprobada |
| **2** | Diseño de Base de Datos Relacional | Esquema InnoDB 3FN, llaves foráneas, triggers y datos semilla. | Aprobada |
| **3** | Arquitectura MVC y Wireframes | Esqueleto MVC, Front Controller y diagramas de pantalla. | Aprobada |
| **4** | Construcción de Interfaz Gráfica | Vistas HTML5/Bootstrap 5, componentes responsivos y layouts. | Aprobada |
| **5** | Conexión PDO y Autenticación RBAC | Singleton PDO, contraseñas BCRYPT, guardias de seguridad. | Aprobada |
| **6** | CRUD Productos e Inventarios | Mantenimiento de catálogo, validación de costo y semáforo. | Aprobada |
| **7** | Dinamismo de Mostrador JS | Carrito reactivo en memoria (`Map`), atajos de teclado y eventos. | Aprobada |
| **8** | Módulo de Cobro y Transacciones ACID | Bloqueo pesimista `FOR UPDATE`, cobro Fetch y ticket térmico. | Aprobada |
| **9** | Consultas SQL Avanzadas y Triggers | Scripts JOIN, GROUP BY, HAVING, subconsultas y triggers. | Aprobada |
| **10** | Cortes de Caja y Reportes | Apertura de caja, Arqueo X, Corte Z y Dashboard de ventas. | Aprobada |
| **11** | Seguridad Integral y Validaciones | Token CSRF, rate-limiting, cookies HttpOnly y .htaccess. | Aprobada |
| **12** | Matriz de Pruebas y QA | Suite automatizada de 25 pruebas CLI y matriz de 25 casos manuales. | Aprobada |
| **13** | Documentación Final y Presentación | Manual de usuario, manual técnico, guion de defensa y README. | **Completada** |

---

<div align="center">
<b>SysVentas POS — Desarrollado con dedicación técnica y rigor académico.</b><br>
CBTis No. 19 "Miguel Hidalgo y Costilla" — Colima, México — 2026-2027.
</div>

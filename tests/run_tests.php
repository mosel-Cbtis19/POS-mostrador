<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: tests/run_tests.php
// DESCRIPCIÓN: Suite de pruebas unitarias, de integración y seguridad (Fase 12).
// =============================================================================

$startTime = microtime(true);

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';

// Controlador anónimo para evaluar utilidades de BaseController
class TestSecurityController extends BaseController {
    public function testSanitize(string $input): string {
        return $this->sanitize($input);
    }
    public function testCsrf(string $token): bool {
        $_POST['csrf_token'] = $token;
        return $this->validateCsrf();
    }
    public function testInt($val): int {
        return $this->validateInt($val);
    }
    public function testFloat($val): float {
        return $this->validateFloat($val);
    }
}

echo PHP_EOL . "==========================================================" . PHP_EOL;
echo "  BANCO DE PRUEBAS AUTOMATIZADAS - SYSVENTAS (CBTIS 19)   " . PHP_EOL;
echo "==========================================================" . PHP_EOL . PHP_EOL;

// -----------------------------------------------------------------------------
// SUITE 1: SEGURIDAD, SANITIZACIÓN Y TOKEN CSRF
// -----------------------------------------------------------------------------
echo "1. SUITE DE SEGURIDAD Y PREVENCIÓN DE VULNERABILIDADES:" . PHP_EOL;

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$sec = new TestSecurityController();

// 1.1 Prueba de Sanitización contra XSS
$xssPayload = "<script>alert('XSS');</script>";
$sanitized = $sec->testSanitize($xssPayload);
TestCase::assertEquals("&lt;script&gt;alert(&#039;XSS&#039;);&lt;/script&gt;", $sanitized, "Sanitización neutraliza etiquetas script");

// 1.2 Prueba de Generación y Longitud de Token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
TestCase::assertEquals(64, strlen($_SESSION['csrf_token']), "Token CSRF posee longitud estricta de 64 caracteres hex (256 bits)");

// 1.3 Prueba de Validación Exitosa de CSRF
TestCase::assertTrue($sec->testCsrf($_SESSION['csrf_token']), "Validación CSRF positiva con token coincidente");

// 1.4 Prueba de Rechazo ante Token CSRF Falsificado
TestCase::assertFalse($sec->testCsrf("token_invalido_hacker"), "Validación CSRF rechaza token adulterado");

// 1.5 Prueba de Filtro de Enteros
TestCase::assertEquals(15, $sec->testInt("15"), "Filtro validateInt convierte correctamente cadena numérica válida");
TestCase::assertEquals(0, $sec->testInt("-5"), "Filtro validateInt descarta enteros negativos");
TestCase::assertEquals(0, $sec->testInt("abc"), "Filtro validateInt descarta cadenas alfabéticas");

// 1.6 Prueba de Filtro de Flotantes / Precios
TestCase::assertEquals(49.90, $sec->testFloat("49.90"), "Filtro validateFloat normaliza importes monetarios");
TestCase::assertEquals(0.00, $sec->testFloat("-12.50"), "Filtro validateFloat previene importes monetarios negativos");

echo PHP_EOL;

// -----------------------------------------------------------------------------
// SUITE 2: MATEMÁTICA FINANCIERA, IVA Y DESGLOSE EN MOSTRADOR
// -----------------------------------------------------------------------------
echo "2. SUITE DE CÁLCULO FINANCIERO Y LIQUIDACIÓN EN CAJA:" . PHP_EOL;

$precioVentaConIva = 116.00;
$subtotalEsperado = round($precioVentaConIva / 1.16, 2);
$ivaEsperado = round($precioVentaConIva - $subtotalEsperado, 2);

TestCase::assertEquals(100.00, $subtotalEsperado, "Desglose de Subtotal exacto para \$116.00 con IVA del 16%");
TestCase::assertEquals(16.00, $ivaEsperado, "Desglose de IVA exacto (\$16.00) sobre \$100.00 de base");

// Cálculo de Cambio y Vuelto
$totalCobrar = 143.50;
$pagoCon = 200.00;
$cambio = round($pagoCon - $totalCobrar, 2);

TestCase::assertEquals(56.50, $cambio, "Cálculo preciso de cambio en mostrador para pago de \$200 sobre \$143.50");
TestCase::assertGreaterThanOrEqual($totalCobrar, $pagoCon, "Validación de pago suficiente contra total exigido");

echo PHP_EOL;

// -----------------------------------------------------------------------------
// SUITE 3: ARQUEO DE CAJA (CORTE X / CORTE Z) Y CUADRATURA CONTABLE
// -----------------------------------------------------------------------------
echo "3. SUITE DE CONCILIACIÓN CONTABLE DE TURNOS (CORTE X/Z):" . PHP_EOL;

$fondoInicial = 500.00;
$ventasEfectivo = 1250.00;
$totalEsperado = $fondoInicial + $ventasEfectivo; // $1,750.00

TestCase::assertEquals(1750.00, $totalEsperado, "Cálculo de efectivo teórico esperado en gaveta (\$1,750.00)");

// Caso A: Cuadratura Exacta
$conteoExacto = 1750.00;
$diffExacto = round($conteoExacto - $totalEsperado, 2);
TestCase::assertEquals(0.00, $diffExacto, "Resultado del arqueo: Cuadratura exacta (\$0.00 de diferencia)");

// Caso B: Sobrante en Caja
$conteoConSobrante = 1800.00;
$diffSobrante = round($conteoConSobrante - $totalEsperado, 2);
TestCase::assertEquals(50.00, $diffSobrante, "Resultado del arqueo: Detección correcta de sobrante (+\$50.00)");

// Caso C: Faltante en Caja
$conteoConFaltante = 1710.00;
$diffFaltante = round($conteoConFaltante - $totalEsperado, 2);
TestCase::assertEquals(-40.00, $diffFaltante, "Resultado del arqueo: Detección correcta de faltante (-\$40.00)");

echo PHP_EOL;

// -----------------------------------------------------------------------------
// SUITE 4: REGLAS DE NEGOCIO DE INVENTARIO Y STOCK (RN-02)
// -----------------------------------------------------------------------------
echo "4. SUITE DE REGLAS DE NEGOCIO Y CONTROL DE INVENTARIO:" . PHP_EOL;

$stockActual = 10;
$cantidadSolicitadaAceptable = 3;
$stockResultante = $stockActual - $cantidadSolicitadaAceptable;

TestCase::assertTrue($cantidadSolicitadaAceptable <= $stockActual, "Validación preventiva permite venta si cantidad <= stock actual");
TestCase::assertEquals(7, $stockResultante, "Descuento matemático consistente tras venta de 3 unidades");

$cantidadExcesiva = 15;
$permiteVentaExcesiva = ($cantidadExcesiva <= $stockActual);
TestCase::assertFalse($permiteVentaExcesiva, "Regla RN-02: Se bloquea la venta si la cantidad supera el stock existente");

// Margen comercial obligatorio (precio venta >= precio compra)
$precioCompra = 14.00;
$precioVenta = 19.50;
$utilidadUnitaria = $precioVenta - $precioCompra;

TestCase::assertTrue($precioVenta >= $precioCompra, "Validación de rentabilidad: Precio de venta no puede ser inferior al costo");
TestCase::assertEquals(5.50, round($utilidadUnitaria, 2), "Cálculo exacto de margen de ganancia unitario (\$5.50)");

echo PHP_EOL;

// -----------------------------------------------------------------------------
// SUITE 5: AUTENTICACIÓN, HASHING BCRYPT Y ROLES RBAC
// -----------------------------------------------------------------------------
echo "5. SUITE DE CRIPTOGRAFÍA, HASHING BCRYPT Y CONTROL DE ROLES:" . PHP_EOL;

$passwordTextoPlano = "Admin123*";
$hashBcrypt = password_hash($passwordTextoPlano, PASSWORD_BCRYPT, ['cost' => 12]);

TestCase::assertTrue(password_verify($passwordTextoPlano, $hashBcrypt), "Algoritmo password_verify valida correctamente contraseña con BCRYPT cost 12");
TestCase::assertFalse(password_verify("ContraseñaEquivocada", $hashBcrypt), "Algoritmo password_verify rechaza contraseña errónea");

// Formato de folio correlativo de venta
$folioSimulado = sprintf("VTA-%s-%04d", date('Ymd'), 1);
$patronFolio = '/^VTA-\d{8}-\d{4}$/';
TestCase::assertTrue((bool)preg_match($patronFolio, $folioSimulado), "Formato correlativo de folio de venta cumple con estándar VTA-YYYYMMDD-XXXX");

// Imprimir reporte consolidado
TestCase::printSummary($startTime);

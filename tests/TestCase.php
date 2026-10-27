<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: tests/TestCase.php
// DESCRIPCIÓN: Micro-framework de aserciones para pruebas automatizadas (Fase 12).
// =============================================================================

class TestCase {
    private static int $passed = 0;
    private static int $failed = 0;
    private static array $failures = [];

    public static function assertEquals($expected, $actual, string $message = ''): void {
        if ($expected === $actual) {
            self::$passed++;
            echo "  [\033[32mPASS\033[0m] " . ($message ?: "Equals assertion passed") . PHP_EOL;
        } else {
            self::$failed++;
            $error = "Expected: " . var_export($expected, true) . ", got: " . var_export($actual, true);
            self::$failures[] = ($message ? "$message - " : "") . $error;
            echo "  [\033[31mFAIL\033[0m] " . ($message ?: "Equals assertion failed") . " ($error)" . PHP_EOL;
        }
    }

    public static function assertTrue(bool $condition, string $message = ''): void {
        if ($condition === true) {
            self::$passed++;
            echo "  [\033[32mPASS\033[0m] " . ($message ?: "True assertion passed") . PHP_EOL;
        } else {
            self::$failed++;
            self::$failures[] = $message ?: "Expected true, got false";
            echo "  [\033[31mFAIL\033[0m] " . ($message ?: "Expected true, got false") . PHP_EOL;
        }
    }

    public static function assertFalse(bool $condition, string $message = ''): void {
        if ($condition === false) {
            self::$passed++;
            echo "  [\033[32mPASS\033[0m] " . ($message ?: "False assertion passed") . PHP_EOL;
        } else {
            self::$failed++;
            self::$failures[] = $message ?: "Expected false, got true";
            echo "  [\033[31mFAIL\033[0m] " . ($message ?: "Expected false, got true") . PHP_EOL;
        }
    }

    public static function assertGreaterThanOrEqual($expected, $actual, string $message = ''): void {
        if ($actual >= $expected) {
            self::$passed++;
            echo "  [\033[32mPASS\033[0m] " . ($message ?: "GTE assertion passed") . PHP_EOL;
        } else {
            self::$failed++;
            $error = "Actual ($actual) is less than expected ($expected)";
            self::$failures[] = ($message ? "$message - " : "") . $error;
            echo "  [\033[31mFAIL\033[0m] " . ($message ?: "GTE assertion failed") . " ($error)" . PHP_EOL;
        }
    }

    public static function printSummary(float $startTime): void {
        $elapsed = round((microtime(true) - $startTime) * 1000, 2);
        $total = self::$passed + self::$failed;

        echo PHP_EOL . "==========================================================" . PHP_EOL;
        echo "               RESUMEN DE PRUEBAS SYSVENTAS               " . PHP_EOL;
        echo "==========================================================" . PHP_EOL;
        echo "Total de Aserciones: " . $total . PHP_EOL;
        echo "Aprobadas (\033[32mPASS\033[0m):    " . self::$passed . PHP_EOL;
        echo "Fallidas (\033[31mFAIL\033[0m):     " . self::$failed . PHP_EOL;
        echo "Tiempo de Ejecución: " . $elapsed . " ms" . PHP_EOL;
        echo "Tasa de Éxito:       " . ($total > 0 ? round((self::$passed / $total) * 100, 1) : 0) . "%" . PHP_EOL;
        echo "==========================================================" . PHP_EOL;

        if (self::$failed > 0) {
            echo PHP_EOL . "Detalle de Fallos:" . PHP_EOL;
            foreach (self::$failures as $idx => $f) {
                echo " " . ($idx + 1) . ". $f" . PHP_EOL;
            }
            exit(1);
        } else {
            echo PHP_EOL . "\033[32mTODAS LAS PRUEBAS UNITARIAS E INTEGRALES FUERON SATISFACTORIAS.\033[0m" . PHP_EOL;
            exit(0);
        }
    }
}

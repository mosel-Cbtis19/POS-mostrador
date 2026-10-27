<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: index.php (Raíz del Proyecto)
// DESCRIPCIÓN: Redirección automática y transparente hacia el Front Controller (public/).
// =============================================================================

// Redirigir de manera limpia al directorio público
header("Location: public/");
exit;

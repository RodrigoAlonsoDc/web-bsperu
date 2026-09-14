<?php
header('Content-Type: text/plain; charset=utf-8');

$host = '48.216.211.109';
$port = 80;
$db = 'BDTPED_SSA';
$user = 'SOPORTE';
$pass = 'SOPORTE';

$dsn = "odbc:Driver=FreeTDS;Server=$host;Port=$port;Database=$db;TDS_Version=7.4;ClientCharset=UTF-8;";

try {
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 8
    ]);
    echo "CONEXIÓN EXITOSA A BDTPED_SSA!\n\n";

    // 1. Asesores / Usuarios en COTCAB
    echo "=== ASESORES / USUARIOS EN COTCAB (Cotizaciones) ===\n";
    $q = $conn->query("SELECT LTRIM(RTRIM(CCUSER)) as asesor, COUNT(*) as total_cots, SUM(CAST(CCIMPORTE as float)) as monto_total, MAX(CCFECDOC) as ultima_fecha FROM COTCAB WHERE CCUSER IS NOT NULL AND CCUSER != '' GROUP BY CCUSER ORDER BY total_cots DESC");
    print_r($q->fetchAll(PDO::FETCH_ASSOC));

    // 2. Tablas de Vendedores en BDTPED_SSA y 003BDCOMUN
    echo "\n=== TABLAS DE VENDEDORES EN BDTPED_SSA Y 003BDCOMUN ===\n";
    $qv = $conn->query("SELECT TABLE_CATALOG, TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%VEN%' UNION SELECT TABLE_CATALOG, TABLE_NAME FROM [003BDCOMUN].INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%VEN%'");
    print_r($qv->fetchAll(PDO::FETCH_ASSOC));

    // 3. Columnas de COTCAB relacionadas a vendedores / usuarios
    echo "\n=== COLUMNAS DE COTCAB (Vendedor / Usuario / Asesor) ===\n";
    $qc = $conn->query("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'COTCAB' AND (COLUMN_NAME LIKE '%VEN%' OR COLUMN_NAME LIKE '%USE%' OR COLUMN_NAME LIKE '%COD%')");
    print_r($qc->fetchAll(PDO::FETCH_ASSOC));

    // 4. Muestra de cotizaciones recientes con asesor y vendedor
    echo "\n=== COTIZACIONES RECIENTES CON ASESOR ===\n";
    $qs = $conn->query("SELECT TOP 5 CCNUMDOC, CCFECDOC, LTRIM(RTRIM(CCNOMBRE)) as cliente, LTRIM(RTRIM(CCUSER)) as usuario, CAST(CCIMPORTE as float) as importe FROM COTCAB ORDER BY CCFECDOC DESC, CCNUMDOC DESC");
    print_r($qs->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}


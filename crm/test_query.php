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

    // 1. Tablas en 003BDCOMUN
    echo "=== TABLAS EN 003BDCOMUN (Clientes y Productos) ===\n";
    $q = $conn->query("SELECT TABLE_NAME FROM [003BDCOMUN].INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' AND (TABLE_NAME LIKE '%CLI%' OR TABLE_NAME LIKE '%ART%' OR TABLE_NAME LIKE '%PRO%') ORDER BY TABLE_NAME");
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['TABLE_NAME']}\n";
    }

    echo "\n=== ÚLTIMOS CLIENTES EN 003BDCOMUN.MAECLI ===\n";
    try {
        $qc = $conn->query("SELECT TOP 3 * FROM [003BDCOMUN].dbo.MAECLI");
        print_r($qc->fetchAll(PDO::FETCH_ASSOC));
    } catch(Exception $e) {
        echo "MAECLI error: " . $e->getMessage() . "\n";
    }

    // 2. Columnas de COTCAB (Cotizaciones)
    echo "\n=== COLUMNAS DE COTCAB (Cotizaciones) ===\n";
    $q3 = $conn->query("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'COTCAB' ORDER BY ORDINAL_POSITION");
    while ($row = $q3->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['COLUMN_NAME']} ({$row['DATA_TYPE']})\n";
    }

    echo "\n=== ÚLTIMAS 3 COTIZACIONES EN COTCAB ===\n";
    $q4 = $conn->query("SELECT TOP 3 * FROM COTCAB ORDER BY 1 DESC");
    print_r($q4->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

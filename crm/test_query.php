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

    // 1. Columnas y contenido de [003BDCOMUN].dbo.VENDEDOR
    echo "=== COLUMNAS DE [003BDCOMUN].dbo.VENDEDOR ===\n";
    $qc = $conn->query("SELECT COLUMN_NAME, DATA_TYPE FROM [003BDCOMUN].INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'VENDEDOR' ORDER BY ORDINAL_POSITION");
    print_r($qc->fetchAll(PDO::FETCH_ASSOC));

    echo "\n=== LISTA DE VENDEDORES / ASESORES EN [003BDCOMUN].dbo.VENDEDOR ===\n";
    $qv = $conn->query("SELECT * FROM [003BDCOMUN].dbo.VENDEDOR");
    print_r($qv->fetchAll(PDO::FETCH_ASSOC));

    // 2. Relación de Vendedores en COTCAB (CCVENDE vs CCUSER)
    echo "\n=== VENDEDORES ACTIVOS HOY EN COTCAB ===\n";
    $qtoday = $conn->query("SELECT TOP 10 CCNUMDOC, CCFECDOC, CCVENDE, CCUSER, LTRIM(RTRIM(CCNOMBRE)) as cliente, CAST(CCIMPORTE as float) as total FROM COTCAB WHERE CCFECDOC >= '2026-09-01' ORDER BY CCFECDOC DESC, CCNUMDOC DESC");
    print_r($qtoday->fetchAll(PDO::FETCH_ASSOC));


} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}


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

    // 1. Estadísticas Generales de BDTPED_SSA
    echo "=== RESUMEN GENERAL DE TABLAS EN BDTPED_SSA ===\n";
    $qtables = $conn->query("SELECT TABLE_TYPE, COUNT(*) as cantidad FROM INFORMATION_SCHEMA.TABLES GROUP BY TABLE_TYPE");
    print_r($qtables->fetchAll(PDO::FETCH_ASSOC));

    // 2. Todas las tablas en BDTPED_SSA agrupadas por nombre
    echo "\n=== TODAS LAS TABLAS BASE EN BDTPED_SSA ===\n";
    $qall = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME");
    $tables = $qall->fetchAll(PDO::FETCH_COLUMN);
    echo "Total Tablas: " . count($tables) . "\n";
    echo implode(", ", $tables) . "\n\n";

    // 3. Cantidad de registros en las tablas clave operativas
    echo "=== CONTEO DE REGISTROS EN TABLAS PRINCIPALES ===\n";
    $keyTables = ['COTCAB', 'COTDET', 'PEDCAB', 'PEDDET', 'BDT_VENDEDORCUOTA', 'COTART', 'PEDART', 'CLIENTES', 'VENDEDOR'];
    foreach ($keyTables as $tbl) {
        try {
            $qc = $conn->query("SELECT COUNT(*) as total FROM $tbl");
            $cnt = $qc->fetch(PDO::FETCH_ASSOC)['total'];
            echo " - $tbl: " . number_format($cnt) . " registros\n";
        } catch (Exception $e) {
            // No existe o error
        }
    }

    // 4. Estructura de PEDCAB (Pedidos / Ventas / Comprobantes)
    echo "\n=== COLUMNAS PRINCIPALES DE PEDCAB (Pedidos de Venta) ===\n";
    try {
        $qp = $conn->query("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'PEDCAB' ORDER BY ORDINAL_POSITION");
        $cols = $qp->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo "   {$c['COLUMN_NAME']} ({$c['DATA_TYPE']})\n";
        }
    } catch(Exception $e) {
        echo "Error PEDCAB: " . $e->getMessage() . "\n";
    }

    // 5. Tabla de Cuotas de Vendedores (BDT_VENDEDORCUOTA)
    echo "\n=== BDT_VENDEDORCUOTA (Metas / Cuotas de Vendedores en BDTPED_SSA) ===\n";
    try {
        $qcuota = $conn->query("SELECT TOP 10 * FROM BDT_VENDEDORCUOTA");
        print_r($qcuota->fetchAll(PDO::FETCH_ASSOC));
    } catch(Exception $e) {
        echo "Error BDT_VENDEDORCUOTA: " . $e->getMessage() . "\n";
    }

    // 6. Últimos Pedidos registrados en PEDCAB (Hoy / Recientes)
    echo "\n=== ÚLTIMOS PEDIDOS REGISTRADOS EN PEDCAB ===\n";
    try {
        $qped = $conn->query("SELECT TOP 5 PCNUMPED, PCFECDOC, LTRIM(RTRIM(PCNOMCLI)) as cliente, LTRIM(RTRIM(PCUSER)) as usuario, PCVENDE, CAST(PCIMPNET as float) as total, PCESTADO FROM PEDCAB ORDER BY PCFECDOC DESC, PCNUMPED DESC");
        print_r($qped->fetchAll(PDO::FETCH_ASSOC));
    } catch(Exception $e) {
        echo "Error PEDCAB query: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "ERROR GLOBAL: " . $e->getMessage() . "\n";
}




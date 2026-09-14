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

    // Helper seguro para ejecutar queries con closeCursor
    function runQuery($conn, $sql) {
        $stmt = $conn->query($sql);
        if (!$stmt) return [];
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $res;
    }

    // 1. Conteo exacto de todas las tablas
    echo "=== CONTEO DE REGISTROS EN TODAS LAS TABLAS DE BDTPED_SSA ===\n";
    $tablas = [
        'COTCAB' => 'Cotizaciones (Cabecera)',
        'COTDET' => 'Cotizaciones (Detalle Ítems)',
        'PEDCAB' => 'Pedidos de Venta (Cabecera)',
        'PEDDET' => 'Pedidos de Venta (Detalle Ítems)',
        'FACCAB' => 'Facturación (Cabecera)',
        'FACDET' => 'Facturación (Detalle Ítems)',
        'GREMISION_CAB' => 'Guías de Remisión (Cabecera)',
        'GREMISION_DET' => 'Guías de Remisión (Detalle)',
        'LISPROART' => 'Lista de Precios / Artículos',
        'BDT_VENDEDORCUOTA' => 'Cuotas / Metas de Vendedores',
        'USUARIO_BS' => 'Usuarios del Sistema BS',
        'PROMOCAB' => 'Promociones (Cabecera)',
        'PROMODET' => 'Promociones (Detalle)',
        'lugares' => 'Lugares de Entrega / Despacho',
        'TIPODESP' => 'Tipos de Despacho',
        'TipoCompra' => 'Tipos de Compra / Pago'
    ];

    foreach ($tablas as $tbl => $desc) {
        try {
            $r = runQuery($conn, "SELECT COUNT(*) as c FROM $tbl");
            $cnt = $r[0]['c'] ?? 0;
            echo sprintf("  %-18s: %7s registros (%s)\n", $tbl, number_format($cnt), $desc);
        } catch (Exception $e) {
            echo "  $tbl: Error ({$e->getMessage()})\n";
        }
    }

    // 2. Vistas en BDTPED_SSA
    echo "\n=== VISTAS EN BDTPED_SSA ===\n";
    $vistas = runQuery($conn, "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='VIEW'");
    print_r($vistas);

    // 3. Usuarios en USUARIO_BS
    echo "\n=== USUARIOS EN USUARIO_BS ===\n";
    try {
        $u = runQuery($conn, "SELECT * FROM USUARIO_BS");
        print_r($u);
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    // 4. Cuotas de Vendedores (BDT_VENDEDORCUOTA)
    echo "\n=== CUOTAS DE VENDEDORES (BDT_VENDEDORCUOTA) ===\n";
    try {
        $cuotas = runQuery($conn, "SELECT TOP 10 * FROM BDT_VENDEDORCUOTA");
        print_r($cuotas);
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    // 5. Columnas exactas de PEDCAB
    echo "\n=== COLUMNAS EXACTAS DE PEDCAB ===\n";
    try {
        $cols = runQuery($conn, "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'PEDCAB' ORDER BY ORDINAL_POSITION");
        foreach ($cols as $c) {
            echo "   {$c['COLUMN_NAME']} ({$c['DATA_TYPE']})\n";
        }
        $sample = runQuery($conn, "SELECT TOP 3 * FROM PEDCAB ORDER BY 1 DESC");
        print_r($sample);
    } catch(Exception $e) {
        echo "Error PEDCAB: " . $e->getMessage() . "\n";
    }


    // 6. Lista de Precios y Artículos (LISPROART)
    echo "\n=== MUESTRA DE LISPROART (Catálogo y Precios) ===\n";
    try {
        $art = runQuery($conn, "SELECT TOP 5 * FROM LISPROART");
        print_r($art);
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }


} catch (Exception $e) {
    echo "ERROR GLOBAL: " . $e->getMessage() . "\n";
}




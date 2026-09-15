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

    // 1. Rutas de facturas en COMPROBANTE_CAB (003BDCOMUN)
    echo "=== RUTA_COMPROBANTE EN [003BDCOMUN].dbo.COMPROBANTE_CAB ===\n";
    try {
        $rutas = runQuery($conn, "SELECT TOP 10 TIPODOCSUNAT, TIPODOCVENTAS, CFNUMSER, CFNUMDOC, RUTA_COMPROBANTE, RUTA_CDR, XML, ESTADO FROM [003BDCOMUN].dbo.COMPROBANTE_CAB WHERE RUTA_COMPROBANTE IS NOT NULL AND RUTA_COMPROBANTE <> '' ORDER BY CFNUMDOC DESC");
        print_r($rutas);
    } catch(Exception $e) {
        echo "Error COMPROBANTE_CAB: " . $e->getMessage() . "\n";
    }

    // 2. Muestra general de COMPROBANTE_CAB sin filtro
    echo "\n=== MUESTRA GENERAL COMPROBANTE_CAB ===\n";
    try {
        $sampleC = runQuery($conn, "SELECT TOP 5 TIPODOCSUNAT, TIPODOCVENTAS, CFNUMSER, CFNUMDOC, RUTA_COMPROBANTE, RUTA_CDR, XML, ESTADO, FECHA_EMISION FROM [003BDCOMUN].dbo.COMPROBANTE_CAB ORDER BY FECHA_EMISION DESC");
        print_r($sampleC);
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    // 3. Tablas en BDARCHIVOS_SS y BD_ARCHIVOS
    echo "\n=== TABLAS EN BDARCHIVOS_SS ===\n";
    try {
        $tArch = runQuery($conn, "SELECT TABLE_NAME FROM [BDARCHIVOS_SS].INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE'");
        echo implode(", ", array_column($tArch, 'TABLE_NAME')) . "\n";
    } catch(Exception $e) {
        echo "Error BDARCHIVOS_SS: " . $e->getMessage() . "\n";
    }

    // 4. Tablas en 003BDCBT2026
    echo "\n=== TABLAS EN 003BDCBT2026 ===\n";
    try {
        $tCbt = runQuery($conn, "SELECT TABLE_NAME FROM [003BDCBT2026].INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE'");
        echo implode(", ", array_column($tCbt, 'TABLE_NAME')) . "\n";
    } catch(Exception $e) {
        echo "Error 003BDCBT2026: " . $e->getMessage() . "\n";
    }

    // 5. GREMISION_CAB en BDTPED_SSA
    echo "\n=== RUTA_COMPROBANTE EN GREMISION_CAB ===\n";
    try {
        $rGuia = runQuery($conn, "SELECT TOP 5 NUMGUIA, RUTA_COMPROBANTE, RUTA_CDR, XML FROM GREMISION_CAB WHERE RUTA_COMPROBANTE IS NOT NULL AND RUTA_COMPROBANTE <> ''");
        print_r($rGuia);
    } catch(Exception $e) {
        echo "Error GREMISION_CAB: " . $e->getMessage() . "\n";
    }


} catch (Exception $e) {
    echo "ERROR GLOBAL: " . $e->getMessage() . "\n";
}




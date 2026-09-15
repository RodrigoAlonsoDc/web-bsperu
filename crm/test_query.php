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

    // 1. Columnas y muestra de COMPROBANTE_CAB
    echo "=== COLUMNAS Y MUESTRA DE [003BDCOMUN].dbo.COMPROBANTE_CAB ===\n";
    try {
        $cols = runQuery($conn, "SELECT COLUMN_NAME, DATA_TYPE FROM [003BDCOMUN].INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='COMPROBANTE_CAB'");
        echo "Columnas: " . implode(", ", array_column($cols, 'COLUMN_NAME')) . "\n\n";
        $rows = runQuery($conn, "SELECT TOP 3 * FROM [003BDCOMUN].dbo.COMPROBANTE_CAB ORDER BY 1 DESC");
        print_r($rows);
    } catch(Exception $e) {
        echo "Error COMPROBANTE_CAB: " . $e->getMessage() . "\n";
    }

    // 2. Rutas no nulas en COMPROBANTE_CAB
    echo "\n=== RUTA_COMPROBANTE NO NULAS EN COMPROBANTE_CAB ===\n";
    try {
        $rutas = runQuery($conn, "SELECT TOP 5 RUTA_COMPROBANTE, RUTA_CDR, XML FROM [003BDCOMUN].dbo.COMPROBANTE_CAB WHERE RUTA_COMPROBANTE IS NOT NULL AND LTRIM(RTRIM(RUTA_COMPROBANTE)) <> ''");
        print_r($rutas);
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    // 3. GREMISION_CAB columnas y muestra
    echo "\n=== GREMISION_CAB COLUMNAS Y MUESTRA ===\n";
    try {
        $colsG = runQuery($conn, "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='GREMISION_CAB'");
        echo "Columnas: " . implode(", ", array_column($colsG, 'COLUMN_NAME')) . "\n\n";
        $rowsG = runQuery($conn, "SELECT TOP 3 * FROM GREMISION_CAB ORDER BY 1 DESC");
        print_r($rowsG);
    } catch(Exception $e) {
        echo "Error GREMISION_CAB: " . $e->getMessage() . "\n";
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




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

    // 1. Todas las Bases de Datos en el Servidor SQL
    echo "=== TODAS LAS BASES DE DATOS EN SQL SERVER ===\n";
    $dbs = runQuery($conn, "SELECT name, database_id FROM sys.databases ORDER BY name");
    foreach ($dbs as $d) {
        echo " - {$d['name']} (ID: {$d['database_id']})\n";
    }

    // 2. Tablas en 003BDCOMUN relacionadas a facturas o configuración
    echo "\n=== TABLAS EN 003BDCOMUN (Facturación / Configuración) ===\n";
    try {
        $tComun = runQuery($conn, "SELECT TABLE_NAME FROM [003BDCOMUN].INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' AND (TABLE_NAME LIKE '%FAC%' OR TABLE_NAME LIKE '%CONF%' OR TABLE_NAME LIKE '%PARAM%' OR TABLE_NAME LIKE '%FE%' OR TABLE_NAME LIKE '%ELECT%' OR TABLE_NAME LIKE '%COMPROB%' OR TABLE_NAME LIKE '%DOC%') ORDER BY TABLE_NAME");
        echo implode(", ", array_column($tComun, 'TABLE_NAME')) . "\n";
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    // 3. Columnas con RUTA, PATH, DIR, ARCHIVO, PDF, XML en TODAS las tablas de 003BDCOMUN y BDTPED_SSA
    echo "\n=== COLUMNAS CON RUTA / PATH / PDF / XML / CARPETA ===\n";
    try {
        $qRutas = runQuery($conn, "
            SELECT TABLE_CATALOG, TABLE_NAME, COLUMN_NAME, DATA_TYPE 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE COLUMN_NAME LIKE '%RUTA%' OR COLUMN_NAME LIKE '%PATH%' OR COLUMN_NAME LIKE '%DIR%' OR COLUMN_NAME LIKE '%CARPETA%' OR COLUMN_NAME LIKE '%PDF%' OR COLUMN_NAME LIKE '%XML%' OR COLUMN_NAME LIKE '%ARCHIVO%'
            UNION ALL
            SELECT TABLE_CATALOG, TABLE_NAME, COLUMN_NAME, DATA_TYPE 
            FROM [003BDCOMUN].INFORMATION_SCHEMA.COLUMNS 
            WHERE COLUMN_NAME LIKE '%RUTA%' OR COLUMN_NAME LIKE '%PATH%' OR COLUMN_NAME LIKE '%DIR%' OR COLUMN_NAME LIKE '%CARPETA%' OR COLUMN_NAME LIKE '%PDF%' OR COLUMN_NAME LIKE '%XML%' OR COLUMN_NAME LIKE '%ARCHIVO%'
        ");
        print_r($qRutas);
    } catch(Exception $e) {
        echo "Error Rutas: " . $e->getMessage() . "\n";
    }

    // 4. Columnas y muestra de FACCAB en BDTPED_SSA
    echo "\n=== COLUMNAS DE FACCAB EN BDTPED_SSA ===\n";
    try {
        $colsFac = runQuery($conn, "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='FACCAB' ORDER BY ORDINAL_POSITION");
        $cNames = array_column($colsFac, 'COLUMN_NAME');
        echo implode(", ", $cNames) . "\n";
        $sampleFac = runQuery($conn, "SELECT TOP 3 * FROM FACCAB ORDER BY 1 DESC");
        print_r($sampleFac);
    } catch(Exception $e) {
        echo "Error FACCAB: " . $e->getMessage() . "\n";
    }

    // 5. Valores en CONFIGURACION o tablas de parámetros en 003BDCOMUN
    echo "\n=== VALORES DE CONFIGURACION EN 003BDCOMUN ===\n";
    try {
        $conf = runQuery($conn, "SELECT TOP 1 * FROM [003BDCOMUN].dbo.CONFIGURACION");
        print_r($conf);
    } catch(Exception $e) {
        echo "Error Config: " . $e->getMessage() . "\n";
    }


} catch (Exception $e) {
    echo "ERROR GLOBAL: " . $e->getMessage() . "\n";
}




<?php
header('Content-Type: text/plain; charset=utf-8');

$host = '48.216.211.109';
$port = 443;
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

    echo "OK - Conexión activa a StarSoft BDTPED_SSA.\n";
} catch (Exception $e) {
    echo "ERROR GLOBAL: " . $e->getMessage() . "\n";
}

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/database.php';

try {
    $conn = getStarsoftDB();
    if (!$conn) {
        echo json_encode(['error' => 'No DB connection']);
        exit;
    }

    // 1. Columnas obligatorias (IS_NULLABLE = 'NO') en MAECLI
    $stmtCols = $conn->query("
        SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE, COLUMN_DEFAULT
        FROM [003BDCOMUN].INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'MAECLI' AND IS_NULLABLE = 'NO'
    ");
    $requiredCols = $stmtCols->fetchAll(PDO::FETCH_ASSOC);

    // 2. Procedimientos almacenados relacionados con clientes
    $stmtSP1 = $conn->query("
        SELECT ROUTINE_SCHEMA, ROUTINE_NAME 
        FROM [003BDCOMUN].INFORMATION_SCHEMA.ROUTINES 
        WHERE ROUTINE_NAME LIKE '%CLI%' OR ROUTINE_NAME LIKE '%MAE%'
    ");
    $spsComun = $stmtSP1->fetchAll(PDO::FETCH_ASSOC);

    $stmtSP2 = $conn->query("
        SELECT ROUTINE_SCHEMA, ROUTINE_NAME 
        FROM BDTPED_SSA.INFORMATION_SCHEMA.ROUTINES 
        WHERE ROUTINE_NAME LIKE '%CLI%' OR ROUTINE_NAME LIKE '%MAE%'
    ");
    $spsPed = $stmtSP2->fetchAll(PDO::FETCH_ASSOC);

    // 3. Muestra de un cliente real recién creado en StarSoft para ver campos llenados
    $stmtSample = $conn->query("
        SELECT TOP 2 * 
        FROM [003BDCOMUN].dbo.MAECLI 
        ORDER BY DFECCRE DESC
    ");
    $sampleClients = $stmtSample->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'required_columns' => $requiredCols,
        'sps_003BDCOMUN' => $spsComun,
        'sps_BDTPED_SSA' => $spsPed,
        'sample_clients' => $sampleClients
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
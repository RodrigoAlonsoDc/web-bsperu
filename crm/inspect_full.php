<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/database.php';
$db = getDB();
if (!$db) { echo json_encode(['error' => 'No DB']); exit; }

// 1. Columnas NOT NULL en MAECLI
$stmt1 = $db->query("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE, COLUMN_DEFAULT 
    FROM [003BDCOMUN].INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'MAECLI'
    ORDER BY ORDINAL_POSITION
");
$allCols = $stmt1->fetchAll(PDO::FETCH_ASSOC);

// 2. Muestra real de un cliente creado por Endrina en StarSoft
$stmt2 = $db->query("
    SELECT TOP 1 * 
    FROM [003BDCOMUN].dbo.MAECLI 
    WHERE CUSUARI LIKE '%ENDRINA%' 
    ORDER BY DFECCRE DESC
");
$sampleRow = $stmt2->fetch(PDO::FETCH_ASSOC);

// Filtrar campos que NO son nulos o que tienen algún valor en la fila real
$nonEmptyFields = [];
if ($sampleRow) {
    foreach ($sampleRow as $k => $v) {
        if ($v !== null && trim((string)$v) !== '') {
            $nonEmptyFields[$k] = trim((string)$v);
        }
    }
}

echo json_encode([
    'total_columns' => count($allCols),
    'not_null_cols' => array_values(array_filter($allCols, function($c) { return $c['IS_NULLABLE'] === 'NO'; })),
    'sample_endrina_client' => $nonEmptyFields
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

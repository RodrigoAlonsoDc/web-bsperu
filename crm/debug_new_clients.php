<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$sql = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE 
        FROM [003BDCOMUN].INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'MAECLI'
        ORDER BY ORDINAL_POSITION";
$cols = $db ? $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) : [];
header('Content-Type: application/json');
echo json_encode($cols, JSON_PRETTY_PRINT);

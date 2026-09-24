<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/database.php';
$db = getDB();
if (!$db) { echo json_encode(['error' => 'No DB']); exit; }

$stmt = $db->query("
    SELECT TOP 1 * 
    FROM [003BDCOMUN].dbo.MAECLI 
    WHERE CUSUARI LIKE '%ENDRINA%' AND CTIPO_DOCUMENTO = '6' 
    ORDER BY DFECCRE DESC
");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$nonEmpty = [];
if ($row) {
    foreach ($row as $k => $v) {
        if ($v !== null && trim((string)$v) !== '') {
            $nonEmpty[$k] = trim((string)$v);
        }
    }
}
echo json_encode($nonEmpty, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

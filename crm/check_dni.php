<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$stmt = $db->query("SELECT * FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI LIKE '%76261461%' OR CNUMRUC LIKE '%76261461%' OR CDOCIDEN LIKE '%76261461%'");
$rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
header('Content-Type: application/json');
echo json_encode($rows);

<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/database.php';
$db = getDB();
if (!$db) { echo json_encode(['error' => 'No DB']); exit; }
$stmt = $db->query("SELECT TOP 3 CCODCLI, CNOMCLI, CDIRCLI, CTELEFO, CNUMRUC, CVENDE, CUSUARI, CESTADO, CTIPVTA, CTIPO_DOCUMENTO, DFECCRE, CDOCIDEN, CDEPT, CPROV, CDIST FROM [003BDCOMUN].dbo.MAECLI WHERE CUSUARI LIKE '%ENDRINA%' ORDER BY DFECCRE DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

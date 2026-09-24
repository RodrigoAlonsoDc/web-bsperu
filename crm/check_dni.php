<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$q = $db->query("SELECT TOP 3 CCODCLI, CNOMCLI, CNUMRUC, CDOCIDEN, CTIPO_DOCUMENTO, CVENDE, CESTADO FROM [003BDCOMUN].dbo.MAECLI WHERE CTIPO_DOCUMENTO = '1' OR LEN(LTRIM(RTRIM(CCODCLI))) = 8");
$rows = $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];
header('Content-Type: application/json');
echo json_encode($rows);

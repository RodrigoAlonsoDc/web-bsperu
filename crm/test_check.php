<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$stmt = $db->query("SELECT CCODCLI, CNOMCLI, CDIRCLI, CTELEFO, CEMAIL, CNOMREP, CVENDE, CUSUARI, CESTADO, DFECCRE FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI = '20602591990' OR CNUMRUC = '20602591990'");
$row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
header('Content-Type: application/json');
echo json_encode($row);

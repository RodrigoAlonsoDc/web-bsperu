<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$sql = "SELECT TOP 10
    COALESCE(NULLIF(LTRIM(RTRIM(CNUMRUC)), ''), NULLIF(LTRIM(RTRIM(CDOCIDEN)), ''), LTRIM(RTRIM(CCODCLI))) as ruc,
    LTRIM(RTRIM(CNOMCLI)) as nombre,
    COALESCE(DFECINS, DFECCRE) as fecha_reciente,
    CVENDE as vendedor
FROM [003BDCOMUN].dbo.MAECLI
WHERE CNOMCLI IS NOT NULL AND LEN(CNOMCLI) > 2
ORDER BY COALESCE(DFECINS, DFECCRE) DESC, CCODCLI DESC";
$stmt = $db->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($rows);

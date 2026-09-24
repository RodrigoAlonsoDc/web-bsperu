<?php
$t0 = microtime(true);
require_once __DIR__ . '/config/database.php';
$db = getDB();
$sql = "SELECT TOP 500
    COALESCE(NULLIF(LTRIM(RTRIM(CNUMRUC)), ''), NULLIF(LTRIM(RTRIM(CDOCIDEN)), ''), LTRIM(RTRIM(CCODCLI))) as ruc,
    LTRIM(RTRIM(CNOMCLI)) as nombre,
    LTRIM(RTRIM(CDIRCLI)) as direccion,
    LTRIM(RTRIM(CTELEFO)) as telefono,
    LTRIM(RTRIM(CEMAIL)) as email,
    LTRIM(RTRIM(CNOMREP)) as contacto,
    LTRIM(RTRIM(CDEPT)) as departamento,
    LTRIM(RTRIM(CPROV)) as provincia,
    LTRIM(RTRIM(CVENDE)) as vendedor
FROM [003BDCOMUN].dbo.MAECLI
WHERE CVENDE = '01' AND CNOMCLI IS NOT NULL AND LEN(CNOMCLI) > 2
ORDER BY COALESCE(DFECINS, DFECCRE) DESC, CCODCLI DESC";
$stmt = $db->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$t1 = microtime(true);
header('Content-Type: application/json');
echo json_encode(['count' => count($rows), 'ms' => round(($t1 - $t0) * 1000, 2), 'first' => $rows[0] ?? null]);

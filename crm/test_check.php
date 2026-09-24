<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

// 1. Delete test row
$db->exec("DELETE FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI = '20999111222'");

// 2. Assign MULTINEGOCIOS AARON to Endrina ('01') and update timestamp to now
$now = date('Y-m-d H:i:s');
$updSql = "UPDATE [003BDCOMUN].dbo.MAECLI 
    SET CVENDE = '01',
        CNOMREP = 'Fanny Ramirez',
        CTELEFO = '942 377 626',
        CEMAIL = 'Consorciomiraflores25@gmail.com',
        CDIRCLI = 'JR. SAGITARIO MZA. C LOTE. 22 URB. VILLA ALEGRE LIMA - LIMA - SANTIAGO DE SURCO',
        DFECINS = '$now',
        CUSUARI = 'ENDRINA'
    WHERE CCODCLI = '20602591990' OR CNUMRUC = '20602591990'";
$aff = $db->exec($updSql);

// 3. Verify
$stmt = $db->query("SELECT CCODCLI, CNOMCLI, CVENDE, CNOMREP, CTELEFO, CEMAIL, DFECINS FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI = '20602591990'");
$row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

header('Content-Type: application/json');
echo json_encode(['updated_affected' => $aff, 'verified' => $row]);

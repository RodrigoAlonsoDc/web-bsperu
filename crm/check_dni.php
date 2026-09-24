<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$sql = "UPDATE [003BDCOMUN].dbo.MAECLI 
    SET CDOCIDEN = '76261461',
        CAPELLIDO_PATERNO = 'DE LA CRUZ',
        CAPELLIDO_MATERNO = 'CARRANZA',
        CPRIMER_NOMBRE = 'RODRIGO',
        CSEGUNDO_NOMBRE = 'ALONSO',
        CDEPT = 'LIMA',
        CPROV = 'LIMA',
        CDISTRI = '01',
        UBIGEO = '150101',
        CGIRNEG = '08',
        RETEN = '0',
        SIN_CONTROL_LIMCREDITO = '0'
    WHERE CCODCLI = '76261461'";
$aff = $db->exec($sql);
$row = $db->query("SELECT CCODCLI, CNOMCLI, CDOCIDEN, CTIPO_DOCUMENTO, CAPELLIDO_PATERNO, CAPELLIDO_MATERNO, CPRIMER_NOMBRE, CVENDE, CESTADO FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI = '76261461'")->fetch(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode(['updated' => $aff, 'row' => $row]);

<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

function escSql($v) {
    if ($v === null) return "NULL";
    return "'" . str_replace("'", "''", trim((string)$v)) . "'";
}

$ruc = '76261461';
$razon = 'DE LA CRUZ CARRANZA RODRIGO ALONSO';
$direccion = 'LIMA';
$telefono = '999999999';
$email = 'rodrigo@bsperu.pe';
$contacto = 'Rodrigo Alonso';
$vendedorAsignado = '01';
$tipoDocVal = '1';
$hoy = date('Y-m-d H:i:s');

$qRuc = escSql($ruc);
$qRazon = escSql($razon);
$qDir = escSql($direccion);
$qTel = escSql($telefono);
$qEmail = escSql($email);
$qContacto = escSql($contacto);
$qVende = escSql($vendedorAsignado);
$qTipoDoc = escSql($tipoDocVal);
$qHoy = escSql($hoy);
$numRucVal = (strlen($ruc) === 11) ? $ruc : '';
$qNumRuc = escSql($numRucVal);

$res = [];
try {
    $insSql = "INSERT INTO [003BDCOMUN].dbo.MAECLI 
        (CCODCLI, CNOMCLI, CDIRCLI, CTELEFO, CNUMRUC, CVENDE, CUSUARI, CESTADO, CTIPVTA, CTIPO_DOCUMENTO, DFECCRE, DFECINS, CEMAIL, CNOMREP, CPAIS, MONCRE, CFLAGPRIN, TCL_CODIGO)
        VALUES ($qRuc, $qRazon, $qDir, $qTel, $qNumRuc, $qVende, 'ENDRINA', 'V', '00', $qTipoDoc, $qHoy, $qHoy, $qEmail, $qContacto, 'PERU', 'MN', 1, '1')";
    $aff = $db->exec($insSql);
    $res['status'] = 'OK';
    $res['affected'] = $aff;
} catch (Throwable $e) {
    $res['status'] = 'ERROR';
    $res['error'] = $e->getMessage();
}

$verify = $db->query("SELECT CCODCLI, CNOMCLI, CDOCIDEN, CNUMRUC, CTIPO_DOCUMENTO, CVENDE, CESTADO FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI = '76261461'")->fetch(PDO::FETCH_ASSOC);
$res['verify'] = $verify;

header('Content-Type: application/json');
echo json_encode($res);

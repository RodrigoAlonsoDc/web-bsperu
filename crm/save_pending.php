<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/crm_backend.php';

$db = getDB();

function saveCli($db, $ruc, $razon, $contacto, $dir, $tel, $email, $vende) {
    $isDni = (strlen($ruc) === 8);
    $tipoDocVal = $isDni ? '1' : ((strlen($ruc) === 11) ? '6' : '0');
    $hoy = date('Y-m-d H:i:s');

    $cleanRuc = mb_substr(trim($ruc), 0, 11);
    $cleanRazon = mb_substr(trim($razon), 0, 100);
    $cleanDir = mb_substr(trim($dir), 0, 100);
    $cleanTel = mb_substr(trim($tel), 0, 30);
    $cleanEmail = mb_substr(trim($email), 0, 200);
    $cleanContacto = mb_substr(trim($contacto ?: $razon), 0, 30);
    $cleanVende = mb_substr(trim($vende ?: '01'), 0, 2);

    $qRuc = escSql($cleanRuc);
    $qRazon = escSql($cleanRazon);
    $qDir = escSql($cleanDir);
    $qTel = escSql($cleanTel);
    $qEmail = escSql($cleanEmail);
    $qContacto = escSql($cleanContacto);
    $qVende = escSql($cleanVende);
    $qTipoDoc = escSql($tipoDocVal);
    $qHoy = escSql($hoy);

    if ($isDni) {
        list($apePat, $apeMat, $priNom, $segNom) = parseNombrePeruano($cleanRazon);
        $qApePat = escSql(mb_substr($apePat, 0, 20));
        $qApeMat = escSql(mb_substr($apeMat, 0, 20));
        $qPriNom = escSql(mb_substr($priNom, 0, 20));
        $qSegNom = escSql(mb_substr($segNom, 0, 20));
        $qGirneg = "'08'";
        $qNumRuc = "''";
        $qDocIden = $qRuc;
    } else {
        $qApePat = "''";
        $qApeMat = "''";
        $qPriNom = "''";
        $qSegNom = "''";
        $qGirneg = "'01'";
        $qNumRuc = $qRuc;
        $qDocIden = "''";
    }

    $chk = $db->query("SELECT CCODCLI FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI = $qRuc OR CNUMRUC = $qRuc OR CDOCIDEN = $qRuc");
    $exists = $chk ? $chk->fetch(PDO::FETCH_ASSOC) : null;
    if ($chk) $chk->closeCursor();

    if ($exists) {
        $cod = escSql($exists['CCODCLI']);
        $sql = "UPDATE [003BDCOMUN].dbo.MAECLI 
            SET CNOMCLI = $qRazon, 
                CDIRCLI = $qDir, 
                CTELEFO = $qTel, 
                CEMAIL = $qEmail, 
                CNOMREP = $qContacto, 
                CVENDE = $qVende,
                DFECINS = $qHoy,
                CUSUARI = 'ENDRINA',
                CTIPO_DOCUMENTO = $qTipoDoc,
                CNUMRUC = $qNumRuc,
                CDOCIDEN = $qDocIden,
                CAPELLIDO_PATERNO = $qApePat,
                CAPELLIDO_MATERNO = $qApeMat,
                CPRIMER_NOMBRE = $qPriNom,
                CSEGUNDO_NOMBRE = $qSegNom,
                CGIRNEG = $qGirneg,
                RETEN = '0',
                SIN_CONTROL_LIMCREDITO = '0'
            WHERE CCODCLI = $cod";
        $db->exec($sql);
        return 'updated';
    } else {
        $sql = "INSERT INTO [003BDCOMUN].dbo.MAECLI 
            (CCODCLI, CNOMCLI, CDIRCLI, CTELEFO, CNUMRUC, CDOCIDEN, CVENDE, CUSUARI, CESTADO, CTIPVTA, CTIPO_DOCUMENTO, DFECCRE, DFECINS, CEMAIL, CNOMREP, CPAIS, MONCRE, CFLAGPRIN, TCL_CODIGO, CAPELLIDO_PATERNO, CAPELLIDO_MATERNO, CPRIMER_NOMBRE, CSEGUNDO_NOMBRE, CGIRNEG, RETEN, SIN_CONTROL_LIMCREDITO)
            VALUES ($qRuc, $qRazon, $qDir, $qTel, $qNumRuc, $qDocIden, $qVende, 'ENDRINA', 'V', '00', $qTipoDoc, $qHoy, $qHoy, $qEmail, $qContacto, 'PERU', 'MN', 1, '1', $qApePat, $qApeMat, $qPriNom, $qSegNom, $qGirneg, '0', '0')";
        $db->exec($sql);
        return 'inserted';
    }
}

$res1 = saveCli($db, '74585705', 'DELGADO DE LA FLOR QUISPE LUZ NAIDA', 'DELGADO DE LA FLOR QUISPE LUZ', 'LIMA', '', '', '01');
$res2 = saveCli($db, '20100047218', 'BANCO DE CREDITO DEL PERU', 'VILLALOBOS OVIEDO JOAQUIN ALBE', 'JR. CENTENARIO NRO 156 URB. LADERAS DE MELGAREJO - LA MOLINA - LIMA', '', '', '01');

$rows = $db->query("SELECT CCODCLI, CNOMCLI, CDOCIDEN, CNUMRUC, CTIPO_DOCUMENTO, CNOMREP, CVENDE FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI IN ('74585705', '20100047218')")->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode(['res1' => $res1, 'res2' => $res2, 'rows' => $rows], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

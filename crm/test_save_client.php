<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
$db = getDB();

function escSql($v) {
    if ($v === null) return "NULL";
    return "'" . str_replace("'", "''", trim((string)$v)) . "'";
}

$razon = 'PRUEBA VERIFICACION CLIENTE SAC';
$ruc = '20999111222';
$direccion = 'CALLE PRUEBA 123';
$email = 'prueba@cliente.com';
$telefono = '987654321';
$contacto = 'INGENIERO PRUEBA';
$categoria = 'Activo';
$vendedorAsignado = '01';
$tipoDoc = '6';
$hoy = date('Y-m-d 00:00:00');

echo "--- TEST ESCAPED SQL ---\n";
try {
    $qRuc = escSql($ruc);
    $qRazon = escSql($razon);
    $qDir = escSql($direccion);
    $qTel = escSql($telefono);
    $qVende = escSql($vendedorAsignado);
    $qTipoDoc = escSql($tipoDoc);
    $qHoy = escSql($hoy);
    $qEmail = escSql($email);
    $qContacto = escSql($contacto);

    // 1. SELECT check
    $checkSql = "SELECT CCODCLI FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI = $qRuc OR CNUMRUC = $qRuc";
    $stmt = $db->query($checkSql);
    $existe = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    echo "Existe: " . ($existe ? "SI" : "NO") . "\n";

    // 2. INSERT
    $insertSql = "INSERT INTO [003BDCOMUN].dbo.MAECLI 
        (CCODCLI, CNOMCLI, CDIRCLI, CTELEFO, CNUMRUC, CVENDE, CUSUARI, CESTADO, CTIPVTA, CTIPO_DOCUMENTO, DFECCRE, DFECINS, CEMAIL, CNOMREP, CPAIS, MONCRE, CFLAGPRIN, TCL_CODIGO)
        VALUES ($qRuc, $qRazon, $qDir, $qTel, $qRuc, $qVende, 'ENDRINA', 'V', '00', $qTipoDoc, $qHoy, $qHoy, $qEmail, $qContacto, 'PERU', 'MN', 1, '1')";
    
    $affected = $db->exec($insertSql);
    echo "INSERT Affected: $affected\n";

    // 3. Verify inserted row
    $verifyStmt = $db->query("SELECT CCODCLI, CNOMCLI, CVENDE FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI = $qRuc");
    $insertedRow = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    echo "VERIFIED: " . json_encode($insertedRow) . "\n";

    // Clean up test row
    $db->exec("DELETE FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI = $qRuc");
    echo "Cleaned up test row\n";

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

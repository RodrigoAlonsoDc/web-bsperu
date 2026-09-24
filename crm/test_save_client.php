<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
$db = getDB();

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

// Test 1: Direct SQL query with quote()
echo "--- TEST DIRECT SQL ---\n";
try {
    $qRuc = $db->quote($ruc);
    $qRazon = $db->quote($razon);
    $qDir = $db->quote($direccion);
    $qTel = $db->quote($telefono);
    $qVende = $db->quote($vendedorAsignado);
    $qTipoDoc = $db->quote($tipoDoc);
    $qHoy = $db->quote($hoy);
    $qEmail = $db->quote($email);
    $qContacto = $db->quote($contacto);

    $sql = "INSERT INTO [003BDCOMUN].dbo.MAECLI 
        (CCODCLI, CNOMCLI, CDIRCLI, CTELEFO, CNUMRUC, CVENDE, CUSUARI, CESTADO, CTIPVTA, CTIPO_DOCUMENTO, DFECCRE, DFECINS, CEMAIL, CNOMREP, CPAIS, MONCRE, CFLAGPRIN, TCL_CODIGO)
        VALUES ($qRuc, $qRazon, $qDir, $qTel, $qRuc, $qVende, 'ENDRINA', 'V', '00', $qTipoDoc, $qHoy, $qHoy, $qEmail, $qContacto, 'PERU', 'MN', 1, '1')";
    
    $affected = $db->exec($sql);
    echo "Direct SQL Affected: $affected\n";

    // Clean up test row
    $db->exec("DELETE FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI = $qRuc");
    echo "Test row deleted cleanly\n";
} catch (Throwable $e) {
    echo "ERROR DIRECT SQL: " . $e->getMessage() . "\n";
}

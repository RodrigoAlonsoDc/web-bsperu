<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
$db = getDB();
echo "1. DB: " . ($db ? "Conectado" : "NULL") . "\n";

$razon = 'PRUEBA VERIFICACION CLIENTE SAC';
$ruc = '20999111222';
$direccion = 'CALLE PRUEBA 123';
$email = 'prueba@cliente.com';
$telefono = '987654321';
$contacto = 'INGENIERO PRUEBA';
$categoria = 'Activo';
$vendedorAsignado = '01';

if ($db) {
    try {
        echo "2. Preparando SELECT...\n";
        $chk = $db->prepare("SELECT CCODCLI FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI = ? OR CNUMRUC = ?");
        $chk->execute([$ruc, $ruc]);
        $existe = $chk->fetch(PDO::FETCH_ASSOC);
        echo "3. Existe: " . ($existe ? "SI" : "NO") . "\n";

        $tipoDoc = (strlen($ruc) === 11) ? '6' : '1';
        $hoy = date('Y-m-d 00:00:00');

        if ($existe) {
            echo "4. Preparando UPDATE...\n";
            $upd = $db->prepare("UPDATE [003BDCOMUN].dbo.MAECLI 
                SET CNOMCLI = ?, CDIRCLI = ?, CTELEFO = ?, CEMAIL = ?, CNOMREP = ?, CVENDE = ? 
                WHERE CCODCLI = ?");
            $upd->execute([$razon, $direccion, $telefono, $email, $contacto, $vendedorAsignado, $existe['CCODCLI']]);
            echo "5. UPDATE exitoso\n";
        } else {
            echo "4. Preparando INSERT...\n";
            $ins = $db->prepare("INSERT INTO [003BDCOMUN].dbo.MAECLI 
                (CCODCLI, CNOMCLI, CDIRCLI, CTELEFO, CNUMRUC, CVENDE, CUSUARI, CESTADO, CTIPVTA, CTIPO_DOCUMENTO, DFECCRE, DFECINS, CEMAIL, CNOMREP, CPAIS, MONCRE, CFLAGPRIN, TCL_CODIGO)
                VALUES (?, ?, ?, ?, ?, ?, 'ENDRINA', 'V', '00', ?, ?, ?, ?, ?, 'PERU', 'MN', 1, '1')");
            $ins->execute([
                $ruc,
                $razon,
                $direccion,
                $telefono,
                (strlen($ruc) === 11 ? $ruc : ''),
                $vendedorAsignado,
                $tipoDoc,
                $hoy,
                $hoy,
                $email,
                $contacto
            ]);
            echo "5. INSERT exitoso\n";
        }
    } catch (Throwable $e) {
        echo "ERROR SQL: " . $e->getMessage() . "\n";
    }
}

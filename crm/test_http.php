<?php
header('Content-Type: text/plain; charset=utf-8');
echo "Probando HTTP hacia Azure VM (48.216.211.109:8089)...\n";
$ctx = stream_context_create(['http' => ['timeout' => 4]]);
$res = @file_get_contents('http://48.216.211.109:8089/', false, $ctx);
if ($res !== false) {
    echo "SUCCESS: Se pudo comunicar con la máquina de Azure por HTTP en el puerto 8089!\n";
    echo "Bytes recibidos: " . strlen($res) . "\n";
} else {
    $err = error_get_last();
    echo "FAILED: " . ($err['message'] ?? 'Desconocido') . "\n";
}

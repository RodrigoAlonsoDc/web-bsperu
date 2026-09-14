<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== PRUEBA DE FIREWALL SALIENTE DESDE BSPERU.PE ===\n\n";

function testUrl($label, $url, $timeout = 3) {
    echo "Probando $label ($url)... ";
    $ctx = stream_context_create(['http' => ['timeout' => $timeout]]);
    $res = @file_get_contents($url, false, $ctx);
    if ($res !== false) {
        echo "OK (Exitoso, puerto saliente PERMITIDO)\n";
    } else {
        $err = error_get_last();
        echo "BLOQUEADO (" . ($err['message'] ?? 'Error') . ")\n";
    }
}

// 1. Probar puertos estándar
testUrl("Puerto 80 Estándar", "http://example.com/");
testUrl("Puerto 443 SSL", "https://google.com/");

// 2. Probar si el hosting permite salir por el puerto 1433 usando portquiz
testUrl("Hosting Salida Puerto 1433 (portquiz.net)", "http://portquiz.net:1433/");

// 3. Probar si el hosting permite salir por el puerto 8089 usando portquiz
testUrl("Hosting Salida Puerto 8089 (portquiz.net)", "http://portquiz.net:8089/");

// 4. Probar la VM de Azure directamente
testUrl("Azure VM Starsoft SQL (48.216.211.109:1433)", "http://48.216.211.109:1433/");
testUrl("Azure VM Cotizador (48.216.211.109:8089)", "http://48.216.211.109:8089/");

echo "\n=== DRIVERS DE BASE DE DATOS EN ESTE HOSTING ===\n";
echo "PDO Drivers instalados: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
if (function_exists('odbc_data_sources')) {
    $ds = @odbc_data_sources(null, SQL_FETCH_FIRST);
    echo "ODBC Data Sources: " . print_r($ds, true) . "\n";
}
echo "\n=== VALIDACIÓN DE TEST_STARSOFT.PHP ===\n";
try {
    ob_start();
    include __DIR__ . '/test_starsoft.php';
    $out = ob_get_clean();
    echo "test_starsoft.php compiló y corrió con éxito. Longitud de salida HTML: " . strlen($out) . " bytes.\n";
} catch (Throwable $t) {
    echo "ERROR DETECTADO: " . $t->getMessage() . " en " . $t->getFile() . ":" . $t->getLine() . "\n";
}

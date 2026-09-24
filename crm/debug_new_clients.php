<?php
$jsonPath = __DIR__ . '/crm_data/clientes.json';
$clients = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];
$found = [];
foreach ($clients as $c) {
    $str = json_encode($c);
    if (stripos($str, 'DELGADO') !== false || stripos($str, 'VILLALOBOS') !== false) {
        $found[] = $c;
    }
}
header('Content-Type: application/json');
echo json_encode($found, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

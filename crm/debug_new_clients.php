<?php
$jsonPath = __DIR__ . '/crm_data/clientes.json';
$content = file_exists($jsonPath) ? file_get_contents($jsonPath) : '';
$found = [];
$lines = file('/home/ene27bspe5226d/logs/bsperu_pe.php.error.log');
$recentErrors = array_slice($lines, -40);
header('Content-Type: application/json');
echo json_encode([
    'recent_errors' => $recentErrors,
    'has_delgado' => stripos($content, 'DELGADO') !== false,
    'has_villalobos' => stripos($content, 'VILLALOBOS') !== false
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

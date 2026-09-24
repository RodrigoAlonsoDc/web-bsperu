<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$sql = "SELECT TOP 10 CCODCLI, CNOMCLI, CDOCIDEN, CNUMRUC, CTIPO_DOCUMENTO, CVENDE, CUSUARI, DFECINS, DFECCRE 
        FROM [003BDCOMUN].dbo.MAECLI 
        ORDER BY COALESCE(DFECINS, DFECCRE) DESC, CCODCLI DESC";
$rows = $db ? $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) : [];

$jsonPath = __DIR__ . '/crm_data/clientes.json';
$jsonClients = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];
$jsonSlice = array_slice($jsonClients, 0, 5);

$errLog = '';
$possibleLogs = [
    ini_get('error_log'),
    __DIR__ . '/error_log',
    __DIR__ . '/../error_log'
];
foreach ($possibleLogs as $p) {
    if ($p && file_exists($p)) {
        $lines = file($p);
        $errLog .= "=== $p ===\n" . implode('', array_slice($lines, -25)) . "\n";
    }
}

header('Content-Type: application/json');
echo json_encode(['last_maecli' => $rows, 'last_json' => $jsonSlice, 'error_log' => $errLog], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

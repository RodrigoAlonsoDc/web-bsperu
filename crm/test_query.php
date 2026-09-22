<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/database.php';

try {
    $conn = getStarsoftDB();
    if (!$conn) {
        echo json_encode(['error' => 'No DB connection']);
        exit;
    }

    $action = $_GET['a'] ?? 'summary';

    if ($action === 'maecli_vendedores') {
        $stmt = $conn->query("SELECT DISTINCT CVENDE, COUNT(*) as cant FROM [003BDCOMUN].dbo.MAECLI GROUP BY CVENDE ORDER BY cant DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($action === 'maecli_endrina') {
        $stmt = $conn->query("SELECT TOP 5 CCODCLI, CNOMCLI, CNUMRUC, CVENDE, CUSUARI FROM [003BDCOMUN].dbo.MAECLI WHERE CVENDE = '01' OR CUSUARI LIKE '%ENDRINA%'");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    echo json_encode(['status' => 'ready']);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
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

    if ($action === 'usuarios') {
        $stmt = $conn->query('SELECT * FROM USUARIO_BS');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($action === 'vendedores') {
        $stmt = $conn->query('SELECT * FROM BDT_VENDEDORCUOTA');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($action === 'maecli_cols') {
        $stmt = $conn->query("SELECT COLUMN_NAME, DATA_TYPE FROM [003BDCOMUN].INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='MAECLI'");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($action === 'maecli_sample') {
        $stmt = $conn->query("SELECT TOP 5 * FROM [003BDCOMUN].dbo.MAECLI");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($action === 'maecli_vendedores') {
        $stmt = $conn->query("SELECT DISTINCT CCODVEN, COUNT(*) as cant FROM [003BDCOMUN].dbo.MAECLI GROUP BY CCODVEN ORDER BY cant DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    echo json_encode(['status' => 'ready']);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
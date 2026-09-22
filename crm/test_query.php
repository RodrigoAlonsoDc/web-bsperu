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

    if ($action === 'databases') {
        $stmt = $conn->query('SELECT name FROM sys.databases');
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
        exit;
    }

    if ($action === 'cotcab_vendedores') {
        $stmt = $conn->query('SELECT DISTINCT TOP 30 CFVENDE FROM COTCAB');
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
        exit;
    }

    if ($action === 'query') {
        $sql = $_GET['q'] ?? '';
        if ($sql) {
            $stmt = $conn->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }
    }

    echo json_encode(['status' => 'ready', 'actions' => ['usuarios', 'vendedores', 'databases', 'cotcab_vendedores', 'query']]);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
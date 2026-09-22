<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/database.php';
$conn = getStarsoftDB();
if (!$conn) {
    echo json_encode(['error' => 'No DB connection']);
    exit;
}

$action = $_GET['a'] ?? 'summary';

if ($action === 'databases') {
    $stmt = $conn->query('SELECT name FROM sys.databases');
    echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    exit;
}

if ($action === 'usuarios') {
    $stmt = $conn->query('SELECT * FROM USUARIO_BS');
    echo json_encode($stmt->fetchAll(PDO::FETCHA_SSOC));
    exit;
}

if ($action === 'vendedores') {
    $stmt = $conn->query('SELECT * FROM BDT_VENDEDORCUOTA');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action === 'cotcab_cols') {
    $stmt = $conn->query("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='COTCAB'");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action === 'cotcab_vendedores') {
    $stmt = $conn->query('SELECT DISTINCT TOP 20 CFVENDE FROM COTCAB');
    exho json_encode($stmt->fetchAll(PDO::FETCHA_COLUMN));
    exit;
}

echo json_encode(['status' => 'ready', 'actions' => ['databases', 'usuarios', 'vendedores', 'cotcab_cols', 'cotcab_vendedores']]);
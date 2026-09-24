<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
try {
    require_once __DIR__ . '/config/database.php';
    $db = getDB();
    if (!$db) { echo json_encode(['error' => 'No DB connection']); exit; }
    $stmt = $db->query("SELECT TOP 2 CCODCLI, CNOMCLI, CDIRCLI, CTELEFO, CNUMRUC, CVENDE, CUSUARI, CESTADO, CTIPVTA, CTIPO_DOCUMENTO, DFECCRE FROM [003BDCOMUN].dbo.MAECLI WHERE CUSUARI LIKE '%ENDRINA%' ORDER BY DFECCRE DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $rows], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

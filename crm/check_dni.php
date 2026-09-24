<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$sql = "SELECT table_catalog, table_name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%MAECLI%'";
$q = $db->query($sql);
$rows = $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];

// Check across all DBs
$dbs = ["003BDCOMUN", "003BDCBT2026", "003BDCONT2026"];
$cross = [];
foreach ($dbs as $d) {
    try {
        $c = $db->query("SELECT COUNT(*) as c FROM [$d].dbo.MAECLI")->fetch(PDO::FETCH_ASSOC);
        $cross[$d] = $c['c'];
    } catch(Exception $e) {
        $cross[$d] = $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode(['tables' => $rows, 'cross' => $cross]);

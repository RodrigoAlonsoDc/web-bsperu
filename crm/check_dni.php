<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

$results = [];

// 1. Search in MAECLI
$q = $db->query("SELECT * FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI LIKE '%76261461%' OR CNUMRUC LIKE '%76261461%' OR CDOCIDEN LIKE '%76261461%'");
$results['maecli'] = $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];

// 2. Search in COTCAB (Cotizaciones StarSoft)
try {
    $qCot = $db->query("SELECT TOP 5 * FROM [003BDCOMUN].dbo.COTCAB WHERE CCRUC LIKE '%76261461%' OR CCODCLI LIKE '%76261461%'");
    $results['cotcab_comun'] = $qCot ? $qCot->fetchAll(PDO::FETCH_ASSOC) : [];
} catch(Exception $e) { $results['cotcab_comun_err'] = $e->getMessage(); }

// 3. Search in PEDCAB (Pedidos StarSoft)
try {
    $qPed = $db->query("SELECT TOP 5 * FROM [003BDCOMUN].dbo.PEDCAB WHERE CFRUC LIKE '%76261461%' OR CFCODCLI LIKE '%76261461%'");
    $results['pedcab_comun'] = $qPed ? $qPed->fetchAll(PDO::FETCH_ASSOC) : [];
} catch(Exception $e) { $results['pedcab_comun_err'] = $e->getMessage(); }

// 4. Check other databases on this SQL Server instance
try {
    $dbs = $db->query("SELECT name FROM sys.databases WHERE name LIKE '%003%' OR name LIKE '%BS%' OR name LIKE '%STAR%'");
    $results['databases'] = $dbs ? $dbs->fetchAll(PDO::FETCH_COLUMN) : [];
} catch(Exception $e) { $results['dbs_err'] = $e->getMessage(); }

header('Content-Type: application/json');
echo json_encode($results);

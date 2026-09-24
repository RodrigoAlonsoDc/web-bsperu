<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$c1 = $db->query("SELECT COUNT(*) as c FROM [003BDCOMUN].dbo.MAECLI")->fetch(PDO::FETCH_ASSOC);
$c2 = $db->query("SELECT COUNT(*) as c FROM [003BDCOMUN].dbo.MAECLI WHERE CVENDE = '01'")->fetch(PDO::FETCH_ASSOC);
$c3 = $db->query("SELECT COUNT(*) as c FROM [003BDCOMUN].dbo.MAECLI WHERE CVENDE = '99'")->fetch(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode(['total' => $c1['c'], 'endrina' => $c2['c'], 'vende99' => $c3['c']]);

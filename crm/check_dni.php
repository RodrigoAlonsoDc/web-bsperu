<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$sql = "SELECT TOP 5 * FROM [003BDCOMUN].dbo.MAECLI ORDER BY COALESCE(DFECINS, DFECCRE) DESC, CCODCLI DESC";
$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($rows);

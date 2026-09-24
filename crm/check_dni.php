<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$row = $db->query("SELECT TOP 1 * FROM [003BDCOMUN].dbo.MAECLI WHERE CTIPO_DOCUMENTO = '1' AND LEN(LTRIM(RTRIM(CDOCIDEN))) = 8")->fetch(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($row);

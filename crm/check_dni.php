<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$sql = "SELECT TOP 1 * FROM [003BDCOMUN].dbo.MAECLI WHERE CTIPO_DOCUMENTO = '1' AND LEN(LTRIM(RTRIM(CDOCIDEN))) = 8 AND CCODCLI != '76261461'";
$row = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
$myRow = $db->query("SELECT * FROM [003BDCOMUN].dbo.MAECLI WHERE CCODCLI = '76261461'")->fetch(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode(['real_dni_sample' => $row, 'my_row' => $myRow]);

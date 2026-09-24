<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/database.php';
$db = getDB();
if (!$db) { echo json_encode(['error' => 'No DB']); exit; }
$stmt = $db->query("SELECT USER_CODE, U_NAME, U_EXF_father FROM USUARIO_BS WHERE U_NAME LIKE '%ENDRINA%' OR USER_CODE LIKE '%ENDRINA%'");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

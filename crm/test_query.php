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

    $vende = $_GET['v'] ?? '04'; // Karen
    $sql = "SELECT TOP 5 
        COALESCE(NULLIF(LTRIM(RTRIM(CNUMRUC)), ''), NULLIF(LTRIM(RTRIM(CDOCIDEN)), ''), LTRIM(RTRIM(CCODCLI))) as ruc,
        LTRIM(RTRIM(CNOMCLI)) as nombre,
        LTRIM(RTRIM(CDIRCLI)) as direccion,
        LTRIM(RTRIM(CTELEFO)) as telefono,
        LTRIM(RTRIM(CEMAIL)) as email,
        LTRIM(RTRIM(CNOMREP)) as contacto,
        CVENDE as vendedor
    FROM [003BDCOMUN].dbo.MAECLI 
    WHERE CVENDE = ? AND CNOMCLI IS NOT NULL AND LEN(CNOMCLI) > 2
    ORDER BY DFECCRE DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$vende]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
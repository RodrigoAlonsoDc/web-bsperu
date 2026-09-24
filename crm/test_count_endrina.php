<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/database.php';

try {
    $conn = getStarsoftDB();
    if (!$conn) {
        echo json_encode(['error' => 'No DB connection']);
        exit;
    }

    $sql = "
        SELECT 
            COUNT(CASE WHEN CVENDE = '01' THEN 1 END) as total_cartera_endrina,
            COUNT(CASE WHEN CVENDE = '01' AND CESTADO = 'V' THEN 1 END) as activos_cartera_endrina,
            COUNT(CASE WHEN CUSUARI LIKE '%ENDRINA%' THEN 1 END) as creados_por_endrina_total,
            COUNT(CASE WHEN CUSUARI LIKE '%ENDRINA%' AND YEAR(DFECCRE) = 2026 THEN 1 END) as creados_endrina_2026,
            COUNT(CASE WHEN CVENDE = '01' AND YEAR(DFECCRE) = 2026 THEN 1 END) as cartera_endrina_2026
        FROM [003BDCOMUN].dbo.MAECLI
    ";
    $stmt = $conn->query($sql);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
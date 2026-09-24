<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/database.php';

try {
    $conn = getStarsoftDB();
    if (!$conn) {
        echo json_encode(['error' => 'No DB connection']);
        exit;
    }

    // 1. Clientes asignados a la cartera de Endrina (CVENDE = '01')
    $stmt1 = $conn->query("SELECT COUNT(*) FROM [003BDCOMUN].dbo.MAECLI WHERE CVENDE = '01'");
    $totalCartera = $stmt1->fetchColumn();

    // 2. Clientes asignados a Endrina con estado Vigente ('V')
    $stmt2 = $conn->query("SELECT COUNT(*) FROM [003BDCOMUN].dbo.MAECLI WHERE CVENDE = '01' AND CESTADO = 'V'");
    $activosCartera = $stmt2->fetchColumn();

    // 3. Clientes creados en el sistema por el usuario ENDRINA (en todas las carteras)
    $stmt3 = $conn->query("SELECT COUNT(*) FROM [003BDCOMUN].dbo.MAECLI WHERE CUSUARI LIKE '%ENDRINA%'");
    $creadosPorEndrina = $stmt3->fetchColumn();

    // 4. Clientes creados por Endrina en el año 2026
    $stmt4 = $conn->query("SELECT COUNT(*) FROM [003BDCOMUN].dbo.MAECLI WHERE CUSUARI LIKE '%ENDRINA%' AND YEAR(DFECCRE) = 2026");
    $creados2026 = $stmt4->fetchColumn();

    // 5. Clientes de la cartera de Endrina creados en el 2026
    $stmt5 = $conn->query("SELECT COUNT(*) FROM [003BDCOMUN].dbo.MAECLI WHERE CVENDE = '01' AND YEAR(DFECCRE) = 2026");
    $cartera2026 = $stmt5->fetchColumn();

    echo json_encode([
        'total_cartera_endrina_01' => (int)$totalCartera,
        'activos_cartera_endrina' => (int)$activosCartera,
        'total_creados_por_endrina' => (int)$creadosPorEndrina,
        'creados_por_endrina_2026' => (int)$creados2026,
        'cartera_endrina_2026' => (int)$cartera2026
    ], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
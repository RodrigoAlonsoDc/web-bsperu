<?php
// crm/api_sync_cpe.php - Receptor Seguro de Comprobantes Electrónicos (StarSoft ERP Push Sync)
header('Content-Type: application/json; charset=utf-8');

// Clave secreta compartida entre el sincronizador en Azure/Oficina y el servidor web
$SYNC_TOKEN = 'bsperu_sec_cpe_2026_98df8a7c2b3e4f1a';

// 1. Validar autenticación por Header o parámetro POST
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_SYNC_TOKEN'] ?? '';
$tokenReceived = '';

if (!empty($authHeader)) {
    if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        $tokenReceived = $matches[1];
    } else {
        $tokenReceived = trim($authHeader);
    }
}
if (empty($tokenReceived)) {
    $tokenReceived = $_POST['token'] ?? $_GET['token'] ?? '';
}

if (!hash_equals($SYNC_TOKEN, (string)$tokenReceived)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'No autorizado. Token de sincronización inválido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Procesar datos entrantes
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    if (isset($_POST['documentos'])) {
        $data = json_decode($_POST['documentos'], true);
    }
}

if (!is_array($data) || (!isset($data['documentos']) && !isset($data[0]))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Formato JSON inválido. Se esperaba lista de documentos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$docs = isset($data['documentos']) ? $data['documentos'] : $data;

$dataDir = __DIR__ . '/crm_data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0777, true);
}

$dataFile = $dataDir . '/comprobantes_cpe.json';

date_default_timezone_set('America/Lima');
$ahora = date('Y-m-d H:i:s');
$ahoraFormato = date('d/m/Y h:i A');

$payloadGuardar = [
    'ultima_actualizacion' => $ahora,
    'ultima_actualizacion_fmt' => $ahoraFormato,
    'total' => count($docs),
    'origen' => 'StarSoft ERP (Sincronización Segura Unidireccional)',
    'documentos' => $docs
];

$jsonFinal = json_encode($payloadGuardar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$tempFile = $dataFile . '.tmp';

if (file_put_contents($tempFile, $jsonFinal, LOCK_EX) !== false) {
    rename($tempFile, $dataFile);
    echo json_encode([
        'success' => true,
        'mensaje' => 'Comprobantes sincronizados exitosamente con el CRM.',
        'total' => count($docs),
        'fecha' => $ahoraFormato
    ], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo guardar el archivo en el servidor.'
    ], JSON_UNESCAPED_UNICODE);
}

<?php
// crm/api_sync_cpe.php - Receptor Seguro de StarSoft ERP (Push Sync de CPE, Pedidos y Cotizaciones)
header('Content-Type: application/json; charset=utf-8');

$SYNC_TOKEN = 'bsperu_sec_cpe_2026_98df8a7c2b3e4f1a';

// 1. Validar autenticación
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
    if (isset($_POST['data'])) {
        $data = json_decode($_POST['data'], true);
    }
}

$dataDir = __DIR__ . '/crm_data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0777, true);
}

date_default_timezone_set('America/Lima');
$ahora = date('Y-m-d H:i:s');
$ahoraFormato = date('d/m/Y h:i A');
$resultados = [];

// A. Sincronizar Pedidos si vienen en el payload
if (isset($data['pedidos']) && is_array($data['pedidos'])) {
    $pedidosFile = $dataDir . '/pedidos.json';
    $nuevosPedidos = $data['pedidos'];
    
    // Si ya existe pedidos.json, fusionar sin perder estados locales
    $existentes = file_exists($pedidosFile) ? (json_decode(file_get_contents($pedidosFile), true) ?: []) : [];
    $mapExistentes = [];
    foreach ($existentes as $ex) {
        $nro = strtoupper(trim($ex['nro_pedido'] ?? ''));
        if (!empty($nro)) $mapExistentes[$nro] = $ex;
    }
    
    foreach ($nuevosPedidos as $np) {
        $nro = strtoupper(trim($np['nro_pedido'] ?? ''));
        if (!empty($nro)) {
            // Preservar si ya fue validado/facturado localmente
            if (isset($mapExistentes[$nro]['estado']) && $mapExistentes[$nro]['estado'] === 'Facturado') {
                $np['estado'] = 'Facturado';
                $np['factura_vinculada'] = $mapExistentes[$nro]['factura_vinculada'] ?? '';
                $np['guia_vinculada'] = $mapExistentes[$nro]['guia_vinculada'] ?? '';
            }
            $mapExistentes[$nro] = $np;
        }
    }
    
    $listaFinalPedidos = array_values($mapExistentes);
    usort($listaFinalPedidos, function($a, $b) {
        return strcmp($b['fecha'] ?? '', $a['fecha'] ?? '');
    });
    
    file_put_contents($pedidosFile, json_encode($listaFinalPedidos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    $resultados['pedidos'] = count($listaFinalPedidos);
}

// B. Sincronizar Cotizaciones si vienen en el payload
if (isset($data['cotizaciones']) && is_array($data['cotizaciones'])) {
    $cotizFile = $dataDir . '/cotizaciones.json';
    $nuevasCotiz = $data['cotizaciones'];
    
    $existentes = file_exists($cotizFile) ? (json_decode(file_get_contents($cotizFile), true) ?: []) : [];
    $mapExistentes = [];
    foreach ($existentes as $ex) {
        $c = strtoupper(trim($ex['codigo'] ?? ''));
        if (!empty($c)) $mapExistentes[$c] = $ex;
    }
    
    foreach ($nuevasCotiz as $nc) {
        $c = strtoupper(trim($nc['codigo'] ?? ''));
        if (!empty($c)) {
            $mapExistentes[$c] = $nc;
        }
    }
    
    $listaFinalCotiz = array_values($mapExistentes);
    usort($listaFinalCotiz, function($a, $b) {
        return strcmp($b['fecha'] ?? '', $a['fecha'] ?? '');
    });
    
    file_put_contents($cotizFile, json_encode($listaFinalCotiz, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    $resultados['cotizaciones'] = count($listaFinalCotiz);
}

// C. Sincronizar Comprobantes CPE si vienen en el payload (o si es el payload clásico)
$docs = null;
if (isset($data['documentos']) && is_array($data['documentos'])) {
    $docs = $data['documentos'];
} elseif (isset($data[0]) && is_array($data[0])) {
    $docs = $data;
}

if ($docs !== null) {
    $cpeFile = $dataDir . '/comprobantes_cpe.json';
    $payloadGuardar = [
        'ultima_actualizacion' => $ahora,
        'ultima_actualizacion_fmt' => $ahoraFormato,
        'total' => count($docs),
        'origen' => 'StarSoft ERP (Sincronización Segura Unidireccional)',
        'documentos' => $docs
    ];
    file_put_contents($cpeFile, json_encode($payloadGuardar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    $resultados['documentos'] = count($docs);
}

echo json_encode([
    'success' => true,
    'mensaje' => 'Sincronización con StarSoft completada exitosamente.',
    'resultados' => $resultados,
    'fecha' => $ahoraFormato
], JSON_UNESCAPED_UNICODE);

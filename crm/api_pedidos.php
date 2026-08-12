<?php
session_start();
// Usamos la misma sesión del panel de administrador que ya tenías
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$dataFile = '../assets/Data/pedidos.json';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'list') {
    header('Content-Type: application/json');
    if (file_exists($dataFile)) {
        echo file_get_contents($dataFile);
    } else {
        echo json_encode([]);
    }
    exit;
}

if ($action === 'save') {
    $cliente = trim($_POST['cliente'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $productos_json = $_POST['productos'] ?? '[]';
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $igv = floatval($_POST['igv'] ?? 0);
    $total = floatval($_POST['total'] ?? 0);
    
    if (empty($cliente) || empty($productos_json) || $productos_json === '[]') {
        echo json_encode(["success" => false, "error" => "El cliente y al menos un producto son obligatorios"]);
        exit;
    }
    
    $pedidos = [];
    if (file_exists($dataFile)) {
        $pedidos = json_decode(file_get_contents($dataFile), true) ?? [];
    }
    
    $nuevoPedido = [
        "id" => "PED-" . date("Ymd-His"),
        "fecha" => date("Y-m-d H:i:s"),
        "cliente" => $cliente,
        "documento" => $documento,
        "productos" => json_decode($productos_json, true),
        "subtotal" => $subtotal,
        "igv" => $igv,
        "total" => $total,
        "estado" => "Pendiente" // Pendiente, En Proceso, Despachado, Cancelado
    ];
    
    array_unshift($pedidos, $nuevoPedido); // Añadir al inicio
    
    file_put_contents($dataFile, json_encode($pedidos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(["success" => true, "id" => $nuevoPedido['id']]);
    exit;
}

if ($action === 'update_status') {
    $id = $_POST['id'] ?? '';
    $estado = $_POST['estado'] ?? '';
    
    if (!$id || !$estado) {
        echo json_encode(["success" => false]);
        exit;
    }
    
    $pedidos = [];
    if (file_exists($dataFile)) {
        $pedidos = json_decode(file_get_contents($dataFile), true) ?? [];
    }
    
    foreach ($pedidos as &$p) {
        if ($p['id'] === $id) {
            $p['estado'] = $estado;
            break;
        }
    }
    
    file_put_contents($dataFile, json_encode($pedidos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(["success" => true]);
    exit;
}
?>

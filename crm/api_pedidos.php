<?php
session_start();
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
    // 1. Cabecera Izquierda
    $fecha_doc = trim($_POST['fecha_doc'] ?? date("Y-m-d"));
    $sucursal = trim($_POST['sucursal'] ?? '');
    $forma_pago = trim($_POST['forma_pago'] ?? '');
    $gestor_campo = trim($_POST['gestor_campo'] ?? '');
    $gestor_tienda = trim($_POST['gestor_tienda'] ?? '');
    $cliente = trim($_POST['cliente'] ?? ''); // Razón Social
    $documento = trim($_POST['documento'] ?? ''); // RUC
    $descuento = trim($_POST['descuento'] ?? '0');
    $direccion = trim($_POST['direccion'] ?? '');
    $lugar_entrega = trim($_POST['lugar_entrega'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');

    // 2. Cabecera Centro
    $lugar_compra = trim($_POST['lugar_compra'] ?? '');
    $tiempo_entrega = trim($_POST['tiempo_entrega'] ?? '');
    $tipo_despacho = trim($_POST['tipo_despacho'] ?? '');
    $glosa = trim($_POST['glosa'] ?? '');
    $comentario = trim($_POST['comentario'] ?? '');

    // 3. CRM
    $fecha_uso = trim($_POST['fecha_uso'] ?? '');
    $fecha_compra = trim($_POST['fecha_compra'] ?? '');
    $fecha_llamada = trim($_POST['fecha_llamada'] ?? '');
    $otro_proveedor = trim($_POST['otro_proveedor'] ?? '');

    // 4. Contacto
    $telefono = trim($_POST['telefono'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $tipo_control = trim($_POST['tipo_control'] ?? '');

    // 5. Productos y Totales
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
    
    // Generar ID
    $last_id = 51700;
    if (count($pedidos) > 0) {
        $last_id = intval($pedidos[0]['id']) + 1;
    }
    $nuevo_id = str_pad($last_id, 7, "0", STR_PAD_LEFT);

    $nuevoPedido = [
        "id" => $nuevo_id,
        "fecha_doc" => $fecha_doc,
        "fecha_sys" => date("Y-m-d H:i:s"),
        "sucursal" => $sucursal,
        "forma_pago" => $forma_pago,
        "gestor_campo" => $gestor_campo,
        "gestor_tienda" => $gestor_tienda,
        
        "cliente" => $cliente,
        "documento" => $documento,
        "descuento" => $descuento,
        "direccion" => $direccion,
        "lugar_entrega" => $lugar_entrega,
        "departamento" => $departamento,
        
        "lugar_compra" => $lugar_compra,
        "tiempo_entrega" => $tiempo_entrega,
        "tipo_despacho" => $tipo_despacho,
        "glosa" => $glosa,
        "comentario" => $comentario,
        
        "fecha_uso" => $fecha_uso,
        "fecha_compra" => $fecha_compra,
        "fecha_llamada" => $fecha_llamada,
        "otro_proveedor" => $otro_proveedor,
        
        "telefono" => $telefono,
        "contacto" => $contacto,
        "tipo_control" => $tipo_control,

        "productos" => json_decode($productos_json, true),
        "subtotal" => $subtotal,
        "igv" => $igv,
        "total" => $total,
        
        "estado" => "AUTORIZADO",
        "detalle" => "STARSOFT",
        "doc_sts" => "null"
    ];
    
    array_unshift($pedidos, $nuevoPedido);
    
    file_put_contents($dataFile, json_encode($pedidos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(["success" => true, "id" => $nuevoPedido['id']]);
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    
    if (!$id) {
        echo json_encode(["success" => false]);
        exit;
    }
    
    $pedidos = json_decode(file_get_contents($dataFile), true) ?? [];
    $pedidos = array_filter($pedidos, function($p) use ($id) {
        return $p['id'] !== $id;
    });
    
    file_put_contents($dataFile, json_encode(array_values($pedidos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(["success" => true]);
    exit;
}
?>

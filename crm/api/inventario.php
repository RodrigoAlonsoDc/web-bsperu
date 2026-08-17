<?php
// api/inventario.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'list_productos') {
        $stmt = $db->query('SELECT * FROM productos ORDER BY nombre ASC');
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'list_lotes') {
        $sql = "SELECT l.*, p.nombre as producto_nombre FROM inventario_lotes l JOIN productos p ON l.producto_id = p.id ORDER BY l.id DESC LIMIT 50";
        $stmt = $db->query($sql);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'save_producto') {
        $nombre = trim($_POST['nombre'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $unidad = trim($_POST['unidad_medida'] ?? 'unidad');

        if(empty($nombre)){
            echo json_encode(['success'=>false, 'error'=>'Nombre requerido']);
            exit;
        }

        $sql = "INSERT INTO productos (nombre, categoria, unidad_medida) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$nombre, $categoria, $unidad]);
        echo json_encode(['success'=>true, 'id'=>$db->lastInsertId()]);
        exit;
    }

    if ($action === 'save_lote') {
        $producto_id = $_POST['producto_id'] ?? '';
        $cantidad = $_POST['cantidad'] ?? 0;
        $estante = trim($_POST['estante'] ?? '');
        $fecha_vencimiento = trim($_POST['fecha_vencimiento'] ?? '');
        $sede_id = 1; // Chorrillos por defecto
        
        if(!$producto_id || $cantidad <= 0) {
            echo json_encode(['success'=>false, 'error'=>'Producto y cantidad validos son requeridos']);
            exit;
        }

        // Generar codigo de barras unico: BSP-YYMMDD-XXXX
        $codigo_barras = 'BSP-' . date('ymd') . '-' . rand(1000, 9999);

        $sql = "INSERT INTO inventario_lotes (producto_id, sede_id, codigo_barras, cantidad, estante, fecha_vencimiento, registrado_por) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$producto_id, $sede_id, $codigo_barras, $cantidad, $estante, $fecha_vencimiento ? $fecha_vencimiento : null, 1]);
        
        echo json_encode(['success'=>true, 'codigo_barras'=>$codigo_barras]);
        exit;
    }

    if ($action === 'scan') {
        $codigo_barras = trim($_POST['codigo_barras'] ?? '');
        if(!$codigo_barras){
            echo json_encode(['success'=>false, 'error'=>'Codigo vacio']);
            exit;
        }

        $sql = "SELECT l.*, p.nombre as producto_nombre, p.unidad_medida FROM inventario_lotes l JOIN productos p ON l.producto_id = p.id WHERE l.codigo_barras = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$codigo_barras]);
        $lote = $stmt->fetch();

        if($lote) {
            echo json_encode(['success'=>true, 'data'=>$lote]);
        } else {
            echo json_encode(['success'=>false, 'error'=>'Codigo de barras no encontrado en el sistema']);
        }
        exit;
    }

} catch(PDOException $e) {
    echo json_encode(['success'=>false, 'error'=>'Error BD: ' . $e->getMessage()]);
}
?>
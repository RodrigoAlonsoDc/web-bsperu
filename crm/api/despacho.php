<?php
// crm/api/despacho.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'list_facturadas') {
        // Pedidos listos para despachar
        $sql = "SELECT c.*, cl.razon_social as cliente_nombre, cl.ruc_dni 
                FROM cotizaciones c 
                JOIN clientes cl ON c.cliente_id = cl.id 
                WHERE c.estado = 'Facturada'
                ORDER BY c.id ASC";
        $stmt = $db->query($sql);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'get_pedido_items') {
        $id = $_GET['id'] ?? 0;
        $sql = "SELECT cd.*, p.nombre, p.codigo_interno 
                FROM cotizacion_detalles cd 
                JOIN productos p ON cd.producto_id = p.id 
                WHERE cd.cotizacion_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        echo json_encode(['success'=>true, 'data'=>$stmt->fetchAll()]);
        exit;
    }

    if ($action === 'scan_lote') {
        // Escaneo de barra (Ej. BSP-260817-0001) y descuento
        $codigo_lote = $_POST['codigo_lote'] ?? '';
        $producto_esperado_id = $_POST['producto_id'] ?? 0;
        
        $sql = "SELECT * FROM inventario_lotes WHERE codigo_barras = ? AND cantidad > 0";
        $stmt = $db->prepare($sql);
        $stmt->execute([$codigo_lote]);
        $lote = $stmt->fetch();

        if (!$lote) {
            echo json_encode(['success'=>false, 'error'=>'Lote no encontrado o sin stock disponible.']);
            exit;
        }

        if ($lote['producto_id'] != $producto_esperado_id) {
            echo json_encode(['success'=>false, 'error'=>'El lote escaneado NO corresponde al producto esperado.']);
            exit;
        }

        // Restar 1 unidad
        $db->beginTransaction();
        $upd = "UPDATE inventario_lotes SET cantidad = cantidad - 1 WHERE id = ?";
        $stmtUpd = $db->prepare($upd);
        $stmtUpd->execute([$lote['id']]);
        $db->commit();

        echo json_encode(['success'=>true, 'lote'=>$lote['codigo_barras']]);
        exit;
    }

    if ($action === 'finalizar_despacho') {
        $cotizacion_id = $_POST['cotizacion_id'] ?? 0;
        $modalidad = $_POST['modalidad'] ?? '';
        $agencia = $_POST['agencia'] ?? '';
        $guia = $_POST['guia'] ?? '';
        $operario_id = 1; // Default

        $db->beginTransaction();

        $sql = "INSERT INTO despachos (cotizacion_id, operario_id, modalidad, agencia_transporte, guia_remision) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$cotizacion_id, $operario_id, $modalidad, $agencia, $guia]);

        $sql_upd = "UPDATE cotizaciones SET estado = 'Despachada' WHERE id = ?";
        $stmt_upd = $db->prepare($sql_upd);
        $stmt_upd->execute([$cotizacion_id]);

        $db->commit();
        echo json_encode(['success'=>true]);
        exit;
    }

} catch(PDOException $e) {
    if(isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success'=>false, 'error'=>'Error BD: ' . $e->getMessage()]);
}
?>
<?php
// crm/api/cotizaciones.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'list') {
        $sql = "SELECT c.*, cl.razon_social as cliente_nombre, cl.ruc_dni 
                FROM cotizaciones c 
                JOIN clientes cl ON c.cliente_id = cl.id 
                ORDER BY c.id DESC LIMIT 100";
        $stmt = $db->query($sql);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'get_details') {
        $id = $_GET['id'] ?? 0;
        
        $sqlC = "SELECT c.*, cl.razon_social, cl.ruc_dni, cl.direccion, cl.telefono, cl.correo 
                 FROM cotizaciones c JOIN clientes cl ON c.cliente_id = cl.id WHERE c.id = ?";
        $stmtC = $db->prepare($sqlC);
        $stmtC->execute([$id]);
        $cotizacion = $stmtC->fetch();

        if(!$cotizacion) {
            echo json_encode(['success'=>false, 'error'=>'No encontrada']); exit;
        }

        $sqlD = "SELECT cd.*, p.nombre as producto_nombre, p.unidad_medida, cd.precio_unitario as precio 
                 FROM cotizacion_detalles cd JOIN productos p ON cd.producto_id = p.id 
                 WHERE cd.cotizacion_id = ?";
        $stmtD = $db->prepare($sqlD);
        $stmtD->execute([$id]);
        $detalles = $stmtD->fetchAll();

        $cotizacion['detalles'] = $detalles;
        echo json_encode(['success'=>true, 'data'=>$cotizacion]);
        exit;
    }

    if ($action === 'save') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $edit_id = $data['edit_id'] ?? 0;
        $cliente_id = $data['cliente_id'] ?? 0;
        $detalles = $data['detalles'] ?? [];
        $notas = $data['notas'] ?? '';
        $vendedor_id = 1; 
        
        if(!$cliente_id || empty($detalles)) {
            echo json_encode(['success'=>false, 'error'=>'Falta cliente o productos']);
            exit;
        }

        $subtotal = 0;
        foreach($detalles as $d) {
            $subtotal += ($d['cantidad'] * $d['precio']);
        }
        $igv = $subtotal * 0.18;
        $total = $subtotal + $igv;

        $db->beginTransaction();

        if ($edit_id > 0) {
            // Edit existing
            $sql = "UPDATE cotizaciones SET cliente_id=?, subtotal=?, igv=?, total=?, notas=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$cliente_id, $subtotal, $igv, $total, $notas, $edit_id]);
            
            $cot_id = $edit_id;
            
            $db->prepare("DELETE FROM cotizacion_detalles WHERE cotizacion_id=?")->execute([$cot_id]);
            
            $stmtC = $db->prepare("SELECT codigo FROM cotizaciones WHERE id=?");
            $stmtC->execute([$cot_id]);
            $codigo = $stmtC->fetchColumn();

        } else {
            // Insert new
            $today = date('ymd');
            $stmt = $db->query("SELECT COUNT(*) FROM cotizaciones WHERE DATE(created_at) = CURDATE()");
            $count = $stmt->fetchColumn() + 1;
            $codigo = 'COT-' . $today . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            $sql = "INSERT INTO cotizaciones (codigo, cliente_id, vendedor_id, subtotal, igv, total, notas) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$codigo, $cliente_id, $vendedor_id, $subtotal, $igv, $total, $notas]);
            $cot_id = $db->lastInsertId();
        }

        $sql_det = "INSERT INTO cotizacion_detalles (cotizacion_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)";
        $stmt_det = $db->prepare($sql_det);
        
        foreach($detalles as $d) {
            $st = $d['cantidad'] * $d['precio'];
            $stmt_det->execute([$cot_id, $d['producto_id'], $d['cantidad'], $d['precio'], $st]);
        }

        $db->commit();
        echo json_encode(['success'=>true, 'cotizacion_id'=>$cot_id, 'codigo'=>$codigo]);
        exit;
    }

    if ($action === 'update_status') {
        $id = $_POST['id'] ?? 0;
        $estado = $_POST['estado'] ?? '';
        
        $sql = "UPDATE cotizaciones SET estado = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$estado, $id]);
        
        echo json_encode(['success'=>true]);
        exit;
    }

} catch(PDOException $e) {
    if(isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success'=>false, 'error'=>'Error BD: ' . $e->getMessage()]);
}
?>
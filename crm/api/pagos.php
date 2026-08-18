<?php
// crm/api/pagos.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'list_aceptadas') {
        // Solo lista cotizaciones que ya fueron aceptadas y no han sido pagadas aun
        $sql = "SELECT c.*, cl.razon_social as cliente_nombre, cl.ruc_dni 
                FROM cotizaciones c 
                JOIN clientes cl ON c.cliente_id = cl.id 
                WHERE c.estado = 'Aceptada'
                ORDER BY c.id DESC";
        $stmt = $db->query($sql);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'registrar_pago') {
        $cotizacion_id = $_POST['cotizacion_id'] ?? 0;
        $metodo_pago = $_POST['metodo_pago'] ?? '';
        $numero_operacion = $_POST['numero_operacion'] ?? '';
        $monto_pagado = $_POST['monto_pagado'] ?? 0;
        $validador_id = 1; // Default por ahora

        if(!$cotizacion_id || !$metodo_pago) {
            echo json_encode(['success'=>false, 'error'=>'Faltan datos obligatorios']);
            exit;
        }

        $db->beginTransaction();

        // Registrar el pago
        $sql = "INSERT INTO pagos (cotizacion_id, metodo_pago, numero_operacion, monto_pagado, validador_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$cotizacion_id, $metodo_pago, $numero_operacion, $monto_pagado, $validador_id]);

        // Actualizar estado de la cotizacion a Facturada (o Pagada)
        // Ya que pidieron "una factura con el monto de cancelado para operaciones"
        $sql_upd = "UPDATE cotizaciones SET estado = 'Facturada' WHERE id = ?";
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
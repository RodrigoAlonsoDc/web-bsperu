<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once '../config/database.php';
header('Content-Type: application/json');

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'list') {
        $stmt = $db->query('SELECT * FROM clientes ORDER BY id DESC');
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'save') {
        $id = $_POST['id'] ?? '';
        $ruc_dni = trim($_POST['ruc_dni'] ?? '');
        $razon_social = trim($_POST['razon_social'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $agente_retencion = isset($_POST['agente_retencion']) && $_POST['agente_retencion'] === '1' ? 1 : 0;
        $correo = trim($_POST['correo'] ?? '');
        $contacto = trim($_POST['contacto'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $usuario_id = 1;

        if (empty($ruc_dni) || empty($razon_social)) {
            echo json_encode(['success' => false, 'error' => 'RUC/DNI y Razon Social son obligatorios']);
            exit;
        }

        if ($id) {
            $sql = 'UPDATE clientes SET ruc_dni=?, razon_social=?, direccion=?, categoria=?, agente_retencion=?, correo=?, contacto=?, telefono=? WHERE id=?';
            $stmt = $db->prepare($sql);
            $stmt->execute([$ruc_dni, $razon_social, $direccion, $categoria, $agente_retencion, $correo, $contacto, $telefono, $id]);
        } else {
            $sql = 'INSERT INTO clientes (ruc_dni, razon_social, direccion, categoria, agente_retencion, correo, contacto, telefono, registrado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $db->prepare($sql);
            $stmt->execute([$ruc_dni, $razon_social, $direccion, $categoria, $agente_retencion, $correo, $contacto, $telefono, $usuario_id]);
        }
        
        echo json_encode(['success' => true]);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de base de datos']);
}
?>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

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

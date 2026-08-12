<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    exit;
}
header('Content-Type: application/json');

$numero = $_GET['numero'] ?? '';

if (!$numero) {
    echo json_encode(['success' => false, 'error' => 'Número requerido']);
    exit;
}

// Determinar si es DNI (8 dígitos) o RUC (11 dígitos)
$tipo = (strlen($numero) === 11) ? 'ruc' : 'dni';
$url = ($tipo === 'ruc') ? "https://api.apis.net.pe/v1/ruc?numero=$numero" : "https://api.apis.net.pe/v1/dni?numero=$numero";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode == 200 && $response) {
    $data = json_decode($response, true);
    if ($tipo === 'dni') {
        $nombre = trim(($data['nombres'] ?? '') . ' ' . ($data['apellidoPaterno'] ?? '') . ' ' . ($data['apellidoMaterno'] ?? ''));
    } else {
        $nombre = trim($data['nombre'] ?? '');
    }
    echo json_encode(['success' => true, 'nombre' => $nombre]);
} else {
    echo json_encode(['success' => false, 'error' => 'Documento no encontrado']);
}
?>

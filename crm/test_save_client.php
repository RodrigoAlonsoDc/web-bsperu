<?php
session_start();
$_SESSION['crm_logged_in'] = true;
$_SESSION['crm_user'] = 'ENDRINA';
$_SESSION['is_endrina'] = true;
$_SESSION['crm_vendedor_cod'] = '01';

$_POST['action'] = 'guardar_cliente';
$_POST['razon'] = 'PRUEBA VERIFICACION CLIENTE SAC';
$_POST['ruc'] = '20999111222';
$_POST['contacto'] = 'INGENIERO PRUEBA';
$_POST['telefono'] = '987654321';
$_POST['categoria'] = 'Activo';
$_POST['direccion'] = 'CALLE PRUEBA 123';
$_POST['email'] = 'prueba@cliente.com';
$_POST['vendedor'] = '01';

ob_start();
require_once __DIR__ . '/crm_backend.php';
$output = ob_get_clean();
echo "RESULTADO: " . $output;

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err) {
        echo "SHUTDOWN ERROR:\n" . print_r($err, true);
    }
});
$_GET['action'] = 'consultar_documento';
$_GET['numero'] = '09647749';
require_once __DIR__ . '/crm_backend.php';

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$_GET['action'] = 'consultar_documento';
$_GET['numero'] = '20602591990';
require_once __DIR__ . '/crm_backend.php';

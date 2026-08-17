<?php
// crm/config/database.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'ene27bspe5226d_erp');
define('DB_USER', 'ene27bspe5226d_admin');
define('DB_PASS', 'BsPeru_ERP2026*');

function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $db = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Error de conexion a la base de datos.']));
        }
    }
    return $db;
}
?>
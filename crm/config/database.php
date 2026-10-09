<?php
// config/database.php - Conector Oficial Starsoft ERP (SQL Server en Azure)

function getStarsoftDB() {
    static $conn = null;
    static $hasChecked = false;
    static $isAvailable = false;

    if ($conn !== null) {
        return $conn;
    }

    if ($hasChecked && !$isAvailable) {
        return null;
    }

    $host = '48.216.211.109';
    $db   = 'BDTPED_SSA';
    $user = 'SOPORTE';
    $pass = 'SOPORTE';

    // 1. Verificacion ultra-rapida de conectividad TCP (timeout de 0.8s)
    // Esto evita que el servidor web se cuelgue con 504 Gateway Timeout
    // si Azure esta inaccesible o la IP no esta en la lista blanca de Azure.
    $activePort = null;
    foreach ([443, 1433] as $port) {
        $fp = @fsockopen($host, $port, $errno, $errstr, 0.8);
        if ($fp) {
            fclose($fp);
            $activePort = $port;
            break;
        }
    }

    $hasChecked = true;
    if (!$activePort) {
        $isAvailable = false;
        return null; // Fallback instantaneo a modo local/JSON (tarda < 1s)
    }

    $isAvailable = true;

    // 2. Solo probar candidatos para el puerto detectado
    $dsnCandidates = [];
    if ($activePort === 443) {
        $dsnCandidates = [
            "odbc:Driver=FreeTDS;Server=$host;Port=443;Database=$db;TDS_Version=7.4;ClientCharset=UTF-8;LoginTimeout=2;Connection Timeout=2;",
            "odbc:Driver=FreeTDS;Server=$host,443;Database=$db;LoginTimeout=2;Connection Timeout=2;",
            "dblib:host=$host:443;dbname=$db;charset=UTF-8"
        ];
    } else {
        $dsnCandidates = [
            "sqlsrv:Server=$host,1433;Database=$db;TrustServerCertificate=true;Encrypt=false;LoginTimeout=2",
            "odbc:Driver=FreeTDS;Server=$host;Port=1433;Database=$db;TDS_Version=7.4;ClientCharset=UTF-8;LoginTimeout=2;Connection Timeout=2;",
            "dblib:host=$host:1433;dbname=$db;charset=UTF-8",
            "odbc:Driver=ODBC Driver 18 for SQL Server;Server=$host,1433;Database=$db;TrustServerCertificate=yes;Encrypt=no;LoginTimeout=2;"
        ];
    }

    foreach ($dsnCandidates as $dsn) {
        try {
            $conn = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            return $conn;
        } catch (Exception $e) {
            // Continua con el siguiente candidato si falla
        }
    }

    return null;
}

function getDB() {
    return getStarsoftDB();
}
<?php
// config/database.php - Conector Oficial Starsoft ERP (SQL Server en Azure)

function getStarsoftDB() {
    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }

    $host = '48.216.211.109';
    $db   = 'BDTPED_SSA';
    $user = 'SOPORTE';
    $pass = 'SOPORTE';

    $dsnCandidates = [
        // Puerto 1433 (Oficial SQL Server)
        "odbc:Driver=FreeTDS;Server=$host;Port=1433;Database=$db;TDS_Version=7.4;ClientCharset=UTF-8;",
        "odbc:Driver=FreeTDS;Server=$host,1433;Database=$db;TDS_Version=7.4;ClientCharset=UTF-8;",
        "dblib:host=$host:1433;dbname=$db;charset=UTF-8",
        // Puerto 443 (HTTPS alternativo libre de bloqueos de hosting)
        "odbc:Driver=FreeTDS;Server=$host;Port=443;Database=$db;TDS_Version=7.4;ClientCharset=UTF-8;",
        "odbc:Driver=FreeTDS;Server=$host;Port=443;Database=$db;TDS_Version=7.3;ClientCharset=UTF-8;",
        "odbc:Driver=FreeTDS;Server=$host,443;Database=$db;",
        "dblib:host=$host:443;dbname=$db;charset=UTF-8",
        // Puerto 80 (FreeTDS proxy)
        "odbc:Driver=FreeTDS;Server=$host;Port=80;Database=$db;TDS_Version=7.4;ClientCharset=UTF-8;",
        "odbc:Driver=FreeTDS;Server=$host;Port=80;Database=$db;TDS_Version=7.3;ClientCharset=UTF-8;",
        "odbc:Driver=FreeTDS;Server=$host,80;Database=$db;",
        // Drivers nativos
        "sqlsrv:Server=$host,1433;Database=$db;TrustServerCertificate=true;Encrypt=false",
        "odbc:Driver=ODBC Driver 18 for SQL Server;Server=$host,1433;Database=$db;TrustServerCertificate=yes;Encrypt=no;",
        "odbc:Driver=ODBC Driver 17 for SQL Server;Server=$host,1433;Database=$db;TrustServerCertificate=yes;Encrypt=no;"
    ];

    foreach ($dsnCandidates as $dsn) {
        try {
            $conn = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            return $conn;
        } catch (Exception $e) {
            // Continúa con el siguiente candidato
        }
    }

    return null;
}

function getDB() {
    return getStarsoftDB();
}

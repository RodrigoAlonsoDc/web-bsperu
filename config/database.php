<?php
// config/database.php - Conector Oficial Starsoft ERP (SQL Server en Azure)

function getStarsoftDB() {
    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }

    $host = '48.216.211.109';
    $port = 80;
    $db   = 'BDTPED_SSA';
    $user = 'SOPORTE';
    $pass = 'SOPORTE';

    $dsnCandidates = [
        "odbc:Driver=FreeTDS;Server=$host;Port=$port;Database=$db;TDS_Version=7.4;ClientCharset=UTF-8;",
        "odbc:Driver=FreeTDS;Server=$host;Port=$port;Database=$db;TDS_Version=7.3;ClientCharset=UTF-8;",
        "odbc:Driver=FreeTDS;Server=$host,$port;Database=$db;",
        "odbc:Driver=ODBC Driver 18 for SQL Server;Server=$host,$port;Database=$db;TrustServerCertificate=yes;Encrypt=no;",
        "odbc:Driver=ODBC Driver 17 for SQL Server;Server=$host,$port;Database=$db;TrustServerCertificate=yes;Encrypt=no;"
    ];

    foreach ($dsnCandidates as $dsn) {
        try {
            $conn = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 6,
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

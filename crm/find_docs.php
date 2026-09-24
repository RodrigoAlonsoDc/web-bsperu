<?php
$logDir = '/home/ene27bspe5226d/access-logs/';
$files = is_dir($logDir) ? scandir($logDir) : [];
$matches = [];

function searchInFile($path, &$matches) {
    if (!is_file($path) || !is_readable($path)) return;
    $fp = fopen($path, 'r');
    if (!$fp) return;
    // Seek near end if large
    $size = filesize($path);
    if ($size > 10000000) {
        fseek($fp, -5000000, SEEK_END);
    }
    while (($line = fgets($fp)) !== false) {
        if (strpos($line, 'consultar_documento') !== false) {
            $matches[] = trim($line);
        }
    }
    fclose($fp);
}

foreach ($files as $f) {
    if ($f === '.' || $f === '..') continue;
    searchInFile($logDir . $f, $matches);
}

// Also check /home/ene27bspe5226d/logs/
$logDir2 = '/home/ene27bspe5226d/logs/';
if (is_dir($logDir2)) {
    foreach (scandir($logDir2) as $f) {
        if (stripos($f, 'access') !== false) {
            searchInFile($logDir2 . $f, $matches);
        }
    }
}

header('Content-Type: application/json');
echo json_encode(array_slice($matches, -20), JSON_PRETTY_PRINT);

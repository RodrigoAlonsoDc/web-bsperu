<?php
$dir = '/home/ene27bspe5226d/logs/';
$files = is_dir($dir) ? scandir($dir) : [];
$accessLog = '';
foreach ($files as $f) {
    if (stripos($f, 'access') !== false || stripos($f, 'bsperu') !== false) {
        $p = $dir . $f;
        if (is_file($p)) {
            $lines = file($p);
            $accessLog .= "=== $p ===\n" . implode('', array_slice($lines, -15)) . "\n";
        }
    }
}
header('Content-Type: application/json');
echo json_encode(['files' => $files, 'logs' => $accessLog]);

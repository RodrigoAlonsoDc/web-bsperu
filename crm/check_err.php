<?php
$lines = file('/home/ene27bspe5226d/logs/bsperu_pe.php.error.log');
header('Content-Type: text/plain');
echo implode('', array_slice($lines, -10));

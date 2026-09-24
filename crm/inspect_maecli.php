<?php
require_once __DIR__ . '/config/database.php';
 = getDB();
if (!) { echo json_encode(['error' => 'No DB']); exit; }
 = ->query("SELECT TOP 3 CCODCLI, CNOMCLI, CDIRCLI, CTELEFO, CNUMRUC, CVENDE, CUSUARI, CESTADO, CTIPVTA, CTIPO_DOCUMENTO, DFECCRE, CDOCIDEN, CDEPT, CPROV, CDIST FROM [003BDCOMUN].dbo.MAECLI WHERE CUSUARI LIKE '%ENDRINA%' ORDER BY DFECCRE DESC");
 = ->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(, JSON_PRETTY_PRINT);

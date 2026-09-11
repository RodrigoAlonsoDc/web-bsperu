<?php
// test_starsoft.php - Diagnóstico de Conexión SQL Server / Azure para Starsoft BS Perú
header('Content-Type: text/html; charset=utf-8');

$phpVersion = phpversion();
$loadedExtensions = get_loaded_extensions();

// 1. Detección de Drivers para SQL Server
$hasPdoSqlsrv = extension_loaded('pdo_sqlsrv');
$hasSqlsrv    = extension_loaded('sqlsrv');
$hasPdoDblib  = extension_loaded('pdo_dblib');
$hasOdbc      = extension_loaded('odbc');
$hasPdoOdbc   = extension_loaded('pdo_odbc');

$hasAnyDriver = $hasPdoSqlsrv || $hasSqlsrv || $hasPdoDblib || $hasPdoOdbc;

// 2. Detección de IP Pública del Hosting (para Azure NSG)
$serverPublicIp = 'No detectada';
try {
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $ipFetch = @file_get_contents('https://api.ipify.org', false, $ctx);
    if ($ipFetch && filter_var(trim($ipFetch), FILTER_VALIDATE_IP)) {
        $serverPublicIp = trim($ipFetch);
    } else if (isset($_SERVER['SERVER_ADDR'])) {
        $serverPublicIp = $_SERVER['SERVER_ADDR'];
    }
} catch (Exception $e) {
    $serverPublicIp = $_SERVER['SERVER_ADDR'] ?? 'Desconocida';
}

// 3. Probar conexión en vivo si se envió el formulario
$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['probar_conexion'])) {
    $host = trim($_POST['host'] ?? '');
    $port = trim($_POST['port'] ?? '1433');
    $db   = trim($_POST['database'] ?? '');
    $user = trim($_POST['user'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    // Primero: Probar si el puerto 1433 está abierto (Prueba de Socket / Firewall)
    $socketConn = @fsockopen($host, (int)$port, $sockErrNo, $sockErrStr, 4);
    if (!$socketConn) {
        $testResult = [
            'success' => false,
            'step' => 'Firewall / Red',
            'message' => "No se pudo alcanzar $host en el puerto $port ($sockErrStr). Posible bloqueo en el Firewall de Azure (NSG) o en Windows Defender Firewall de la máquina virtual."
        ];
    } else {
        fclose($socketConn);
        // Segundo: Probar autenticación con SQL Server
        if (!$hasAnyDriver) {
            $testResult = [
                'success' => false,
                'step' => 'Driver PHP',
                'message' => "El puerto $port responde, pero este hosting no tiene instalado el driver de SQL Server (sqlsrv / pdo_dblib)."
            ];
        } else {
            try {
                $conn = null;
                if ($hasPdoSqlsrv) {
                    $conn = new PDO("sqlsrv:server=$host,$port;Database=$db;Encrypt=no;TrustServerCertificate=yes", $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5
                    ]);
                } elseif ($hasPdoDblib) {
                    $conn = new PDO("dblib:host=$host:$port;dbname=$db", $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5
                    ]);
                } elseif ($hasSqlsrv) {
                    $connInfo = ["Database" => $db, "UID" => $user, "PWD" => $pass];
                    $rConn = sqlsrv_connect("$host,$port", $connInfo);
                    if (!$rConn) {
                        throw new Exception(print_r(sqlsrv_errors(), true));
                    }
                } elseif ($hasPdoOdbc || $hasOdbc) {
                    $odbcDrivers = [
                        "ODBC Driver 18 for SQL Server",
                        "ODBC Driver 17 for SQL Server",
                        "SQL Server Native Client 11.0",
                        "FreeTDS",
                        "SQL Server"
                    ];
                    $connectedOdbc = false;
                    $lastOdbcErr = '';
                    foreach ($odbcDrivers as $drv) {
                        try {
                            $dsn = "odbc:Driver={$drv};Server=$host,$port;Database=$db;TrustServerCertificate=yes;Encrypt=no;";
                            $conn = new PDO($dsn, $user, $pass, [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_TIMEOUT => 5
                            ]);
                            $connectedOdbc = true;
                            break;
                        } catch (Exception $e) {
                            $lastOdbcErr = $e->getMessage();
                        }
                    }
                    if (!$connectedOdbc && !$conn) {
                        throw new Exception("Error al conectar mediante ODBC: " . $lastOdbcErr);
                    }
                }

                $testResult = [
                    'success' => true,
                    'step' => 'Conexión Exitosa',
                    'message' => "¡ENHORABUENA! Conexión exitosa a la base de datos '$db' de Starsoft en Azure."
                ];
            } catch (Exception $ex) {
                $testResult = [
                    'success' => false,
                    'step' => 'Autenticación / SQL',
                    'message' => "El puerto respondió pero SQL Server rechazó la conexión: " . $ex->getMessage()
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico SQL Server / Starsoft - BS Perú</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1B4079;
            --accent: #4D7C8A;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --bg: #F4F7FA;
            --card-bg: #FFFFFF;
            --text-dark: #1E293B;
            --text-muted: #64748B;
            --border: #E2E8F0;
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; }
        body { background: var(--bg); color: var(--text-dark); padding: 30px 20px; }
        .container { max-width: 820px; margin: 0 auto; }
        .card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }
        .header-title {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 18px;
        }
        .header-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #FFF;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .header-title h1 { font-family: 'Outfit', sans-serif; font-size: 1.5rem; color: var(--primary); }
        .header-title p { font-size: 0.85rem; color: var(--text-muted); }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-success { background: #D1FAE5; color: #065F46; }
        .badge-danger { background: #FEE2E2; color: #991B1B; }
        .badge-warning { background: #FEF3C7; color: #92400E; }

        .grid-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        .info-box {
            background: #F8FAFC;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
        }
        .info-box span { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }
        .info-box h4 { font-size: 1.1rem; color: var(--text-dark); margin-top: 4px; word-break: break-all; }

        .driver-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 16px 0;
        }
        .driver-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 18px;
            background: #F8FAFC;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 0.88rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-top: 14px;
        }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: span 2; }
        .form-group label { font-size: 0.82rem; font-weight: 600; color: var(--text-muted); }
        .form-group input {
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid var(--border);
            outline: none;
            font-size: 0.88rem;
        }
        .form-group input:focus { border-color: var(--accent); }

        .btn-test {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #FFF;
            padding: 12px 24px;
            border-radius: 30px;
            border: none;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            margin-top: 18px;
            transition: all 0.2s ease;
        }
        .btn-test:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(27, 64, 121, 0.25); }

        .alert-box {
            padding: 16px;
            border-radius: 14px;
            margin-top: 18px;
            font-size: 0.88rem;
            line-height: 1.5;
        }
        .alert-box.success { background: #ECFDF5; border: 1.5px solid #6EE7B7; color: #065F46; }
        .alert-box.danger { background: #FEF2F2; border: 1.5px solid #FCA5A5; color: #991B1B; }
        
        .copy-box {
            background: #1E293B;
            color: #E2E8F0;
            padding: 14px 18px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 0.82rem;
            margin-top: 10px;
            user-select: all;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- CARD 1: ESTADO DEL SERVIDOR -->
        <div class="card">
            <div class="header-title">
                <div class="header-icon"><i class="fa-solid fa-server"></i></div>
                <div>
                    <h1>Diagnóstico de Hosting para Starsoft (Azure)</h1>
                    <p>Verificación de compatibilidad PHP, drivers SQL Server y conectividad de red</p>
                </div>
            </div>

            <div class="grid-info">
                <div class="info-box">
                    <span>Versión de PHP</span>
                    <h4>PHP <?php echo htmlspecialchars($phpVersion); ?></h4>
                </div>
                <div class="info-box">
                    <span>IP Pública del Hosting (Para Azure NSG)</span>
                    <h4 style="color:var(--primary);"><?php echo htmlspecialchars($serverPublicIp); ?></h4>
                </div>
                <div class="info-box">
                    <span>Compatibilidad SQL Server</span>
                    <h4 style="margin-top:6px;">
                        <?php if ($hasAnyDriver): ?>
                            <span class="status-badge badge-success"><i class="fa-solid fa-circle-check"></i> LISTO</span>
                        <?php else: ?>
                            <span class="status-badge badge-danger"><i class="fa-solid fa-circle-xmark"></i> REQUIERE DRIVER</span>
                        <?php endif; ?>
                    </h4>
                </div>
            </div>

            <h3 style="font-family:'Outfit',sans-serif; font-size:1.1rem; margin-top:20px;">Drivers SQL Server detectados en este PHP:</h3>
            <div class="driver-list">
                <div class="driver-item">
                    <span><strong>pdo_sqlsrv</strong> (Driver Oficial Microsoft PDO - Recomendado)</span>
                    <?php if ($hasPdoSqlsrv): ?>
                        <span class="status-badge badge-success"><i class="fa-solid fa-check"></i> Instalado</span>
                    <?php else: ?>
                        <span class="status-badge badge-danger"><i class="fa-solid fa-xmark"></i> No instalado</span>
                    <?php endif; ?>
                </div>

                <div class="driver-item">
                    <span><strong>sqlsrv</strong> (Driver Oficial Microsoft Nativo)</span>
                    <?php if ($hasSqlsrv): ?>
                        <span class="status-badge badge-success"><i class="fa-solid fa-check"></i> Instalado</span>
                    <?php else: ?>
                        <span class="status-badge badge-danger"><i class="fa-solid fa-xmark"></i> No instalado</span>
                    <?php endif; ?>
                </div>

                <div class="driver-item">
                    <span><strong>pdo_dblib</strong> (Driver FreeTDS Linux para SQL Server)</span>
                    <?php if ($hasPdoDblib): ?>
                        <span class="status-badge badge-success"><i class="fa-solid fa-check"></i> Instalado</span>
                    <?php else: ?>
                        <span class="status-badge badge-danger"><i class="fa-solid fa-xmark"></i> No instalado</span>
                    <?php endif; ?>
                </div>

                <div class="driver-item">
                    <span><strong>odbc / pdo_odbc</strong> (Driver ODBC para SQL Server)</span>
                    <?php if ($hasOdbc || $hasPdoOdbc): ?>
                        <span class="status-badge badge-success"><i class="fa-solid fa-check"></i> Instalado</span>
                    <?php else: ?>
                        <span class="status-badge badge-danger"><i class="fa-solid fa-xmark"></i> No instalado</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$hasAnyDriver): ?>
                <div class="alert-box danger">
                    <strong>⚠️ Atención: Tu versión actual de PHP no tiene ningún driver para SQL Server activo.</strong><br>
                    Al tener <em>Administrador MultiPHP</em> en tu cPanel, puedes solicitarle a soporte técnico de tu hosting con este texto de 1 minuto:
                    <div class="copy-box">
                        Estimado soporte técnico, por favor habilitar la extensión de SQL Server (ea-php-sqlsrv y ea-php-pdo_sqlsrv o pdo_dblib) para mi dominio en cPanel para conectar con una base de datos remota.
                    </div>
                </div>
            <?php else: ?>
                <div class="alert-box success">
                    <strong>✅ ¡Excelente! Tu hosting ya tiene activo el driver necesario para conectar con SQL Server.</strong><br>
                    Cuando tengas las credenciales de Azure, la conexión funcionará de manera nativa.
                </div>
            <?php endif; ?>
        </div>

        <!-- CARD 2: PROBADOR EN VIVO DE CREDENCIALES (PARA MAÑANA) -->
        <div class="card">
            <h2 style="font-family:'Outfit',sans-serif; font-size:1.3rem; color:var(--primary); margin-bottom:6px;">
                <i class="fa-solid fa-plug-circle-check"></i> Probador de Conexión a Starsoft (Azure)
            </h2>
            <p style="font-size:0.85rem; color:var(--text-muted);">
                Puedes usar este probador apenas tengas el usuario y la clave de SQL Server mañana:
            </p>

            <form method="POST" action="">
                <input type="hidden" name="probar_conexion" value="1">
                <div class="form-grid">
                    <div class="form-group">
                        <label>IP o Host de Azure (El mismo de Escritorio Remoto):</label>
                        <input type="text" name="host" placeholder="Ej: 48.216.211.109" value="<?php echo htmlspecialchars($_POST['host'] ?? '48.216.211.109'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Puerto SQL Server:</label>
                        <input type="text" name="port" value="<?php echo htmlspecialchars($_POST['port'] ?? '1433'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Nombre de la Base de Datos Starsoft:</label>
                        <input type="text" name="database" placeholder="Ej: BD_STARSOFT_BS" value="<?php echo htmlspecialchars($_POST['database'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Usuario SQL Server:</label>
                        <input type="text" name="user" placeholder="Ej: usr_crm_bsperu" value="<?php echo htmlspecialchars($_POST['user'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group full">
                        <label>Contraseña SQL Server:</label>
                        <input type="password" name="password" placeholder="Tu contraseña..." required>
                    </div>
                </div>

                <button type="submit" class="btn-test">
                    <i class="fa-solid fa-bolt"></i> Probar Conexión en Vivo
                </button>
            </form>

            <?php if ($testResult): ?>
                <div class="alert-box <?php echo $testResult['success'] ? 'success' : 'danger'; ?>">
                    <strong>[Paso: <?php echo htmlspecialchars($testResult['step']); ?>]</strong><br>
                    <?php echo htmlspecialchars($testResult['message']); ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>

<?php
// crm/api_pedidos.php - Integración Starsoft ERP & Gestión de Cotizaciones / Pedidos
session_start();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
require_once __DIR__ . '/config/database.php';

// ==========================================
// ACCIÓN: DIAGNÓSTICO DE CONEXIÓN (PÚBLICO PARA TESTING)
// ==========================================
if ($action === 'test_db') {
    header('Content-Type: application/json; charset=utf-8');
    
    $host = '48.216.211.109';
    $dbName = 'BDTPED_SSA';
    $user = 'SOPORTE';
    $pass = 'SOPORTE';

    // 1. Detección rápida de IP pública del hosting cPanel (para Azure NSG)
    $hostingIp = 'Desconocida';
    try {
        $ctx = stream_context_create(['http' => ['timeout' => 1.5]]);
        $ipFetch = @file_get_contents('https://api.ipify.org', false, $ctx);
        if ($ipFetch) $hostingIp = trim($ipFetch);
        else $hostingIp = $_SERVER['SERVER_ADDR'] ?? 'Desconocida';
    } catch(Exception $e) {
        $hostingIp = $_SERVER['SERVER_ADDR'] ?? 'Desconocida';
    }

    // 2. Pre-chequeo directo a los puertos de Azure (8089, 80, 443, 1433)
    $puertosAzure = [8089, 80, 443, 1433, 86];
    $azureStatus = [];
    foreach ($puertosAzure as $pz) {
        $s = @fsockopen($host, $pz, $errnoZ, $errstrZ, 2.0);
        if ($s) {
            $azureStatus[$pz] = "ABIERTO_CONECTADO";
            fclose($s);
        } else {
            $azureStatus[$pz] = "$errstrZ ($errnoZ)";
        }
    }
    $socketErrors['azure_ports'] = $azureStatus;
    $port443_open = ($azureStatus[443] ?? '') === 'ABIERTO_CONECTADO';
    $port80_open  = ($azureStatus[80] ?? '') === 'ABIERTO_CONECTADO';
    $port1433_open = ($azureStatus[1433] ?? '') === 'ABIERTO_CONECTADO';
    $port8089_open = ($azureStatus[8089] ?? '') === 'ABIERTO_CONECTADO';

    // Probar con cURL hacia 443 para ver código de error exacto
    $ch443 = curl_init("http://$host:443/");
    curl_setopt($ch443, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch443, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch443, CURLOPT_CONNECTTIMEOUT, 3);
    curl_exec($ch443);
    $curl443Error = curl_error($ch443);
    $curl443Info = curl_getinfo($ch443);
    curl_close($ch443);

    // Fin pre-chequeo de sockets

    $port3389_open = false;
    $s3389 = @fsockopen($host, 3389, $errno3389, $errstr3389, 2.0);
    if ($s3389) {
        $port3389_open = true;
        fclose($s3389);
    } else {
        $socketErrors['port_3389'] = "$errstr3389 ($errno3389)";
    }

    // Proba de fuego: ¿Qué puertos permite salir cPanel?
    $testPorts = [80, 443, 8080, 8443, 8888, 2083, 2087, 1433, 3306];
    $salidaPuertos = [];
    foreach ($testPorts as $p) {
        $s = @fsockopen('1.1.1.1', $p, $pErrNo, $pErrStr, 0.8);
        if ($s) {
            $salidaPuertos[$p] = "ABIERTO";
            fclose($s);
        } else {
            // Si el error es 111 (Connection Refused), el firewall local de cPanel lo RECHAZÓ
            // Si el error es 110 (Timeout), el paquete SALIÓ a Internet y llegó al destino (que lo ignoró)
            if ($pErrNo === 111) {
                $salidaPuertos[$p] = "BLOQUEADO_POR_CPANEL (Refused 111)";
            } else if ($pErrNo === 110) {
                $salidaPuertos[$p] = "PERMITIDO_SALIENTE (Timeout 110 en destino)";
            } else {
                $salidaPuertos[$p] = "$pErrStr ($pErrNo)";
            }
        }
    }

    $diagnostico_red_hosting = [
        "cpanel_salida_puertos" => $salidaPuertos
    ];

    // 3. Forzar candidatos DSN en puerto 443 directamente hacia StarSoft
    $dsnCandidates = [
        "odbc:Driver=FreeTDS;Server=$host;Port=443;Database=$dbName;TDS_Version=7.4;ClientCharset=UTF-8;",
        "odbc:Driver=FreeTDS;Server=$host,443;Database=$dbName;",
        "dblib:host=$host:443;dbname=$dbName;charset=UTF-8",
        "sqlsrv:Server=$host,443;Database=$dbName;TrustServerCertificate=true;Encrypt=false"
    ];

    $db = null;
    $intentos = [];
    foreach ($dsnCandidates as $dsn) {
        try {
            $conn = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $db = $conn;
            $intentos[] = ["dsn" => $dsn, "status" => "CONECTADO"];
            break;
        } catch (Exception $e) {
            $intentos[] = ["dsn" => $dsn, "error" => $e->getMessage()];
        }
    }

    if ($db) {
        try {
            $stmt = $db->query("EXEC RPT_Vta_BuscaNroCot");
            $res = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            $siguienteNro = $res['CCNUMDOC'] ?? 'Desconocido';
            echo json_encode([
                "success" => true,
                "conexion" => "OK",
                "motor" => "SQL Server Azure (BDTPED_SSA)",
                "siguiente_coti_starsoft" => $siguienteNro,
                "ip_hosting" => $hostingIp,
                "puertos" => ["443" => $port443_open, "80" => $port80_open, "1433" => $port1433_open],
                "detalles" => $intentos
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "conexion" => "CONECTADO_PERO_FALLA_QUERY",
                "ip_hosting" => $hostingIp,
                "error" => $e->getMessage(),
                "detalles" => $intentos
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode([
            "success" => false,
            "conexion" => "PUERTOS_NO_ACCESIBLES",
            "ip_hosting" => $hostingIp,
            "puerto_443_abierto" => $port443_open,
            "puerto_80_abierto" => $port80_open,
            "puerto_1433_abierto" => $port1433_open,
            "puerto_50027_abierto" => $port50027_open,
            "errores_socket" => $socketErrors,
            "curl_443_error" => $curl443Error,
            "diagnostico_red_cpanel" => $diagnostico_red_hosting,
            "drivers_pdo_php" => PDO::getAvailableDrivers(),
            "mensaje" => (!$port80_open && !$port1433_open)
                ? "El servidor de cPanel no puede alcanzar la IP 48.216.211.109."
                : "No se pudo autenticar con las credenciales SOPORTE/SOPORTE.",
            "intentos" => $intentos
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// Para las demás acciones (save, list, delete, aprobar) se requiere autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado. Inicie sesión en el CRM."]);
    exit;
}

$dataFile = __DIR__ . '/../assets/Data/pedidos.json';

// Obtener conexión a Starsoft SQL Server
$db = function_exists('getStarsoftDB') ? getStarsoftDB() : null;

// ==========================================
// ACCIÓN: LISTAR COTIZACIONES
// ==========================================
if ($action === 'list') {
    header('Content-Type: application/json');
    if (file_exists($dataFile)) {
        echo file_get_contents($dataFile);
    } else {
        echo json_encode([]);
    }
    exit;
}

// ==========================================
// ACCIÓN: GUARDAR COTIZACIÓN (STARSOFT SP)
// ==========================================
if ($action === 'save') {
    header('Content-Type: application/json');

    // 1. Cabecera Izquierda
    $fecha_doc = trim($_POST['fecha_doc'] ?? date("Y-m-d"));
    $sucursal = trim($_POST['sucursal'] ?? '01');
    $forma_pago = trim($_POST['forma_pago'] ?? '01');
    $gestor_campo = trim($_POST['gestor_campo'] ?? 'OFICINA');
    $gestor_tienda = trim($_POST['gestor_tienda'] ?? '');
    $cliente = trim($_POST['cliente'] ?? ''); // Razón Social
    $documento = trim($_POST['documento'] ?? ''); // RUC / DNI
    $descuento = floatval($_POST['descuento'] ?? 0);
    $direccion = trim($_POST['direccion'] ?? '');
    $lugar_entrega = trim($_POST['lugar_entrega'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');

    // 2. Cabecera Centro
    $lugar_compra = trim($_POST['lugar_compra'] ?? '');
    $tiempo_entrega = trim($_POST['tiempo_entrega'] ?? 'INMEDIATO');
    $tipo_despacho = trim($_POST['tipo_despacho'] ?? 'ENTREGA');
    $glosa = trim($_POST['glosa'] ?? '');
    $comentario = trim($_POST['comentario'] ?? '');

    // 3. CRM
    $fecha_uso = trim($_POST['fecha_uso'] ?? date("Y-m-d"));
    $fecha_compra = trim($_POST['fecha_compra'] ?? date("Y-m-d"));
    $fecha_llamada = trim($_POST['fecha_llamada'] ?? date("Y-m-d"));
    $otro_proveedor = trim($_POST['otro_proveedor'] ?? '');

    // 4. Contacto
    $telefono = trim($_POST['telefono'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $tipo_control = trim($_POST['tipo_control'] ?? 'NORMAL');

    // 5. Productos y Totales
    $productos_raw = $_POST['productos'] ?? '[]';
    $productos = is_array($productos_raw) ? $productos_raw : (json_decode($productos_raw, true) ?: []);
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $igv = floatval($_POST['igv'] ?? 0);
    $total = floatval($_POST['total'] ?? 0);

    if (empty($cliente) || empty($productos)) {
        echo json_encode(["success" => false, "error" => "El cliente y al menos un producto son obligatorios"]);
        exit;
    }

    $currentUser = $_SESSION['admin_user'] ?? 'SOPORTE';
    $nroCotizacion = '';
    $starsoftOk = false;
    $starsoftError = '';

    // Intentar registrar en SQL Server (Starsoft Stored Procedures)
    if ($db) {
        try {
            // A. Cabecera ADD_CotCab
            $f_fecdoc    = addslashes($fecha_doc);
            $f_valofer   = '15 DIAS';
            $f_vende     = addslashes($gestor_campo ?: '01');
            $f_punven    = addslashes($sucursal ?: '01');
            $f_codcli    = addslashes($documento);
            $f_direcc    = addslashes($direccion);
            $f_ruc       = addslashes($documento);
            $f_pordescl  = $descuento;
            $f_importe   = $total;
            $f_forven    = addslashes($forma_pago ?: '01');
            $f_tipcam    = 3.75;
            $f_codmon    = 'MN';
            $f_rftd      = '';
            $f_rfnumdoc  = '';
            $f_user      = addslashes($currentUser);
            $f_comenta   = addslashes($comentario);
            $f_estado    = '0'; // 0 = Creado
            $f_glosa     = addslashes($glosa);
            $f_desval    = 0.00;
            $f_igv       = $igv;
            $f_forimp    = '1';
            $f_lugent    = addslashes($lugar_entrega);
            $f_tiempent  = addslashes($tiempo_entrega);
            $f_telefono  = addslashes($telefono);
            $f_contacto  = addslashes($contacto);
            $f_tipo      = 'NORMAL';
            $f_fechauso  = addslashes($fecha_uso);
            $f_fechacomp = addslashes($fecha_compra);
            $f_fechallam = addslashes($fecha_llamada);
            $f_otroprov  = addslashes($otro_proveedor);
            $f_tipocontrol = addslashes($tipo_control);
            $f_vencam    = addslashes($gestor_campo ?: '01');
            $f_desglo    = 0.00;
            $f_tipdes    = addslashes($tipo_despacho);
            $f_tipcom    = '';
            $f_lugcom    = addslashes($lugar_compra);

            $sqlCab = "SET NOCOUNT ON; EXEC ADD_CotCab '$f_fecdoc', '$f_valofer', '$f_vende', '$f_punven', '$f_codcli', '$f_direcc', '$f_ruc', $f_pordescl, $f_importe, '$f_forven', $f_tipcam, '$f_codmon', '$f_rftd', '$f_rfnumdoc', '$f_user', '$f_comenta', '$f_estado', '$f_glosa', $f_desval, $f_igv, '$f_forimp', '$f_lugent', '$f_tiempent', '$f_telefono', '$f_contacto', '$f_tipo', '$f_fechauso', '$f_fechacomp', '$f_fechallam', '$f_otroprov', '$f_tipocontrol', '$f_vencam', $f_desglo, '$f_tipdes', '$f_tipcom', '$f_lugcom'";
            $db->query($sqlCab);

            // B. Obtener correlativo oficial generado
            $stmtNro = $db->query("EXEC RPT_Vta_BuscaNroCot");
            if ($stmtNro) {
                $rowNro = $stmtNro->fetch(PDO::FETCH_ASSOC);
                $nroCotizacion = $rowNro['CCNUMDOC'] ?? '';
                $stmtNro->closeCursor();
            }

            // C. Insertar detalles de productos con ADD_CotDet
            foreach ($productos as $p) {
                $cd_codigo = addslashes($p['codigo'] ?? ($p['producto_id'] ?? ($p['id'] ?? '')));
                $cd_cant   = floatval($p['cantidad'] ?? 1);
                $cd_prec_v = floatval($p['precio'] ?? 0);
                $cd_prec_o = floatval($p['precio_ori'] ?? $cd_prec_v);
                $cd_pordes = floatval($p['descuento'] ?? 0);
                $cd_descto = $cd_cant * $cd_prec_o * ($cd_pordes / 100);
                $cd_impmn  = $cd_cant * $cd_prec_v;
                $cd_igv    = $cd_impmn - ($cd_impmn / 1.18);
                $cd_alma   = addslashes($p['almacen'] ?? '01');
                $cd_lista  = addslashes($p['lista_precio'] ?? '01');

                if ($cd_cant > 0 && !empty($cd_codigo)) {
                    $sqlDet = "SET NOCOUNT ON; EXEC ADD_CotDet '$cd_codigo', $cd_cant, $cd_prec_v, $cd_prec_o, $cd_descto, $cd_pordes, $cd_igv, $f_tipcam, $cd_impmn, '$cd_alma', $cd_prec_o, '$cd_lista', '$f_user'";
                    $db->query($sqlDet);
                }
            }

            // D. Actualizar promociones
            if (!empty($nroCotizacion)) {
                foreach ($productos as $p) {
                    $cd_codigo = addslashes($p['codigo'] ?? ($p['producto_id'] ?? ($p['id'] ?? '')));
                    if (!empty($cd_codigo)) {
                        $db->query("SET NOCOUNT ON; EXEC RPT_Vta_UpdateAutorizaPromo '$nroCotizacion', '$cd_codigo'");
                    }
                }
            }

            $starsoftOk = true;
        } catch (Exception $e) {
            $starsoftError = $e->getMessage();
        }
    }

    // Guardar respaldo local en JSON
    $pedidos = [];
    if (file_exists($dataFile)) {
        $pedidos = json_decode(file_get_contents($dataFile), true) ?? [];
    }

    // Si Starsoft generó el correlativo, usarlo; de lo contrario generar uno local
    if (!empty($nroCotizacion)) {
        $nuevo_id = $nroCotizacion;
    } else {
        $last_id = 51700;
        if (count($pedidos) > 0) {
            $last_id = intval($pedidos[0]['id']) + 1;
        }
        $nuevo_id = str_pad($last_id, 7, "0", STR_PAD_LEFT);
    }

    $nuevoPedido = [
        "id" => $nuevo_id,
        "nro_starsoft" => $nroCotizacion,
        "fecha_doc" => $fecha_doc,
        "fecha_sys" => date("Y-m-d H:i:s"),
        "sucursal" => $sucursal,
        "forma_pago" => $forma_pago,
        "gestor_campo" => $gestor_campo,
        "gestor_tienda" => $gestor_tienda,
        
        "cliente" => $cliente,
        "documento" => $documento,
        "descuento" => $descuento,
        "direccion" => $direccion,
        "lugar_entrega" => $lugar_entrega,
        "departamento" => $departamento,
        
        "lugar_compra" => $lugar_compra,
        "tiempo_entrega" => $tiempo_entrega,
        "tipo_despacho" => $tipo_despacho,
        "glosa" => $glosa,
        "comentario" => $comentario,
        
        "fecha_uso" => $fecha_uso,
        "fecha_compra" => $fecha_compra,
        "fecha_llamada" => $fecha_llamada,
        "otro_proveedor" => $otro_proveedor,
        
        "telefono" => $telefono,
        "contacto" => $contacto,
        "tipo_control" => $tipo_control,

        "productos" => $productos,
        "subtotal" => $subtotal,
        "igv" => $igv,
        "total" => $total,
        
        "estado" => "CREADO",
        "detalle" => $starsoftOk ? "SINCRONIZADO_STARSOFT" : ($starsoftError ? "LOCAL_ERROR_SQL" : "LOCAL"),
        "doc_sts" => $nroCotizacion ?: "null",
        "error_sql" => $starsoftError
    ];

    array_unshift($pedidos, $nuevoPedido);
    @file_put_contents($dataFile, json_encode($pedidos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    echo json_encode([
        "success" => true,
        "id" => $nuevoPedido['id'],
        "nro_starsoft" => $nroCotizacion,
        "starsoft_sync" => $starsoftOk,
        "error_sql" => $starsoftError
    ]);
    exit;
}

// ==========================================
// ACCIÓN: APROBAR / AUTORIZAR (ENVIAR A STARSOFT)
// ==========================================
if ($action === 'aprobar' || $action === 'autorizar') {
    header('Content-Type: application/json');
    $id = trim($_POST['id'] ?? '');
    $user = $_SESSION['admin_user'] ?? 'ADMIN';

    if (empty($id)) {
        echo json_encode(["success" => false, "error" => "ID de cotización no proporcionado"]);
        exit;
    }

    $spOk = false;
    $spMsg = '';

    if ($db) {
        try {
            // Ejecutar Stored Procedure de Aprobación
            $db->query("SET NOCOUNT ON; EXEC UPD_VTA_AprobarCotiza '$id', '$user'");
            $spOk = true;
        } catch (Exception $e) {
            $spMsg = $e->getMessage();
        }
    }

    // Actualizar estado en el archivo JSON
    $pedidos = [];
    if (file_exists($dataFile)) {
        $pedidos = json_decode(file_get_contents($dataFile), true) ?? [];
    }
    foreach ($pedidos as &$p) {
        if ($p['id'] === $id) {
            $p['estado'] = "AUTORIZADO";
            $p['detalle'] = "STARSOFT";
            break;
        }
    }
    @file_put_contents($dataFile, json_encode(array_values($pedidos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    echo json_encode([
        "success" => true,
        "mensaje" => "Cotización $id autorizada exitosamente",
        "starsoft_updated" => $spOk,
        "error_sql" => $spMsg
    ]);
    exit;
}

// ==========================================
// ACCIÓN: ELIMINAR COTIZACIÓN
// ==========================================
if ($action === 'delete') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? '';
    if (!$id) {
        echo json_encode(["success" => false]);
        exit;
    }

    if ($db) {
        try {
            $db->query("SET NOCOUNT ON; EXEC UPD_VTA_EliminaCoti '$id'");
        } catch (Exception $e) {}
    }

    $pedidos = json_decode(file_get_contents($dataFile), true) ?? [];
    $pedidos = array_filter($pedidos, function($p) use ($id) {
        return $p['id'] !== $id;
    });

    file_put_contents($dataFile, json_encode(array_values($pedidos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    echo json_encode(["success" => true]);
    exit;
}
?>

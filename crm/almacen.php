<?php
// crm/almacen.php - Sistema de Almacén y Control de Stock (Giovana)
session_start();

$fileStockPath = __DIR__ . '/crm_data/almacen_stock.json';
$fileMovPath   = __DIR__ . '/crm_data/almacen_movimientos.json';

// Cargar o inicializar Stock
function getStockData() {
    global $fileStockPath;
    if (file_exists($fileStockPath)) {
        return json_decode(file_get_contents($fileStockPath), true) ?: [];
    }
    return [];
}

function saveStockData($data) {
    global $fileStockPath;
    file_put_contents($fileStockPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// Cargar o inicializar Movimientos
function getMovimientosData() {
    global $fileMovPath;
    if (file_exists($fileMovPath)) {
        return json_decode(file_get_contents($fileMovPath), true) ?: [];
    }
    return [];
}

function saveMovimientosData($data) {
    global $fileMovPath;
    file_put_contents($fileMovPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function getSiguienteGS() {
    $movs = getMovimientosData();
    $maxNum = 18824;
    foreach ($movs as $m) {
        if (($m['tipo'] ?? '') === 'GS' && !empty($m['numero'])) {
            $parts = explode('-', $m['numero']);
            if (isset($parts[1])) {
                $n = intval(trim($parts[1]));
                if ($n >= $maxNum) $maxNum = $n + 1;
            }
        }
    }
    return 'T001 - ' . str_pad($maxNum, 7, '0', STR_PAD_LEFT);
}

// Nombres y direcciones oficiales de las sedes
$sucursalesInfo = [
    'PRINCIPAL' => [
        'nombre' => 'Sede Principal (Chorrillos - Lima)',
        'dir' => 'AV. LOS FAISANES 675 URB. LA CAMPIÑA',
        'dir_completa' => 'Av. Los Faisanes Nº 675 Urb. La Campiña, Chorrillos - Lima - Lima'
    ],
    'PIURA' => [
        'nombre' => 'Sucursal Piura',
        'dir' => 'MZ. D LOTE 17 ZONA INDUSTRIAL',
        'dir_completa' => 'Mz. D Lote 17 Zona Industrial - Piura'
    ],
    'AREQUIPA' => [
        'nombre' => 'Sucursal Arequipa',
        'dir' => 'PARQUE INDUSTRIAL RIO SECO',
        'dir_completa' => 'Parque Industrial Rio Seco - Arequipa'
    ],
    'SURQUILLO' => [
        'nombre' => 'Sucursal Surquillo',
        'dir' => 'AV. TOMAS MARSANO 1234',
        'dir_completa' => 'Av. Tomás Marsano 1234 - Surquillo - Lima'
    ],
    'SAN_BORJA' => [
        'nombre' => 'Sucursal San Borja',
        'dir' => 'AV. JAVIER PRADO ESTE 2450',
        'dir_completa' => 'Av. Javier Prado Este 2450 - San Borja - Lima'
    ]
];

// === API BACKEND HANDLER (AJAX) ===
if (isset($_REQUEST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_REQUEST['action'];
    $stockAll = getStockData();
    $movsAll  = getMovimientosData();

    // 1. Obtener Stock por Sucursal
    if ($action === 'get_stock') {
        $suc = $_GET['sucursal'] ?? 'PRINCIPAL';
        $stockSuc = $stockAll[$suc] ?? [];
        echo json_encode([
            'success' => true,
            'sucursal' => $suc,
            'items' => array_values($stockSuc),
            'total_items' => count($stockSuc),
            'sucursal_info' => $sucursalesInfo[$suc] ?? []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Transferir Stock entre Sucursales
    if ($action === 'transferir_stock') {
        $origen = trim($_POST['origen'] ?? '');
        $destino = trim($_POST['destino'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $cantidad = floatval($_POST['cantidad'] ?? 0);
        $generarGuia = !empty($_POST['generar_guia']);
        $responsable = $_SESSION['crm_user'] ?? 'Giovana';

        if (empty($origen) || empty($destino) || $origen === $destino) {
            echo json_encode(['success' => false, 'error' => 'Debes seleccionar una sucursal de origen y una de destino distintas.']);
            exit;
        }

        if (empty($sku) || $cantidad <= 0) {
            echo json_encode(['success' => false, 'error' => 'Producto no válido o cantidad menor o igual a cero.']);
            exit;
        }

        if (!isset($stockAll[$origen][$sku])) {
            echo json_encode(['success' => false, 'error' => 'El producto no existe en el catálogo de la sucursal de origen.']);
            exit;
        }

        $itemOrig = &$stockAll[$origen][$sku];
        if ($itemOrig['stock'] < $cantidad) {
            echo json_encode(['success' => false, 'error' => "Stock insuficiente en origen. Stock disponible: {$itemOrig['stock']}."]);
            exit;
        }

        // Descontar en origen
        $itemOrig['stock'] -= $cantidad;

        // Sumar en destino (si no existe, copiar estructura de origen)
        if (!isset($stockAll[$destino][$sku])) {
            $stockAll[$destino][$sku] = $itemOrig;
            $stockAll[$destino][$sku]['stock'] = 0;
        }
        $stockAll[$destino][$sku]['stock'] += $cantidad;

        // Guardar stock
        saveStockData($stockAll);

        // Generar correlativo de Guía GS
        $nroGuia = getSiguienteGS();
        $guiaLimpia = str_replace(' ', '', $nroGuia);

        $nomOrig = $sucursalesInfo[$origen]['nombre'] ?? $origen;
        $nomDest = $sucursalesInfo[$destino]['nombre'] ?? $destino;
        $dirDest = $sucursalesInfo[$destino]['dir'] ?? 'DIRECCION DE SUCURSAL';

        // Registrar movimiento
        $nuevoMov = [
            'numero' => $guiaLimpia,
            'tipo' => 'GS',
            'fecha' => date('d/m/Y'),
            'hora' => date('H:i'),
            'origen' => $origen,
            'origen_nombre' => $nomOrig,
            'destino' => $destino,
            'destino_nombre' => $nomDest,
            'motivo' => 'Traslado entre establecimientos de la misma empresa',
            'cliente' => 'BUILDING SYSTEMS PERU S.A.C.',
            'ruc' => '20609793806',
            'vendedor' => '99 VENTAS OFICINA',
            'glosa' => "TRASLADO DE STOCK $nomOrig A $nomDest // GIOVANA",
            'conductor' => trim($_POST['conductor'] ?? 'MIGUEL HUMBERTO CONDEÑA AVALOS'),
            'conductor_dni' => trim($_POST['conductor_dni'] ?? '46830741'),
            'conductor_lic' => trim($_POST['conductor_lic'] ?? 'Q46830741'),
            'vehiculo' => trim($_POST['vehiculo'] ?? 'CANTER'),
            'placa' => trim($_POST['placa'] ?? 'BYF906'),
            'items' => [
                [
                    'sku' => $sku,
                    'nombre' => $itemOrig['nombre'],
                    'lote' => $itemOrig['lote'] ?? '200426',
                    'cant' => $cantidad,
                    'um' => $itemOrig['um'] ?? 'UND',
                    'peso' => $itemOrig['peso'] ?? 20.0
                ]
            ],
            'peso_total' => round($cantidad * floatval($itemOrig['peso'] ?? 20.0), 2),
            'responsable' => $responsable,
            'estado' => 'EMITIDO'
        ];

        array_unshift($movsAll, $nuevoMov);
        saveMovimientosData($movsAll);

        echo json_encode([
            'success' => true,
            'mensaje' => "Transferencia exitosa de {$cantidad} {$itemOrig['um']} de {$nomOrig} hacia {$nomDest}.",
            'guia_numero' => $nroGuia,
            'movimiento' => $nuevoMov,
            'generar_guia' => $generarGuia
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. Ajustar / Estabilizar Stock
    if ($action === 'ajustar_stock') {
        $suc = trim($_POST['sucursal'] ?? 'PRINCIPAL');
        $sku = trim($_POST['sku'] ?? '');
        $nuevoStock = floatval($_POST['nuevo_stock'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? 'Conteo físico de inventario');
        $responsable = $_SESSION['crm_user'] ?? 'Giovana';

        if (empty($sku) || !isset($stockAll[$suc][$sku])) {
            echo json_encode(['success' => false, 'error' => 'Producto no encontrado en la sucursal seleccionada.']);
            exit;
        }

        $stockAnterior = $stockAll[$suc][$sku]['stock'];
        $diff = $nuevoStock - $stockAnterior;
        $stockAll[$suc][$sku]['stock'] = $nuevoStock;
        saveStockData($stockAll);

        // Registrar en auditoría
        $nomSuc = $sucursalesInfo[$suc]['nombre'] ?? $suc;
        $signo = $diff >= 0 ? "+$diff" : "$diff";
        $nuevoMov = [
            'numero' => 'AJUSTE-' . date('YmdHis'),
            'tipo' => 'AJUSTE',
            'fecha' => date('d/m/Y'),
            'hora' => date('H:i'),
            'origen' => $suc,
            'origen_nombre' => $nomSuc,
            'destino' => $suc,
            'destino_nombre' => $nomSuc,
            'motivo' => "Ajuste de Stock: $motivo ($signo uds)",
            'cliente' => 'INVENTARIO INTERNO BS PERU',
            'ruc' => '20609793806',
            'vendedor' => 'GIOVANA ALMACEN',
            'glosa' => "ESTABILIZACIÓN DE STOCK: {$stockAll[$suc][$sku]['nombre']}. Anterior: $stockAnterior, Nuevo: $nuevoStock.",
            'items' => [
                [
                    'sku' => $sku,
                    'nombre' => $stockAll[$suc][$sku]['nombre'],
                    'lote' => $stockAll[$suc][$sku]['lote'] ?? '200426',
                    'cant' => abs($diff),
                    'um' => $stockAll[$suc][$sku]['um'] ?? 'UND',
                    'peso' => $stockAll[$suc][$sku]['peso'] ?? 20.0
                ]
            ],
            'peso_total' => 0,
            'responsable' => $responsable,
            'estado' => 'CONCILIADO'
        ];
        array_unshift($movsAll, $nuevoMov);
        saveMovimientosData($movsAll);

        echo json_encode([
            'success' => true,
            'mensaje' => "Stock estabilizado correctamente. Stock anterior: {$stockAnterior}, Nuevo stock: {$nuevoStock}.",
            'nuevo_stock' => $nuevoStock
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 4. Obtener Movimientos (Histórico Completo con Fallback y StarSoft Sync)
    if ($action === 'get_movimientos') {
        $movs = $movsAll;

        // Si StarSoft está configurado, podemos enriquecer con comprobantes recientes si hay conexión activa
        $configDb = __DIR__ . '/config/database.php';
        if (file_exists($configDb)) {
            require_once $configDb;
            if (function_exists('getDB')) {
                try {
                    $dbConn = getDB();
                    if ($dbConn) {
                        $sqlStarsoft = "SELECT TOP 100 
                            c.TIPODOC_COMPROBANTE as tipo_cod,
                            LTRIM(RTRIM(c.CFNUMSER)) as serie,
                            LTRIM(RTRIM(c.CFNUMDOC)) as numero,
                            CONVERT(varchar, c.CFFECDOC, 103) as fecha_dmy,
                            LTRIM(RTRIM(COALESCE(c.CFNOMBRE, ''))) as razon_social,
                            LTRIM(RTRIM(COALESCE(c.NRO_DOC_RECEPTOR, c.CFCODCLI))) as ruc,
                            LTRIM(RTRIM(COALESCE(c.DIRECCION_RECEPTOR, ''))) as direccion,
                            LTRIM(RTRIM(COALESCE(c.SERIE_GUIA, ''))) as serie_guia,
                            LTRIM(RTRIM(COALESCE(c.NRO_GUIA, ''))) as nro_guia
                        FROM [003BDCOMUN].dbo.COMPROBANTE_CAB c
                        WHERE c.TIPODOC_COMPROBANTE IN ('01', '03', '09')
                        ORDER BY c.CFFECDOC DESC, c.CFNUMDOC DESC";
                        
                        $stmt = $dbConn->query($sqlStarsoft);
                        if ($stmt) {
                            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            $mapNumeros = [];
                            foreach ($movs as $mv) {
                                if (!empty($mv['numero'])) $mapNumeros[str_replace(' ', '', $mv['numero'])] = true;
                            }

                            foreach ($rows as $r) {
                                $fullNum = $r['serie'] . '-' . $r['numero'];
                                if (!isset($mapNumeros[$fullNum])) {
                                    $tipoMap = ['01' => 'FT', '03' => 'BV', '09' => 'GS'];
                                    $tipo = $tipoMap[$r['tipo_cod']] ?? 'FT';
                                    $movs[] = [
                                        'numero' => $fullNum,
                                        'tipo' => $tipo,
                                        'fecha' => $r['fecha_dmy'],
                                        'hora' => '12:00',
                                        'origen' => 'PRINCIPAL',
                                        'origen_nombre' => 'Sede Principal (Chorrillos)',
                                        'destino' => 'CLIENTE',
                                        'destino_nombre' => $r['razon_social'],
                                        'motivo' => $tipo === 'GS' ? 'Traslado' : 'Venta comercial',
                                        'cliente' => $r['razon_social'],
                                        'ruc' => $r['ruc'],
                                        'vendedor' => 'StarSoft ERP',
                                        'glosa' => "COMPROBANTE STARSOFT $fullNum // CLIENTE {$r['razon_social']}",
                                        'items' => [
                                            ['sku' => '110014513', 'nombre' => 'DESPACHO DE PRODUCTOS SEGÚN COMPROBANTE', 'cant' => 1, 'um' => 'UND', 'peso' => 20.0]
                                        ],
                                        'peso_total' => 20.0,
                                        'responsable' => 'StarSoft',
                                        'estado' => 'EMITIDO'
                                    ];
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Fallback transparente a datos del archivo JSON histórico
                }
            }
        }

        echo json_encode([
            'success' => true,
            'movimientos' => $movs,
            'total' => count($movs)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$siguienteGS = getSiguienteGS();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BS Perú - Almacén y Control de Stock</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --outer-bg: #1B4079;
            --app-frame: #161719;
            --main-bg: #F8FAFC;
            --sidebar-bg: #0F172A;
            --accent-tan: #1B4079;
            --accent-blue: #0284C7;
            --text-dark: #1E293B;
            --text-muted: #64748B;
            --border-soft: #E2E8F0;
            --card-radius: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--sidebar-bg);
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            overflow: hidden;
            display: flex;
            color: var(--text-dark);
        }
        .app-window {
            width: 100vw;
            max-width: 100%;
            height: 100vh;
            min-height: 100vh;
            background: var(--sidebar-bg);
            border-radius: 0;
            box-shadow: none;
            display: flex;
            overflow: hidden;
            border: none;
            position: relative;
            padding: 0;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            border-radius: 0;
            display: flex;
            flex-direction: column;
            padding: 26px 18px;
            height: 100vh;
            flex-shrink: 0;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }
        .brand-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 30px; text-decoration: none; }
        .brand-logo-icon { width: 42px; height: 42px; background: var(--accent-tan); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #FFF; font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 700; }
        .brand-logo-text { font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 700; color: #FFF; letter-spacing: -0.5px; }
        .nav-menu { display: flex; flex-direction: column; gap: 6px; }
        .nav-item { display: flex; align-items: center; gap: 14px; padding: 12px 16px; border-radius: 12px; color: #94A3B8; text-decoration: none; font-size: 0.9rem; font-weight: 500; cursor: pointer; transition: 0.2s; }
        .nav-item:hover { color: #FFF; background: rgba(255,255,255,0.06); }
        .nav-item.active { background: var(--accent-tan); color: #FFF; font-weight: 600; box-shadow: 0 4px 12px rgba(27,64,121,0.3); }
        
        /* Contenedor Principal */
        .main-content {
            flex: 1;
            background: var(--main-bg);
            border-radius: 36px 0 0 36px;
            padding: 34px 44px;
            overflow-y: auto;
            height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .header-title h1 { font-family: 'Outfit', sans-serif; font-size: 1.8rem; font-weight: 700; color: var(--text-dark); }
        .header-title p { font-size: 0.85rem; color: var(--text-muted); }
        
        .header-actions { display: flex; align-items: center; gap: 12px; }
        .user-badge { display: flex; align-items: center; gap: 8px; background: #FFF; padding: 8px 14px; border-radius: 12px; border: 1px solid var(--border-soft); font-size: 0.85rem; font-weight: 600; color: var(--text-dark); }
        
        .sucursal-selector { display: flex; align-items: center; gap: 10px; background: #FFF; padding: 8px 16px; border-radius: 12px; border: 1.5px solid var(--accent-tan); box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
        .sucursal-selector select { border: none; background: transparent; font-family: 'Outfit', sans-serif; font-size: 0.95rem; font-weight: 700; color: var(--accent-tan); outline: none; cursor: pointer; }

        /* KPI Cards */
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 25px; }
        .kpi-card { background: #FFF; border-radius: 16px; padding: 18px 22px; border: 1px solid var(--border-soft); display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .kpi-info h4 { font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; }
        .kpi-info .val { font-family: 'Outfit', sans-serif; font-size: 1.7rem; font-weight: 700; color: var(--text-dark); }
        .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .kpi-blue { background: rgba(27,64,121,0.1); color: var(--accent-tan); }
        .kpi-green { background: rgba(56,161,105,0.1); color: #38A169; }
        .kpi-amber { background: rgba(236,201,75,0.2); color: #B7791F; }
        .kpi-purple { background: rgba(128,90,213,0.1); color: #805AD5; }

        /* Botones y Tablas */
        .btn-primary { background: var(--accent-tan); color: #FFF; border: none; padding: 10px 20px; border-radius: 10px; font-family: 'Outfit', sans-serif; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; text-decoration: none; }
        .btn-primary:hover { opacity: 0.92; transform: translateY(-1px); }
        .btn-success { background: #16A34A; color: #FFF; border: none; padding: 10px 20px; border-radius: 10px; font-family: 'Outfit', sans-serif; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; }
        .btn-success:hover { opacity: 0.92; transform: translateY(-1px); }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; transition: 0.15s; }
        .btn-transfer { background: #EEF2FF; color: #4338CA; border: 1px solid #C7D2FE; }
        .btn-transfer:hover { background: #4338CA; color: #FFF; }
        .btn-adjust { background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A; }
        .btn-adjust:hover { background: #B45309; color: #FFF; }

        .view-section { display: none; }
        .view-section.active { display: block; }
        
        .table-container { background: #FFF; border-radius: 16px; border: 1px solid var(--border-soft); overflow: hidden; margin-top: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #F8FAFC; padding: 14px 18px; font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; border-bottom: 1px solid var(--border-soft); }
        td { padding: 14px 18px; font-size: 0.88rem; color: var(--text-dark); border-bottom: 1px solid var(--border-soft); font-weight: 500; vertical-align: middle; }
        tr:hover { background: #F8FAFC; }
        
        .badge { padding: 4px 9px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
        .badge.gs { background: rgba(27,64,121,0.12); color: var(--accent-tan); }
        .badge.ft { background: rgba(22,163,74,0.12); color: #16A34A; }
        .badge.bv { background: rgba(202,138,4,0.15); color: #A16207; }
        .badge.stock-ok { background: #DCFCE7; color: #15803D; font-weight: 700; }
        .badge.stock-low { background: #FEF9C3; color: #A16207; font-weight: 700; }
        .badge.stock-out { background: #FEE2E2; color: #B91C1C; font-weight: 700; }
        .badge.ajuste { background: #FEF3C7; color: #B45309; }

        /* Barra de herramientas */
        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
        .search-box { display: flex; align-items: center; gap: 10px; background: #FFF; border: 1px solid var(--border-soft); border-radius: 10px; padding: 8px 14px; width: 340px; }
        .search-box input { border: none; outline: none; font-size: 0.88rem; width: 100%; }
        .filter-group { display: flex; gap: 6px; }
        .filter-btn { background: #FFF; border: 1px solid var(--border-soft); padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); cursor: pointer; }
        .filter-btn.active { background: var(--accent-tan); color: #FFF; border-color: var(--accent-tan); }

        /* Formularios */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 0.82rem; font-weight: 600; color: var(--text-muted); }
        .form-group input, .form-group select, .form-group textarea { padding: 10px 14px; border: 1px solid var(--border-soft); border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 0.9rem; outline: none; }
        .form-group input:focus, .form-group select:focus { border-color: var(--accent-tan); }

        /* Modales */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
        .modal-card { background: #FFF; border-radius: 20px; width: 100%; max-width: 520px; padding: 25px 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); position: relative; animation: modalIn 0.2s ease-out; }
        @keyframes modalIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { font-family: 'Outfit', sans-serif; font-size: 1.3rem; color: var(--text-dark); }
        .modal-close { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted); }

        /* ============================================================ */
        /* === ESTILOS OFICIALES DE IMPRESIÓN (PDF EXACTO STARSOFT) === */
        /* ============================================================ */
        @media print {
            .app-window, .sidebar, .main-content, .modal-overlay { display: none !important; }
            body { 
                background: white !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                display: block !important; 
            }
            
            #pdfTemplate { 
                display: block !important; 
                position: relative !important; 
                width: 100% !important; 
                max-width: 195mm !important;
                margin: 0 auto !important; 
                padding: 4mm 5mm !important; 
                font-family: Arial, Helvetica, sans-serif !important;
                font-size: 8.5pt !important;
                color: #000 !important;
            }
            
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                box-sizing: border-box !important;
            }

            @page { 
                size: A4 portrait; 
                margin: 0mm; 
            }
        }

        #pdfTemplate {
            display: none;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background: #fff;
            max-width: 195mm;
            margin: 0 auto;
            padding: 4mm 5mm;
        }

        #pdfTemplate * { box-sizing: border-box; }

        .pdf-header-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; width: 100%; }
        .pdf-logo { width: 23%; display: flex; align-items: center; justify-content: flex-start; }
        .pdf-logo img { width: 140px; max-width: 100%; height: auto; }
        .pdf-company-info { width: 44%; text-align: center; white-space: nowrap; display: flex; flex-direction: column; align-items: center; justify-content: center; font-family: Arial, Helvetica, sans-serif; }
        .pdf-comp-title { font-size: 11pt; font-weight: bold; color: #000; margin-bottom: 5px; letter-spacing: 0.2px; }
        .pdf-comp-fiscal { font-size: 8pt; font-weight: bold; line-height: 1.25; margin-bottom: 5px; color: #000; }
        .pdf-comp-branch { font-size: 8.5pt; font-weight: bold; line-height: 1.25; color: #000; }
        .pdf-ruc-box { width: 32%; border: 1.5px solid #000; border-radius: 6px; text-align: center; overflow: hidden; background: #fff; }
        .pdf-ruc-top { font-size: 11.5pt; font-weight: bold; padding: 5px 0; letter-spacing: 0.5px; }
        .pdf-ruc-mid { background-color: #004080 !important; color: #ffffff !important; padding: 5px 2px; line-height: 1.2; }
        .pdf-ruc-mid div:first-child { font-size: 10pt; font-weight: bold; white-space: nowrap; letter-spacing: 0px; }
        .pdf-ruc-mid div:last-child { font-size: 10.5pt; font-weight: bold; letter-spacing: 0.5px; margin-top: 1px; }
        .pdf-ruc-bot { font-size: 11.5pt; font-weight: bold; padding: 5px 0; letter-spacing: 0.5px; }

        .pdf-info-box { border: 1px solid #000; border-radius: 4px; padding: 6px 10px; margin-bottom: 8px; font-size: 8.5pt; line-height: 1.35; }
        .pdf-info-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1px; }
        .pdf-info-left { display: flex; align-items: baseline; flex: 1; }
        .pdf-info-right { display: flex; align-items: baseline; width: 220px; white-space: nowrap; justify-content: flex-start; }
        .pdf-lbl { font-weight: bold; display: inline-block; min-width: 90px; white-space: nowrap; }
        .pdf-val { font-weight: normal; }

        .pdf-locations-box { border: 1px solid #000; border-radius: 4px; display: flex; margin-bottom: 8px; font-size: 8pt; line-height: 1.3; }
        .pdf-loc-half { flex: 1; padding: 5px 8px; }
        .pdf-loc-divider { width: 1px; background-color: #000; }
        .pdf-loc-title { font-weight: bold; margin-bottom: 2px; }

        .pdf-motivo-title { font-weight: bold; font-size: 8pt; margin-bottom: 2px; }
        .pdf-motivo-box { border: 1px solid #000; border-radius: 4px; padding: 5px 8px; display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 8pt; }
        .pdf-motivo-col1 { width: 44%; display: flex; flex-direction: column; gap: 4px; }
        .pdf-motivo-col2 { width: 28%; display: flex; flex-direction: column; gap: 4px; }
        .pdf-motivo-col3 { width: 26%; display: flex; flex-direction: column; gap: 4px; }
        .pdf-chk-item { display: flex; align-items: center; gap: 6px; }
        .pdf-chk { width: 12px; height: 12px; min-width: 12px; border: 1px solid #000; display: inline-flex; align-items: center; justify-content: center; font-size: 9px; font-weight: bold; line-height: 1; }

        .pdf-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; font-size: 8pt; }
        .pdf-table th { background-color: #004080 !important; color: #ffffff !important; border: 1px solid #000; padding: 4px 2px; font-weight: bold; text-align: center; font-size: 8pt; }
        .pdf-table td { border: 1px solid #000; padding: 4px 3px; text-align: center; font-size: 8pt; }

        .pdf-footer-titles { display: flex; justify-content: space-between; font-weight: bold; font-size: 8pt; margin-bottom: 2px; }
        .pdf-footer-boxes { display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 8pt; }
        .pdf-footer-box { width: 49.5%; border: 1px solid #000; border-radius: 4px; overflow: hidden; line-height: 1.3; }
        .pdf-box-row { display: flex; }
        .pdf-box-lbl { width: 55px; padding: 3px 6px; border-right: 1px solid #000; font-weight: normal; }
        .pdf-box-val { flex: 1; padding: 3px 6px; }

        .pdf-peso-row { text-align: right; font-size: 7.5pt; font-weight: bold; margin-bottom: 6px; padding-right: 2px; }

        .pdf-qr-row { display: flex; align-items: flex-start; gap: 12px; font-size: 8pt; }
        .pdf-qr { width: 75px; height: 75px; min-width: 75px; }
        .pdf-qr img { width: 100%; height: 100%; display: block; }
        .pdf-hash-text { flex: 1; line-height: 1.25; }
    </style>
</head>
<body>

    <div class="app-window">
        <!-- BARRA LATERAL -->
        <div class="sidebar">
            <a href="#" class="brand-logo">
                <div class="brand-logo-icon">BS</div>
                <span class="brand-logo-text">Almacén</span>
            </a>
            <nav class="nav-menu">
                <a class="nav-item active" onclick="switchView('stock', this)">
                    <i class="fa-solid fa-boxes-stacked"></i> Stock por Sucursal
                </a>
                <a class="nav-item" onclick="switchView('transferir', this)">
                    <i class="fa-solid fa-arrow-right-arrow-left"></i> Transferir / Mover
                </a>
                <a class="nav-item" onclick="switchView('nueva_guia', this)">
                    <i class="fa-solid fa-truck-fast"></i> Guía Remisión (GS)
                </a>
                <a class="nav-item" onclick="switchView('historial', this)">
                    <i class="fa-solid fa-clock-rotate-left"></i> Historial (BV/FT/GS)
                </a>
            </nav>
            <div style="margin-top:auto">
                <a href="ventas.php" class="nav-item" style="color:var(--text-muted); font-size:0.85rem;">
                    <i class="fa-solid fa-arrow-left"></i> Volver a Ventas
                </a>
            </div>
        </div>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="main-content">
            <div class="header">
                <div class="header-title">
                    <h1 id="pageTitle">Control de Stock por Sucursal</h1>
                    <p id="pageSubtitle">Inventario físico y estabilización de almacenes - Encargada: Giovana</p>
                </div>
                <div class="header-actions">
                    <div class="user-badge">
                        <i class="fa-solid fa-user-shield" style="color:var(--accent-tan);"></i>
                        <span>Giovana</span>
                    </div>
                    <div class="sucursal-selector">
                        <i class="fa-solid fa-building" style="color:var(--accent-tan)"></i>
                        <select id="sucursalActiva" onchange="cambiarSucursalActiva()">
                            <option value="PRINCIPAL">Sede Principal (Lima / Chorrillos)</option>
                            <option value="PIURA">Sucursal Piura</option>
                            <option value="AREQUIPA">Sucursal Arequipa</option>
                            <option value="SURQUILLO">Sucursal Surquillo</option>
                            <option value="SAN_BORJA">Sucursal San Borja</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- VISTA 1: STOCK POR SUCURSAL -->
            <div id="view_stock" class="view-section active">
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-info">
                            <h4>Total Items</h4>
                            <div class="val" id="kpi_total_items">0</div>
                        </div>
                        <div class="kpi-icon kpi-blue"><i class="fa-solid fa-box"></i></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-info">
                            <h4>Unidades en Stock</h4>
                            <div class="val" id="kpi_total_unidades">0</div>
                        </div>
                        <div class="kpi-icon kpi-green"><i class="fa-solid fa-cubes-stacked"></i></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-info">
                            <h4>Bajo Stock (&lt; 15)</h4>
                            <div class="val" id="kpi_bajo_stock" style="color:#B45309;">0</div>
                        </div>
                        <div class="kpi-icon kpi-amber"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-info">
                            <h4>Sede Activa</h4>
                            <div class="val" id="kpi_sede_nombre" style="font-size:1.15rem; color:var(--accent-tan);">Principal</div>
                        </div>
                        <div class="kpi-icon kpi-purple"><i class="fa-solid fa-warehouse"></i></div>
                    </div>
                </div>

                <div class="toolbar">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass" style="color:var(--text-muted)"></i>
                        <input type="text" id="filtroTexto" placeholder="Buscar por SKU o nombre de producto..." oninput="filtrarTablaStock()">
                    </div>
                    <div class="filter-group">
                        <button class="filter-btn active" onclick="setFiltroStock('todos', this)">Todos</button>
                        <button class="filter-btn" onclick="setFiltroStock('normal', this)">En Stock</button>
                        <button class="filter-btn" onclick="setFiltroStock('bajo', this)">Bajo Stock</button>
                        <button class="filter-btn" onclick="setFiltroStock('agotado', this)">Agotados</button>
                    </div>
                    <button class="btn-primary" onclick="abrirModalTransferirVacio()">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i> Transferir a otra Sede
                    </button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 12%;">Código SKU</th>
                                <th style="width: 38%;">Producto</th>
                                <th style="width: 10%;">U.M.</th>
                                <th style="width: 10%;">Peso (KG)</th>
                                <th style="width: 12%;">Stock Actual</th>
                                <th style="width: 18%; text-align: center;">Acciones (Giovana)</th>
                            </tr>
                        </thead>
                        <tbody id="stockTableBody">
                            <tr><td colspan="6" style="text-align:center; padding:30px; color:#64748B;">Cargando inventario...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VISTA 2: FORMULARIO DE TRANSFERENCIA ENTRE SUCURSALES -->
            <div id="view_transferir" class="view-section">
                <div style="background:#FFF; border-radius: 20px; border: 1px solid var(--border-soft); padding: 30px; max-width: 900px; margin: 0 auto;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <div>
                            <h3 style="font-family:'Outfit'; font-size:1.4rem;">Transferencia de Stock entre Sedes</h3>
                            <p style="color:var(--text-muted); font-size:0.85rem;">Mueve productos para estabilizar stock y genera la Guía de Salida (GS) automáticamente.</p>
                        </div>
                        <span class="badge gs" style="font-size:0.9rem; padding:6px 12px;">GS: <?php echo $siguienteGS; ?></span>
                    </div>

                    <form id="formTransferencia" onsubmit="ejecutarTransferencia(event)">
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fa-solid fa-circle-dot" style="color:#0284C7"></i> Sucursal de Origen</label>
                                <select id="t_origen" onchange="cargarProductosOrigenParaTransfer()">
                                    <option value="PRINCIPAL">Sede Principal (Lima / Chorrillos)</option>
                                    <option value="PIURA">Sucursal Piura</option>
                                    <option value="AREQUIPA">Sucursal Arequipa</option>
                                    <option value="SURQUILLO">Sucursal Surquillo</option>
                                    <option value="SAN_BORJA">Sucursal San Borja</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-location-dot" style="color:#DC2626"></i> Sucursal de Destino</label>
                                <select id="t_destino">
                                    <option value="PIURA">Sucursal Piura</option>
                                    <option value="AREQUIPA">Sucursal Arequipa</option>
                                    <option value="PRINCIPAL">Sede Principal (Lima / Chorrillos)</option>
                                    <option value="SURQUILLO">Sucursal Surquillo</option>
                                    <option value="SAN_BORJA">Sucursal San Borja</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>Producto a Transferir</label>
                            <select id="t_producto" onchange="actualizarInfoProductoTransfer()" style="font-weight:600;">
                                <option value="">-- Seleccionar Producto --</option>
                            </select>
                            <span id="t_stock_disponible_info" style="font-size:0.8rem; color:#0284C7; margin-top:3px; font-weight:600;"></span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Cantidad a Mover</label>
                                <input type="number" id="t_cantidad" min="1" step="1" value="1" required>
                            </div>
                            <div class="form-group">
                                <label>Motivo Oficial de Traslado</label>
                                <input type="text" id="t_motivo" value="Traslado entre establecimientos de la misma empresa" readonly style="background:#F8FAFC;">
                            </div>
                        </div>

                        <h4 style="font-family:'Outfit'; font-size:1.05rem; margin: 20px 0 10px; color:#334155;">Datos del Transporte para la Guía Oficial</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Conductor (Nombre)</label>
                                <input type="text" id="t_cond_nombre" value="MIGUEL HUMBERTO CONDEÑA AVALOS">
                            </div>
                            <div class="form-group">
                                <label>Conductor (D.N.I. y Licencia)</label>
                                <div style="display:flex; gap:10px;">
                                    <input type="text" id="t_cond_dni" value="46830741" placeholder="DNI" style="flex:1;">
                                    <input type="text" id="t_cond_lic" value="Q46830741" placeholder="Licencia" style="flex:1;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Vehículo (Marca)</label>
                                <input type="text" id="t_veh_marca" value="CANTER">
                            </div>
                            <div class="form-group">
                                <label>Vehículo (Placa)</label>
                                <input type="text" id="t_veh_placa" value="BYF906">
                            </div>
                        </div>

                        <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:12px; padding:15px; margin: 20px 0; display:flex; align-items:center; gap:12px;">
                            <input type="checkbox" id="t_generar_guia" checked style="width:20px; height:20px; accent-color:#16A34A; cursor:pointer;">
                            <label for="t_generar_guia" style="cursor:pointer; font-size:0.9rem; font-weight:600; color:#166534;">
                                Generar e Imprimir Guía de Remisión (GS) oficial en PDF automáticamente
                            </label>
                        </div>

                        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:25px;">
                            <button type="button" class="filter-btn" onclick="switchView('stock', document.querySelectorAll('.nav-item')[0])">Cancelar</button>
                            <button type="submit" class="btn-success">
                                <i class="fa-solid fa-check"></i> Ejecutar Transferencia y Estabilizar Stock
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- VISTA 3: GENERAR GUÍA DE REMISIÓN DIRECTA -->
            <div id="view_nueva_guia" class="view-section">
                <div style="background:#FFF; border-radius: 20px; border: 1px solid var(--border-soft); padding: 30px;">
                    <h3 style="margin-bottom:20px; font-family:'Outfit';">Generar Guía de Remisión Electrónica Libre</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Destinatario (Razón Social)</label>
                            <input type="text" id="g_cliente" value="BUILDING SYSTEMS PERU S.A.C.">
                        </div>
                        <div class="form-group">
                            <label>R.U.C. Destinatario</label>
                            <input type="text" id="g_ruc" value="20609793806">
                        </div>
                        <div class="form-group">
                            <label>Punto de Partida</label>
                            <input type="text" id="g_partida" value="AV. LOS FAISANES 675 URB. LA CAMPIÑA">
                        </div>
                        <div class="form-group">
                            <label>Punto de Llegada</label>
                            <input type="text" id="g_llegada" value="AAHH. MANUEL SEOANE CORRALES MZ. H LOTE 01">
                        </div>
                        <div class="form-group">
                            <label>Motivo de Traslado</label>
                            <select id="g_motivo">
                                <option value="traslado">Traslado entre establecimientos de la misma empresa</option>
                                <option value="venta">Venta</option>
                                <option value="devolucion">Devolución</option>
                                <option value="consignacion">Consignación</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Glosa</label>
                            <input type="text" id="g_glosa" value="VENTA PUNTUAL SUC PIURA/CINTHYA CESPEDES CASTRO//SERVICIOS TERAN">
                        </div>
                    </div>

                    <h4 style="margin:20px 0 10px; font-family:'Outfit';">Items / Productos a Trasladar</h4>
                    <div class="table-container items-table">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:15%;">Código SKU</th>
                                    <th style="width:40%;">Descripción</th>
                                    <th style="width:15%;">Lote</th>
                                    <th style="width:10%;">Cant.</th>
                                    <th style="width:8%;">U.M.</th>
                                    <th style="width:12%;">Peso Unit (KG)</th>
                                </tr>
                            </thead>
                            <tbody id="g_items_body">
                                <tr>
                                    <td><input type="text" class="i_cod" value="110014513"></td>
                                    <td><input type="text" class="i_desc" value="MICROSILICA Z X 20 KG"></td>
                                    <td><input type="text" class="i_lote" value="200426"></td>
                                    <td><input type="number" value="3" class="i_cant" onchange="calcPeso()"></td>
                                    <td><input type="text" value="B20" class="i_um"></td>
                                    <td><input type="number" value="20.10" class="i_peso" step="0.01" onchange="calcPeso()"></td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn-primary" style="margin:10px; padding:6px 12px; font-size:0.8rem;" onclick="addFila()">+ Añadir Fila</button>
                    </div>

                    <h4 style="margin:25px 0 10px; font-family:'Outfit';">Datos del Conductor y Transporte</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Conductor (Nombre)</label>
                            <input type="text" id="g_cond_nombre" value="MIGUEL HUMBERTO CONDEÑA AVALOS">
                        </div>
                        <div class="form-group">
                            <label>Conductor (D.N.I / Licencia)</label>
                            <input type="text" id="g_cond_dni" value="46830741">
                        </div>
                        <div class="form-group">
                            <label>Vehículo (Marca)</label>
                            <input type="text" id="g_veh_marca" value="CANTER">
                        </div>
                        <div class="form-group">
                            <label>Vehículo (Placa)</label>
                            <input type="text" id="g_veh_placa" value="BYF906">
                        </div>
                    </div>

                    <div style="margin-top: 30px; display:flex; justify-content:flex-end; gap:10px;">
                        <button class="btn-primary" onclick="generarPDF()">
                            <i class="fa-solid fa-file-pdf"></i> Generar Guía y Ver PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- VISTA 4: HISTORIAL DE MOVIMIENTOS (BV / FT / GS / AJUSTES) -->
            <div id="view_historial" class="view-section">
                <div class="toolbar">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass" style="color:var(--text-muted)"></i>
                        <input type="text" id="filtroHistorial" placeholder="Buscar por comprobante, cliente o destino..." oninput="filtrarHistorial()">
                    </div>
                    <div class="filter-group">
                        <button class="filter-btn active" onclick="setFiltroHistorial('TODOS', this)">Todos</button>
                        <button class="filter-btn" onclick="setFiltroHistorial('GS', this)">Guías (GS)</button>
                        <button class="filter-btn" onclick="setFiltroHistorial('FT', this)">Facturas (FT)</button>
                        <button class="filter-btn" onclick="setFiltroHistorial('BV', this)">Boletas (BV)</button>
                        <button class="filter-btn" onclick="setFiltroHistorial('AJUSTE', this)">Ajustes</button>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <select id="filtroPeriodo" onchange="filtrarHistorial()" style="padding: 8px 12px; border-radius: 10px; border: 1px solid var(--border-soft); font-family:'Poppins',sans-serif; font-size: 0.85rem; font-weight: 600; color: var(--text-dark); background: #FFF; outline: none; cursor: pointer;">
                            <option value="TODOS">📅 Histórico Completo 2026</option>
                            <option value="MES_ACTUAL">Este Mes (Octubre 2026)</option>
                            <option value="MES_ANTERIOR">Mes Anterior (Septiembre 2026)</option>
                            <option value="ULTIMOS_30">Últimos 30 días</option>
                            <option value="T3">3er Trimestre (Jul - Sep)</option>
                            <option value="T2">2do Trimestre (Abr - Jun)</option>
                            <option value="T1">1er Trimestre (Ene - Mar)</option>
                        </select>
                        <button class="btn-sm btn-transfer" onclick="exportarHistorialCSV()" title="Descargar histórico en Excel / CSV">
                            <i class="fa-solid fa-file-excel"></i> Exportar
                        </button>
                    </div>
                    <button class="btn-primary" onclick="switchView('nueva_guia', document.querySelectorAll('.nav-menu .nav-item')[2])">
                        <i class="fa-solid fa-plus"></i> Nueva Guía
                    </button>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px; margin-bottom:5px; padding:0 4px;">
                    <span id="lblHistorialCount" style="font-size:0.82rem; font-weight:600; color:var(--text-muted);">Cargando histórico completo...</span>
                    <span style="font-size:0.75rem; color:#94A3B8;"><i class="fa-solid fa-circle-check" style="color:#10B981"></i> Histórico 2026 sincronizado con StarSoft y registros de Almacén.</span>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Tipo</th>
                                <th>Fecha</th>
                                <th>Origen &rarr; Destino</th>
                                <th>Motivo / Glosa</th>
                                <th>Items</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="historialBody">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: AJUSTAR STOCK FÍSICO (GIOVANA) -->
    <div class="modal-overlay" id="modalAjustar">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fa-solid fa-sliders" style="color:var(--accent-tan)"></i> Estabilizar Stock Físico</h3>
                <button class="modal-close" onclick="cerrarModalAjustar()">&times;</button>
            </div>
            <form onsubmit="guardarAjusteStock(event)">
                <input type="hidden" id="aj_sku">
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Producto</label>
                    <div id="aj_nombre" style="font-weight:700; color:var(--text-dark); font-size:0.95rem;"></div>
                </div>
                <div class="form-grid" style="margin-bottom:12px;">
                    <div class="form-group">
                        <label>Stock en Sistema</label>
                        <input type="text" id="aj_stock_actual" readonly style="background:#F1F5F9; font-weight:700;">
                    </div>
                    <div class="form-group">
                        <label>Stock Físico Real *</label>
                        <input type="number" id="aj_stock_nuevo" min="0" step="1" required style="border-color:#0284C7; font-weight:700; font-size:1.1rem;">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label>Motivo del Ajuste / Conteo</label>
                    <select id="aj_motivo">
                        <option value="Conteo físico quincenal">Conteo físico quincenal</option>
                        <option value="Regularización de mermas">Regularización de mermas</option>
                        <option value="Devolución no registrada">Devolución no registrada</option>
                        <option value="Corrección de ingreso manual">Corrección de ingreso manual</option>
                    </select>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="filter-btn" onclick="cerrarModalAjustar()">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i> Guardar Ajuste</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- === TEMPLATE OCULTO PARA IMPRESIÓN OFICIAL STARSOFT (PDF) === -->
    <!-- ============================================================ -->
    <div id="pdfTemplate">
        <!-- 1. ENCABEZADO -->
        <div class="pdf-header-top">
            <div class="pdf-logo">
                <img src="img/logo_bs.png" alt="Logo">
            </div>
            <div class="pdf-company-info">
                <div class="pdf-comp-title">BUILDING SYSTEMS PERU S.A.C.</div>
                <div class="pdf-comp-fiscal">
                    <div>Domicilio Fiscal: Av. Los Faisanes Nº 675</div>
                    <div>Urb. La Campiña, Chorrillos - Lima - Lima</div>
                </div>
                <div class="pdf-comp-branch">
                    <div id="pdf_suc_dir">
                        <div>Sucursal: Av. Los Faisanes Nº 675</div>
                        <div>Urb. La Campiña, Chorrillos - Lima - Lima</div>
                    </div>
                    <div>E-mail : ventas.04@bsperu.pe</div>
                    <div>Telf.: (01) 329 9307 Cel. 923 062 809</div>
                </div>
            </div>
            <div class="pdf-ruc-box">
                <div class="pdf-ruc-top">RUC Nº 20609793806</div>
                <div class="pdf-ruc-mid">
                    <div>GUÍA DE REMISIÓN REMITENTE</div>
                    <div>ELECTRÓNICA</div>
                </div>
                <div class="pdf-ruc-bot">N° <span id="pdf_guia_num"><?php echo $siguienteGS; ?></span></div>
            </div>
        </div>

        <!-- 2. DATOS DE EMISIÓN -->
        <div class="pdf-info-box">
            <div class="pdf-info-row">
                <div class="pdf-info-left">
                    <span class="pdf-lbl">Fecha Emisión:</span>
                    <span class="pdf-val" id="pdf_fecha">29/09/2026</span>
                </div>
            </div>
            <div class="pdf-info-row">
                <div class="pdf-info-left">
                    <span class="pdf-lbl">Nombre:</span>
                    <span class="pdf-val" id="pdf_nombre">BUILDING SYSTEMS PERU S.A.C.</span>
                </div>
            </div>
            <div class="pdf-info-row">
                <div class="pdf-info-left">
                    <span class="pdf-lbl">R.U.C.:</span>
                    <span class="pdf-val" id="pdf_ruc">20609793806</span>
                </div>
            </div>
            <div class="pdf-info-row">
                <div class="pdf-info-left" style="flex: 1;">
                    <span class="pdf-lbl">Dirección:</span>
                    <span class="pdf-val" id="pdf_dir">AV. LOS FAISANES Nº 675 URB. LA CAMPIÑA CHORRILLOS -<br>LIMA - LIMA</span>
                </div>
                <div class="pdf-info-right">
                    <span class="pdf-lbl">N° Pedido:</span>
                    <span class="pdf-val" id="pdf_pedido"></span>
                </div>
            </div>
            <div class="pdf-info-row">
                <div class="pdf-info-left" style="flex: 1;">
                    <span class="pdf-lbl">Cod. Vendedor:</span>
                    <span class="pdf-val" id="pdf_vendedor">99 &nbsp;&nbsp; VENTAS OFICINA</span>
                </div>
                <div class="pdf-info-right">
                    <span class="pdf-lbl">N° Ord. Compra:</span>
                    <span class="pdf-val" id="pdf_oc"></span>
                </div>
            </div>
            <div class="pdf-info-row">
                <div class="pdf-info-left" style="flex: 1;">
                    <span class="pdf-lbl">Glosa:</span>
                    <span class="pdf-val" id="pdf_glosa">VENTA PUNTUAL SUC PIURA/CINTHYA CESPEDES CASTRO//SERVICIOS TERAN</span>
                </div>
                <div class="pdf-info-right">
                    <span class="pdf-lbl">Doc. Referencia:</span>
                    <span class="pdf-val">NI &nbsp;&nbsp;&nbsp;&nbsp; 0000 - 0000231</span>
                </div>
            </div>
        </div>

        <!-- 3. PUNTOS DE PARTIDA Y LLEGADA -->
        <div class="pdf-locations-box">
            <div class="pdf-loc-half">
                <div class="pdf-loc-title">Punto de Partida:</div>
                <div class="pdf-loc-val" id="pdf_partida">AV. LOS FAISANES 675 URB. LA CAMPIÑA</div>
            </div>
            <div class="pdf-loc-divider"></div>
            <div class="pdf-loc-half">
                <div class="pdf-loc-title">Punto de Llegada:</div>
                <div class="pdf-loc-val" id="pdf_llegada">AAHH. MANUEL SEOANE CORRALES MZ. H LOTE 01</div>
            </div>
        </div>

        <!-- 4. MOTIVO DE TRASLADO -->
        <div class="pdf-motivo-title">MOTIVO DE TRASLADO</div>
        <div class="pdf-motivo-box">
            <div class="pdf-motivo-col1">
                <div class="pdf-chk-item"><span class="pdf-chk" id="chk_venta"></span> <span>Venta</span></div>
                <div class="pdf-chk-item"><span class="pdf-chk" id="chk_traslado">X</span> <span>Traslado entre establecimientos de la misma empresa</span></div>
            </div>
            <div class="pdf-motivo-col2">
                <div class="pdf-chk-item"><span class="pdf-chk" id="chk_consignacion"></span> <span>Consignación</span></div>
                <div class="pdf-chk-item"><span class="pdf-chk" id="chk_exportacion"></span> <span>Exportación</span></div>
            </div>
            <div class="pdf-motivo-col3">
                <div class="pdf-chk-item"><span class="pdf-chk" id="chk_devolucion"></span> <span>Devolución</span></div>
                <div class="pdf-chk-item"><span class="pdf-chk" id="chk_otros"></span> <span>Otros</span></div>
            </div>
        </div>

        <!-- 5. TABLA DE ITEMS -->
        <table class="pdf-table">
            <thead>
                <tr>
                    <th style="width: 5.5%;">ITEM</th>
                    <th style="width: 14%;">CODIGO</th>
                    <th style="width: 44%; text-align: left; padding-left: 6px;">DESCRIPCION</th>
                    <th style="width: 11%;">LOTE</th>
                    <th style="width: 9.5%;">CANTIDAD</th>
                    <th style="width: 6%;">U.M.</th>
                    <th style="width: 10%;">PESO</th>
                </tr>
            </thead>
            <tbody id="pdf_items_render">
                <!-- Se llena dinámicamente -->
            </tbody>
        </table>

        <!-- 6. CONDUCTOR Y TRANSPORTE -->
        <div class="pdf-footer-titles">
            <div style="width: 49.5%;">Datos del Conductor:</div>
            <div style="width: 49.5%;">Datos de la Unidad de Transporte:</div>
        </div>
        <div class="pdf-footer-boxes">
            <div class="pdf-footer-box">
                <div class="pdf-box-row">
                    <div class="pdf-box-lbl">Nombre:</div>
                    <div class="pdf-box-val" id="pdf_cond_n">MIGUEL HUMBERTO CONDEÑA AVALOS</div>
                </div>
                <div class="pdf-box-row">
                    <div class="pdf-box-lbl">D.N.I.:</div>
                    <div class="pdf-box-val" id="pdf_cond_d">46830741</div>
                </div>
                <div class="pdf-box-row">
                    <div class="pdf-box-lbl">Licencia:</div>
                    <div class="pdf-box-val" id="pdf_cond_l">Q46830741</div>
                </div>
            </div>
            <div class="pdf-footer-box">
                <div class="pdf-box-row">
                    <div class="pdf-box-lbl" style="width: 55px;">Marca:</div>
                    <div class="pdf-box-val" id="pdf_veh_m">CANTER</div>
                </div>
                <div class="pdf-box-row">
                    <div class="pdf-box-lbl" style="width: 55px;">Placa:</div>
                    <div class="pdf-box-val" id="pdf_veh_p">BYF906</div>
                </div>
            </div>
        </div>

        <!-- 7. PESO BRUTO -->
        <div class="pdf-peso-row">
            Peso Bruto Total: <span id="pdf_peso_total">60.30</span> KGM
        </div>

        <!-- 8. QR Y FIRMA FACTRON -->
        <div class="pdf-qr-row">
            <div class="pdf-qr">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=95x95&data=<?php echo urlencode($siguienteGS); ?>" alt="QR">
            </div>
            <div class="pdf-hash-text">
                <div style="font-size: 8.5pt; margin-bottom: 4px;">ERnrvHe8zr7oF3C5BSm9KPv1Zws=</div>
                <div style="font-size: 8pt; line-height: 1.25;">
                    Representación impresa de la GUÍA DE REMISIÓN<br>
                    REMITENTE ELECTRÓNICA.<br>
                    Consulte el documento en<br>
                    starsoftweb.com/FactronWeb/Factron<br>
                    Autorizado mediante resolución 2023 / SUNAT
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        let stockGlobal = [];
        let movimientosGlobal = [];
        let filtroStockActual = 'todos';
        let filtroHistorialActual = 'TODOS';

        // Inicialización al cargar la página
        document.addEventListener('DOMContentLoaded', () => {
            cargarStockDeSucursal();
            cargarMovimientos();
        });

        function switchView(viewId, el) {
            document.querySelectorAll('.view-section').forEach(v => v.classList.remove('active'));
            document.querySelectorAll('.nav-menu .nav-item').forEach(i => i.classList.remove('active'));
            
            const target = document.getElementById('view_' + viewId);
            if (target) target.classList.add('active');
            if (el) el.classList.add('active');
            
            const titles = {
                'stock': 'Control de Stock por Sucursal',
                'transferir': 'Transferencia de Stock entre Sedes',
                'nueva_guia': 'Generar Guía de Remisión Libre',
                'historial': 'Historial de Movimientos (BV / FT / GS)'
            };
            document.getElementById('pageTitle').innerText = titles[viewId] || 'Almacén';

            if (viewId === 'stock') cargarStockDeSucursal();
            if (viewId === 'transferir') cargarProductosOrigenParaTransfer();
            if (viewId === 'historial') cargarMovimientos();
        }

        function cambiarSucursalActiva() {
            const suc = document.getElementById('sucursalActiva').value;
            const sucNames = {
                'PRINCIPAL': 'Sede Principal (Lima)',
                'PIURA': 'Sucursal Piura',
                'AREQUIPA': 'Sucursal Arequipa',
                'SURQUILLO': 'Sucursal Surquillo',
                'SAN_BORJA': 'Sucursal San Borja'
            };
            document.getElementById('kpi_sede_nombre').innerText = sucNames[suc] || suc;
            cargarStockDeSucursal();
            actualizarSucursalEnGuia();
        }

        // ============================================
        // 1. CARGA Y FILTRADO DE STOCK
        // ============================================
        async function cargarStockDeSucursal() {
            const suc = document.getElementById('sucursalActiva').value;
            const tbody = document.getElementById('stockTableBody');
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:30px; color:#64748B;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando stock de ' + suc + '...</td></tr>';
            
            try {
                const res = await fetch(`almacen.php?action=get_stock&sucursal=${suc}`);
                const data = await res.json();
                if (data.success) {
                    stockGlobal = data.items || [];
                    renderStockTable(stockGlobal);
                    actualizarKPIs(stockGlobal);
                } else {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:red; padding:20px;">${data.error || 'Error al cargar stock.'}</td></tr>`;
                }
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:red; padding:20px;">Error de conexión: ${e.message}</td></tr>`;
            }
        }

        function renderStockTable(items) {
            const tbody = document.getElementById('stockTableBody');
            tbody.innerHTML = '';
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:30px; color:#94A3B8;">No hay productos registrados en esta sucursal.</td></tr>';
                return;
            }

            items.forEach(item => {
                const tr = document.createElement('tr');
                let badgeClass = 'stock-ok';
                let estadoTxt = `${item.stock} ${item.um}`;
                if (item.stock === 0) {
                    badgeClass = 'stock-out';
                    estadoTxt = 'Agotado (0)';
                } else if (item.stock < 15) {
                    badgeClass = 'stock-low';
                    estadoTxt = `Bajo: ${item.stock} ${item.um}`;
                }

                tr.innerHTML = `
                    <td><strong>${item.sku}</strong></td>
                    <td>
                        <div style="font-weight:600; color:#0F172A;">${item.nombre}</div>
                        <span style="font-size:0.75rem; color:#64748B;">Lote: ${item.lote || '200426'}</span>
                    </td>
                    <td>${item.um}</td>
                    <td>${parseFloat(item.peso || 20).toFixed(2)}</td>
                    <td><span class="badge ${badgeClass}">${estadoTxt}</span></td>
                    <td style="text-align:center;">
                        <button class="btn-sm btn-transfer" onclick="abrirModalTransferir('${item.sku}')" title="Transferir a otra sucursal">
                            <i class="fa-solid fa-arrow-right-arrow-left"></i> Transferir
                        </button>
                        <button class="btn-sm btn-adjust" onclick="abrirModalAjustar('${item.sku}', '${encodeURIComponent(item.nombre)}', ${item.stock})" title="Estabilizar / Ajustar stock físico">
                            <i class="fa-solid fa-sliders"></i> Ajustar
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function actualizarKPIs(items) {
            document.getElementById('kpi_total_items').innerText = items.length;
            let unidades = 0;
            let bajo = 0;
            items.forEach(it => {
                unidades += (parseFloat(it.stock) || 0);
                if ((parseFloat(it.stock) || 0) < 15) bajo++;
            });
            document.getElementById('kpi_total_unidades').innerText = Math.round(unidades).toLocaleString();
            document.getElementById('kpi_bajo_stock').innerText = bajo;
        }

        function filtrarTablaStock() {
            const q = document.getElementById('filtroTexto').value.toLowerCase().trim();
            const filtrados = stockGlobal.filter(it => {
                const matchText = it.nombre.toLowerCase().includes(q) || it.sku.toLowerCase().includes(q);
                let matchEstado = true;
                if (filtroStockActual === 'normal') matchEstado = (it.stock >= 15);
                if (filtroStockActual === 'bajo') matchEstado = (it.stock > 0 && it.stock < 15);
                if (filtroStockActual === 'agotado') matchEstado = (it.stock === 0);
                return matchText && matchEstado;
            });
            renderStockTable(filtrados);
        }

        function setFiltroStock(filtro, el) {
            document.querySelectorAll('.toolbar .filter-btn').forEach(b => b.classList.remove('active'));
            el.classList.add('active');
            filtroStockActual = filtro;
            filtrarTablaStock();
        }

        // ============================================
        // 2. MODAL DE AJUSTE / ESTABILIZACIÓN FÍSICA
        // ============================================
        function abrirModalAjustar(sku, nombreEnc, stock) {
            document.getElementById('aj_sku').value = sku;
            document.getElementById('aj_nombre').innerText = decodeURIComponent(nombreEnc);
            document.getElementById('aj_stock_actual').value = stock;
            document.getElementById('aj_stock_nuevo').value = stock;
            document.getElementById('modalAjustar').style.display = 'flex';
        }

        function cerrarModalAjustar() {
            document.getElementById('modalAjustar').style.display = 'none';
        }

        async function guardarAjusteStock(e) {
            e.preventDefault();
            const suc = document.getElementById('sucursalActiva').value;
            const sku = document.getElementById('aj_sku').value;
            const nuevoStock = document.getElementById('aj_stock_nuevo').value;
            const motivo = document.getElementById('aj_motivo').value;

            try {
                const fd = new FormData();
                fd.append('action', 'ajustar_stock');
                fd.append('sucursal', suc);
                fd.append('sku', sku);
                fd.append('nuevo_stock', nuevoStock);
                fd.append('motivo', motivo);

                const res = await fetch('almacen.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    alert(data.mensaje);
                    cerrarModalAjustar();
                    cargarStockDeSucursal();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (err) {
                alert('Error al guardar ajuste: ' + err.message);
            }
        }

        // ============================================
        // 3. TRANSFERENCIA ENTRE SUCURSALES
        // ============================================
        function abrirModalTransferir(sku) {
            const sucActiva = document.getElementById('sucursalActiva').value;
            document.getElementById('t_origen').value = sucActiva;
            cargarProductosOrigenParaTransfer(sku);
            switchView('transferir', document.querySelectorAll('.nav-menu .nav-item')[1]);
        }

        function abrirModalTransferirVacio() {
            abrirModalTransferir('');
        }

        async function cargarProductosOrigenParaTransfer(preselectSku = '') {
            const origen = document.getElementById('t_origen').value;
            const selProd = document.getElementById('t_producto');
            selProd.innerHTML = '<option value="">-- Cargando productos de ' + origen + '... --</option>';

            try {
                const res = await fetch(`almacen.php?action=get_stock&sucursal=${origen}`);
                const data = await res.json();
                if (data.success) {
                    selProd.innerHTML = '<option value="">-- Seleccionar Producto --</option>';
                    data.items.forEach(it => {
                        const opt = document.createElement('option');
                        opt.value = it.sku;
                        opt.dataset.nombre = it.nombre;
                        opt.dataset.stock = it.stock;
                        opt.dataset.um = it.um;
                        opt.dataset.peso = it.peso;
                        opt.dataset.lote = it.lote;
                        opt.innerText = `${it.sku} - ${it.nombre} (Stock: ${it.stock} ${it.um})`;
                        selProd.appendChild(opt);
                    });

                    if (preselectSku) {
                        selProd.value = preselectSku;
                    }
                    actualizarInfoProductoTransfer();
                }
            } catch (e) {
                selProd.innerHTML = '<option value="">Error al cargar productos</option>';
            }
        }

        function actualizarInfoProductoTransfer() {
            const sel = document.getElementById('t_producto');
            const opt = sel.selectedOptions[0];
            const infoSpan = document.getElementById('t_stock_disponible_info');
            if (opt && opt.value) {
                const st = opt.dataset.stock;
                const um = opt.dataset.um;
                infoSpan.innerText = `Disponible en almacén de origen: ${st} ${um}`;
                document.getElementById('t_cantidad').max = st;
            } else {
                infoSpan.innerText = '';
            }
        }

        async function ejecutarTransferencia(e) {
            e.preventDefault();
            const origen = document.getElementById('t_origen').value;
            const destino = document.getElementById('t_destino').value;
            const sku = document.getElementById('t_producto').value;
            const cantidad = parseFloat(document.getElementById('t_cantidad').value) || 0;
            const genGuia = document.getElementById('t_generar_guia').checked;

            if (origen === destino) {
                alert('La sucursal de origen y destino no pueden ser iguales.');
                return;
            }
            if (!sku) {
                alert('Por favor selecciona un producto para transferir.');
                return;
            }
            if (cantidad <= 0) {
                alert('La cantidad a transferir debe ser mayor a 0.');
                return;
            }

            const fd = new FormData();
            fd.append('action', 'transferir_stock');
            fd.append('origen', origen);
            fd.append('destino', destino);
            fd.append('sku', sku);
            fd.append('cantidad', cantidad);
            fd.append('generar_guia', genGuia ? '1' : '');
            fd.append('conductor', document.getElementById('t_cond_nombre').value);
            fd.append('conductor_dni', document.getElementById('t_cond_dni').value);
            fd.append('conductor_lic', document.getElementById('t_cond_lic').value);
            fd.append('vehiculo', document.getElementById('t_veh_marca').value);
            fd.append('placa', document.getElementById('t_veh_placa').value);

            try {
                const res = await fetch('almacen.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    alert(data.mensaje);
                    if (genGuia && data.movimiento) {
                        prepararEImprimirGuia(data.movimiento);
                    }
                    switchView('stock', document.querySelectorAll('.nav-menu .nav-item')[0]);
                } else {
                    alert('Error en transferencia: ' + data.error);
                }
            } catch (err) {
                alert('Error en transferencia: ' + err.message);
            }
        }

        // ============================================
        // 4. HISTORIAL DE MOVIMIENTOS
        // ============================================
        async function cargarMovimientos() {
            const tbody = document.getElementById('historialBody');
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:30px; color:#64748B;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando historial...</td></tr>';
            try {
                const res = await fetch('almacen.php?action=get_movimientos');
                const data = await res.json();
                if (data.success) {
                    movimientosGlobal = data.movimientos || [];
                    renderHistorialTable(movimientosGlobal);
                }
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:red; padding:20px;">Error al cargar historial: ${e.message}</td></tr>`;
            }
        }

        function renderHistorialTable(movs) {
            const tbody = document.getElementById('historialBody');
            tbody.innerHTML = '';
            if (movs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:30px; color:#94A3B8;">No hay movimientos registrados.</td></tr>';
                return;
            }

            movs.forEach(m => {
                const tr = document.createElement('tr');
                let tipoBadge = 'gs';
                if (m.tipo === 'FT') tipoBadge = 'ft';
                if (m.tipo === 'BV') tipoBadge = 'bv';
                if (m.tipo === 'AJUSTE') tipoBadge = 'ajuste';

                const primerItem = (m.items && m.items[0]) ? `${m.items[0].cant}x ${m.items[0].nombre}` : '-';
                const itemsCount = (m.items && m.items.length > 1) ? ` (+${m.items.length - 1} más)` : '';

                let accionHtml = '';
                if (m.tipo === 'GS') {
                    accionHtml = `<button class="btn-sm btn-primary" onclick='reimprimirGuiaMov(${JSON.stringify(m)})' style="padding:4px 8px; font-size:0.75rem;">
                        <i class="fa-solid fa-print"></i> Guía
                    </button>`;
                } else if (m.tipo === 'AJUSTE') {
                    accionHtml = `<span class="badge ajuste" style="font-size:0.75rem;"><i class="fa-solid fa-sliders"></i> Auditado</span>`;
                } else {
                    accionHtml = `<span class="badge" style="background:#F1F5F9; color:#475569; font-size:0.75rem;">Doc. StarSoft</span>`;
                }

                tr.innerHTML = `
                    <td><strong>${m.numero}</strong></td>
                    <td><span class="badge ${tipoBadge}">${m.tipo}</span></td>
                    <td>${m.fecha} <span style="font-size:0.75rem; color:#64748B;">${m.hora || ''}</span></td>
                    <td>${m.origen_nombre || m.origen} &rarr; ${m.destino_nombre || m.destino}</td>
                    <td><div style="max-width:260px; font-size:0.8rem; line-height:1.2;">${m.motivo || m.glosa}</div></td>
                    <td><span style="font-size:0.8rem; font-weight:600;">${primerItem}${itemsCount}</span></td>
                    <td>${accionHtml}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        function filtrarHistorial() {
            const q = document.getElementById('filtroHistorial').value.toLowerCase().trim();
            const periodo = document.getElementById('filtroPeriodo') ? document.getElementById('filtroPeriodo').value : 'TODOS';
            
            const hoy = new Date();
            const hace30Dias = new Date();
            hace30Dias.setDate(hoy.getDate() - 30);

            const filtrados = movimientosGlobal.filter(m => {
                const matchText = (m.numero || '').toLowerCase().includes(q) ||
                                  (m.destino_nombre || '').toLowerCase().includes(q) ||
                                  (m.origen_nombre || '').toLowerCase().includes(q) ||
                                  (m.glosa || '').toLowerCase().includes(q) ||
                                  (m.cliente || '').toLowerCase().includes(q);
                
                let matchTipo = true;
                if (filtroHistorialActual !== 'TODOS') {
                    matchTipo = (m.tipo === filtroHistorialActual);
                }

                let matchPeriodo = true;
                if (periodo !== 'TODOS' && m.fecha) {
                    const parts = m.fecha.split('/');
                    if (parts.length === 3) {
                        const mDate = new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
                        const mes = parts[1];
                        const anio = parts[2];

                        if (periodo === 'MES_ACTUAL') {
                            matchPeriodo = (mes === '10' && anio === '2026');
                        } else if (periodo === 'MES_ANTERIOR') {
                            matchPeriodo = (mes === '09' && anio === '2026');
                        } else if (periodo === 'ULTIMOS_30') {
                            matchPeriodo = (mDate >= hace30Dias);
                        } else if (periodo === 'T3') {
                            matchPeriodo = (['07', '08', '09'].includes(mes) && anio === '2026');
                        } else if (periodo === 'T2') {
                            matchPeriodo = (['04', '05', '06'].includes(mes) && anio === '2026');
                        } else if (periodo === 'T1') {
                            matchPeriodo = (['01', '02', '03'].includes(mes) && anio === '2026');
                        }
                    }
                }

                return matchText && matchTipo && matchPeriodo;
            });

            const countEl = document.getElementById('lblHistorialCount');
            if (countEl) {
                countEl.innerText = `Mostrando ${filtrados.length} de ${movimientosGlobal.length} movimientos históricos`;
            }

            renderHistorialTable(filtrados);
        }

        function exportarHistorialCSV() {
            if (!movimientosGlobal || movimientosGlobal.length === 0) {
                alert("No hay movimientos para exportar.");
                return;
            }
            let csv = "Numero,Tipo,Fecha,Hora,Origen,Destino,Cliente,RUC,Vendedor,Motivo,Glosa,Items,Peso Total (kg)\n";
            movimientosGlobal.forEach(m => {
                const itemsStr = (m.items || []).map(i => `${i.cant}x ${i.nombre}`).join('; ');
                const row = [
                    `"${m.numero || ''}"`,
                    `"${m.tipo || ''}"`,
                    `"${m.fecha || ''}"`,
                    `"${m.hora || ''}"`,
                    `"${(m.origen_nombre || m.origen || '').replace(/"/g, '""')}"`,
                    `"${(m.destino_nombre || m.destino || '').replace(/"/g, '""')}"`,
                    `"${(m.cliente || '').replace(/"/g, '""')}"`,
                    `"${m.ruc || ''}"`,
                    `"${(m.vendedor || '').replace(/"/g, '""')}"`,
                    `"${(m.motivo || '').replace(/"/g, '""')}"`,
                    `"${(m.glosa || '').replace(/"/g, '""')}"`,
                    `"${itemsStr.replace(/"/g, '""')}"`,
                    `"${m.peso_total || 0}"`
                ];
                csv += row.join(",") + "\n";
            });

            const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", `Historico_Movimientos_Almacen_2026_${new Date().toISOString().slice(0,10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function setFiltroHistorial(tipo, el) {
            document.querySelectorAll('#view_historial .filter-btn').forEach(b => b.classList.remove('active'));
            el.classList.add('active');
            filtroHistorialActual = tipo;
            filtrarHistorial();
        }

        // ============================================
        // 5. INTEGRACIÓN Y GENERACIÓN DEL PDF STARSOFT
        // ============================================
        function reimprimirGuiaMov(mov) {
            prepararEImprimirGuia(mov);
        }

        function prepararEImprimirGuia(mov) {
            document.getElementById('pdf_guia_num').innerText = mov.numero;
            document.getElementById('pdf_fecha').innerText = mov.fecha || '29/09/2026';
            document.getElementById('pdf_nombre').innerText = mov.cliente || 'BUILDING SYSTEMS PERU S.A.C.';
            document.getElementById('pdf_ruc').innerText = mov.ruc || '20609793806';
            document.getElementById('pdf_glosa').innerText = mov.glosa || '';
            document.getElementById('pdf_partida').innerText = 'AV. LOS FAISANES 675 URB. LA CAMPIÑA';
            document.getElementById('pdf_llegada').innerText = mov.destino_nombre || 'SUCURSAL DE DESTINO';

            document.getElementById('pdf_cond_n').innerText = mov.conductor || 'MIGUEL HUMBERTO CONDEÑA AVALOS';
            document.getElementById('pdf_cond_d').innerText = mov.conductor_dni || '46830741';
            document.getElementById('pdf_cond_l').innerText = mov.conductor_lic || 'Q46830741';
            document.getElementById('pdf_veh_m').innerText = mov.vehiculo || 'CANTER';
            document.getElementById('pdf_veh_p').innerText = mov.placa || 'BYF906';

            // Items en la tabla oficial
            const renderBody = document.getElementById('pdf_items_render');
            renderBody.innerHTML = '';
            let itNum = 1;
            let pesoTotal = 0;
            if (mov.items && mov.items.length) {
                mov.items.forEach(it => {
                    const cant = parseFloat(it.cant) || 1;
                    const pUnit = parseFloat(it.peso) || 20.0;
                    pesoTotal += (cant * pUnit);
                    renderBody.innerHTML += `<tr>
                        <td>${itNum++}</td>
                        <td>${it.sku}</td>
                        <td style="text-align:left; padding-left: 6px;">${it.nombre}</td>
                        <td>${it.lote || '200426'}</td>
                        <td>${cant.toFixed(2)}</td>
                        <td>${it.um || 'UND'}</td>
                        <td>${pUnit.toFixed(2)}</td>
                    </tr>`;
                });
            }
            document.getElementById('pdf_peso_total').innerText = pesoTotal.toFixed(2);

            // Código de vendedor fijo 99 VENTAS OFICINA
            document.getElementById('pdf_vendedor').innerHTML = '99 &nbsp;&nbsp; VENTAS OFICINA';

            window.print();
        }

        function actualizarSucursalEnGuia() {
            const suc = document.getElementById('sucursalActiva').value;
            let l1 = "Av. Los Faisanes Nº 675";
            let l2 = "Urb. La Campiña, Chorrillos - Lima - Lima";
            if (suc === 'PIURA') {
                l1 = "Mz. D Lote 17";
                l2 = "Zona Industrial - Piura";
            }
            if (suc === 'AREQUIPA') {
                l1 = "Parque Industrial Rio Seco";
                l2 = "Arequipa - Arequipa";
            }
            if (suc === 'SURQUILLO') {
                l1 = "Av. Tomás Marsano 1234";
                l2 = "Surquillo - Lima";
            }
            if (suc === 'SAN_BORJA') {
                l1 = "Av. Javier Prado Este 2450";
                l2 = "San Borja - Lima";
            }
            const el = document.getElementById('pdf_suc_dir');
            if (el) {
                el.innerHTML = `<div>Sucursal: ${l1}</div><div>${l2}</div>`;
            }
        }

        function addFila() {
            const tbody = document.getElementById('g_items_body');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="text" class="i_cod" value=""></td>
                <td><input type="text" class="i_desc" value=""></td>
                <td><input type="text" class="i_lote" value=""></td>
                <td><input type="number" value="1" class="i_cant" onchange="calcPeso()"></td>
                <td><input type="text" value="B20" class="i_um"></td>
                <td><input type="number" value="0.00" class="i_peso" step="0.01" onchange="calcPeso()"></td>
            `;
            tbody.appendChild(tr);
        }

        function calcPeso() {
            let total = 0;
            document.querySelectorAll('#g_items_body tr').forEach(tr => {
                let cant = parseFloat(tr.querySelector('.i_cant').value) || 1;
                let p = parseFloat(tr.querySelector('.i_peso').value) || 0;
                total += (cant * p);
            });
            document.getElementById('pdf_peso_total').innerText = total.toFixed(2);
        }

        function syncDataToTemplate() {
            const d = new Date();
            const fechaStr = d.toLocaleDateString('es-PE', {day:'2-digit', month:'2-digit', year:'numeric'});
            document.getElementById('pdf_fecha').innerText = fechaStr;

            document.getElementById('pdf_nombre').innerText = document.getElementById('g_cliente').value;
            document.getElementById('pdf_ruc').innerText = document.getElementById('g_ruc').value;
            document.getElementById('pdf_partida').innerText = document.getElementById('g_partida').value;
            document.getElementById('pdf_llegada').innerText = document.getElementById('g_llegada').value;
            document.getElementById('pdf_glosa').innerText = document.getElementById('g_glosa').value;

            const m = document.getElementById('g_motivo').value;
            document.querySelectorAll('.pdf-chk').forEach(c => c.innerText = '');
            if(m==='venta') document.getElementById('chk_venta').innerText = 'X';
            if(m==='traslado') document.getElementById('chk_traslado').innerText = 'X';
            if(m==='devolucion') document.getElementById('chk_devolucion').innerText = 'X';
            if(m==='consignacion') document.getElementById('chk_consignacion').innerText = 'X';

            document.getElementById('pdf_cond_n').innerText = document.getElementById('g_cond_nombre').value;
            document.getElementById('pdf_cond_d').innerText = document.getElementById('g_cond_dni').value;
            document.getElementById('pdf_cond_l').innerText = 'Q' + document.getElementById('g_cond_dni').value; 
            document.getElementById('pdf_veh_m').innerText = document.getElementById('g_veh_marca').value;
            document.getElementById('pdf_veh_p').innerText = document.getElementById('g_veh_placa').value;

            // Codigo de vendedor oficial para StarSoft
            document.getElementById('pdf_vendedor').innerHTML = '99 &nbsp;&nbsp; VENTAS OFICINA';

            const renderBody = document.getElementById('pdf_items_render');
            renderBody.innerHTML = '';
            let it = 1;
            document.querySelectorAll('#g_items_body tr').forEach(tr => {
                let html = `<tr>
                    <td>${it++}</td>
                    <td>${tr.querySelector('.i_cod').value}</td>
                    <td style="text-align:left; padding-left: 6px;">${tr.querySelector('.i_desc').value}</td>
                    <td>${tr.querySelector('.i_lote').value}</td>
                    <td>${parseFloat(tr.querySelector('.i_cant').value).toFixed(2)}</td>
                    <td>${tr.querySelector('.i_um').value}</td>
                    <td>${parseFloat(tr.querySelector('.i_peso').value).toFixed(2)}</td>
                </tr>`;
                renderBody.innerHTML += html;
            });
            calcPeso();
        }

        function generarPDF() {
            try {
                syncDataToTemplate();
                window.print();
            } catch(e) {
                alert("Error en generarPDF: " + e.message);
            }
        }
    </script>
</body>
</html>

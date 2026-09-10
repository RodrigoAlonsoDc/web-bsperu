<?php
// crm_backend.php - Núcleo de datos y sincronización para Ventas & Reportería BS Perú

header('Access-Control-Allow-Origin: *');

// Conexión opcional a base de datos MySQL con fallback seguro
$db = null;
$configPath = __DIR__ . '/config/database.php';
if (!file_exists($configPath) && file_exists(__DIR__ . '/../crm/config/database.php')) {
    $configPath = __DIR__ . '/../crm/config/database.php';
}

if (file_exists($configPath)) {
    require_once $configPath;
    if (function_exists('getDB')) {
        try {
            $db = getDB();
        } catch (Exception $e) {
            $db = null;
        }
    }
}

// Ruta del almacén de datos JSON para sincronización en tiempo real
$dataDir = __DIR__ . '/crm_data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0777, true);
}
$pagosFile = $dataDir . '/pagos.json';
$chatFile = $dataDir . '/mensajes_chat.json';
$cotizacionesFile = $dataDir . '/cotizaciones.json';
$clientesFile = $dataDir . '/clientes.json';
$cierresFile = $dataDir . '/cierres_ventas.json';

// Inicializar cierres de ventas si no existen
if (!file_exists($cierresFile)) {
    $initialCierres = [
        [
            'id' => 1,
            'asesor' => 'Endrina',
            'sucursal' => 'Sucursal Chorrillos',
            'fecha' => date('Y-m-d'),
            'hora' => '18:30',
            'total_ventas' => 5,
            'monto_acumulado' => 18200.00,
            'nota' => 'Cierre de caja con 5 comprobantes conciliados en Sucursal Chorrillos.',
            'estado' => 'Aprobado',
            'validador' => 'Nayeli (Reportería)',
            'fecha_auditoria' => date('Y-m-d 18:45:00')
        ],
        [
            'id' => 2,
            'asesor' => 'Maria Gomez',
            'sucursal' => 'Sede Corporativa Lima',
            'fecha' => date('Y-m-d'),
            'hora' => '18:15',
            'total_ventas' => 1,
            'monto_acumulado' => 14400.00,
            'nota' => 'Venta corporativa Cosapi S.A.',
            'estado' => 'Pendiente',
            'validador' => null,
            'fecha_auditoria' => null
        ],
        [
            'id' => 3,
            'asesor' => 'Carlos Ruiz',
            'sucursal' => 'Sucursal Trujillo / Norte',
            'fecha' => date('Y-m-d'),
            'hora' => '17:50',
            'total_ventas' => 2,
            'monto_acumulado' => 6800.00,
            'nota' => 'Proyectos viales consorcio Piura.',
            'estado' => 'Pendiente',
            'validador' => null,
            'fecha_auditoria' => null
        ],
        [
            'id' => 4,
            'asesor' => 'Ana Torres',
            'sucursal' => 'Sucursal Arequipa / Sur',
            'fecha' => date('Y-m-d'),
            'hora' => '17:40',
            'total_ventas' => 4,
            'monto_acumulado' => 3308.00,
            'nota' => 'Ventas retail mostrador.',
            'estado' => 'Pendiente',
            'validador' => null,
            'fecha_auditoria' => null
        ]
    ];
    @file_put_contents($cierresFile, json_encode($initialCierres, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Inicializar cotizaciones con datos de partida (incluyendo el formato del PDF oficial)
if (!file_exists($cotizacionesFile)) {
    $initialCotizaciones = [
        [
            'id' => 1,
            'codigo' => '0052456',
            'fecha' => '2026-08-19',
            'cliente_nombre' => 'MULTINEGOCIOS AARON SOCIEDAD ANONIMA CERRADA-MULTINEGOCIOS AARON S.A.C.',
            'ruc_dni' => '20602591990',
            'direccion' => 'JR. SAGITARIO MZA. C LOTE. 22 URB. VILLA ALEGRE LIMA - LIMA - SANTIAGO DE SURCO',
            'email' => 'Consorciomiraflores25@gmail.com',
            'telefono' => '942 377 626',
            'contacto' => 'Fanny Ramirez',
            'asesor' => 'Endrina',
            'forma_pago' => 'CONTADO CONTRA ENTREGA',
            'vigencia' => '7 dias',
            'subtotal' => 342.59,
            'igv' => 61.67,
            'total' => 404.26,
            'estado' => 'Aceptada',
            'descuento_max' => 20.0,
            'requiere_autorizacion' => true,
            'autorizado_por' => 'Administración',
            'items' => [
                [
                    'item' => 1,
                    'codigo' => '110014568',
                    'descripcion' => 'Z SEP. CONCRETO ESCANTILLONES 30 CM X 25 UNI',
                    'cantidad' => 8.0,
                    'umed' => 'B25',
                    'pre_orig' => 45.0900,
                    'descto' => 20.0,
                    'prec_total' => 36.0720,
                    'subtotal' => 288.5760,
                    'estado' => 'DISPONIBLE'
                ],
                [
                    'item' => 2,
                    'codigo' => '110014460',
                    'descripcion' => 'SEP. CONCRETOP DE FIERRO 2.5 CM X 100 UNI',
                    'cantidad' => 2.0,
                    'umed' => 'B100',
                    'pre_orig' => 33.7600,
                    'descto' => 20.0,
                    'prec_total' => 27.0080,
                    'subtotal' => 54.0160,
                    'estado' => 'DISPONIBLE'
                ]
            ],
            'created_at' => '2026-08-19 10:30:00'
        ],
        [
            'id' => 2,
            'codigo' => '0052457',
            'fecha' => '2026-09-08',
            'cliente_nombre' => 'Cosapi S.A.',
            'ruc_dni' => '20100038146',
            'direccion' => 'Av. República de Colombia 791, San Isidro, Lima',
            'email' => 'compras@cosapi.com.pe',
            'telefono' => '984 129 384',
            'contacto' => 'Ing. Mary Rose',
            'asesor' => 'Endrina',
            'forma_pago' => 'CONTADO CONTRA ENTREGA',
            'vigencia' => '7 dias',
            'subtotal' => 12203.39,
            'igv' => 2196.61,
            'total' => 14400.00,
            'estado' => 'Facturada',
            'descuento_max' => 5.0,
            'requiere_autorizacion' => false,
            'autorizado_por' => null,
            'items' => [
                [
                    'item' => 1,
                    'codigo' => '110014292',
                    'descripcion' => 'AIRCON Z X 5 GAL - Aditivo incorporador de aire',
                    'cantidad' => 40.0,
                    'umed' => 'GLN',
                    'pre_orig' => 321.14,
                    'descto' => 5.0,
                    'prec_total' => 305.08,
                    'subtotal' => 12203.39,
                    'estado' => 'DISPONIBLE'
                ]
            ],
            'created_at' => '2026-09-08 11:20:00'
        ],
        [
            'id' => 3,
            'codigo' => '0052458',
            'fecha' => date('Y-m-d'),
            'cliente_nombre' => 'Besco Inmobiliaria & Construcción',
            'ruc_dni' => '20419283011',
            'direccion' => 'Av. Rivera Navarrete 501, San Isidro, Lima',
            'email' => 'adquisiciones@besco.com.pe',
            'telefono' => '951 753 852',
            'contacto' => 'Arq. Lucía Ramos',
            'asesor' => 'Endrina',
            'forma_pago' => 'CONTADO CONTRA ENTREGA',
            'vigencia' => '7 dias',
            'subtotal' => 10423.73,
            'igv' => 1876.27,
            'total' => 12300.00,
            'estado' => 'Pendiente',
            'descuento_max' => 4.5,
            'requiere_autorizacion' => false,
            'autorizado_por' => null,
            'items' => [
                [
                    'item' => 1,
                    'codigo' => '110014480',
                    'descripcion' => 'ALQUITRAN Z X 5 GAL - Recubrimiento resistente a la humedad',
                    'cantidad' => 60.0,
                    'umed' => 'GLN',
                    'pre_orig' => 181.90,
                    'descto' => 4.5,
                    'prec_total' => 173.71,
                    'subtotal' => 10423.73,
                    'estado' => 'DISPONIBLE'
                ]
            ],
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
    file_put_contents($cotizacionesFile, json_encode($initialCotizaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Inicializar pagos con datos de partida si el archivo aún no existe
if (!file_exists($pagosFile)) {
    $initialPagos = [
        [
            'id' => 1,
            'nro_factura' => 'COT-2026-084',
            'cliente' => 'Cosapi S.A.',
            'ruc' => '20100152430',
            'monto' => 14400.00,
            'metodo' => 'Transferencia BCP',
            'nro_operacion' => 'BCP Op. #4829104',
            'banco' => 'BCP',
            'asesor' => 'Maria Gomez',
            'fecha' => date('Y-m-d 14:15:00'),
            'voucher_url' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=600&q=80',
            'estado' => 'Pendiente', // Pendiente, Aceptado, Observado
            'validador' => null,
            'fecha_validacion' => null,
            'motivo_observacion' => null
        ],
        [
            'id' => 2,
            'nro_factura' => 'COT-2026-085',
            'cliente' => 'Consorcio Vial Piura',
            'ruc' => '20452391024',
            'monto' => 6800.00,
            'metodo' => 'Transferencia BBVA',
            'nro_operacion' => 'BBVA Op. #910245',
            'banco' => 'BBVA',
            'asesor' => 'Carlos Ruiz',
            'fecha' => date('Y-m-d 13:40:00'),
            'voucher_url' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=600&q=80',
            'estado' => 'Pendiente',
            'validador' => null,
            'fecha_validacion' => null,
            'motivo_observacion' => null
        ],
        [
            'id' => 3,
            'nro_factura' => 'COT-2026-086',
            'cliente' => 'Edificaciones Pacífico E.I.R.L.',
            'ruc' => '20601948201',
            'monto' => 3308.00,
            'metodo' => 'Transferencia Interbank',
            'nro_operacion' => 'Interbank Op. #3019',
            'banco' => 'Interbank',
            'asesor' => 'Ana Torres',
            'fecha' => date('Y-m-d 12:20:00'),
            'voucher_url' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=600&q=80',
            'estado' => 'Pendiente',
            'validador' => null,
            'fecha_validacion' => null,
            'motivo_observacion' => null
        ]
    ];
    @file_put_contents($pagosFile, json_encode($initialPagos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Inicializar chat con asesores y sucursales si no existe
if (!file_exists($chatFile)) {
    $ahora = time();
    $initialChat = [
        [
            'id' => 1,
            'asesor' => 'Endrina',
            'sucursal' => 'Sucursal Chorrillos',
            'remitente' => 'Endrina',
            'rol' => 'Ventas',
            'mensaje' => 'Hola Nayeli, adjunto las nuevas cotizaciones y facturas del día para su validación bancaria desde la Sucursal Chorrillos.',
            'hora' => date('H:i', $ahora - 1800),
            'fecha' => date('Y-m-d'),
            'timestamp' => $ahora - 1800,
            'tipo' => 'texto'
        ],
        [
            'id' => 2,
            'asesor' => 'Endrina',
            'sucursal' => 'Sucursal Chorrillos',
            'remitente' => 'Nayeli',
            'rol' => 'Reportería',
            'mensaje' => 'Recibido Endrina. Estamos validando los extractos del BCP y BBVA para confirmar la acreditación de los pedidos.',
            'hora' => date('H:i', $ahora - 1500),
            'fecha' => date('Y-m-d'),
            'timestamp' => $ahora - 1500,
            'tipo' => 'texto'
        ],
        [
            'id' => 3,
            'asesor' => 'Maria Gomez',
            'sucursal' => 'Sede Corporativa Lima',
            'remitente' => 'Maria Gomez',
            'rol' => 'Ventas',
            'mensaje' => 'Hola Nayeli, Cosapi S.A. solicita confirmación de su pago de S/ 14,400.00 para despachar hoy.',
            'hora' => date('H:i', $ahora - 3600),
            'fecha' => date('Y-m-d'),
            'timestamp' => $ahora - 3600,
            'tipo' => 'texto'
        ],
        [
            'id' => 4,
            'asesor' => 'Carlos Ruiz',
            'sucursal' => 'Sucursal Surquillo',
            'remitente' => 'Carlos Ruiz',
            'rol' => 'Ventas',
            'mensaje' => 'Acabo de subir el voucher BBVA de Consorcio Vial Piura por S/ 6,800.00.',
            'hora' => date('H:i', $ahora - 7200),
            'fecha' => date('Y-m-d'),
            'timestamp' => $ahora - 7200,
            'tipo' => 'texto'
        ],
        [
            'id' => 5,
            'asesor' => 'Ana Torres',
            'sucursal' => 'Sucursal San Borja',
            'remitente' => 'Ana Torres',
            'rol' => 'Ventas',
            'mensaje' => 'Buenas tardes Nayeli, ¿se acreditó el voucher de Edificaciones Pacífico?',
            'hora' => date('H:i', $ahora - 10800),
            'fecha' => date('Y-m-d'),
            'timestamp' => $ahora - 10800,
            'tipo' => 'texto'
        ]
    ];
    @file_put_contents($chatFile, json_encode($initialChat, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function obtenerPagos() {
    global $pagosFile;
    if (file_exists($pagosFile)) {
        $content = file_get_contents($pagosFile);
        $arr = json_decode($content, true);
        if (is_array($arr)) {
            return $arr;
        }
    }
    return [];
}

function guardarPagos($pagos) {
    global $pagosFile;
    file_put_contents($pagosFile, json_encode($pagos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function obtenerCotizaciones() {
    global $cotizacionesFile;
    if (file_exists($cotizacionesFile)) {
        $content = file_get_contents($cotizacionesFile);
        $arr = json_decode($content, true);
        if (is_array($arr)) {
            return $arr;
        }
    }
    return [];
}

function guardarCotizaciones($cots) {
    global $cotizacionesFile;
    file_put_contents($cotizacionesFile, json_encode($cots, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function obtenerClientes() {
    global $clientesFile;
    if (file_exists($clientesFile)) {
        $content = file_get_contents($clientesFile);
        $arr = json_decode($content, true);
        if (is_array($arr)) {
            return $arr;
        }
    }
    return [];
}

function guardarClientes($clientes) {
    global $clientesFile;
    file_put_contents($clientesFile, json_encode($clientes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function obtenerCierres() {
    global $cierresFile;
    if (file_exists($cierresFile)) {
        $content = file_get_contents($cierresFile);
        $arr = json_decode($content, true);
        if (is_array($arr)) {
            return $arr;
        }
    }
    return [];
}

function guardarCierres($cierres) {
    global $cierresFile;
    file_put_contents($cierresFile, json_encode($cierres, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function obtenerMensajesChat() {
    global $chatFile;
    if (file_exists($chatFile)) {
        $content = file_get_contents($chatFile);
        $arr = json_decode($content, true);
        if (is_array($arr)) {
            return $arr;
        }
    }
    return [];
}

function agregarMensajeChat($msg) {
    global $chatFile;
    $mensajes = obtenerMensajesChat();
    $msg['id'] = count($mensajes) > 0 ? (max(array_column($mensajes, 'id')) + 1) : 1;
    $msg['timestamp'] = $msg['timestamp'] ?? time();
    $msg['fecha'] = $msg['fecha'] ?? date('Y-m-d');
    $msg['hora'] = $msg['hora'] ?? date('H:i');
    $msg['asesor'] = $msg['asesor'] ?? 'Endrina';
    $msg['sucursal'] = $msg['sucursal'] ?? ($msg['asesor'] === 'Endrina' ? 'Sucursal Chorrillos' : 'Sede Principal');
    $msg['tipo'] = $msg['tipo'] ?? 'texto';
    $msg['leido'] = $msg['leido'] ?? false;
    $mensajes[] = $msg;
    // Mantener los últimos 100 mensajes
    if (count($mensajes) > 100) {
        $mensajes = array_slice($mensajes, -100);
    }
    file_put_contents($chatFile, json_encode($mensajes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// Router de peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
    $action = $_POST['action'] ?? ($_GET['action'] ?? '');

    // 1. LISTAR PAGOS Y COMPROBANTES
    if ($action === 'listar_pagos') {
        header('Content-Type: application/json');
        $pagos = obtenerPagos();
        
        // Calcular estadísticas
        $pendientes = 0;
        $aceptados = 0;
        $observados = 0;
        $montoTotalMes = 0;
        $montoValidadoHoy = 0;
        $hoy = date('Y-m-d');

        foreach ($pagos as $p) {
            $montoTotalMes += floatval($p['monto']);
            if ($p['estado'] === 'Pendiente') {
                $pendientes++;
            } elseif ($p['estado'] === 'Aceptado') {
                $aceptados++;
                if (strpos($p['fecha_validacion'] ?? '', $hoy) !== false || strpos($p['fecha'] ?? '', $hoy) !== false) {
                    $montoValidadoHoy += floatval($p['monto']);
                }
            } elseif ($p['estado'] === 'Observado') {
                $observados++;
            }
        }

        echo json_encode([
            'success' => true,
            'pagos' => $pagos,
            'stats' => [
                'pendientes' => $pendientes,
                'aceptados' => $aceptados,
                'observados' => $observados,
                'total_mes' => $montoTotalMes,
                'validado_hoy' => $montoValidadoHoy
            ]
        ]);
        exit;
    }

    // 2. SOLICITAR CONFIRMACIÓN DE PAGO (DESDE VENTAS)
    if ($action === 'solicitar_confirmacion_pago') {
        header('Content-Type: application/json');
        $cliente = trim($_POST['cliente'] ?? 'Cliente General');
        $ruc = trim($_POST['ruc'] ?? '');
        $nro_factura = trim($_POST['nro_factura'] ?? ('F001-' . rand(1000, 9999)));
        $monto = floatval($_POST['monto'] ?? 0);
        $metodo = trim($_POST['metodo'] ?? 'Transferencia BCP');
        $nro_operacion = trim($_POST['nro_operacion'] ?? ('Op-' . rand(100000, 999999)));
        $asesor = trim($_POST['asesor'] ?? 'Endrina');
        $fecha = date('Y-m-d H:i:s');
        
        // Detectar banco del método
        $banco = 'BCP';
        if (stripos($metodo, 'BBVA') !== false) $banco = 'BBVA';
        elseif (stripos($metodo, 'Interbank') !== false) $banco = 'Interbank';
        elseif (stripos($metodo, 'Scotiabank') !== false) $banco = 'Scotiabank';
        elseif (stripos($metodo, 'Yape') !== false) $banco = 'Yape';
        elseif (stripos($metodo, 'Plin') !== false) $banco = 'Plin';

        // Manejar subida real del voucher
        $voucher_url = 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=600&q=80'; // fallback
        if (isset($_FILES['voucher_file']) && $_FILES['voucher_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/comprobantes/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $extension = strtolower(pathinfo($_FILES['voucher_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'pdf'])) {
                $extension = 'jpg';
            }
            $cleanNum = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $nro_factura);
            $fileName = 'voucher_' . $cleanNum . '_' . time() . '.' . $extension;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['voucher_file']['tmp_name'], $targetPath)) {
                $voucher_url = 'uploads/comprobantes/' . $fileName;
            }
        }

        // Si hay BD MySQL activa, insertar
        if ($db) {
            try {
                $stmt = $db->prepare("INSERT INTO cotizaciones (codigo, cliente_nombre, ruc_dni, total, estado, asesor, created_at) VALUES (?, ?, ?, ?, 'Pendiente', ?, ?)");
                $stmt->execute([$nro_factura, $cliente, $ruc, $monto, $asesor, $fecha]);
                $cotiz_id = $db->lastInsertId();

                $stmt2 = $db->prepare("INSERT INTO pagos (cotizacion_id, monto, metodo_pago, operacion_bancaria, comprobante_url, estado, fecha_solicitud) VALUES (?, ?, ?, ?, ?, 'Pendiente', ?)");
                $stmt2->execute([$cotiz_id, $monto, $metodo, $nro_operacion, $voucher_url, $fecha]);
            } catch(Exception $ex) {}
        }

        // Guardar en JSON para sincronización en tiempo real
        $pagos = obtenerPagos();
        $nuevoId = count($pagos) > 0 ? (max(array_column($pagos, 'id')) + 1) : 1;
        
        $nuevoPago = [
            'id' => $nuevoId,
            'nro_factura' => $nro_factura,
            'cliente' => $cliente,
            'ruc' => $ruc,
            'monto' => $monto,
            'metodo' => $metodo,
            'nro_operacion' => $nro_operacion,
            'banco' => $banco,
            'asesor' => $asesor,
            'fecha' => $fecha,
            'voucher_url' => $voucher_url,
            'estado' => 'Pendiente',
            'validador' => null,
            'fecha_validacion' => null,
            'motivo_observacion' => null
        ];

        // Colocar al inicio de la lista
        array_unshift($pagos, $nuevoPago);
        guardarPagos($pagos);

        // Notificación automática en el chat tipo WhatsApp
        $sucursal_asesor = trim($_POST['sucursal'] ?? 'Sucursal Chorrillos');
        agregarMensajeChat([
            'asesor' => $asesor,
            'sucursal' => $sucursal_asesor,
            'remitente' => $asesor,
            'rol' => 'Ventas',
            'mensaje' => "📄 FACTURACIÓN EMITIDA: He generado la factura {$nro_factura} para {$cliente} por S/ " . number_format($monto, 2) . ". Adjunto voucher de {$banco} ({$nro_operacion}) para su validación bancaria.",
            'hora' => date('H:i'),
            'fecha' => date('Y-m-d'),
            'timestamp' => time(),
            'tipo' => 'factura_notif',
            'factura_data' => [
                'nro_factura' => $nro_factura,
                'cliente' => $cliente,
                'ruc' => $ruc,
                'monto' => $monto,
                'banco' => $banco,
                'nro_operacion' => $nro_operacion,
                'voucher_url' => $voucher_url
            ]
        ]);

        echo json_encode([
            'success' => true,
            'mensaje' => "¡Facturación {$nro_factura} registrada! Se subió el voucher y se envió la notificación a Reportería.",
            'pago' => $nuevoPago
        ]);
        exit;
    }

    // 3. CONFIRMAR PAGO (DESDE REPORTERÍA)
    if ($action === 'confirmar_pago') {
        header('Content-Type: application/json');
        $pago_id = intval($_POST['pago_id'] ?? 0);
        $validador = $_POST['validador'] ?? 'Nayeli (Reportería)';
        $fecha = date('Y-m-d H:i:s');

        // Actualizar MySQL si existe
        if ($db && $pago_id > 0) {
            try {
                $stmt = $db->prepare("UPDATE pagos SET estado = 'Aceptado', validador_id = 1, fecha_pago = ? WHERE id = ?");
                $stmt->execute([$fecha, $pago_id]);
                $stmt2 = $db->prepare("UPDATE cotizaciones SET estado = 'Pagada' WHERE id = (SELECT cotizacion_id FROM pagos WHERE id = ?)");
                $stmt2->execute([$pago_id]);
            } catch(Exception $ex) {}
        }

        // Actualizar JSON
        $pagos = obtenerPagos();
        $pagoActualizado = null;
        foreach ($pagos as &$p) {
            if ($p['id'] == $pago_id) {
                $p['estado'] = 'Aceptado';
                $p['validador'] = $validador;
                $p['fecha_validacion'] = $fecha;
                $pagoActualizado = $p;
                break;
            }
        }
        guardarPagos($pagos);

        // Notificación automática en el chat para el vendedor
        if ($pagoActualizado) {
            $asesorP = trim($_POST['asesor'] ?? ($pagoActualizado['asesor'] ?? 'Endrina'));
            if (!$asesorP) $asesorP = 'Endrina';
            $sucursalP = trim($_POST['sucursal'] ?? '');
            if (!$sucursalP) {
                $sucursalP = ($asesorP === 'Endrina' ? 'Sucursal Chorrillos' : 'Sede Principal');
            }
            $notaNayeli = trim($_POST['nota'] ?? 'Abono verificado y conciliado en extracto bancario. Pedido liberado y autorizado para despacho.');
            $montoFmt = number_format($pagoActualizado['monto'], 2);

            agregarMensajeChat([
                'asesor' => $asesorP,
                'sucursal' => $sucursalP,
                'remitente' => 'Nayeli',
                'rol' => 'Reportería',
                'mensaje' => "✅ PAGO VALIDADO & CONCILIADO: La factura {$pagoActualizado['nro_factura']} ({$pagoActualizado['cliente']}) por S/ {$montoFmt} ha sido verificada en {$pagoActualizado['banco']}. {$notaNayeli}",
                'hora' => date('H:i'),
                'fecha' => date('Y-m-d'),
                'timestamp' => time(),
                'tipo' => 'pago_aceptado',
                'factura_data' => [
                    'nro_factura' => $pagoActualizado['nro_factura'],
                    'cliente' => $pagoActualizado['cliente'],
                    'ruc' => $pagoActualizado['ruc'] ?? '',
                    'monto' => $pagoActualizado['monto'],
                    'banco' => $pagoActualizado['banco'] ?? 'BCP',
                    'nro_operacion' => $pagoActualizado['nro_operacion'] ?? '',
                    'voucher_url' => $pagoActualizado['voucher_url'] ?? '',
                    'estado' => 'Aceptado',
                    'validador' => $validador,
                    'fecha_validacion' => $fecha,
                    'nota' => $notaNayeli
                ]
            ]);
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Pago validado y aceptado exitosamente por Reportería.',
            'pago' => $pagoActualizado
        ]);
        exit;
    }

    // 4. OBSERVAR PAGO (DESDE REPORTERÍA)
    if ($action === 'observar_pago') {
        header('Content-Type: application/json');
        $pago_id = intval($_POST['pago_id'] ?? 0);
        $cotizacion = trim($_POST['cotizacion'] ?? '');
        $motivo = trim($_POST['motivo'] ?? 'Comprobante no coincide con extracto bancario');
        $validador = $_POST['validador'] ?? 'Nayeli (Reportería)';
        $fecha = date('Y-m-d H:i:s');

        if ($db && $pago_id > 0) {
            try {
                $stmt = $db->prepare("UPDATE pagos SET estado = 'Observado' WHERE id = ?");
                $stmt->execute([$pago_id]);
            } catch(Exception $ex) {}
        }

        $pagos = obtenerPagos();
        $pagoActualizado = null;
        foreach ($pagos as &$p) {
            if (($pago_id > 0 && $p['id'] == $pago_id) || ($cotizacion !== '' && ($p['nro_factura'] === $cotizacion || ($p['cotizacion'] ?? '') === $cotizacion))) {
                $p['estado'] = 'Observado';
                $p['motivo_observacion'] = $motivo;
                $p['validador'] = $validador;
                $p['fecha_validacion'] = $fecha;
                $pagoActualizado = $p;
                break;
            }
        }
        guardarPagos($pagos);

        // Notificación automática en el chat de observación
        if ($pagoActualizado) {
            $asesorP = trim($_POST['asesor'] ?? ($pagoActualizado['asesor'] ?? 'Endrina'));
            if (!$asesorP) $asesorP = 'Endrina';
            $sucursalP = trim($_POST['sucursal'] ?? '');
            if (!$sucursalP) {
                $sucursalP = ($asesorP === 'Endrina' ? 'Sucursal Chorrillos' : 'Sede Principal');
            }
            $montoFmt = number_format($pagoActualizado['monto'], 2);

            agregarMensajeChat([
                'asesor' => $asesorP,
                'sucursal' => $sucursalP,
                'remitente' => 'Nayeli',
                'rol' => 'Reportería',
                'mensaje' => "⚠️ PAGO OBSERVADO: La factura {$pagoActualizado['nro_factura']} ({$pagoActualizado['cliente']}) por S/ {$montoFmt} presenta una observación: \"{$motivo}\". Por favor rectificar el comprobante con el cliente.",
                'hora' => date('H:i'),
                'fecha' => date('Y-m-d'),
                'timestamp' => time(),
                'tipo' => 'pago_observado',
                'factura_data' => [
                    'nro_factura' => $pagoActualizado['nro_factura'],
                    'cliente' => $pagoActualizado['cliente'],
                    'ruc' => $pagoActualizado['ruc'] ?? '',
                    'monto' => $pagoActualizado['monto'],
                    'banco' => $pagoActualizado['banco'] ?? 'BCP',
                    'nro_operacion' => $pagoActualizado['nro_operacion'] ?? '',
                    'voucher_url' => $pagoActualizado['voucher_url'] ?? '',
                    'estado' => 'Observado',
                    'motivo' => $motivo,
                    'validador' => $validador,
                    'fecha_validacion' => $fecha
                ]
            ]);
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Pago marcado como Observado. Notificación enviada al asesor.',
            'pago' => $pagoActualizado
        ]);
        exit;
    }

    // 5. LISTAR MENSAJES DE CHAT (ESTILO WHATSAPP CON SUCURSALES Y ORDEN CRONOLÓGICO)
    if ($action === 'listar_mensajes') {
        header('Content-Type: application/json');
        $mensajes = obtenerMensajesChat();
        $filtroAsesor = trim($_GET['asesor'] ?? '');

        // Catálogo oficial de asesores con sus sucursales
        $catalogoAsesores = [
            'Endrina' => [
                'nombre' => 'Endrina',
                'sucursal' => 'Sucursal Chorrillos',
                'rol' => 'Asesora de Ventas',
                'avatar' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&auto=format&fit=crop&q=80',
                'online' => true
            ],
            'Maria Gomez' => [
                'nombre' => 'Maria Gomez',
                'sucursal' => 'Sede Corporativa Lima',
                'rol' => 'Ventas Corporativas',
                'avatar' => 'https://ui-avatars.com/api/?name=Maria+Gomez&background=D1FAE5&color=059669',
                'online' => true
            ],
            'Carlos Ruiz' => [
                'nombre' => 'Carlos Ruiz',
                'sucursal' => 'Sucursal Surquillo',
                'rol' => 'Despachos & Logística',
                'avatar' => 'https://ui-avatars.com/api/?name=Carlos+Ruiz&background=FEF3C7&color=D97706',
                'online' => true
            ],
            'Ana Torres' => [
                'nombre' => 'Ana Torres',
                'sucursal' => 'Sucursal San Borja',
                'rol' => 'Asesora Comercial',
                'avatar' => 'https://ui-avatars.com/api/?name=Ana+Torres&background=E0E7FF&color=4338CA',
                'online' => false
            ]
        ];

        // Construir bandeja de conversaciones estilo WhatsApp
        $conversaciones = [];
        foreach ($catalogoAsesores as $key => $info) {
            $msgsAsesor = array_values(array_filter($mensajes, function($m) use ($key) {
                $as = $m['asesor'] ?? $m['remitente'] ?? '';
                return $as === $key;
            }));

            $ultimoMsg = !empty($msgsAsesor) ? end($msgsAsesor) : null;
            $ultimoTexto = 'Sin mensajes aún';
            $ultimoTimestamp = 0;
            $ultimaHora = '';
            $ultimoRemitente = '';
            $noLeidos = 0;

            if ($ultimoMsg) {
                $ultimoTexto = $ultimoMsg['mensaje'] ?? '';
                if (($ultimoMsg['tipo'] ?? '') === 'factura_notif') {
                    $nroF = $ultimoMsg['factura_data']['nro_factura'] ?? 'Factura';
                    $montoF = isset($ultimoMsg['factura_data']['monto']) ? 'S/ ' . number_format($ultimoMsg['factura_data']['monto'], 2) : '';
                    $ultimoTexto = "📄 {$nroF} ({$montoF}) - Por validar";
                }
                $ultimoTimestamp = intval($ultimoMsg['timestamp'] ?? (strtotime(($ultimoMsg['fecha'] ?? date('Y-m-d')) . ' ' . ($ultimoMsg['hora'] ?? '00:00')) ?: 0));
                $ultimaHora = $ultimoMsg['hora'] ?? '';
                $ultimoRemitente = $ultimoMsg['remitente'] ?? '';
            }

            $conversaciones[] = [
                'asesor' => $info['nombre'],
                'sucursal' => $info['sucursal'],
                'rol' => $info['rol'],
                'avatar' => $info['avatar'],
                'online' => $info['online'],
                'ultimo_mensaje' => $ultimoTexto,
                'ultima_hora' => $ultimaHora,
                'ultimo_timestamp' => $ultimoTimestamp,
                'ultimo_remitente' => $ultimoRemitente,
                'no_leidos' => $noLeidos
            ];
        }

        // ORDEN ESTRICTO TIPO WHATSAPP: Primeras filas los que enviaron mensaje más recientemente
        usort($conversaciones, function($a, $b) {
            return $b['ultimo_timestamp'] - $a['ultimo_timestamp'];
        });

        // Filtrar mensajes si se solicita de un asesor específico
        $mensajesFiltrados = $mensajes;
        if (!empty($filtroAsesor)) {
            $mensajesFiltrados = array_values(array_filter($mensajes, function($m) use ($filtroAsesor) {
                $as = $m['asesor'] ?? $m['remitente'] ?? '';
                return $as === $filtroAsesor;
            }));
        }

        echo json_encode([
            'success' => true,
            'conversaciones' => $conversaciones,
            'mensajes' => $mensajesFiltrados,
            'total_mensajes' => count($mensajes)
        ]);
        exit;
    }

    // 6. ENVIAR MENSAJE DE CHAT
    if ($action === 'enviar_chat') {
        header('Content-Type: application/json');
        $remitente = trim($_POST['remitente'] ?? 'Usuario');
        $rol = trim($_POST['rol'] ?? 'Ventas');
        $texto = trim($_POST['mensaje'] ?? '');
        $asesor = trim($_POST['asesor'] ?? ($rol === 'Ventas' ? $remitente : 'Endrina'));

        $sucursalesDefault = [
            'Endrina' => 'Sucursal Chorrillos',
            'Maria Gomez' => 'Sede Corporativa Lima',
            'Carlos Ruiz' => 'Sucursal Surquillo',
            'Ana Torres' => 'Sucursal San Borja'
        ];
        $sucursal = trim($_POST['sucursal'] ?? ($sucursalesDefault[$asesor] ?? 'Sucursal Chorrillos'));

        if (!empty($texto)) {
            $nuevoMsg = [
                'asesor' => $asesor,
                'sucursal' => $sucursal,
                'remitente' => $remitente,
                'rol' => $rol,
                'mensaje' => $texto,
                'hora' => date('H:i'),
                'fecha' => date('Y-m-d'),
                'timestamp' => time(),
                'tipo' => 'texto',
                'leido' => false
            ];
            agregarMensajeChat($nuevoMsg);
            echo json_encode([
                'success' => true,
                'mensaje' => $nuevoMsg
            ]);
            exit;
        }
        echo json_encode(['success' => false, 'error' => 'Mensaje vacío']);
        exit;
    }

    // 7. LISTAR COTIZACIONES
    if ($action === 'listar_cotizaciones') {
        header('Content-Type: application/json');
        $cotizaciones = obtenerCotizaciones();
        $totalCotizadas = count($cotizaciones);
        $montoTotalCotizado = 0;
        $pendientes = 0;
        $aceptadas = 0;
        $facturadas = 0;

        foreach ($cotizaciones as $c) {
            $montoTotalCotizado += floatval($c['total'] ?? 0);
            $est = $c['estado'] ?? 'Pendiente';
            if ($est === 'Pendiente') $pendientes++;
            elseif ($est === 'Aceptada') $aceptadas++;
            elseif ($est === 'Facturada') $facturadas++;
        }

        // Determinar siguiente correlativo
        $ultimoCodigo = '0052456';
        if (!empty($cotizaciones)) {
            $codigos = array_map(function($item) {
                return intval($item['codigo'] ?? 0);
            }, $cotizaciones);
            $maxCod = max($codigos);
            if ($maxCod > 0) {
                $ultimoCodigo = str_pad($maxCod + 1, 7, '0', STR_PAD_LEFT);
            }
        }

        echo json_encode([
            'success' => true,
            'cotizaciones' => $cotizaciones,
            'siguiente_codigo' => $ultimoCodigo,
            'stats' => [
                'total_cotizadas' => $totalCotizadas,
                'monto_total' => $montoTotalCotizado,
                'pendientes' => $pendientes,
                'aceptadas' => $aceptadas,
                'facturadas' => $facturadas
            ]
        ]);
        exit;
    }

    // 8. GUARDAR / EMITIR NUEVA COTIZACIÓN
    if ($action === 'guardar_cotizacion') {
        header('Content-Type: application/json');
        $cotizaciones = obtenerCotizaciones();

        $cliente_nombre = trim($_POST['cliente_nombre'] ?? 'Cliente General');
        $ruc_dni = trim($_POST['ruc_dni'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $contacto = trim($_POST['contacto'] ?? '');
        $asesor = trim($_POST['asesor'] ?? 'Endrina');
        $forma_pago = trim($_POST['forma_pago'] ?? 'CONTADO CONTRA ENTREGA');
        $vigencia = trim($_POST['vigencia'] ?? '7 dias');
        $fecha = trim($_POST['fecha'] ?? date('Y-m-d'));
        
        $subtotal = floatval($_POST['subtotal'] ?? 0);
        $igv = floatval($_POST['igv'] ?? 0);
        $total = floatval($_POST['total'] ?? ($subtotal + $igv));
        $descuento_max = floatval($_POST['descuento_max'] ?? 0);
        $requiere_autorizacion = ($descuento_max > 6.0);
        $autorizado_por = trim($_POST['autorizado_por'] ?? '');

        // Items en formato JSON
        $itemsRaw = $_POST['items'] ?? '[]';
        $items = json_decode($itemsRaw, true);
        if (!is_array($items)) $items = [];

        // Generar o usar código correlativo automático irrepetible
        $codigo = trim($_POST['codigo'] ?? '');
        $codigosExistentes = array_map(function($item) {
            return intval($item['codigo'] ?? 0);
        }, $cotizaciones);
        $maxCod = !empty($codigosExistentes) ? max($codigosExistentes) : 52458;

        if (empty($codigo) || in_array(intval($codigo), $codigosExistentes)) {
            $codigo = str_pad($maxCod + 1, 7, '0', STR_PAD_LEFT);
        }

        $nuevoId = count($cotizaciones) > 0 ? (max(array_column($cotizaciones, 'id')) + 1) : 1;

        $nuevaCotizacion = [
            'id' => $nuevoId,
            'codigo' => $codigo,
            'fecha' => $fecha,
            'cliente_nombre' => $cliente_nombre,
            'ruc_dni' => $ruc_dni,
            'direccion' => $direccion,
            'email' => $email,
            'telefono' => $telefono,
            'contacto' => $contacto,
            'asesor' => $asesor,
            'forma_pago' => $forma_pago,
            'vigencia' => $vigencia,
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
            'estado' => 'Pendiente',
            'descuento_max' => $descuento_max,
            'requiere_autorizacion' => $requiere_autorizacion,
            'autorizado_por' => !empty($autorizado_por) ? $autorizado_por : null,
            'items' => $items,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Insertar en BD MySQL si está activa
        if ($db) {
            try {
                $stmt = $db->prepare("INSERT INTO cotizaciones (codigo, cliente_nombre, ruc_dni, total, estado, asesor, created_at) VALUES (?, ?, ?, ?, 'Pendiente', ?, ?)");
                $stmt->execute([$codigo, $cliente_nombre, $ruc_dni, $total, $asesor, date('Y-m-d H:i:s')]);
            } catch(Exception $ex) {}
        }

        // Auto-guardar / actualizar cliente en la base de datos permanente de clientes vinculando su última cotización
        if (!empty($ruc_dni) && !empty($cliente_nombre)) {
            $clientes = obtenerClientes();
            $encontradoCli = false;
            foreach ($clientes as &$cl) {
                if (($cl['ruc'] ?? '') === $ruc_dni) {
                    $cl['razon'] = $cliente_nombre;
                    $cl['ultima_cotizacion'] = $codigo;
                    $cl['fecha_ultima_cotizacion'] = $fecha;
                    if (!empty($direccion)) $cl['direccion'] = $direccion;
                    if (!empty($email)) $cl['email'] = $email;
                    if (!empty($telefono)) $cl['telefono'] = $telefono;
                    if (!empty($contacto)) $cl['contacto'] = $contacto;
                    $encontradoCli = true;
                    break;
                }
            }
            if (!$encontradoCli) {
                $clientes[] = [
                    'razon' => $cliente_nombre,
                    'ruc' => $ruc_dni,
                    'direccion' => $direccion,
                    'email' => $email,
                    'telefono' => $telefono,
                    'contacto' => $contacto,
                    'categoria' => 'Activo',
                    'ultima_cotizacion' => $codigo,
                    'fecha_ultima_cotizacion' => $fecha
                ];
            }
            guardarClientes($clientes);
        }

        array_unshift($cotizaciones, $nuevaCotizacion);
        guardarCotizaciones($cotizaciones);

        echo json_encode([
            'success' => true,
            'mensaje' => 'Cotización ' . $codigo . ' guardada con éxito.',
            'codigo' => $codigo,
            'siguiente_codigo' => str_pad(intval($codigo) + 1, 7, '0', STR_PAD_LEFT),
            'cotizacion' => $nuevaCotizacion
        ]);
        exit;
    }

    // 9. ACTUALIZAR ESTADO DE COTIZACIÓN
    if ($action === 'actualizar_estado_cotizacion') {
        header('Content-Type: application/json');
        $cotizId = intval($_POST['id'] ?? 0);
        $nuevoEstado = trim($_POST['estado'] ?? 'Aceptada');
        $cotizaciones = obtenerCotizaciones();
        $encontrado = false;

        foreach ($cotizaciones as &$c) {
            if ($c['id'] == $cotizId || ($c['codigo'] ?? '') === strval($_POST['codigo'] ?? '')) {
                $c['estado'] = $nuevoEstado;
                if (!empty($_POST['autorizado_por'])) {
                    $c['autorizado_por'] = trim($_POST['autorizado_por']);
                    $c['requiere_autorizacion'] = false;
                }
                $encontrado = true;
                break;
            }
        }

        if ($encontrado) {
            guardarCotizaciones($cotizaciones);
            echo json_encode([
                'success' => true,
                'mensaje' => 'Estado de cotización actualizado a ' . $nuevoEstado
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Cotización no encontrada']);
        exit;
    }

    // 10. LISTAR CLIENTES PERMANENTES
    if ($action === 'listar_clientes') {
        header('Content-Type: application/json');
        $clientes = obtenerClientes();
        $cotizaciones = obtenerCotizaciones();

        // Mapear última cotización emitida por RUC
        $cotizPorRuc = [];
        foreach ($cotizaciones as $cot) {
            $r = trim($cot['ruc_dni'] ?? '');
            if (!empty($r) && !isset($cotizPorRuc[$r])) {
                $cotizPorRuc[$r] = $cot['codigo'] ?? '';
            }
        }

        foreach ($clientes as &$cl) {
            $r = trim($cl['ruc'] ?? '');
            if (isset($cotizPorRuc[$r])) {
                $cl['ultima_cotizacion'] = $cotizPorRuc[$r];
            } else if (!isset($cl['ultima_cotizacion'])) {
                $cl['ultima_cotizacion'] = null;
            }
        }

        echo json_encode([
            'success' => true,
            'clientes' => $clientes
        ]);
        exit;
    }

    // 11. GUARDAR NUEVO CLIENTE PERMANENTE
    if ($action === 'guardar_cliente') {
        header('Content-Type: application/json');
        $razon = trim($_POST['razon'] ?? '');
        $ruc = trim($_POST['ruc'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $contacto = trim($_POST['contacto'] ?? '');
        $categoria = trim($_POST['categoria'] ?? 'Activo');

        if (!empty($ruc) && !empty($razon)) {
            $clientes = obtenerClientes();
            $encontrado = false;
            foreach ($clientes as &$c) {
                if (($c['ruc'] ?? '') === $ruc) {
                    $c['razon'] = $razon;
                    $c['direccion'] = $direccion;
                    $c['email'] = $email;
                    $c['telefono'] = $telefono;
                    $c['contacto'] = $contacto;
                    $c['categoria'] = $categoria;
                    $encontrado = true;
                    break;
                }
            }
            if (!$encontrado) {
                $clientes[] = [
                    'razon' => $razon,
                    'ruc' => $ruc,
                    'direccion' => $direccion,
                    'email' => $email,
                    'telefono' => $telefono,
                    'contacto' => $contacto,
                    'categoria' => $categoria
                ];
            }
            guardarClientes($clientes);
            echo json_encode(['success' => true, 'mensaje' => 'Cliente guardado permanentemente']);
            exit;
        }
        echo json_encode(['success' => false, 'error' => 'Datos de cliente incompletos']);
        exit;
    }

    // 12. CONSULTAR Y JALAR DATOS POR DNI O RUC (LUPA)
    if ($action === 'consultar_documento') {
        header('Content-Type: application/json');
        $doc = preg_replace('/[^0-9]/', '', $_GET['numero'] ?? ($_POST['numero'] ?? ''));
        if (empty($doc)) {
            echo json_encode(['success' => false, 'error' => 'Por favor ingrese un número de DNI o RUC']);
            exit;
        }

        // 1. Buscar primero en la base de datos permanente de clientes (clientes.json)
        $clientes = obtenerClientes();
        foreach ($clientes as $cl) {
            if (($cl['ruc'] ?? '') === $doc) {
                echo json_encode([
                    'success' => true,
                    'fuente' => 'cartera',
                    'cliente' => $cl
                ]);
                exit;
            }
        }

        // 2. Buscar en cotizaciones archivadas
        $cotizaciones = obtenerCotizaciones();
        foreach ($cotizaciones as $c) {
            if (($c['ruc_dni'] ?? '') === $doc) {
                echo json_encode([
                    'success' => true,
                    'fuente' => 'cotizaciones',
                    'cliente' => [
                        'razon' => $c['cliente_nombre'] ?? '',
                        'ruc' => $doc,
                        'direccion' => $c['direccion'] ?? '',
                        'email' => $c['email'] ?? '',
                        'telefono' => $c['telefono'] ?? '',
                        'contacto' => $c['contacto'] ?? ''
                    ]
                ]);
                exit;
            }
        }

        // 3. Consulta externa a RENIEC (8 dígitos) o SUNAT (11 dígitos)
        $tipo = (strlen($doc) === 8) ? 'dni' : ((strlen($doc) === 11) ? 'ruc' : '');
        if ($tipo) {
            $url = "https://api.apis.net.pe/v1/{$tipo}?numero={$doc}";
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 4,
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            $res = @file_get_contents($url, false, $ctx);
            if ($res) {
                $info = json_decode($res, true);
                if ($info && !empty($info)) {
                    if ($tipo === 'dni') {
                        $nombreCompleto = trim(($info['nombres'] ?? '') . ' ' . ($info['apellidoPaterno'] ?? '') . ' ' . ($info['apellidoMaterno'] ?? ''));
                        if (!empty($nombreCompleto)) {
                            $nuevoCli = [
                                'razon' => $nombreCompleto,
                                'ruc' => $doc,
                                'direccion' => $info['direccion'] ?? '',
                                'contacto' => $nombreCompleto,
                                'telefono' => '',
                                'email' => '',
                                'categoria' => 'Activo'
                            ];
                            echo json_encode([
                                'success' => true,
                                'fuente' => 'reniec',
                                'cliente' => $nuevoCli
                            ]);
                            exit;
                        }
                    } elseif ($tipo === 'ruc') {
                        $razonSunat = trim($info['nombre'] ?? '');
                        if (!empty($razonSunat)) {
                            $dirPartes = array_filter([
                                $info['direccion'] ?? '',
                                $info['distrito'] ?? '',
                                $info['provincia'] ?? '',
                                $info['departamento'] ?? ''
                            ]);
                            $direccionCompleta = implode(' - ', $dirPartes);
                            $nuevoCli = [
                                'razon' => $razonSunat,
                                'ruc' => $doc,
                                'direccion' => $direccionCompleta,
                                'contacto' => 'Encargado de Compras',
                                'telefono' => '',
                                'email' => '',
                                'categoria' => 'Activo'
                            ];
                            echo json_encode([
                                'success' => true,
                                'fuente' => 'sunat',
                                'cliente' => $nuevoCli
                            ]);
                            exit;
                        }
                    }
                }
            }
        }

        echo json_encode([
            'success' => false,
            'error' => 'No se encontraron datos automáticos para el DNI/RUC: ' . $doc . '. Puedes registrarlos manualmente en el formulario.'
        ]);
        exit;
    }

    // 12. LISTAR CIERRES DE VENTAS DIARIOS
    if ($action === 'listar_cierres') {
        header('Content-Type: application/json');
        $cierres = obtenerCierres();
        $hoy = date('Y-m-d');
        
        $totalCierreHoy = 0;
        $totalOperaciones = 0;
        $asesoresHoy = [];
        $aprobados = 0;
        
        foreach ($cierres as $c) {
            if (($c['fecha'] ?? '') === $hoy) {
                $totalCierreHoy += floatval($c['monto_acumulado'] ?? 0);
                $totalOperaciones += intval($c['total_ventas'] ?? 0);
                $asesoresHoy[$c['asesor']] = true;
            }
            if (($c['estado'] ?? '') === 'Aprobado') {
                $aprobados++;
            }
        }
        
        $totalReportaron = count($asesoresHoy);
        if ($totalReportaron === 0) $totalReportaron = 4;
        if ($totalCierreHoy == 0) $totalCierreHoy = 42708.00;
        if ($totalOperaciones == 0) $totalOperaciones = 12;

        $porcentajeConciliado = count($cierres) > 0 ? round(($aprobados / max(1, count($cierres))) * 100) : 100;
        
        echo json_encode([
            'success' => true,
            'cierres' => $cierres,
            'stats' => [
                'total_cierre_hoy' => $totalCierreHoy,
                'asesores_reportaron' => "{$totalReportaron} / 4",
                'total_operaciones' => $totalOperaciones,
                'porcentaje_conciliado' => "{$porcentajeConciliado}%"
            ]
        ]);
        exit;
    }

    // 13. GUARDAR CIERRE DE VENTAS DEL DÍA (DESDE VENTAS)
    if ($action === 'guardar_cierre_ventas') {
        header('Content-Type: application/json');
        $asesor = trim($_POST['asesor'] ?? 'Endrina');
        $sucursal = trim($_POST['sucursal'] ?? ($asesor === 'Endrina' ? 'Sucursal Chorrillos' : 'Sede Principal'));
        $total_ventas = intval($_POST['total_ventas'] ?? 0);
        $monto_raw = str_replace(',', '', trim($_POST['monto_acumulado'] ?? '0'));
        $monto_acumulado = floatval($monto_raw);
        $nota = trim($_POST['nota'] ?? 'Cierre consolidado del día con comprobantes para validación.');
        $fecha = date('Y-m-d');
        $hora = date('H:i');

        $cierres = obtenerCierres();
        $nuevoId = count($cierres) > 0 ? (max(array_column($cierres, 'id')) + 1) : 1;

        $nuevoCierre = [
            'id' => $nuevoId,
            'asesor' => $asesor,
            'sucursal' => $sucursal,
            'fecha' => $fecha,
            'hora' => $hora,
            'total_ventas' => $total_ventas,
            'monto_acumulado' => $monto_acumulado,
            'nota' => $nota,
            'estado' => 'Pendiente',
            'validador' => null,
            'fecha_auditoria' => null
        ];

        array_unshift($cierres, $nuevoCierre);
        guardarCierres($cierres);

        // Notificación automática en el chat para Reportería (Nayeli)
        $montoFmt = number_format($monto_acumulado, 2);
        agregarMensajeChat([
            'asesor' => $asesor,
            'sucursal' => $sucursal,
            'remitente' => $asesor,
            'rol' => 'Ventas',
            'mensaje' => "📊 CIERRE DE VENTAS DEL DÍA: {$asesor} ({$sucursal}) ha enviado el cuadre de caja con {$total_ventas} ventas por un total de S/ {$montoFmt}. Nota: {$nota}",
            'hora' => $hora,
            'fecha' => $fecha,
            'timestamp' => time(),
            'tipo' => 'cierre_notif',
            'cierre_data' => $nuevoCierre
        ]);

        echo json_encode([
            'success' => true,
            'mensaje' => 'Cierre del día registrado y enviado a Reportería exitosamente.',
            'cierre' => $nuevoCierre
        ]);
        exit;
    }

    // 14. APROBAR CUADRE DE CIERRE DIARIO (DESDE REPORTERÍA)
    if ($action === 'aprobar_cierre') {
        header('Content-Type: application/json');
        $cierre_id = intval($_POST['cierre_id'] ?? 0);
        $validador = $_POST['validador'] ?? 'Nayeli (Reportería)';
        $fecha = date('Y-m-d H:i:s');

        $cierres = obtenerCierres();
        $cierreActualizado = null;
        foreach ($cierres as &$c) {
            if ($c['id'] == $cierre_id) {
                $c['estado'] = 'Aprobado';
                $c['validador'] = $validador;
                $c['fecha_auditoria'] = $fecha;
                $cierreActualizado = $c;
                break;
            }
        }
        guardarCierres($cierres);

        if ($cierreActualizado) {
            $montoFmt = number_format($cierreActualizado['monto_acumulado'], 2);
            agregarMensajeChat([
                'asesor' => $cierreActualizado['asesor'],
                'sucursal' => $cierreActualizado['sucursal'],
                'remitente' => 'Nayeli',
                'rol' => 'Reportería',
                'mensaje' => "✅ CUADRE DE CAJA APROBADO: El cierre de ventas del día de {$cierreActualizado['sucursal']} ({$cierreActualizado['asesor']}) por S/ {$montoFmt} ha sido auditado y aprobado sin diferencias. Caja cuadrada.",
                'hora' => date('H:i'),
                'fecha' => date('Y-m-d'),
                'timestamp' => time(),
                'tipo' => 'cierre_aprobado',
                'cierre_data' => $cierreActualizado
            ]);
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Cuadre de ventas del día auditado y aprobado exitosamente.',
            'cierre' => $cierreActualizado
        ]);
        exit;
    }
}


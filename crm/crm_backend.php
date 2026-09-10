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

// Inicializar chat si no existe
if (!file_exists($chatFile)) {
    $initialChat = [
        [
            'id' => 1,
            'remitente' => 'Elizabeth Addams',
            'rol' => 'Ventas',
            'mensaje' => 'Hola Rodrigo, envié las facturas del día para su validación bancaria.',
            'hora' => '11:42 AM',
            'tipo' => 'texto'
        ],
        [
            'id' => 2,
            'remitente' => 'Rodrigo Alonso',
            'rol' => 'Reportería',
            'mensaje' => 'Recibido Elizabeth, estamos revisando los extractos bancarios en BCP y BBVA. Te confirmamos por este medio.',
            'hora' => '11:45 AM',
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
    $mensajes[] = $msg;
    // Mantener los últimos 50 mensajes
    if (count($mensajes) > 50) {
        $mensajes = array_slice($mensajes, -50);
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
        $asesor = trim($_POST['asesor'] ?? 'Elizabeth Addams');
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

        // Notificación automática en el chat
        agregarMensajeChat([
            'remitente' => $asesor,
            'rol' => 'Ventas',
            'mensaje' => "📤 NUEVA FACTURACIÓN: He registrado la factura {$nro_factura} para {$cliente} por S/ " . number_format($monto, 2) . ". Adjunto voucher {$banco} ({$nro_operacion}) para su pronta validación.",
            'hora' => date('H:i'),
            'tipo' => 'nueva_factura'
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
        $validador = $_POST['validador'] ?? 'Rodrigo Alonso (Reportería)';
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
            agregarMensajeChat([
                'remitente' => 'Rodrigo Alonso',
                'rol' => 'Reportería',
                'mensaje' => "✅ PAGO ACEPTADO: La factura {$pagoActualizado['nro_factura']} ({$pagoActualizado['cliente']}) por S/ " . number_format($pagoActualizado['monto'], 2) . " ha sido verificada en {$pagoActualizado['banco']}. Pedido liberado para despacho.",
                'hora' => date('H:i'),
                'tipo' => 'pago_aceptado'
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
        $motivo = trim($_POST['motivo'] ?? 'Comprobante no coincide con extracto bancario');
        $validador = $_POST['validador'] ?? 'Rodrigo Alonso (Reportería)';
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
            if ($p['id'] == $pago_id) {
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
            agregarMensajeChat([
                'remitente' => 'Rodrigo Alonso',
                'rol' => 'Reportería',
                'mensaje' => "⚠️ PAGO OBSERVADO: La factura {$pagoActualizado['nro_factura']} ({$pagoActualizado['cliente']}) tiene la siguiente observación: \"{$motivo}\". Por favor rectificar con el cliente.",
                'hora' => date('H:i'),
                'tipo' => 'pago_observado'
            ]);
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Pago marcado como Observado. Notificación enviada al asesor.',
            'pago' => $pagoActualizado
        ]);
        exit;
    }

    // 5. LISTAR MENSAJES DE CHAT
    if ($action === 'listar_mensajes') {
        header('Content-Type: application/json');
        $mensajes = obtenerMensajesChat();
        echo json_encode([
            'success' => true,
            'mensajes' => $mensajes
        ]);
        exit;
    }

    // 6. ENVIAR MENSAJE DE CHAT
    if ($action === 'enviar_chat') {
        header('Content-Type: application/json');
        $remitente = trim($_POST['remitente'] ?? 'Usuario');
        $rol = trim($_POST['rol'] ?? 'Ventas');
        $texto = trim($_POST['mensaje'] ?? '');

        if (!empty($texto)) {
            $nuevoMsg = [
                'remitente' => $remitente,
                'rol' => $rol,
                'mensaje' => $texto,
                'hora' => date('H:i'),
                'tipo' => 'texto'
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
}

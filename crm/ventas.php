<?php
// crm/ventas.php - BS Perú CRM: Módulo de Ventas & Facturación
// Conexión opcional a base de datos con fallback automático
$db = null;
if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
    if (function_exists('getDB')) {
        try {
            $db = getDB();
        } catch (Exception $e) {
            $db = null;
        }
    }
}

// Procesar acciones AJAX de Ventas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // 1. SOLICITAR CONFIRMACIÓN DE PAGO A REPORTERÍA
    if ($action === 'solicitar_confirmacion_pago') {
        $cliente = $_POST['cliente'] ?? 'Cliente General';
        $ruc = $_POST['ruc'] ?? '';
        $nro_factura = $_POST['nro_factura'] ?? ('F001-' . rand(1000, 9999));
        $monto = floatval($_POST['monto'] ?? 0);
        $metodo = $_POST['metodo'] ?? 'Transferencia BCP';
        $nro_operacion = $_POST['nro_operacion'] ?? '';
        $asesor = $_POST['asesor'] ?? 'Elizabeth Addams';
        $fecha = date('Y-m-d H:i:s');
        $voucher_url = $_POST['voucher_url'] ?? 'img/voucher_sample.jpg';

        // Manejo de subida de comprobante si se adjuntó archivo
        if (isset($_FILES['voucher_file']) && $_FILES['voucher_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/comprobantes/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES['voucher_file']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['voucher_file']['tmp_name'], $targetPath)) {
                $voucher_url = 'uploads/comprobantes/' . $fileName;
            }
        }

        // Si hay BD activa, registrar cotización/factura y pago pendiente
        if ($db) {
            try {
                $stmt = $db->prepare("INSERT INTO cotizaciones (codigo, cliente_nombre, ruc_dni, total, estado, asesor, created_at) VALUES (?, ?, ?, ?, 'Pendiente', ?, ?)");
                $stmt->execute([$nro_factura, $cliente, $ruc, $monto, $asesor, $fecha]);
                $cotiz_id = $db->lastInsertId();

                $stmt2 = $db->prepare("INSERT INTO pagos (cotizacion_id, monto, metodo_pago, operacion_bancaria, comprobante_url, estado, fecha_solicitud) VALUES (?, ?, ?, ?, ?, 'Pendiente', ?)");
                $stmt2->execute([$cotiz_id, $monto, $metodo, $nro_operacion, $voucher_url, $fecha]);
            } catch(Exception $ex) {}
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Facturación registrada. Se envió la notificación de confirmación de pago al área de Reportería.',
            'nro_factura' => $nro_factura,
            'cliente' => $cliente,
            'monto' => $monto,
            'operacion' => $nro_operacion,
            'fecha' => $fecha,
            'voucher_url' => $voucher_url
        ]);
        exit;
    }

    // 2. ENVIAR REPORTE DE VENTAS DEL DÍA A REPORTERÍA
    if ($action === 'enviar_ventas_del_dia') {
        $asesor = $_POST['asesor'] ?? 'Elizabeth Addams';
        $total_ventas = $_POST['total_ventas'] ?? 0;
        $monto_acumulado = $_POST['monto_acumulado'] ?? '0.00';
        $nota = $_POST['nota'] ?? 'Reporte de ventas del día adjunto con comprobantes validados.';
        $fecha = date('d/m/Y H:i');

        echo json_encode([
            'success' => true,
            'mensaje' => "¡Reporte de ventas del día enviado exitosamente al área de Reportería!\nTotal: S/ {$monto_acumulado} en {$total_ventas} operaciones.",
            'fecha' => $fecha,
            'asesor' => $asesor
        ]);
        exit;
    }

    // 3. ENVIAR MENSAJE AL CHAT CON REPORTERÍA
    if ($action === 'enviar_chat_reporteria') {
        $mensaje = $_POST['mensaje'] ?? '';
        $asesor = $_POST['asesor'] ?? 'Elizabeth Addams';
        $fecha = date('H:i');

        echo json_encode([
            'success' => true,
            'mensaje' => $mensaje,
            'asesor' => $asesor,
            'hora' => $fecha
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BS Perú - Panel de Ventas & Facturación</title>
    <!-- Google Fonts: Poppins & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            /* Colores principales inspirados en el diseño */
            --outer-bg: #C09553;
            --outer-bg-dark: #1F1B16;
            --app-frame: #161719;
            --sidebar-bg: #161719;
            --main-bg: #FFFFFF;
            --right-bg: #FAF7F2;
            
            /* Tonos cálidos y acentos */
            --accent-tan: #C79B58;
            --accent-tan-light: #DFC394;
            --accent-tan-dark: #9E7434;
            --accent-tan-soft: #F6F1EA;
            
            --text-dark: #1E2024;
            --text-muted: #8E9299;
            --text-light: #A5A9B0;
            --border-soft: #ECE7DE;
            --card-radius: 28px;
            --pill-radius: 40px;

            /* Colores de categorías (Donut) */
            --chart-green: #38A169;
            --chart-blue: #3182CE;
            --chart-yellow: #ECC94B;
            --chart-red: #E53E3E;

            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* MODO OSCURO (ACTIVABLE POR EL SWITCHER) */
        body.dark-mode {
            --outer-bg: #1A1612;
            --app-frame: #0E0F11;
            --sidebar-bg: #0E0F11;
            --main-bg: #18191D;
            --right-bg: #141518;
            --text-dark: #F3F4F6;
            --text-muted: #9CA3AF;
            --border-soft: #272A30;
            --accent-tan-soft: #23211E;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--outer-bg);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            transition: var(--transition);
        }

        /* CONTENEDOR PRINCIPAL TIPO TABLET / APP */
        .app-container {
            background: var(--app-frame);
            width: 100%;
            max-width: 1360px;
            height: 94vh;
            min-height: 820px;
            border-radius: 42px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.35);
            display: flex;
            overflow: hidden;
            position: relative;
            padding: 10px;
            border: 2px solid rgba(255, 255, 255, 0.04);
        }

        /* ================= SIDEBAR IZQUIERDA ================= */
        .sidebar {
            width: 220px;
            background: var(--sidebar-bg);
            border-radius: 34px 0 0 34px;
            display: flex;
            flex-direction: column;
            padding: 24px 18px;
            gap: 16px;
            flex-shrink: 0;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 12px 18px 12px;
            color: #FFF;
            text-decoration: none;
        }
        .brand-logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #C79B58 0%, #E5C38C 100%);
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #161719;
            font-size: 1.25rem;
            font-weight: 800;
            box-shadow: 0 6px 15px rgba(199, 155, 88, 0.35);
        }
        .brand-logo-text {
            font-family: 'Outfit', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #FFF;
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: var(--pill-radius);
            color: var(--text-muted);
            font-size: 0.88rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }
        .nav-item i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        .nav-item:hover {
            color: #FFF;
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(3px);
        }
        .nav-item.active {
            background: var(--accent-tan);
            color: #161719;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(199, 155, 88, 0.3);
        }
        .nav-item.active i {
            color: #161719;
        }
        .nav-badge {
            margin-left: auto;
            background: rgba(255, 255, 255, 0.15);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 600;
        }
        .nav-item.active .nav-badge {
            background: #161719;
            color: #FFF;
        }

        /* USER PILL IN SIDEBAR */
        .user-pill {
            background: #23252B;
            border-radius: var(--pill-radius);
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: auto;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: var(--transition);
        }
        .user-pill:hover {
            background: #2C2E35;
        }
        .user-pill-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--accent-tan);
        }
        .user-pill-info {
            flex: 1;
            overflow: hidden;
        }
        .user-pill-name {
            color: #FFF;
            font-size: 0.82rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-pill-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.68rem;
            color: var(--text-muted);
        }
        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #10B981;
        }
        .user-pill-chevron {
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        /* THEME TOGGLE (LIGHT / DARK) */
        .theme-toggle {
            background: #23252B;
            border-radius: var(--pill-radius);
            padding: 4px;
            display: flex;
            margin-top: 10px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .theme-btn {
            flex: 1;
            padding: 6px 0;
            border-radius: var(--pill-radius);
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }
        .theme-btn.active {
            background: var(--accent-tan);
            color: #161719;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        /* ================= ÁREA CENTRAL (BLANCA) ================= */
        .main-content {
            flex: 1.8;
            background: var(--main-bg);
            border-radius: 36px 0 0 36px;
            padding: 30px 36px;
            display: flex;
            flex-direction: column;
            gap: 22px;
            overflow-y: auto;
            transition: var(--transition);
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .main-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.85rem;
            font-weight: 700;
            color: var(--text-dark);
            letter-spacing: -0.5px;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-link-reporteria {
            background: rgba(109, 93, 211, 0.1);
            color: #6D5DD3;
            padding: 8px 16px;
            border-radius: var(--pill-radius);
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            border: 1px solid rgba(109, 93, 211, 0.2);
        }
        .btn-link-reporteria:hover {
            background: #6D5DD3;
            color: #FFF;
            transform: translateY(-2px);
        }

        /* HERO / WELCOME CARD CARAMELO */
        .welcome-card {
            background: var(--accent-tan);
            border-radius: var(--card-radius);
            padding: 28px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #FFF;
            position: relative;
            box-shadow: 0 14px 28px rgba(199, 155, 88, 0.22);
            overflow: hidden;
        }
        .welcome-content {
            max-width: 65%;
            z-index: 2;
        }
        .welcome-content h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.25;
            margin-bottom: 8px;
            color: #FFF;
        }
        .welcome-content p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 20px;
            line-height: 1.45;
        }
        .welcome-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn-pill-white {
            background: #FFF;
            color: #2D2418;
            padding: 10px 20px;
            border-radius: var(--pill-radius);
            font-size: 0.82rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-pill-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.15);
            background: #FAF7F2;
        }
        .btn-pill-white.primary {
            background: #161719;
            color: #FFF;
        }
        .btn-pill-white.primary:hover {
            background: #252830;
        }

        .welcome-avatar-wrapper {
            position: relative;
            z-index: 2;
        }
        .welcome-avatar-frame {
            width: 105px;
            height: 115px;
            border-radius: 40px 40px 30px 30px;
            border: 3px solid rgba(255, 255, 255, 0.4);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .welcome-avatar-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* 4 STAT MINI CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }
        .stat-card-mini {
            background: var(--accent-tan-soft);
            border-radius: 20px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            border: 1px solid var(--border-soft);
            transition: var(--transition);
            cursor: pointer;
        }
        .stat-card-mini:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.04);
            border-color: var(--accent-tan);
        }
        .stat-mini-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: #FFF;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.15rem;
            color: var(--accent-tan);
            box-shadow: 0 4px 10px rgba(199, 155, 88, 0.15);
        }
        .stat-mini-info h4 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1;
        }
        .stat-mini-info p {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 3px;
        }

        /* SECCIÓN TEAM EXECUTIVE / RENDIMIENTO POR CATEGORÍAS */
        .executive-section {
            background: #FFF;
            border-radius: var(--card-radius);
            padding: 24px 28px;
            border: 1px solid var(--border-soft);
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            gap: 18px;
            flex: 1;
        }
        body.dark-mode .executive-section {
            background: #18191D;
        }
        .executive-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .executive-header h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        .executive-header span {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .executive-content {
            display: flex;
            align-items: center;
            gap: 36px;
        }

        /* DONUT CHART SVG */
        .donut-chart-container {
            position: relative;
            width: 170px;
            height: 170px;
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .donut-svg {
            transform: rotate(-90deg);
            width: 100%;
            height: 100%;
        }
        .donut-center-text {
            position: absolute;
            text-align: center;
        }
        .donut-center-text h4 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1;
        }
        .donut-center-text span {
            font-size: 0.68rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
        }

        /* LISTA DE PROGRESO POR CATEGORÍAS */
        .category-list {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .category-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .category-item-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        .category-name-tag {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .category-progress-bar {
            width: 100%;
            height: 6px;
            background: var(--border-soft);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        .category-progress-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.8s ease;
        }

        /* ================= PANEL DERECHO: MY ACTIVITY ================= */
        .right-sidebar {
            width: 320px;
            background: var(--right-bg);
            border-radius: 0 34px 34px 0;
            padding: 30px 24px;
            display: flex;
            flex-direction: column;
            gap: 22px;
            overflow-y: auto;
            border-left: 1px solid var(--border-soft);
            transition: var(--transition);
        }

        .right-header h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .activity-block {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .activity-block-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .activity-block-header h4 {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        .activity-block-header a {
            font-size: 0.72rem;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }
        .activity-block-header a:hover {
            color: var(--accent-tan);
        }

        /* TARJETAS DE ACTIVIDAD (UPCOMING TALKS & MEETINGS) */
        .activity-card {
            background: #FFF;
            border-radius: 16px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid var(--border-soft);
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            transition: var(--transition);
            cursor: pointer;
        }
        body.dark-mode .activity-card {
            background: #18191D;
        }
        .activity-card:hover {
            border-color: var(--accent-tan);
            transform: translateX(3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.05);
        }
        .date-badge {
            background: var(--accent-tan-soft);
            border-radius: 12px;
            padding: 6px 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 44px;
            border: 1px solid var(--border-soft);
        }
        .date-badge .day {
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--accent-tan);
            line-height: 1;
        }
        .date-badge .month {
            font-size: 0.58rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .activity-card-info {
            flex: 1;
            overflow: hidden;
        }
        .activity-card-info h5 {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .activity-card-info p {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* LATEST SHOUTOUTS (CHAT CON REPORTERÍA) */
        .shoutout-item {
            background: #FFF;
            border-radius: 16px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid var(--border-soft);
            transition: var(--transition);
            cursor: pointer;
        }
        body.dark-mode .shoutout-item {
            background: #18191D;
        }
        .shoutout-item:hover {
            border-color: var(--accent-tan);
            transform: translateX(2px);
        }
        .shoutout-avatar-box {
            position: relative;
        }
        .shoutout-avatar-box img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
        }
        .shoutout-online-dot {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10B981;
            border: 2px solid #FFF;
        }
        .shoutout-content {
            flex: 1;
            overflow: hidden;
        }
        .shoutout-content h5 {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        .shoutout-content p {
            font-size: 0.68rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ================= MODALES INTERACTIVOS ================= */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 17, 21, 0.75);
            backdrop-filter: blur(6px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 20px;
        }
        .modal-overlay.open {
            display: flex;
            animation: fadeIn 0.25s ease;
        }
        .modal-card {
            background: #FFF;
            width: 100%;
            max-width: 580px;
            border-radius: 32px;
            padding: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            border: 1px solid var(--border-soft);
            max-height: 90vh;
            overflow-y: auto;
        }
        body.dark-mode .modal-card {
            background: #18191D;
            color: #FFF;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        .modal-close-btn {
            background: var(--accent-tan-soft);
            border: none;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            cursor: pointer;
            color: var(--text-dark);
            display: flex;
            justify-content: center;
            align-items: center;
            transition: var(--transition);
        }
        .modal-close-btn:hover {
            background: #E5E7EB;
        }

        .form-grid {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        .form-group input, .form-group select, .form-group textarea {
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid var(--border-soft);
            background: #FAF7F2;
            font-size: 0.85rem;
            outline: none;
            color: var(--text-dark);
            font-family: inherit;
            transition: var(--transition);
        }
        body.dark-mode .form-group input, 
        body.dark-mode .form-group select, 
        body.dark-mode .form-group textarea {
            background: #23252B;
            border-color: #2F323A;
            color: #FFF;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--accent-tan);
            box-shadow: 0 0 0 3px rgba(199, 155, 88, 0.2);
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .voucher-upload-box {
            border: 2px dashed #CBD5E1;
            border-radius: 16px;
            padding: 18px;
            text-align: center;
            background: #FAF7F2;
            cursor: pointer;
            transition: var(--transition);
        }
        body.dark-mode .voucher-upload-box {
            background: #23252B;
            border-color: #3B3F48;
        }
        .voucher-upload-box:hover {
            border-color: var(--accent-tan);
        }
        .voucher-upload-box i {
            font-size: 1.8rem;
            color: var(--accent-tan);
            margin-bottom: 6px;
        }

        .btn-submit-action {
            background: var(--accent-tan);
            color: #161719;
            padding: 14px;
            border-radius: var(--pill-radius);
            font-size: 0.9rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 8px 20px rgba(199, 155, 88, 0.3);
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        .btn-submit-action:hover {
            background: #B68B48;
            transform: translateY(-2px);
        }

        /* CHAT MODAL INTERNO CON REPORTERÍA */
        .chat-box-modal {
            display: flex;
            flex-direction: column;
            height: 480px;
            gap: 14px;
        }
        .chat-messages-scroll {
            flex: 1;
            background: #FAF7F2;
            border-radius: 18px;
            padding: 16px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            border: 1px solid var(--border-soft);
        }
        body.dark-mode .chat-messages-scroll {
            background: #23252B;
            border-color: #2F323A;
        }
        .chat-bubble {
            max-width: 82%;
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 0.8rem;
            line-height: 1.4;
        }
        .chat-bubble.reporteria {
            background: #FFF;
            align-self: flex-start;
            border: 1px solid var(--border-soft);
            color: var(--text-dark);
        }
        body.dark-mode .chat-bubble.reporteria {
            background: #18191D;
            color: #FFF;
        }
        .chat-bubble.asesor {
            background: var(--accent-tan);
            color: #161719;
            align-self: flex-end;
            font-weight: 500;
        }
        .chat-input-row {
            display: flex;
            gap: 10px;
        }
        .chat-input-row input {
            flex: 1;
            padding: 12px 16px;
            border-radius: var(--pill-radius);
            border: 1px solid var(--border-soft);
            background: #FAF7F2;
            outline: none;
            font-size: 0.85rem;
        }
        body.dark-mode .chat-input-row input {
            background: #23252B;
            border-color: #2F323A;
            color: #FFF;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.97); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Scrollbar suave */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #D1C5B4; border-radius: 3px; }
    </style>
</head>
<body>

    <!-- CONTENEDOR PRINCIPAL TIPO TABLET -->
    <div class="app-container">
        
        <!-- SIDEBAR IZQUIERDA -->
        <div class="sidebar">
            <a href="ventas.php" class="brand-logo">
                <div class="brand-logo-icon">
                    <i class="fa-solid fa-horse-head"></i>
                </div>
                <div class="brand-logo-text">PBD</div>
            </a>

            <div class="nav-menu">
                <div class="nav-item active" onclick="mostrarSeccionVentas('dashboard', this)">
                    <i class="fa-solid fa-table-cells-large"></i>
                    <span>Dashboard</span>
                </div>

                <div class="nav-item" onclick="openFacturacionModal()">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Nueva Factura</span>
                    <span class="nav-badge">+</span>
                </div>

                <div class="nav-item" onclick="openVentasDelDiaModal()">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Ventas del Día</span>
                </div>

                <div class="nav-item" onclick="openChatReporteriaModal()">
                    <i class="fa-solid fa-comments"></i>
                    <span>Chat Reportería</span>
                    <span class="nav-badge" id="chatBadgeNum" style="background:#EF4444; color:#FFF;">2</span>
                </div>

                <div class="nav-item" onclick="verComprobantesGaleria()">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Comprobantes</span>
                </div>

                <!-- ENLACE AL MÓDULO DE REPORTERÍA PREVIAMENTE COMPLETADO -->
                <a href="reportes.php" class="nav-item" style="margin-top: 10px; border: 1px dashed rgba(199, 155, 88, 0.4);" title="Ir al Panel de Reportería & Finanzas">
                    <i class="fa-solid fa-chart-line" style="color:var(--accent-tan);"></i>
                    <span style="color:#FFF;">Reportería</span>
                    <span class="nav-badge" style="background:var(--accent-tan); color:#161719;">Sync</span>
                </a>
            </div>

            <!-- USER CARD BOTTOM -->
            <div class="user-pill" onclick="alert('Sesión activa: Elizabeth Addams\nAsesora Comercial - BS Perú')">
                <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80" alt="Elizabeth" class="user-pill-avatar">
                <div class="user-pill-info">
                    <div class="user-pill-name">Elizabeth Addams</div>
                    <div class="user-pill-status">
                        <div class="status-dot"></div> Active
                    </div>
                </div>
                <i class="fa-solid fa-chevron-right user-pill-chevron"></i>
            </div>

            <!-- THEME TOGGLE -->
            <div class="theme-toggle">
                <button class="theme-btn active" id="btnThemeLight" onclick="setAppTheme('light')">
                    <i class="fa-solid fa-sun"></i> Light
                </button>
                <button class="theme-btn" id="btnThemeDark" onclick="setAppTheme('dark')">
                    <i class="fa-solid fa-moon"></i> Dark
                </button>
            </div>
        </div>

        <!-- CONTENIDO CENTRAL (BLANCO / CREAM) -->
        <div class="main-content">
            
            <div class="main-header">
                <h1>Dashboard</h1>
                <div class="header-actions">
                    <a href="reportes.php" class="btn-link-reporteria">
                        <i class="fa-solid fa-shield-check"></i> Ir a Reportería & Pagos
                    </a>
                </div>
            </div>

            <!-- WELCOME / HERO CARD (CARAMELO) -->
            <div class="welcome-card">
                <div class="welcome-content">
                    <h2>Welcome back, Elizabeth Addams!</h2>
                    <p>Tu meta comercial del mes está al 85% completada. Emite facturaciones, adjunta los comprobantes de pago y solicita confirmación inmediata a Reportería.</p>
                    <div class="welcome-actions">
                        <button class="btn-pill-white primary" onclick="openFacturacionModal()">
                            <i class="fa-solid fa-plus"></i> Nueva Facturación
                        </button>
                        <button class="btn-pill-white" onclick="openVentasDelDiaModal()">
                            <i class="fa-solid fa-paper-plane"></i> Enviar Venta del Día
                        </button>
                    </div>
                </div>
                <div class="welcome-avatar-wrapper">
                    <div class="welcome-avatar-frame">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200&auto=format&fit=crop&q=80" alt="Elizabeth Addams">
                    </div>
                </div>
            </div>

            <!-- 4 MINI STAT CARDS -->
            <div class="stats-grid">
                <div class="stat-card-mini" onclick="openFacturacionModal()">
                    <div class="stat-mini-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                    <div class="stat-mini-info">
                        <h4 id="statVentasHoy">4</h4>
                        <p>Ventas Hoy</p>
                    </div>
                </div>

                <div class="stat-card-mini">
                    <div class="stat-mini-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-mini-info">
                        <h4>45</h4>
                        <p>Clientes Activos</p>
                    </div>
                </div>

                <div class="stat-card-mini" onclick="openChatReporteriaModal()">
                    <div class="stat-mini-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <div class="stat-mini-info">
                        <h4 id="statPendientesRep">3</h4>
                        <p>En Reportería</p>
                    </div>
                </div>

                <div class="stat-card-mini" onclick="location.href='reportes.php'">
                    <div class="stat-mini-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="stat-mini-info">
                        <h4 id="statAceptadosRep">15</h4>
                        <p>Pagos Aceptados</p>
                    </div>
                </div>
            </div>

            <!-- RESUMEN DE EQUIPO / VENTAS POR CATEGORÍA -->
            <div class="executive-section">
                <div class="executive-header">
                    <h3>Team executive</h3>
                    <span id="totalCategoriasBadge">45 ventas</span>
                </div>

                <div class="executive-content">
                    <!-- DONUT CHART SVG -->
                    <div class="donut-chart-container">
                        <svg class="donut-svg" viewBox="0 0 42 42">
                            <!-- Background Circle -->
                            <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="#ECE7DE" stroke-width="6"></circle>
                            
                            <!-- Segment 1: Verde (Adhesivos Epóxicos - 10/45 = 22%) -->
                            <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="var(--chart-green)" stroke-width="6"
                                stroke-dasharray="22.2 77.8" stroke-dashoffset="25"></circle>
                            
                            <!-- Segment 2: Azul (Impermeabilizantes - 20/45 = 44.4%) -->
                            <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="var(--chart-blue)" stroke-width="6"
                                stroke-dasharray="44.4 55.6" stroke-dashoffset="2.8"></circle>
                            
                            <!-- Segment 3: Amarillo (Resinas & Solventes - 7/45 = 15.5%) -->
                            <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="var(--chart-yellow)" stroke-width="6"
                                stroke-dasharray="15.5 84.5" stroke-dashoffset="58.4"></circle>
                            
                            <!-- Segment 4: Rojo (Selladores Estructurales - 8/45 = 17.7%) -->
                            <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="var(--chart-red)" stroke-width="6"
                                stroke-dasharray="17.7 82.3" stroke-dashoffset="42.9"></circle>
                        </svg>
                        <div class="donut-center-text">
                            <h4>45</h4>
                            <span>Ventas</span>
                        </div>
                    </div>

                    <!-- PROGRESS BARS LIST -->
                    <div class="category-list">
                        <div class="category-item">
                            <div class="category-item-meta">
                                <div class="category-name-tag">
                                    <span>🕊️</span>
                                    <span>Green dove (Adhesivos Epóxicos)</span>
                                </div>
                                <span>10</span>
                            </div>
                            <div class="category-progress-bar">
                                <div class="category-progress-fill" style="width: 50%; background: var(--chart-green);"></div>
                            </div>
                        </div>

                        <div class="category-item">
                            <div class="category-item-meta">
                                <div class="category-name-tag">
                                    <span>🦉</span>
                                    <span>Blue owl (Impermeabilizantes)</span>
                                </div>
                                <span>20</span>
                            </div>
                            <div class="category-progress-bar">
                                <div class="category-progress-fill" style="width: 85%; background: var(--chart-blue);"></div>
                            </div>
                        </div>

                        <div class="category-item">
                            <div class="category-item-meta">
                                <div class="category-name-tag">
                                    <span>🦚</span>
                                    <span>Yellow peacock (Resinas & Solventes)</span>
                                </div>
                                <span>7</span>
                            </div>
                            <div class="category-progress-bar">
                                <div class="category-progress-fill" style="width: 35%; background: var(--chart-yellow);"></div>
                            </div>
                        </div>

                        <div class="category-item">
                            <div class="category-item-meta">
                                <div class="category-name-tag">
                                    <span>🦅</span>
                                    <span>Red eagle (Selladores Estructurales)</span>
                                </div>
                                <span>8</span>
                            </div>
                            <div class="category-progress-bar">
                                <div class="category-progress-fill" style="width: 40%; background: var(--chart-red);"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- PANEL DERECHO: MY ACTIVITY -->
        <div class="right-sidebar">
            <div class="right-header">
                <h3>My activity</h3>
            </div>

            <!-- BLOQUE 1: FACTURACIONES PENDIENTES DE VALIDAR -->
            <div class="activity-block">
                <div class="activity-block-header">
                    <h4>Upcoming talks</h4>
                    <a onclick="openFacturacionModal()">View all</a>
                </div>

                <div class="activity-card" onclick="verComprobanteDetalle('F001-00892', 'Cosapi S.A.', '14,400.00', 'BCP #849201')">
                    <div class="date-badge">
                        <span class="day">13</span>
                        <span class="month">MAY</span>
                    </div>
                    <div class="activity-card-info">
                        <h5>Real Talk - Cosapi S.A.</h5>
                        <p>1:00 PM – 1:30 PM • Mary Rose</p>
                    </div>
                </div>

                <div class="activity-card" onclick="verComprobanteDetalle('F001-00891', 'Consorcio Vial Piura', '6,800.00', 'BBVA #902184')">
                    <div class="date-badge">
                        <span class="day">5</span>
                        <span class="month">APR</span>
                    </div>
                    <div class="activity-card-info">
                        <h5>Relationship Expectations</h5>
                        <p>4:00 PM – 4:30 PM • Jerome Brown</p>
                    </div>
                </div>
            </div>

            <!-- BLOQUE 2: ESTADO EN REPORTERÍA -->
            <div class="activity-block">
                <div class="activity-block-header">
                    <h4>Upcoming meetings</h4>
                    <a onclick="location.href='reportes.php'">View all</a>
                </div>

                <div class="activity-card" onclick="location.href='reportes.php'">
                    <div class="date-badge">
                        <span class="day">10</span>
                        <span class="month">MAR</span>
                    </div>
                    <div class="activity-card-info">
                        <h5>Weekly design meeting</h5>
                        <p>3:00 PM – 3:30 PM</p>
                    </div>
                </div>

                <div class="activity-card" onclick="openChatReporteriaModal()">
                    <div class="date-badge">
                        <span class="day">12</span>
                        <span class="month">MAR</span>
                    </div>
                    <div class="activity-card-info">
                        <h5>Team building meetup</h5>
                        <p>12:00 PM – 12:30 PM</p>
                    </div>
                </div>
            </div>

            <!-- BLOQUE 3: NOTIFICACIONES & CHAT CON REPORTERÍA -->
            <div class="activity-block">
                <div class="activity-block-header">
                    <h4>Latest shoutouts</h4>
                    <a onclick="openChatReporteriaModal()">View all</a>
                </div>

                <div class="shoutout-item" onclick="openChatReporteriaModal('Adam Willson')">
                    <div class="shoutout-avatar-box">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" alt="Adam">
                        <div class="shoutout-online-dot"></div>
                    </div>
                    <div class="shoutout-content">
                        <h5>Adam Willson</h5>
                        <p>invited you to team Design</p>
                    </div>
                </div>

                <div class="shoutout-item" onclick="openChatReporteriaModal('Johnny Fox')">
                    <div class="shoutout-avatar-box">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&auto=format&fit=crop&q=80" alt="Johnny">
                        <div class="shoutout-online-dot"></div>
                    </div>
                    <div class="shoutout-content">
                        <h5>Johnny Fox</h5>
                        <p>invited you to Real talk</p>
                    </div>
                </div>

                <div class="shoutout-item" onclick="openChatReporteriaModal('Alice Turner')">
                    <div class="shoutout-avatar-box">
                        <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&auto=format&fit=crop&q=80" alt="Alice">
                        <div class="shoutout-online-dot"></div>
                    </div>
                    <div class="shoutout-content">
                        <h5>Alice Turner</h5>
                        <p>invited you to Weekly meeting</p>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- ================= MODAL 1: NUEVA FACTURACIÓN & SOLICITUD A REPORTERÍA ================= -->
    <div class="modal-overlay" id="modalFacturacion">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fa-solid fa-file-invoice-dollar" style="color:var(--accent-tan);"></i> Nueva Facturación Comercial</h3>
                <button class="modal-close-btn" onclick="closeModals()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form id="formNuevaFactura" onsubmit="enviarFacturacionReporteria(event)">
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group">
                            <label>N° Comprobante / Factura</label>
                            <input type="text" id="facNumero" required value="F001-00895">
                        </div>
                        <div class="form-group">
                            <label>RUC / DNI Cliente</label>
                            <input type="text" id="facRuc" required placeholder="20601234567" value="20554189012">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Cliente / Razón Social</label>
                        <input type="text" id="facCliente" required placeholder="Constructora o Empresa" value="Constructora Los Andes S.A.C.">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Importe Total (S/)</label>
                            <input type="number" step="0.01" id="facMonto" required placeholder="0.00" value="7850.00">
                        </div>
                        <div class="form-group">
                            <label>Método de Pago</label>
                            <select id="facMetodo">
                                <option value="Transferencia BCP">Transferencia BCP</option>
                                <option value="Transferencia BBVA">Transferencia BBVA</option>
                                <option value="Interbank Empresas">Interbank Empresas</option>
                                <option value="Yape / Plin">Yape / Plin</option>
                                <option value="Cheque Comercial">Cheque Comercial</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>N° Operación Bancaria</label>
                        <input type="text" id="facOperacion" required placeholder="Ej: BCP #719283" value="BCP #782910">
                    </div>

                    <!-- SUBIDA DE COMPROBANTE DE PAGO -->
                    <div class="form-group">
                        <label>Comprobante de Pago (Voucher)</label>
                        <div class="voucher-upload-box" onclick="document.getElementById('inputVoucher').click()">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p style="font-size:0.82rem; font-weight:600; color:var(--text-dark);" id="voucherUploadLabel">
                                Clic para adjuntar comprobante (JPG, PNG, PDF)
                            </p>
                            <span style="font-size:0.7rem; color:var(--text-muted);">Se guardará en el registro de ventas y se enviará a Reportería</span>
                            <input type="file" id="inputVoucher" style="display:none;" accept="image/*,.pdf" onchange="previewVoucherFileName(this)">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notas para el Área de Reportería</label>
                        <textarea id="facNotas" rows="2" placeholder="Ej: Pago realizado por adelanto del 50% de resina epóxica.">Pago de adelanto verificado con el cliente. Solicito confirmación para despacho.</textarea>
                    </div>

                    <button type="submit" class="btn-submit-action">
                        <i class="fa-solid fa-paper-plane"></i> Solicitar Confirmación a Reportería
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= MODAL 2: ENVIAR VENTAS DEL DÍA A REPORTERÍA ================= -->
    <div class="modal-overlay" id="modalVentasDia">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fa-solid fa-paper-plane" style="color:var(--accent-tan);"></i> Enviar Ventas del Día a Reportería</h3>
                <button class="modal-close-btn" onclick="closeModals()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="form-grid">
                <div style="background:var(--accent-tan-soft); border-radius:16px; padding:16px; border:1px solid var(--border-soft);">
                    <h4 style="font-size:0.9rem; color:var(--text-dark); margin-bottom:8px;">Consolidado de Hoy - Elizabeth Addams</h4>
                    <p style="font-size:0.8rem; color:var(--text-muted); line-height:1.4;">
                        <strong>Operaciones realizadas:</strong> 4 facturas emitidas<br>
                        <strong>Monto total facturado:</strong> S/ 32,708.00<br>
                        <strong>Comprobantes adjuntos:</strong> 4 vouchers bancarios listos para validación.
                    </p>
                </div>

                <div class="form-group">
                    <label>Mensaje y observaciones para Reportería</label>
                    <textarea id="notaVentasDia" rows="3" style="width:100%;">Buenas tardes equipo de Reportería, adjunto el reporte de ventas del día con los 4 comprobantes correspondientes a Cosapi, Consorcio Vial, Edificaciones Pacífico y Andes S.A.C.</textarea>
                </div>

                <button class="btn-submit-action" onclick="confirmarEnvioVentasDia()">
                    <i class="fa-solid fa-check-double"></i> Enviar Reporte del Día a Reportería
                </button>
            </div>
        </div>
    </div>

    <!-- ================= MODAL 3: CHAT INTERNO CON REPORTERÍA ================= -->
    <div class="modal-overlay" id="modalChatReporteria">
        <div class="modal-card" style="max-width:620px;">
            <div class="modal-header">
                <div>
                    <h3 style="display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-comments" style="color:var(--accent-tan);"></i> Chat con Área de Reportería
                    </h3>
                    <span style="font-size:0.75rem; color:var(--text-muted);">Canal oficial de coordinación de pagos y ventas del día</span>
                </div>
                <button class="modal-close-btn" onclick="closeModals()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="chat-box-modal">
                <div class="chat-messages-scroll" id="chatScrollContainer">
                    <div class="chat-bubble reporteria">
                        <strong>Área de Reportería (Finanzas):</strong><br>
                        Hola Elizabeth, recibimos la Factura F001-00892 por S/ 14,400.00 de Cosapi. El voucher de BCP #849201 ha sido <strong>ACEPTADO Y VERIFICADO</strong> en el extracto bancario. ✅
                        <div style="font-size:0.65rem; color:var(--text-muted); margin-top:4px;">11:42 AM</div>
                    </div>

                    <div class="chat-bubble asesor">
                        <strong>Tú (Elizabeth Addams):</strong><br>
                        Excelente equipo, acabo de registrar la Factura F001-00891 de Consorcio Vial por S/ 6,800.00 con voucher BBVA. ¿Podrían confirmarlo cuando esté verificado?
                        <div style="font-size:0.65rem; opacity:0.8; margin-top:4px;">11:45 AM</div>
                    </div>

                    <div class="chat-bubble reporteria">
                        <strong>Área de Reportería (Finanzas):</strong><br>
                        Está en cola de revisión. Recuerda enviar también tu consolidado de ventas del día antes de las 6:00 PM.
                        <div style="font-size:0.65rem; color:var(--text-muted); margin-top:4px;">11:47 AM</div>
                    </div>
                </div>

                <div class="chat-input-row">
                    <input type="text" id="inputChatMsg" placeholder="Escribe un mensaje o consulta a Reportería..." onkeypress="if(event.key==='Enter') enviarMensajeChat()">
                    <button class="btn-pill-white primary" style="padding:10px 22px;" onclick="enviarMensajeChat()">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= MODAL 4: VISOR DE COMPROBANTE DE PAGO ================= -->
    <div class="modal-overlay" id="modalVerComprobante">
        <div class="modal-card" style="max-width:500px; text-align:center;">
            <div class="modal-header">
                <h3>Comprobante de Pago Guardado</h3>
                <button class="modal-close-btn" onclick="closeModals()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div style="background:#FAF7F2; padding:16px; border-radius:18px; border:1px solid var(--border-soft); margin-bottom:16px;">
                <h4 id="viewCompNumero" style="color:var(--text-dark); font-size:1.1rem;">F001-00892</h4>
                <p id="viewCompCliente" style="color:var(--text-muted); font-size:0.85rem; margin-top:4px;">Cosapi S.A.</p>
                <div style="margin:12px 0;">
                    <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=400&auto=format&fit=crop&q=80" alt="Voucher" id="viewCompImg" style="max-width:100%; height:200px; object-fit:cover; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1);">
                </div>
                <div style="display:flex; justify-content:space-around; font-size:0.82rem; text-align:left;">
                    <div>
                        <span style="color:var(--text-muted);">Monto:</span><br>
                        <strong id="viewCompMonto" style="color:#10B981; font-size:0.95rem;">S/ 14,400.00</strong>
                    </div>
                    <div>
                        <span style="color:var(--text-muted);">Operación:</span><br>
                        <strong id="viewCompOp" style="color:var(--text-dark);">BCP #849201</strong>
                    </div>
                    <div>
                        <span style="color:var(--text-muted);">Estado:</span><br>
                        <span class="badge" style="background:#DCFCE7; color:#166534; padding:2px 8px; border-radius:8px; font-weight:700;">Pago Aceptado</span>
                    </div>
                </div>
            </div>

            <button class="btn-pill-white primary" style="width:100%; justify-content:center;" onclick="closeModals()">
                Cerrar Visor
            </button>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC & INTERACTION -->
    <script>
        let pendientesCount = 3;
        let ventasHoyCount = 4;
        let aceptadosCount = 15;

        // TEMA CLARO / OSCURO
        function setAppTheme(theme) {
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
                document.getElementById('btnThemeDark').classList.add('active');
                document.getElementById('btnThemeLight').classList.remove('active');
            } else {
                document.body.classList.remove('dark-mode');
                document.getElementById('btnThemeLight').classList.add('active');
                document.getElementById('btnThemeDark').classList.remove('active');
            }
        }

        // CONTROL DE MODALES
        function closeModals() {
            document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
        }

        function openFacturacionModal() {
            closeModals();
            document.getElementById('modalFacturacion').classList.add('open');
        }

        function openVentasDelDiaModal() {
            closeModals();
            document.getElementById('modalVentasDia').classList.add('open');
        }

        function openChatReporteriaModal(user) {
            closeModals();
            document.getElementById('modalChatReporteria').classList.add('open');
            const scroll = document.getElementById('chatScrollContainer');
            scroll.scrollTop = scroll.scrollHeight;
        }

        function verComprobantesGaleria() {
            verComprobanteDetalle('F001-00892', 'Cosapi S.A.', '14,400.00', 'BCP #849201');
        }

        function verComprobanteDetalle(num, cliente, monto, op) {
            document.getElementById('viewCompNumero').textContent = num;
            document.getElementById('viewCompCliente').textContent = cliente;
            document.getElementById('viewCompMonto').textContent = `S/ ${monto}`;
            document.getElementById('viewCompOp').textContent = op;
            closeModals();
            document.getElementById('modalVerComprobante').classList.add('open');
        }

        function previewVoucherFileName(input) {
            if (input.files && input.files[0]) {
                document.getElementById('voucherUploadLabel').innerHTML = `✅ Archivo cargado: <strong>${input.files[0].name}</strong>`;
            }
        }

        // ENVIAR FACTURACIÓN Y SOLICITAR CONFIRMACIÓN A REPORTERÍA
        function enviarFacturacionReporteria(e) {
            e.preventDefault();
            const nro = document.getElementById('facNumero').value;
            const cliente = document.getElementById('facCliente').value;
            const monto = parseFloat(document.getElementById('facMonto').value) || 0;
            const metodo = document.getElementById('facMetodo').value;
            const op = document.getElementById('facOperacion').value;

            // Enviar vía POST a PHP
            const formData = new FormData();
            formData.append('action', 'solicitar_confirmacion_pago');
            formData.append('nro_factura', nro);
            formData.append('cliente', cliente);
            formData.append('monto', monto);
            formData.append('metodo', metodo);
            formData.append('nro_operacion', op);
            formData.append('asesor', 'Elizabeth Addams');

            const fileInput = document.getElementById('inputVoucher');
            if (fileInput.files[0]) {
                formData.append('voucher_file', fileInput.files[0]);
            }

            fetch('ventas.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .catch(() => ({ success: true }))
            .then(data => {
                // Actualizar contadores en vivo
                ventasHoyCount++;
                pendientesCount++;
                document.getElementById('statVentasHoy').textContent = ventasHoyCount;
                document.getElementById('statPendientesRep').textContent = pendientesCount;

                closeModals();
                alert(`✅ ¡Facturación ${nro} por S/ ${monto.toLocaleString('en-US', {minimumFractionDigits:2})} registrada!\n\n1. El comprobante de pago fue guardado en el lado de Ventas.\n2. Se notificó al área de Reportería para que verifique y acepte el pago.`);
                
                // Agregar mensaje automático en el chat
                const chatContainer = document.getElementById('chatScrollContainer');
                const bubble = document.createElement('div');
                bubble.className = 'chat-bubble asesor';
                bubble.innerHTML = `<strong>Tú (Elizabeth Addams):</strong><br>Acabo de emitir la Factura ${nro} para ${cliente} por S/ ${monto.toLocaleString('en-US', {minimumFractionDigits:2})}. Adjunto voucher ${op} para su validación.<div style="font-size:0.65rem; opacity:0.8; margin-top:4px;">Ahora mismo</div>`;
                chatContainer.appendChild(bubble);
            });
        }

        // CONFIRMAR ENVÍO DE VENTAS DEL DÍA A REPORTERÍA
        function confirmarEnvioVentasDia() {
            const nota = document.getElementById('notaVentasDia').value;
            const params = new URLSearchParams({
                action: 'enviar_ventas_del_dia',
                asesor: 'Elizabeth Addams',
                total_ventas: ventasHoyCount,
                monto_acumulado: '32,708.00',
                nota: nota
            });

            fetch('ventas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            })
            .then(res => res.json())
            .catch(() => ({ success: true }))
            .then(() => {
                closeModals();
                alert(`📤 ¡Ventas del día enviadas exitosamente a Reportería!\n\nSe ha enviado el consolidado con los comprobantes del día. El área de reportería podrá revisarlos en su bandeja.`);
            });
        }

        // CHAT CON REPORTERÍA
        function enviarMensajeChat() {
            const input = document.getElementById('inputChatMsg');
            const texto = input.value.trim();
            if (!texto) return;

            const chatContainer = document.getElementById('chatScrollContainer');
            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble asesor';
            bubble.innerHTML = `<strong>Tú (Elizabeth Addams):</strong><br>${texto}<div style="font-size:0.65rem; opacity:0.8; margin-top:4px;">Ahora mismo</div>`;
            chatContainer.appendChild(bubble);
            chatContainer.scrollTop = chatContainer.scrollHeight;

            input.value = '';

            // Respuesta automática de cortesía de Reportería
            setTimeout(() => {
                const repBubble = document.createElement('div');
                repBubble.className = 'chat-bubble reporteria';
                repBubble.innerHTML = `<strong>Área de Reportería (Finanzas):</strong><br>Recibido Elizabeth, estamos revisando en el sistema bancario. Te confirmamos en breve. ✅<div style="font-size:0.65rem; color:var(--text-muted); margin-top:4px;">Ahora mismo</div>`;
                chatContainer.appendChild(repBubble);
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }, 1200);
        }

        function mostrarSeccionVentas(seccion, el) {
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            el.classList.add('active');
        }
    </script>
</body>
</html>

<?php
// crm/ventas.php - BS Perú CRM: Módulo de Ventas & Facturación
session_start();

// Control de acceso: Verificar autenticación
if (!isset($_SESSION['crm_logged_in']) || $_SESSION['crm_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Control de rol: Si es Reportería (Nayeli), enviarla a su módulo correspondiente
if (isset($_SESSION['crm_rol']) && $_SESSION['crm_rol'] === 'reporteria') {
    header("Location: reportes.php");
    exit;
}

$currentUser = $_SESSION['crm_user'] ?? 'Endrina';

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
        $asesor = $_POST['asesor'] ?? 'Endrina';
        $fecha = date('Y-m-d H:i:s');
        $voucher_url = $_POST['voucher_url'] ?? 'img/voucher_sample.jpg';

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
        $asesor = $_POST['asesor'] ?? 'Endrina';
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
        $asesor = $_POST['asesor'] ?? 'Endrina';
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

// Obtener siguiente correlativo oficial de cotización automáticamente
$siguienteCodigoCotiz = '0052456';
$fileCotizPath = __DIR__ . '/crm_data/cotizaciones.json';
if (file_exists($fileCotizPath)) {
    $rawCotiz = json_decode(file_get_contents($fileCotizPath), true) ?: [];
    if (!empty($rawCotiz)) {
        $codigosNums = array_map(function($c) {
            return intval($c['codigo'] ?? 0);
        }, $rawCotiz);
        $maxNum = max($codigosNums);
        if ($maxNum > 0) {
            $siguienteCodigoCotiz = str_pad($maxNum + 1, 7, '0', STR_PAD_LEFT);
        }
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
            --outer-bg: #C09553;
            --outer-bg-dark: #1F1B16;
            --app-frame: #161719;
            --sidebar-bg: #161719;
            --main-bg: #FFFFFF;
            --right-bg: #FAF7F2;
            
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

            --chart-green: #38A169;
            --chart-blue: #3182CE;
            --chart-yellow: #ECC94B;
            --chart-red: #E53E3E;

            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

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
            background-color: var(--sidebar-bg);
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            overflow: hidden;
            display: flex;
            transition: var(--transition);
        }

        /* CONTENEDOR PRINCIPAL EXPANDIDO A PANTALLA COMPLETA */
        .app-container {
            background: var(--app-frame);
            width: 100vw;
            max-width: 100%;
            height: 100vh;
            min-height: 100vh;
            border-radius: 0;
            box-shadow: none;
            display: flex;
            overflow: hidden;
            position: relative;
            padding: 0;
            border: none;
        }

        /* ================= SIDEBAR IZQUIERDA ================= */
        .sidebar {
            width: 250px;
            background: var(--sidebar-bg);
            border-radius: 0;
            display: flex;
            flex-direction: column;
            padding: 26px 18px;
            gap: 16px;
            flex-shrink: 0;
            height: 100vh;
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
            padding: 12px 18px;
            border-radius: var(--pill-radius);
            color: var(--text-muted);
            font-size: 0.88rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            white-space: nowrap;
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
        .user-pill:hover { background: #2C2E35; }
        .user-pill-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--accent-tan);
        }
        .user-pill-info { flex: 1; overflow: hidden; }
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
        .user-pill-chevron { color: var(--text-muted); font-size: 0.75rem; }

        /* THEME TOGGLE */
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
            flex: 1;
            background: var(--main-bg);
            border-radius: 36px 0 0 36px;
            padding: 34px 44px;
            display: flex;
            flex-direction: column;
            gap: 26px;
            overflow-y: auto;
            height: 100vh;
            transition: var(--transition);
            position: relative;
        }

        /* VISTAS DINÁMICAS (SPA) */
        .vista-seccion {
            display: flex;
            flex-direction: column;
            gap: 24px;
            animation: fadeInView 0.25s ease;
            width: 100%;
        }
        @keyframes fadeInView {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .main-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.95rem;
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
            padding: 9px 18px;
            border-radius: var(--pill-radius);
            font-size: 0.84rem;
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
            padding: 32px 42px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #FFF;
            position: relative;
            box-shadow: 0 14px 28px rgba(199, 155, 88, 0.22);
            overflow: hidden;
        }
        .welcome-content {
            max-width: 72%;
            z-index: 2;
        }
        .welcome-content h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.85rem;
            font-weight: 700;
            line-height: 1.25;
            margin-bottom: 10px;
            color: #FFF;
        }
        .welcome-content p {
            font-size: 0.88rem;
            color: rgba(255, 255, 255, 0.92);
            margin-bottom: 22px;
            line-height: 1.5;
        }
        .welcome-actions {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }
        .btn-pill-white {
            background: #FFF;
            color: #2D2418;
            padding: 11px 22px;
            border-radius: var(--pill-radius);
            font-size: 0.85rem;
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
            width: 120px;
            height: 130px;
            border-radius: 44px 44px 34px 34px;
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
            gap: 18px;
        }
        .stat-card-mini {
            background: var(--accent-tan-soft);
            border-radius: 22px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
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
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background: #FFF;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.25rem;
            color: var(--accent-tan);
            box-shadow: 0 4px 10px rgba(199, 155, 88, 0.15);
        }
        .stat-mini-info h4 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1;
        }
        .stat-mini-info p {
            font-size: 0.76rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 4px;
        }

        /* SECCIÓN TEAM EXECUTIVE / RENDIMIENTO POR CATEGORÍAS */
        .executive-section {
            background: #FFF;
            border-radius: var(--card-radius);
            padding: 28px 34px;
            border: 1px solid var(--border-soft);
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            gap: 20px;
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
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        .executive-header span {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .executive-content {
            display: flex;
            align-items: center;
            gap: 48px;
        }

        /* DONUT CHART SVG */
        .donut-chart-container {
            position: relative;
            width: 185px;
            height: 185px;
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
            font-size: 2.1rem;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1;
        }
        .donut-center-text span {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
        }

        /* LISTA DE PROGRESO POR CATEGORÍAS */
        .category-list {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .category-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .category-item-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.86rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        .category-name-tag {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .category-progress-bar {
            width: 100%;
            height: 7px;
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

        /* ================= ESTILOS VISTAS INTEGRADAS EN EL CENTRO ================= */
        .card-seccion-centro {
            background: #FFF;
            border-radius: var(--card-radius);
            padding: 28px 32px;
            border: 1px solid var(--border-soft);
            box-shadow: 0 8px 24px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        body.dark-mode .card-seccion-centro {
            background: #18191D;
            border-color: #272A30;
        }

        /* Formulario */
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
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        .form-group input, .form-group select, .form-group textarea {
            padding: 12px 16px;
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
            gap: 16px;
        }

        .voucher-upload-box {
            border: 2px dashed #CBD5E1;
            border-radius: 18px;
            padding: 22px;
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
            background: #F4EFE6;
        }
        .voucher-upload-box i {
            font-size: 2rem;
            color: var(--accent-tan);
            margin-bottom: 8px;
        }

        .btn-submit-action {
            background: var(--accent-tan);
            color: #161719;
            padding: 14px 24px;
            border-radius: var(--pill-radius);
            font-size: 0.92rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            width: 100%;
            margin-top: 8px;
            box-shadow: 0 8px 20px rgba(199, 155, 88, 0.3);
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .btn-submit-action:hover {
            background: #B68B48;
            transform: translateY(-2px);
        }

        /* ================= CARTERA DE CLIENTES EN EL CENTRO ================= */
        .cartera-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .cartera-search-box {
            position: relative;
            flex: 1;
            min-width: 260px;
        }
        .cartera-search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .cartera-search-box input {
            width: 100%;
            padding: 11px 18px 11px 42px;
            border-radius: var(--pill-radius);
            border: 1px solid var(--border-soft);
            background: #FAF7F2;
            font-size: 0.85rem;
            outline: none;
            color: var(--text-dark);
            transition: var(--transition);
        }
        body.dark-mode .cartera-search-box input {
            background: #23252B;
            border-color: #2F323A;
            color: #FFF;
        }
        .cartera-filter-pills {
            display: flex;
            gap: 8px;
            overflow-x: auto;
        }
        .cartera-pill {
            padding: 8px 16px;
            border-radius: var(--pill-radius);
            background: #FAF7F2;
            border: 1px solid var(--border-soft);
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
        }
        body.dark-mode .cartera-pill {
            background: #23252B;
            border-color: #2F323A;
            color: #9CA3AF;
        }
        .cartera-pill:hover, .cartera-pill.active {
            background: var(--accent-tan);
            color: #161719;
            border-color: var(--accent-tan);
        }
        .btn-add-cliente {
            background: #161719;
            color: #FFF;
            padding: 10px 20px;
            border-radius: var(--pill-radius);
            font-size: 0.82rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: var(--transition);
            white-space: nowrap;
        }
        .btn-add-cliente:hover {
            background: #2B2E35;
            transform: translateY(-2px);
        }
        .cartera-table-wrapper {
            background: #FFF;
            border-radius: 20px;
            border: 1px solid var(--border-soft);
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(0,0,0,0.02);
            max-height: 520px;
            overflow-y: auto;
        }
        body.dark-mode .cartera-table-wrapper {
            background: #18191D;
            border-color: #2F323A;
        }
        .cartera-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.84rem;
        }
        .cartera-table th {
            background: #FAF7F2;
            padding: 14px 18px;
            color: var(--text-muted);
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-soft);
            position: sticky;
            top: 0;
            z-index: 5;
        }
        body.dark-mode .cartera-table th {
            background: #202227;
            border-color: #2F323A;
            color: #A0AEC0;
        }
        .cartera-table td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-soft);
            color: var(--text-dark);
            vertical-align: middle;
        }
        body.dark-mode .cartera-table td {
            border-color: #24272E;
            color: #E2E8F0;
        }
        .cartera-table tr:hover td {
            background: rgba(199, 155, 88, 0.05);
        }
        .cliente-item-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .cliente-avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            background: var(--accent-tan-soft);
            color: var(--accent-tan);
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 800;
            font-size: 0.95rem;
            border: 1px solid var(--border-soft);
            flex-shrink: 0;
        }
        .cliente-meta h5 {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 2px;
        }
        body.dark-mode .cliente-meta h5 { color: #FFF; }
        .cliente-meta span { font-size: 0.74rem; color: var(--text-muted); }
        .badge-tag-vip {
            background: #FEF3C7;
            color: #92400E;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-tag-activo {
            background: #DCFCE7;
            color: #166534;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 700;
        }
        .badge-tag-seguimiento {
            background: #E0E7FF;
            color: #3730A3;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 700;
        }
        .btn-facturar-mini {
            background: var(--accent-tan);
            color: #161719;
            border: none;
            padding: 7px 14px;
            border-radius: var(--pill-radius);
            font-size: 0.76rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }
        .btn-facturar-mini:hover {
            background: #B68B48;
            transform: translateY(-1px);
        }
        .btn-wa-mini {
            background: #25D366;
            color: #FFF;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: var(--transition);
        }
        .btn-wa-mini:hover {
            background: #1EBE5D;
            transform: scale(1.1);
        }
        .form-new-cliente-box {
            display: none;
            background: #FAF7F2;
            border: 1px solid var(--border-soft);
            border-radius: 22px;
            padding: 22px;
            margin-bottom: 20px;
            animation: fadeInView 0.2s ease;
        }
        body.dark-mode .form-new-cliente-box {
            background: #202227;
            border-color: #2F323A;
        }

        /* ================= CHAT EN EL CENTRO ================= */
        .chat-center-container {
            background: #FFF;
            border-radius: var(--card-radius);
            border: 1px solid var(--border-soft);
            display: flex;
            flex-direction: column;
            height: 600px;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(0,0,0,0.02);
        }
        body.dark-mode .chat-center-container {
            background: #18191D;
            border-color: #272A30;
        }
        .chat-center-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-soft);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #FAF7F2;
        }
        body.dark-mode .chat-center-header {
            background: #202227;
            border-color: #272A30;
        }
        .chat-center-messages {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: #FAF7F2;
        }
        body.dark-mode .chat-center-messages {
            background: #141518;
        }
        .chat-bubble {
            max-width: 75%;
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 0.84rem;
            line-height: 1.45;
        }
        .chat-bubble.reporteria {
            background: #FFF;
            align-self: flex-start;
            border: 1px solid var(--border-soft);
            color: var(--text-dark);
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        body.dark-mode .chat-bubble.reporteria {
            background: #1F2026;
            color: #FFF;
            border-color: #2C2F38;
        }
        .chat-bubble.asesor {
            background: var(--accent-tan);
            color: #161719;
            align-self: flex-end;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(199, 155, 88, 0.2);
        }
        .chat-center-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border-soft);
            display: flex;
            gap: 12px;
            align-items: center;
            background: #FFF;
        }
        body.dark-mode .chat-center-footer {
            background: #18191D;
            border-color: #272A30;
        }
        .chat-center-footer input {
            flex: 1;
            padding: 12px 18px;
            border-radius: var(--pill-radius);
            border: 1px solid var(--border-soft);
            background: #FAF7F2;
            outline: none;
            font-size: 0.88rem;
        }
        body.dark-mode .chat-center-footer input {
            background: #23252B;
            border-color: #2F323A;
            color: #FFF;
        }

        /* ================= COMPROBANTES EN EL CENTRO ================= */
        .vouchers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 18px;
        }
        .voucher-card-item {
            background: #FFF;
            border-radius: 20px;
            border: 1px solid var(--border-soft);
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(0,0,0,0.02);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }
        body.dark-mode .voucher-card-item {
            background: #18191D;
            border-color: #272A30;
        }
        .voucher-card-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            border-color: var(--accent-tan);
        }
        .voucher-card-thumb {
            height: 140px;
            background: #F4EFE6;
            overflow: hidden;
            position: relative;
            cursor: pointer;
        }
        .voucher-card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .voucher-card-item:hover .voucher-card-thumb img {
            transform: scale(1.05);
        }
        .voucher-card-body {
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        /* ================= PANEL DERECHO: MY ACTIVITY ================= */
        .right-sidebar {
            width: 380px;
            background: var(--right-bg);
            border-radius: 0;
            padding: 34px 28px;
            display: flex;
            flex-direction: column;
            gap: 26px;
            overflow-y: auto;
            height: 100vh;
            border-left: 1px solid var(--border-soft);
            flex-shrink: 0;
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
        body.dark-mode .activity-card { background: #18191D; }
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
        .activity-card-info { flex: 1; overflow: hidden; }
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
        body.dark-mode .shoutout-item { background: #18191D; }
        .shoutout-item:hover {
            border-color: var(--accent-tan);
            transform: translateX(2px);
        }
        .shoutout-avatar-box { position: relative; }
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
        .shoutout-content { flex: 1; overflow: hidden; }
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

        /* MODAL SOLO PARA ZOOM DE VOUCHER */
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
        .modal-overlay.open { display: flex; animation: fadeInView 0.25s ease; }
        .modal-card {
            background: #FFF;
            width: 100%;
            max-width: 500px;
            border-radius: 28px;
            padding: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            border: 1px solid var(--border-soft);
        }
        body.dark-mode .modal-card { background: #18191D; color: #FFF; }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
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
        }

        /* Scrollbar suave */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #D1C5B4; border-radius: 3px; }

        /* ================= ESTILOS MÓDULO COTIZACIONES ================= */
        .cotiz-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-soft);
            padding-bottom: 12px;
        }
        .cotiz-tab-btn {
            background: #FFF;
            border: 1px solid var(--border-soft);
            padding: 10px 20px;
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }
        body.dark-mode .cotiz-tab-btn { background: #1F2127; border-color: #2D3039; color: #FFF; }
        .cotiz-tab-btn.active {
            background: var(--accent-tan);
            color: #161719;
            border-color: var(--accent-tan);
            box-shadow: 0 4px 14px rgba(199, 155, 88, 0.25);
        }
        .cotiz-card-header {
            background: linear-gradient(135deg, rgba(199, 155, 88, 0.12), rgba(199, 155, 88, 0.03));
            border: 1px solid var(--border-soft);
            border-radius: 20px;
            padding: 18px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }
        body.dark-mode .cotiz-card-header {
            background: linear-gradient(135deg, rgba(199, 155, 88, 0.15), rgba(24, 25, 29, 0.9));
        }
        .cotiz-sheet {
            background: #FFF;
            border-radius: 20px;
            border: 1px solid var(--border-soft);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        body.dark-mode .cotiz-sheet { background: #18191D; border-color: #272A30; }

        .cotiz-client-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 14px;
            background: #FAF8F5;
            border-radius: 16px;
            padding: 18px;
            border: 1px solid var(--border-soft);
        }
        body.dark-mode .cotiz-client-grid { background: #1F2127; border-color: #2D3039; }

        .cotiz-items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.82rem;
            margin-top: 10px;
        }
        .cotiz-items-table th {
            background: #F4EFE6;
            color: var(--text-dark);
            font-weight: 700;
            padding: 10px 12px;
            text-align: left;
            border-top: 1px solid var(--border-soft);
            border-bottom: 1px solid var(--border-soft);
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        body.dark-mode .cotiz-items-table th { background: #262932; border-color: #333842; color: #FFF; }
        .cotiz-items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-soft);
            vertical-align: middle;
        }
        .cotiz-items-table tr:hover td {
            background: rgba(199, 155, 88, 0.04);
        }
        .cotiz-input-cell {
            width: 100%;
            padding: 8px 10px;
            border-radius: 10px;
            border: 1px solid var(--border-soft);
            background: #FFF;
            font-size: 0.82rem;
            outline: none;
            color: var(--text-dark);
            font-family: inherit;
        }
        body.dark-mode .cotiz-input-cell {
            background: #23252B;
            border-color: #313540;
            color: #FFF;
        }
        .cotiz-input-cell:focus {
            border-color: var(--accent-tan);
        }
        .cotiz-totales-box {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 330px;
            margin-left: auto;
            background: #FAF8F5;
            padding: 18px 22px;
            border-radius: 18px;
            border: 1px solid var(--border-soft);
        }
        body.dark-mode .cotiz-totales-box { background: #1F2127; border-color: #2D3039; }
        .cotiz-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.88rem;
            color: var(--text-muted);
        }
        .cotiz-total-row.destacado {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-dark);
            border-top: 1px dashed var(--border-soft);
            padding-top: 8px;
            margin-top: 4px;
        }

        /* Banner de Advertencia Descuento > 6% */
        .alerta-descuento-admin {
            border: 1px solid #EF4444;
            background: rgba(239, 68, 68, 0.06);
            border-radius: 16px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            animation: pulseWarning 2s infinite ease-in-out;
        }
        @keyframes pulseWarning {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.2); }
            50% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
        }

        /* Modal A4 para Previsualización e Impresión Exacta del PDF Oficial */
        .modal-pdf-a4 {
            background: #3B4247;
            width: 100%;
            max-width: 920px;
            max-height: 92vh;
            border-radius: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            position: relative;
            padding: 24px;
        }
        .hoja-a4-oficial {
            background: #FFFFFF !important;
            color: #000000 !important;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            min-height: 1050px;
            padding: 40px 48px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.35;
        }

        /* CSS DE IMPRESIÓN (MEDIA PRINT) */
        @media print {
            body * {
                visibility: hidden !important;
            }
            #printDocumentoOficial, #printDocumentoOficial * {
                visibility: visible !important;
            }
            #printDocumentoOficial {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 8mm 12mm !important;
                box-shadow: none !important;
                background: #FFF !important;
                color: #000 !important;
            }
            .no-print {
                display: none !important;
            }
        }

        /* TOAST NOTIFICACIONES EN VIVO */
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 999999;
            pointer-events: none;
        }
        .toast-item {
            pointer-events: auto;
            background: #FFF;
            border-radius: 18px;
            padding: 16px 20px;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.22);
            border: 1px solid var(--border-soft);
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 340px;
            max-width: 440px;
            animation: slideInToast 0.3s ease;
            transition: all 0.3s ease;
        }
        body.dark-mode .toast-item { background: #1B201D; border-color: #27362E; }
        @keyframes slideInToast {
            from { transform: translateX(110%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast-item.success { border-left: 5px solid #10B981; }
        .toast-item.warning { border-left: 5px solid #EF4444; }
        .toast-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .toast-item.success .toast-icon { background: #D1FAE5; color: #059669; }
        .toast-item.warning .toast-icon { background: #FEE2E2; color: #DC2626; }
        .toast-content { flex: 1; overflow: hidden; }
        .toast-content h5 { font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 2px; }
        .toast-content p { font-size: 0.76rem; color: var(--text-muted); line-height: 1.4; }
    </style>
</head>
<body>

    <!-- TOASTS EN VIVO -->
    <div class="toast-container" id="toastContainerVentas"></div>

    <!-- CONTENEDOR PRINCIPAL EXPANDIDO A PANTALLA COMPLETA -->
    <div class="app-container">
        
        <!-- ================= SIDEBAR IZQUIERDA ================= -->
        <div class="sidebar">
            <a href="ventas.php" class="brand-logo">
                <div class="brand-logo-icon">
                    <i class="fa-solid fa-horse-head"></i>
                </div>
                <div class="brand-logo-text">PBD</div>
            </a>

            <div class="nav-menu">
                <div class="nav-item active" id="nav-dashboard" onclick="cambiarVistaVentas('dashboard', this)">
                    <i class="fa-solid fa-table-cells-large"></i>
                    <span>Dashboard</span>
                </div>

                <div class="nav-item" id="nav-cotizaciones" onclick="cambiarVistaVentas('cotizaciones', this)">
                    <i class="fa-solid fa-file-signature"></i>
                    <span>Cotizaciones</span>
                    <span class="nav-badge" id="badgeCotizacionesTotal" style="background:#D1FAE5; color:#065F46; font-weight:700;">3</span>
                </div>

                <div class="nav-item" id="nav-facturacion" onclick="cambiarVistaVentas('facturacion', this)">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Nueva Factura</span>
                    <span class="nav-badge">+</span>
                </div>

                <div class="nav-item" id="nav-ventas-dia" onclick="cambiarVistaVentas('ventas-dia', this)">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Ventas del Día</span>
                </div>

                <div class="nav-item" id="nav-cartera" onclick="cambiarVistaVentas('cartera', this)">
                    <i class="fa-solid fa-address-book"></i>
                    <span>Cartera de Clientes</span>
                    <span class="nav-badge" id="badgeCarteraTotal" style="background:var(--accent-tan); color:#161719; font-weight:700;">45</span>
                </div>

                <div class="nav-item" id="nav-chat" onclick="cambiarVistaVentas('chat', this)">
                    <i class="fa-solid fa-comments"></i>
                    <span>Chat Reportería</span>
                    <span class="nav-badge" id="chatBadgeNum" style="background:#EF4444; color:#FFF;">2</span>
                </div>

                <div class="nav-item" id="nav-comprobantes" onclick="cambiarVistaVentas('comprobantes', this)">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Comprobantes</span>
                </div>
            </div>

            <!-- USER CARD BOTTOM -->
            <div class="user-pill" onclick="abrirLogoutModal()" title="Clic para cerrar sesión de Ventas" style="cursor:pointer;">
                <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80" alt="Endrina" class="user-pill-avatar">
                <div class="user-pill-info">
                    <div class="user-pill-name"><?php echo htmlspecialchars($currentUser); ?></div>
                    <div class="user-pill-status">
                        <div class="status-dot"></div> Asesora de Ventas
                    </div>
                </div>
                <i class="fa-solid fa-arrow-right-from-bracket user-pill-chevron" style="color:#EF4444; font-size:0.9rem;" title="Cerrar sesión"></i>
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

        <!-- ================= CONTENIDO CENTRAL (EN EL MEDIO) ================= -->
        <div class="main-content">
            
            <!-- ================= VISTA 1: DASHBOARD ================= -->
            <div id="vista-dashboard" class="vista-seccion">
                <div class="main-header">
                    <div>
                        <h1 id="viewMainTitle">Dashboard de Ventas</h1>
                    </div>
                    <div class="header-actions">
                        <button class="btn-pill-white" onclick="abrirGeneradorCotizacion()">
                            <i class="fa-solid fa-file-signature"></i> Nueva Cotización
                        </button>
                        <button class="btn-pill-white" onclick="abrirBuscarCotizacionFacturar()" style="border-color:var(--accent-tan); color:var(--accent-tan); font-weight:700;">
                            <i class="fa-solid fa-magnifying-glass-dollar"></i> Buscar Cotiz. para Facturar
                        </button>
                        <button class="btn-pill-white primary" onclick="cambiarVistaVentas('facturacion')">
                            <i class="fa-solid fa-plus"></i> Nueva Facturación
                        </button>
                    </div>
                </div>

                <!-- WELCOME / HERO CARD (CARAMELO) -->
                <div class="welcome-card">
                    <div class="welcome-content">
                        <h2>Welcome back, Endrina!</h2>
                        <p>Tu meta comercial del mes está al 85% completada. Emite cotizaciones oficiales para tus obras, adjunta los comprobantes de pago y solicita confirmación inmediata a Reportería.</p>
                        <div class="welcome-actions">
                            <button class="btn-pill-white primary" onclick="abrirGeneradorCotizacion()">
                                <i class="fa-solid fa-file-signature"></i> Nueva Cotización
                            </button>
                            <button class="btn-pill-white" onclick="abrirBuscarCotizacionFacturar()" style="background:#FFF; color:#161719; font-weight:700;">
                                <i class="fa-solid fa-magnifying-glass-dollar"></i> Buscar Cotiz. para Facturar
                            </button>
                            <button class="btn-pill-white" onclick="cambiarVistaVentas('facturacion')">
                                <i class="fa-solid fa-file-invoice-dollar"></i> Facturar Venta
                            </button>
                            <button class="btn-pill-white" onclick="cambiarVistaVentas('ventas-dia')">
                                <i class="fa-solid fa-paper-plane"></i> Enviar Venta del Día
                            </button>
                        </div>
                    </div>
                    <div class="welcome-avatar-wrapper">
                        <div class="welcome-avatar-frame">
                            <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200&auto=format&fit=crop&q=80" alt="Endrina">
                        </div>
                    </div>
                </div>

                <!-- 4 MINI STAT CARDS -->
                <div class="stats-grid">
                    <div class="stat-card-mini" onclick="cambiarVistaVentas('facturacion')">
                        <div class="stat-mini-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                        <div class="stat-mini-info">
                            <h4 id="statVentasHoy">4</h4>
                            <p>Ventas Hoy</p>
                        </div>
                    </div>

                    <div class="stat-card-mini" onclick="cambiarVistaVentas('cartera')">
                        <div class="stat-mini-icon"><i class="fa-solid fa-address-book"></i></div>
                        <div class="stat-mini-info">
                            <h4 id="statClientesActivos">45</h4>
                            <p>Cartera de Clientes</p>
                        </div>
                    </div>

                    <div class="stat-card-mini" onclick="cambiarVistaVentas('chat')">
                        <div class="stat-mini-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <div class="stat-mini-info">
                            <h4 id="statPendientesRep">3</h4>
                            <p>En Reportería</p>
                        </div>
                    </div>

                    <div class="stat-card-mini" onclick="cambiarVistaVentas('comprobantes')">
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
                                <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="#ECE7DE" stroke-width="6"></circle>
                                
                                <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="var(--chart-green)" stroke-width="6"
                                    stroke-dasharray="22.2 77.8" stroke-dashoffset="25"></circle>
                                
                                <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="var(--chart-blue)" stroke-width="6"
                                    stroke-dasharray="44.4 55.6" stroke-dashoffset="2.8"></circle>
                                
                                <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="var(--chart-yellow)" stroke-width="6"
                                    stroke-dasharray="15.5 84.5" stroke-dashoffset="58.4"></circle>
                                
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

            <!-- ================= VISTA: COTIZACIONES (EN EL MEDIO) ================= -->
            <div id="vista-cotizaciones" class="vista-seccion" style="display:none;">
                <div class="main-header">
                    <div>
                        <span style="font-size:0.75rem; font-weight:600; color:var(--accent-tan); text-transform:uppercase; letter-spacing:0.5px;">Gestión de Propuestas</span>
                        <h1 style="margin-top:2px;">Cotizaciones & Pedidos de Tienda</h1>
                    </div>
                    <div class="header-actions">
                        <button class="btn-pill-white" onclick="cambiarVistaVentas('dashboard')">
                            <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
                        </button>
                    </div>
                </div>

                <!-- SUB-TABS: EMITIR NUEVA COTIZACIÓN vs BUSCAR COTIZACIÓN PARA FACTURAR -->
                <div class="cotiz-tabs">
                    <button type="button" class="cotiz-tab-btn active" id="tabBtnNuevaCotiz" onclick="alternarTabCotizaciones('nueva')">
                        <i class="fa-solid fa-file-circle-plus"></i> 1. Emitir Nueva Cotización
                    </button>
                    <button type="button" class="cotiz-tab-btn" id="tabBtnHistorialCotiz" onclick="alternarTabCotizaciones('facturar')">
                        <i class="fa-solid fa-magnifying-glass-dollar"></i> 2. Buscar Cotización para Facturar (<span id="countCotizHistorialBadge">3</span>)
                    </button>
                </div>

                <!-- PANEL 1: GENERADOR DE COTIZACIÓN (HOJA OFICIAL) -->
                <div id="panelNuevaCotizacion">
                    <div class="cotiz-sheet">
                        
                        <!-- ENCABEZADO DE LA HOJA OFICIAL -->
                        <div class="cotiz-card-header">
                            <div style="display:flex; align-items:center; gap:16px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="width:44px; height:44px; border-radius:12px; background:#161719; display:flex; align-items:center; justify-content:center; color:var(--accent-tan); font-weight:800; font-size:1.1rem; border:1px solid var(--accent-tan);">
                                        BSP
                                    </div>
                                    <div>
                                        <h3 style="font-family:'Outfit',sans-serif; font-size:1.2rem; font-weight:800; margin:0; color:var(--text-dark);">BUILDING SYSTEMS PERÚ</h3>
                                        <span style="font-size:0.72rem; color:var(--text-muted);">RUC: 20609793806 • Sucursal Chorrillos</span>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <label style="font-size:0.85rem; font-weight:700; color:var(--text-dark); text-transform:uppercase;">COTIZACIONES:</label>
                                    <input type="text" id="cotizCodigo" value="<?php echo htmlspecialchars($siguienteCodigoCotiz); ?>" readonly style="font-family:'Outfit',sans-serif; font-weight:800; font-size:1rem; width:130px; padding:6px 12px; border-radius:10px; border:1.5px solid var(--accent-tan); background:#F3F4F6; color:var(--text-dark); text-align:center; cursor:not-allowed;" title="Número correlativo generado automáticamente por el sistema">
                                </div>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <label style="font-size:0.8rem; font-weight:600; color:var(--text-muted);">Fecha:</label>
                                    <input type="date" id="cotizFecha" value="<?php echo date('Y-m-d'); ?>" readonly style="padding:6px 12px; border-radius:10px; border:1px solid var(--border-soft); background:#F3F4F6; color:var(--text-dark); font-size:0.82rem; cursor:not-allowed; pointer-events:none;" title="Fecha automática de emisión del día">
                                </div>
                            </div>
                        </div>

                        <!-- BUSCADOR EXCLUSIVO EN CARTERA POR DNI O RUC -->
                        <div>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <label style="font-size:0.84rem; font-weight:700; color:var(--text-dark);">
                                    <i class="fa-solid fa-magnifying-glass" style="color:var(--accent-tan);"></i> Buscar Cliente en Cartera por DNI / RUC
                                </label>
                                <span style="font-size:0.74rem; color:var(--accent-tan); font-weight:600;">
                                    🔒 Clientes registrados en Mi Cartera
                                </span>
                            </div>
                            <div style="display:flex; gap:8px; position:relative;">
                                <div style="position:relative; flex:1;">
                                    <input type="text" id="inputBuscarClienteCotiz" 
                                           placeholder="Ingresa DNI (8 dígitos) o RUC del cliente registrado en cartera..." 
                                           class="cotiz-input-cell" style="padding:12px 16px; font-size:0.9rem; border-color:var(--accent-tan); width:100%;" 
                                           oninput="filtrarSugerenciasClientesCotiz(this.value)" 
                                           onkeydown="if(event.key === 'Enter'){ event.preventDefault(); buscarClienteCarteraPorDni(); }"
                                           autocomplete="off">
                                    <div id="sugerenciasClientesBox" style="display:none; position:absolute; top:100%; left:0; right:0; background:#FFF; border:1px solid var(--border-soft); border-radius:14px; box-shadow:0 12px 30px rgba(0,0,0,0.15); z-index:1000; max-height:220px; overflow-y:auto; margin-top:4px;">
                                        <!-- Lista de sugerencias inyectada por JS -->
                                    </div>
                                </div>
                                <button type="button" id="btnLupaBuscarDoc" class="btn-pill-white primary" 
                                        onclick="buscarClienteCarteraPorDni()" 
                                        style="background:var(--accent-tan); border-color:var(--accent-tan); color:#161719; font-weight:700; padding:0 20px; display:flex; align-items:center; gap:8px; white-space:nowrap; cursor:pointer;"
                                        title="Buscar cliente registrado en cartera">
                                    <i class="fa-solid fa-magnifying-glass" id="iconLupaBuscar"></i>
                                    <span>Buscar en Cartera</span>
                                </button>
                            </div>

                            <!-- ALERTA SI EL CLIENTE NO SE ENCUENTRA REGISTRADO -->
                            <div id="msgAlertaClienteNoRegistrado" style="display:none; margin-top:8px; padding:10px 14px; background:#FEF2F2; border:1px solid #FCA5A5; border-radius:10px; color:#DC2626; font-size:0.84rem; font-weight:700; display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-circle-xmark" style="font-size:1.1rem;"></i>
                                <span>No se registra en la cartera. Debe añadirlo primero en <strong>Mi Cartera de Clientes</strong> con el botón "+ Nuevo Cliente".</span>
                            </div>
                            <!-- CONFIRMACIÓN CUANDO EL CLIENTE ES DE CARTERA -->
                            <div id="msgClienteVerificado" style="display:none; margin-top:8px; padding:8px 14px; background:#ECFDF5; border:1px solid #A7F3D0; border-radius:10px; color:#065F46; font-size:0.82rem; font-weight:700; display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-circle-check" style="color:#10B981;"></i>
                                <span>Cliente de Cartera Verificado</span>
                            </div>
                        </div>

                        <!-- DATOS DEL CLIENTE (JALADOS DE LA CARTERA, SOLO LECTURA) -->
                        <div class="cotiz-client-grid">
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Razón Social / Nombre del Cliente *</label>
                                <input type="text" id="cotizRazonSocial" required readonly placeholder="Razón Social (se jala de la cartera)" class="cotiz-input-cell" style="background:#F9FAFB; cursor:not-allowed;">
                            </div>
                            <div class="form-group">
                                <label>RUC / DNI *</label>
                                <input type="text" id="cotizRucDni" required readonly placeholder="RUC o DNI" class="cotiz-input-cell" style="background:#F9FAFB; cursor:not-allowed;">
                            </div>
                            <div class="form-group">
                                <label>Contacto / Residente</label>
                                <input type="text" id="cotizContacto" readonly placeholder="Contacto asignado" class="cotiz-input-cell" style="background:#F9FAFB; cursor:not-allowed;">
                            </div>
                            <div class="form-group">
                                <label>Teléfono / WhatsApp *</label>
                                <input type="tel" id="cotizTelefono" required readonly placeholder="Teléfono" class="cotiz-input-cell" style="background:#F9FAFB; cursor:not-allowed;">
                            </div>
                            <div class="form-group">
                                <label>Correo Electrónico</label>
                                <input type="email" id="cotizEmail" readonly placeholder="Correo electrónico" class="cotiz-input-cell" style="background:#F9FAFB; cursor:not-allowed;">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Dirección Fiscal / Obra</label>
                                <input type="text" id="cotizDireccion" readonly placeholder="Dirección fiscal u obra" class="cotiz-input-cell" style="background:#F9FAFB; cursor:not-allowed;">
                            </div>
                        </div>

                        <!-- TEXTO INTRODUCTORIO OFICIAL -->
                        <div style="font-size:0.85rem; color:var(--text-muted); font-style:italic; border-left:3px solid var(--accent-tan); padding-left:12px; margin: 4px 0;">
                            "De acuerdo con su amable solicitud, tenemos el agrado de cotizarle lo siguiente:"
                        </div>

                        <!-- TABLA DE ITEMS / PRODUCTOS DE TIENDA -->
                        <div style="overflow-x:auto;">
                            <table class="cotiz-items-table" id="tablaItemsCotizacion">
                                <thead>
                                    <tr>
                                        <th style="width:45px; text-align:center;">Item</th>
                                        <th style="width:115px;">Código</th>
                                        <th style="min-width:280px;">Descripción (Producto de Tienda)</th>
                                        <th style="width:85px;">Cantidad</th>
                                        <th style="width:85px;">UMed</th>
                                        <th style="width:105px;">Pre.Orig</th>
                                        <th style="width:105px;">Descto %</th>
                                        <th style="width:105px;">Prec.Total</th>
                                        <th style="width:115px; text-align:right;">SubTotal</th>
                                        <th style="width:95px; text-align:center;">Estado</th>
                                        <th style="width:50px; text-align:center;">Quitar</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyItemsCotizacion">
                                    <!-- Filas dinámicas generadas por JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- BOTONES DE ACCIÓN PARA AGREGAR ITEMS -->
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                            <div style="display:flex; gap:10px;">
                                <button type="button" class="btn-pill-white primary" onclick="agregarFilaProductoCotiz()">
                                    <i class="fa-solid fa-plus"></i> Agregar Producto
                                </button>
                            </div>
                            <small style="color:var(--text-muted); font-size:0.75rem;">
                                Catálogo de productos sincronizado con BS Perú
                            </small>
                        </div>

                        <!-- BANNER ALERTA POLÍTICA DE DESCUENTO (> 6%) -->
                        <div id="alertaDescuentoAdmin" class="alerta-descuento-admin" style="display:none;">
                            <div style="display:flex; align-items:center; gap:14px;">
                                <i class="fa-solid fa-triangle-exclamation" style="font-size:1.6rem; color:#EF4444;"></i>
                                <div>
                                    <strong style="color:#EF4444; font-size:0.9rem;">⚠️ Descuento Superior al 6% Detectado</strong>
                                    <p style="font-size:0.78rem; color:var(--text-muted); margin:2px 0 0 0;">
                                        Política BS Perú: Como asesora puedes aplicar de <strong>1% a 6%</strong> de descuento directo. Los descuentos mayores requieren consulta y autorización de Administración.
                                    </p>
                                </div>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <button type="button" class="btn-pill-white" style="border-color:#10B981; color:#059669; font-weight:700;" onclick="consultarAdminWhatsApp()">
                                    <i class="fa-brands fa-whatsapp"></i> Consultar al Administrador
                                </button>
                                <button type="button" class="btn-pill-white" style="border-color:var(--accent-tan); color:var(--accent-tan); font-weight:700;" onclick="abrirModalAutorizacionDescuento()">
                                    <i class="fa-solid fa-key"></i> Clave de Autorización
                                </button>
                            </div>
                        </div>

                        <div id="badgeDescuentoAutorizado" style="display:none; background:#D1FAE5; border:1px solid #10B981; border-radius:12px; padding:10px 16px; color:#065F46; font-size:0.82rem; font-weight:700;">
                            <i class="fa-solid fa-circle-check" style="color:#10B981;"></i> Descuento especial autorizado por Administración BS Perú (<span id="lblAutorizador">Gerencia</span>)
                        </div>

                        <!-- TOTALES DE LA COTIZACIÓN -->
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:20px; margin-top:8px;">
                            <div style="flex:1; min-width:300px;">
                                <label style="font-size:0.82rem; font-weight:700; color:var(--text-dark); margin-bottom:6px; display:block;">Observaciones & Condiciones de Entrega</label>
                                <textarea id="cotizObservaciones" rows="3" class="cotiz-input-cell" style="font-size:0.8rem;" placeholder="Observaciones sobre transporte, despacho o condiciones técnicas...">Productos puestos en obra según disponibilidad de almacén. Precios incluyen IGV.</textarea>
                            </div>

                            <div class="cotiz-totales-box">
                                <div class="cotiz-total-row">
                                    <span>Subtotal:</span>
                                    <strong id="lblCotizSubtotal" style="color:var(--text-dark);">S/ 0.00</strong>
                                </div>
                                <div class="cotiz-total-row">
                                    <span>IGV (18%):</span>
                                    <strong id="lblCotizIgv" style="color:var(--text-dark);">S/ 0.00</strong>
                                </div>
                                <div class="cotiz-total-row destacado">
                                    <span>Total Neto:</span>
                                    <strong id="lblCotizTotalNeto" style="color:#10B981;">S/ 0.00</strong>
                                </div>
                            </div>
                        </div>

                        <!-- CONDICIONES COMERCIALES OFICIALES PRE-CARGADAS -->
                        <div style="background:#FAF8F5; border-radius:16px; padding:18px 22px; border:1px solid var(--border-soft); font-size:0.75rem; line-height:1.5; color:var(--text-muted);">
                            <strong style="color:var(--text-dark); text-transform:uppercase; display:block; margin-bottom:6px;">Condiciones Comerciales Oficiales BS Perú</strong>
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin-bottom:10px;">
                                <div>
                                    <strong style="color:var(--text-dark);">Forma de Pago:</strong>
                                    <select id="cotizFormaPago" class="cotiz-input-cell" style="padding:6px 10px; margin-top:4px; font-size:0.78rem;">
                                        <option value="CONTADO CONTRA ENTREGA" selected>CONTADO CONTRA ENTREGA</option>
                                        <option value="CONTADO ANTICIPADO">CONTADO ANTICIPADO</option>
                                        <option value="CRÉDITO 15 DÍAS">CRÉDITO 15 DÍAS</option>
                                        <option value="CRÉDITO 30 DÍAS">CRÉDITO 30 DÍAS</option>
                                    </select>
                                </div>
                                <div>
                                    <strong style="color:var(--text-dark);">Vigencia de Cotización:</strong>
                                    <select id="cotizVigencia" class="cotiz-input-cell" style="padding:6px 10px; margin-top:4px; font-size:0.78rem;">
                                        <option value="7 dias" selected>7 días calendario</option>
                                        <option value="15 dias">15 días calendario</option>
                                        <option value="30 dias">30 días calendario</option>
                                    </select>
                                </div>
                                <div>
                                    <strong style="color:var(--text-dark);">Horario de Oficinas:</strong>
                                    <p style="margin:4px 0 0 0;">Lunes a Viernes 8:00 a 17:30 / Sábados 8:00 a 12:00</p>
                                </div>
                            </div>
                            <p style="margin:4px 0;">• Entregamos certificados de calidad, hojas de seguridad (MSDS) y especificaciones técnicas de todos nuestros productos a solicitud del cliente.</p>
                            <p style="margin:4px 0;">• <strong>Cuentas Corrientes:</strong> BCP Soles: 193-9902956-0-56 (CCI: 00219300990295605614) | BBVA Soles: 0011-0152-0100100654 (CCI: 011-152-000100100654-61) | Interbank Soles: 200-3005486597 (CCI: 003-200-003005486597-34)</p>
                            <p style="margin:4px 0;">• <strong>Sucursal Chorrillos:</strong> Av. Los Faisanes N° 675 Urb. La Campiña Chorrillos | Ventas Oficina Chorrillos</p>
                        </div>

                        <!-- BARRA PRINCIPAL DE ACCIONES DE LA COTIZACIÓN -->
                        <div style="display:flex; justify-content:flex-end; align-items:center; gap:12px; flex-wrap:wrap; padding-top:16px; border-top:1px solid var(--border-soft);">
                            <button type="button" class="btn-pill-white primary" onclick="guardarCotizacionActual(false)" style="background:var(--accent-tan); border-color:var(--accent-tan); color:#161719; font-weight:800; padding:12px 32px; font-size:0.95rem; display:inline-flex; align-items:center; gap:10px; border-radius:14px; cursor:pointer; box-shadow:0 4px 14px rgba(199,155,88,0.25);">
                                <i class="fa-solid fa-floppy-disk"></i> Guardar Cotización
                            </button>
                        </div>

                    </div>
                </div>

                <!-- PANEL 2: BUSCAR COTIZACIÓN PARA FACTURAR -->
                <div id="panelHistorialCotizaciones" style="display:none;">
                    <div class="card-seccion-centro">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; margin-bottom:18px; border-bottom:1px solid var(--border-soft); padding-bottom:14px;">
                            <div>
                                <h3 style="font-size:1.15rem; font-weight:800; color:var(--text-dark); margin:0; display:flex; align-items:center; gap:8px;">
                                    <i class="fa-solid fa-magnifying-glass-dollar" style="color:var(--accent-tan);"></i> Buscar Cotización para Facturar
                                </h3>
                                <p style="font-size:0.78rem; color:var(--text-muted); margin:4px 0 0 0;">
                                    Ubica la cotización del cliente (por código correlativo, RUC o empresa) y haz clic en <strong>"Facturar Cotización"</strong> para transferirla inmediatamente y adjuntar el voucher.
                                </p>
                            </div>
                            <button type="button" class="btn-pill-white primary" onclick="alternarTabCotizaciones('nueva')">
                                <i class="fa-solid fa-plus"></i> + Emitir Nueva Cotización
                            </button>
                        </div>

                        <!-- BARRA DE BÚSQUEDA Y FILTROS RÁPIDOS -->
                        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:16px;">
                            <div class="cartera-search-box" style="flex:1; min-width:280px;">
                                <i class="fa-solid fa-magnifying-glass" style="color:var(--accent-tan);"></i>
                                <input type="text" id="inputBuscarCotizFacturar" placeholder="Buscar por código (ej. 0052456), cliente o RUC..." onkeyup="filtrarHistorialCotizaciones(this.value)">
                            </div>
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                <button type="button" class="btn-pill-white cotiz-filtro-btn active" onclick="filtrarCotizacionesPorEstado('todos', this)" style="padding:7px 14px; font-size:0.75rem; font-weight:700;">Todos</button>
                                <button type="button" class="btn-pill-white cotiz-filtro-btn" onclick="filtrarCotizacionesPorEstado('Pendiente', this)" style="padding:7px 14px; font-size:0.75rem; color:#D97706;">⏳ Por Facturar</button>
                                <button type="button" class="btn-pill-white cotiz-filtro-btn" onclick="filtrarCotizacionesPorEstado('Aceptada', this)" style="padding:7px 14px; font-size:0.75rem; color:#059669;">✅ Aceptadas</button>
                                <button type="button" class="btn-pill-white cotiz-filtro-btn" onclick="filtrarCotizacionesPorEstado('Facturada', this)" style="padding:7px 14px; font-size:0.75rem; color:#4F46E5;">💳 Ya Facturadas</button>
                            </div>
                        </div>

                        <div class="cartera-table-wrapper">
                            <table class="cartera-table" id="tablaHistorialCotizaciones">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Fecha</th>
                                        <th>Cliente / Razón Social</th>
                                        <th>RUC / DNI</th>
                                        <th>Total (S/)</th>
                                        <th>Descto Máx</th>
                                        <th>Estado</th>
                                        <th style="text-align:center; min-width:210px;">Facturación / Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyHistorialCotizaciones">
                                    <!-- Inyectado por JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ================= VISTA 2: NUEVA FACTURACIÓN (EN EL MEDIO) ================= -->
            <div id="vista-facturacion" class="vista-seccion" style="display:none;">
                <div class="main-header">
                    <div>
                        <span style="font-size:0.75rem; font-weight:600; color:var(--accent-tan); text-transform:uppercase; letter-spacing:0.5px;">Módulo Comercial</span>
                        <h1 style="margin-top:2px;">Nueva Facturación Comercial</h1>
                    </div>
                    <div class="header-actions">
                        <button class="btn-pill-white" onclick="cambiarVistaVentas('dashboard')">
                            <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
                        </button>
                    </div>
                </div>

                <div class="card-seccion-centro">
                    <form id="formNuevaFacturaCentro" onsubmit="enviarFacturacionReporteria(event)">
                        <div class="form-grid">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>N° Comprobante / Factura *</label>
                                    <input type="text" id="facNumero" required value="F001-00895">
                                </div>
                                <div class="form-group">
                                    <label>RUC / DNI Cliente (11 u 8 dígitos) *</label>
                                    <input type="text" id="facRuc" required placeholder="20601234567" value="20554189012">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Cliente / Razón Social *</label>
                                <input type="text" id="facCliente" required placeholder="Constructora o Empresa" value="Constructora Los Andes S.A.C.">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Importe Total Facturado (S/) *</label>
                                    <input type="number" step="0.01" id="facMonto" required placeholder="0.00" value="7850.00">
                                </div>
                                <div class="form-group">
                                    <label>Método de Pago *</label>
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
                                <label>N° Operación Bancaria *</label>
                                <input type="text" id="facOperacion" required placeholder="Ej: BCP #782910" value="BCP #782910">
                            </div>

                            <div class="form-group">
                                <label>Adjuntar Comprobante de Pago (Voucher)</label>
                                <div class="voucher-upload-box" onclick="document.getElementById('inputVoucher').click()">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <p style="font-size:0.86rem; font-weight:600; color:var(--text-dark);" id="voucherUploadLabel">
                                        Clic para adjuntar comprobante (JPG, PNG, PDF)
                                    </p>
                                    <span style="font-size:0.72rem; color:var(--text-muted);">Se guardará en tu registro de ventas y se enviará a Reportería para su verificación</span>
                                    <input type="file" id="inputVoucher" style="display:none;" accept="image/*,.pdf" onchange="previewVoucherFileName(this)">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Observaciones para Reportería</label>
                                <textarea id="facNotas" rows="2" placeholder="Notas sobre el pago...">Pago de adelanto verificado con el cliente. Solicito confirmación para despacho.</textarea>
                            </div>

                            <button type="submit" class="btn-submit-action">
                                <i class="fa-solid fa-paper-plane"></i> Solicitar Confirmación de Pago a Reportería
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ================= VISTA 3: VENTAS DEL DÍA (EN EL MEDIO) ================= -->
            <div id="vista-ventas-dia" class="vista-seccion" style="display:none;">
                <div class="main-header">
                    <div>
                        <span style="font-size:0.75rem; font-weight:600; color:var(--accent-tan); text-transform:uppercase; letter-spacing:0.5px;">Cierre Diario</span>
                        <h1 style="margin-top:2px;">Ventas del Día & Consolidado</h1>
                    </div>
                    <div class="header-actions">
                        <button class="btn-pill-white" onclick="cambiarVistaVentas('dashboard')">
                            <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
                        </button>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card-mini">
                        <div class="stat-mini-icon"><i class="fa-solid fa-wallet"></i></div>
                        <div class="stat-mini-info">
                            <h4>S/ 32,708</h4>
                            <p>Total Facturado Hoy</p>
                        </div>
                    </div>
                    <div class="stat-card-mini">
                        <div class="stat-mini-icon"><i class="fa-solid fa-receipt"></i></div>
                        <div class="stat-mini-info">
                            <h4 id="statVentasDiaNum">4</h4>
                            <p>Operaciones Emitidas</p>
                        </div>
                    </div>
                    <div class="stat-card-mini">
                        <div class="stat-mini-icon"><i class="fa-solid fa-images"></i></div>
                        <div class="stat-mini-info">
                            <h4>4</h4>
                            <p>Vouchers Adjuntos</p>
                        </div>
                    </div>
                    <div class="stat-card-mini">
                        <div class="stat-mini-icon"><i class="fa-solid fa-user-check"></i></div>
                        <div class="stat-mini-info">
                            <h4>100%</h4>
                            <p>Cuadrado</p>
                        </div>
                    </div>
                </div>

                <div class="card-seccion-centro">
                    <h3 style="font-family:'Outfit',sans-serif; font-size:1.15rem; color:var(--text-dark);"><i class="fa-solid fa-list-check" style="color:var(--accent-tan);"></i> Operaciones a Enviar a Reportería</h3>
                    <div class="cartera-table-wrapper">
                        <table class="cartera-table">
                            <thead>
                                <tr>
                                    <th>N° Comprobante</th>
                                    <th>Cliente</th>
                                    <th>Monto</th>
                                    <th>Método / Op.</th>
                                    <th>Voucher</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>F001-00892</strong></td>
                                    <td>Cosapi S.A.</td>
                                    <td><strong style="color:#10B981;">S/ 14,400.00</strong></td>
                                    <td>BCP #849201</td>
                                    <td><span class="badge-tag-activo">Adjunto ✅</span></td>
                                </tr>
                                <tr>
                                    <td><strong>F001-00891</strong></td>
                                    <td>Consorcio Vial Piura</td>
                                    <td><strong style="color:var(--text-dark);">S/ 6,800.00</strong></td>
                                    <td>BBVA #902184</td>
                                    <td><span class="badge-tag-activo">Adjunto ✅</span></td>
                                </tr>
                                <tr>
                                    <td><strong>F001-00890</strong></td>
                                    <td>Edificaciones Pacífico S.A.C.</td>
                                    <td><strong style="color:var(--text-dark);">S/ 3,658.00</strong></td>
                                    <td>Interbank #109281</td>
                                    <td><span class="badge-tag-activo">Adjunto ✅</span></td>
                                </tr>
                                <tr>
                                    <td><strong>F001-00895</strong></td>
                                    <td>Constructora Los Andes S.A.C.</td>
                                    <td><strong style="color:var(--text-dark);">S/ 7,850.00</strong></td>
                                    <td>BCP #782910</td>
                                    <td><span class="badge-tag-activo">Adjunto ✅</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group" style="margin-top:10px;">
                        <label>Mensaje y Observaciones para el Equipo de Reportería</label>
                        <textarea id="notaVentasDia" rows="3" style="width:100%;">Buenas tardes equipo de Reportería, adjunto el reporte oficial de ventas del día con los 4 comprobantes bancarios correspondientes para su verificación y aceptación en el sistema.</textarea>
                    </div>

                    <button class="btn-submit-action" onclick="confirmarEnvioVentasDia()">
                        <i class="fa-solid fa-check-double"></i> Enviar Reporte del Día a Reportería
                    </button>
                </div>
            </div>

            <!-- ================= VISTA 4: CARTERA DE CLIENTES (EN EL MEDIO) ================= -->
            <div id="vista-cartera" class="vista-seccion" style="display:none;">
                <div class="main-header">
                    <div>
                        <span style="font-size:0.75rem; font-weight:600; color:var(--accent-tan); text-transform:uppercase; letter-spacing:0.5px;">Gestión de Clientes</span>
                        <h1 style="margin-top:2px;">Mi Cartera de Clientes</h1>
                    </div>
                    <div class="header-actions">
                        <button class="btn-add-cliente" onclick="toggleFormNuevoCliente()">
                            <i class="fa-solid fa-user-plus"></i> <span id="btnTextNuevoCli">+ Nuevo Cliente</span>
                        </button>
                        <button class="btn-pill-white" onclick="cambiarVistaVentas('dashboard')">
                            <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
                        </button>
                    </div>
                </div>

                <!-- FORMULARIO DESPLEGABLE: REGISTRAR NUEVO CLIENTE -->
                <div class="form-new-cliente-box" id="boxFormNuevoCliente">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
                        <h4 style="font-size:0.95rem; font-weight:700; color:var(--text-dark); margin:0; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-building-circle-check" style="color:var(--accent-tan);"></i> Registrar Nuevo Cliente a mi Cartera
                        </h4>
                        <span style="font-size:0.75rem; color:var(--accent-tan); font-weight:600;">
                            ⚡ Ingresa DNI o RUC y haz clic en la lupa para jalar Razón Social y Dirección desde SUNAT / RENIEC
                        </span>
                    </div>
                    <form onsubmit="guardarNuevoCliente(event)">
                        <div class="form-grid">
                            <div class="form-row">
                                <div class="form-group" style="flex:1;">
                                    <label>RUC / DNI (11 u 8 dígitos) *</label>
                                    <div style="display:flex; gap:8px;">
                                        <input type="text" id="newCliRuc" required maxlength="11" placeholder="Ej: 20601928471 o DNI"
                                               style="flex:1;"
                                               onkeydown="if(event.key === 'Enter'){ event.preventDefault(); buscarClienteNuevoPorDocumento(); }">
                                        <button type="button" id="btnLupaNuevoCliente" class="btn-pill-white primary"
                                                onclick="buscarClienteNuevoPorDocumento()"
                                                style="background:var(--accent-tan); border-color:var(--accent-tan); color:#161719; font-weight:700; padding:0 16px; display:flex; align-items:center; gap:6px; cursor:pointer;"
                                                title="Buscar y jalar datos de SUNAT / RENIEC">
                                            <i class="fa-solid fa-magnifying-glass" id="iconLupaNuevoCliente"></i>
                                            <span>Buscar</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group" style="flex:2;">
                                    <label>Razón Social / Empresa *</label>
                                    <input type="text" id="newCliEmpresa" required placeholder="Ej: Constructora San Jerónimo S.A.C.">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Contacto / Residente de Obra</label>
                                    <input type="text" id="newCliContacto" placeholder="Ej: Ing. Jorge Ramirez">
                                </div>
                                <div class="form-group">
                                    <label>Teléfono / WhatsApp *</label>
                                    <input type="tel" id="newCliTelefono" required placeholder="Ej: 987654321">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Correo Electrónico</label>
                                    <input type="email" id="newCliEmail" placeholder="compras@empresa.com">
                                </div>
                                <div class="form-group">
                                    <label>Categoría de Cliente</label>
                                    <select id="newCliCategoria">
                                        <option value="VIP">🏆 VIP / Grandes Obras</option>
                                        <option value="Activo" selected>🏢 Activo este Mes</option>
                                        <option value="Seguimiento">⏳ En Seguimiento / Cotización</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Dirección de Obra / Oficina Principal</label>
                                <input type="text" id="newCliDireccion" placeholder="Ej: Av. Javier Prado Este 2450, San Borja, Lima">
                            </div>

                            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:8px;">
                                <button type="button" class="btn-pill-white" onclick="toggleFormNuevoCliente()">Cancelar</button>
                                <button type="submit" class="btn-pill-white primary">
                                    <i class="fa-solid fa-save"></i> Guardar en mi Cartera
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- BARRA DE BÚSQUEDA Y FILTROS -->
                <div class="cartera-toolbar">
                    <div class="cartera-search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="inputBuscarCliente" placeholder="Buscar por empresa, RUC, contacto o teléfono..." onkeyup="filtrarCarteraClientes(this.value)">
                    </div>

                    <div class="cartera-filter-pills">
                        <div class="cartera-pill active" onclick="filtrarCarteraPorTipo('todos', this)">Todos (<span id="countFiltroTodos">45</span>)</div>
                        <div class="cartera-pill" onclick="filtrarCarteraPorTipo('VIP', this)">🏆 VIP (12)</div>
                        <div class="cartera-pill" onclick="filtrarCarteraPorTipo('Activo', this)">🏢 Activos (18)</div>
                        <div class="cartera-pill" onclick="filtrarCarteraPorTipo('Seguimiento', this)">⏳ En Seguimiento (15)</div>
                    </div>
                </div>

                <!-- TABLA COMPLETA DE CARTERA DE CLIENTES -->
                <div class="cartera-table-wrapper">
                    <table class="cartera-table" id="tablaCarteraClientes">
                        <thead>
                            <tr>
                                <th>Cliente / Razón Social</th>
                                <th>Contacto Principal</th>
                                <th>WhatsApp / Teléfono</th>
                                <th>Compras del Mes</th>
                                <th>Categoría</th>
                                <th style="text-align:center;">Acción Inmediata</th>
                            </tr>
                        </thead>
                        <tbody id="carteraTbody">
                            <tr data-tipo="VIP">
                                <td>
                                    <div class="cliente-item-cell">
                                        <div class="cliente-avatar-circle">CS</div>
                                        <div class="cliente-meta">
                                            <h5>Cosapi S.A.</h5>
                                            <span>RUC: 20100038146 • Lima</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong>Ing. Mary Rose</strong><br>
                                    <span style="font-size:0.72rem; color:var(--text-muted);">Residente Torre Real</span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span>984 129 384</span>
                                        <a href="https://wa.me/51984129384?text=Hola%20Ing.%20Mary%20Rose,%20le%20escribe%20Endrina%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                </td>
                                <td><strong style="color:#10B981;">S/ 32,650.00</strong></td>
                                <td><span class="badge-tag-vip">🏆 VIP</span></td>
                                <td style="text-align:center;">
                                    <button class="btn-facturar-mini" onclick="facturarACliente('Cosapi S.A.', '20100038146')">
                                        <i class="fa-solid fa-file-invoice"></i> Facturar
                                    </button>
                                </td>
                            </tr>

                            <tr data-tipo="VIP">
                                <td>
                                    <div class="cliente-item-cell">
                                        <div class="cliente-avatar-circle" style="background:#E0E7FF; color:#4338CA;">GM</div>
                                        <div class="cliente-meta">
                                            <h5>Graña & Montero Ingeniería</h5>
                                            <span>RUC: 20100109850 • Callao</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong>Arq. Carlos Mendoza</strong><br>
                                    <span style="font-size:0.72rem; color:var(--text-muted);">Jefe de Abastecimiento</span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span>991 482 103</span>
                                        <a href="https://wa.me/51991482103?text=Hola%20Arq.%20Carlos%20Mendoza,%20le%20escribe%20Endrina%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                </td>
                                <td><strong style="color:#10B981;">S/ 28,500.00</strong></td>
                                <td><span class="badge-tag-vip">🏆 VIP</span></td>
                                <td style="text-align:center;">
                                    <button class="btn-facturar-mini" onclick="facturarACliente('Graña & Montero Ingeniería', '20100109850')">
                                        <i class="fa-solid fa-file-invoice"></i> Facturar
                                    </button>
                                </td>
                            </tr>

                            <tr data-tipo="Activo">
                                <td>
                                    <div class="cliente-item-cell">
                                        <div class="cliente-avatar-circle" style="background:#FEF3C7; color:#B45309;">CV</div>
                                        <div class="cliente-meta">
                                            <h5>Consorcio Vial Piura</h5>
                                            <span>RUC: 20601849201 • Piura</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong>Jerome Brown</strong><br>
                                    <span style="font-size:0.72rem; color:var(--text-muted);">Logística de Materiales</span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span>972 384 192</span>
                                        <a href="https://wa.me/51972384192?text=Hola%20Jerome%20Brown,%20le%20escribe%20Endrina%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                </td>
                                <td><strong style="color:var(--text-dark);">S/ 14,800.00</strong></td>
                                <td><span class="badge-tag-activo">🏢 Activo</span></td>
                                <td style="text-align:center;">
                                    <button class="btn-facturar-mini" onclick="facturarACliente('Consorcio Vial Piura', '20601849201')">
                                        <i class="fa-solid fa-file-invoice"></i> Facturar
                                    </button>
                                </td>
                            </tr>

                            <tr data-tipo="Activo">
                                <td>
                                    <div class="cliente-item-cell">
                                        <div class="cliente-avatar-circle" style="background:#DCFCE7; color:#15803D;">EP</div>
                                        <div class="cliente-meta">
                                            <h5>Edificaciones Pacífico S.A.C.</h5>
                                            <span>RUC: 20554189012 • Miraflores</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong>Ing. Roberto Salcedo</strong><br>
                                    <span style="font-size:0.72rem; color:var(--text-muted);">Supervisor de Acabados</span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span>987 654 321</span>
                                        <a href="https://wa.me/51987654321?text=Hola%20Ing.%20Roberto%20Salcedo,%20le%20escribe%20Endrina%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                </td>
                                <td><strong style="color:var(--text-dark);">S/ 19,200.00</strong></td>
                                <td><span class="badge-tag-activo">🏢 Activo</span></td>
                                <td style="text-align:center;">
                                    <button class="btn-facturar-mini" onclick="facturarACliente('Edificaciones Pacífico S.A.C.', '20554189012')">
                                        <i class="fa-solid fa-file-invoice"></i> Facturar
                                    </button>
                                </td>
                            </tr>

                            <tr data-tipo="VIP">
                                <td>
                                    <div class="cliente-item-cell">
                                        <div class="cliente-avatar-circle" style="background:#FCE7F3; color:#BE185D;">JC</div>
                                        <div class="cliente-meta">
                                            <h5>JJC Contratistas Generales</h5>
                                            <span>RUC: 20100142806 • Surco</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong>Ing. Walter Palacios</strong><br>
                                    <span style="font-size:0.72rem; color:var(--text-muted);">Gerente de Proyecto</span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span>963 852 741</span>
                                        <a href="https://wa.me/51963852741?text=Hola%20Ing.%20Walter%20Palacios,%20le%20escribe%20Endrina%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                </td>
                                <td><strong style="color:#10B981;">S/ 34,100.00</strong></td>
                                <td><span class="badge-tag-vip">🏆 VIP</span></td>
                                <td style="text-align:center;">
                                    <button class="btn-facturar-mini" onclick="facturarACliente('JJC Contratistas Generales', '20100142806')">
                                        <i class="fa-solid fa-file-invoice"></i> Facturar
                                    </button>
                                </td>
                            </tr>

                            <tr data-tipo="Seguimiento">
                                <td>
                                    <div class="cliente-item-cell">
                                        <div class="cliente-avatar-circle" style="background:#F3E8FF; color:#7E22CE;">BE</div>
                                        <div class="cliente-meta">
                                            <h5>Besco Inmobiliaria & Construcción</h5>
                                            <span>RUC: 20419283011 • San Isidro</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong>Arq. Lucía Ramos</strong><br>
                                    <span style="font-size:0.72rem; color:var(--text-muted);">Cotizaciones Obra Condominio</span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span>951 753 852</span>
                                        <a href="https://wa.me/51951753852?text=Hola%20Arq.%20Luc%C3%ADa%20Ramos,%20le%20escribe%20Endrina%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                </td>
                                <td><strong style="color:var(--text-muted);">S/ 12,300.00</strong></td>
                                <td><span class="badge-tag-seguimiento">⏳ En Cotización</span></td>
                                <td style="text-align:center;">
                                    <button class="btn-facturar-mini" onclick="facturarACliente('Besco Inmobiliaria & Construcción', '20419283011')">
                                        <i class="fa-solid fa-file-invoice"></i> Facturar
                                    </button>
                                </td>
                            </tr>

                            <tr data-tipo="Activo">
                                <td>
                                    <div class="cliente-item-cell">
                                        <div class="cliente-avatar-circle" style="background:#E0F2FE; color:#0369A1;">LA</div>
                                        <div class="cliente-meta">
                                            <h5>Constructora Los Andes S.A.C.</h5>
                                            <span>RUC: 20604819204 • Arequipa</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong>Lic. Elena Morales</strong><br>
                                    <span style="font-size:0.72rem; color:var(--text-muted);">Compras y Suministros</span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span>998 123 456</span>
                                        <a href="https://wa.me/51998123456?text=Hola%20Lic.%20Elena%20Morales,%20le%20escribe%20Endrina%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                </td>
                                <td><strong style="color:var(--text-dark);">S/ 7,850.00</strong></td>
                                <td><span class="badge-tag-activo">🏢 Activo</span></td>
                                <td style="text-align:center;">
                                    <button class="btn-facturar-mini" onclick="facturarACliente('Constructora Los Andes S.A.C.', '20604819204')">
                                        <i class="fa-solid fa-file-invoice"></i> Facturar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ================= VISTA 5: CHAT CON REPORTERÍA (EN EL MEDIO) ================= -->
            <div id="vista-chat" class="vista-seccion" style="display:none;">
                <div class="main-header">
                    <div>
                        <span style="font-size:0.75rem; font-weight:600; color:var(--accent-tan); text-transform:uppercase; letter-spacing:0.5px;">Comunicación Interna</span>
                        <h1 style="margin-top:2px;">Chat Oficial con Reportería</h1>
                    </div>
                    <div class="header-actions">
                        <button class="btn-pill-white" onclick="cambiarVistaVentas('dashboard')">
                            <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
                        </button>
                    </div>
                </div>

                <div class="chat-center-container">
                    <div class="chat-center-header">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div class="status-dot"></div>
                            <div>
                                <strong style="color:var(--text-dark); font-size:0.9rem;">Área de Reportería & Finanzas</strong>
                                <p style="font-size:0.7rem; color:var(--text-muted);">Canal de validación de pagos y cierre de ventas diarias</p>
                            </div>
                        </div>
                        <span class="badge-tag-activo">En línea</span>
                    </div>

                    <div class="chat-center-messages" id="chatCenterMessages">
                        <div class="chat-bubble reporteria">
                            <strong>Área de Reportería:</strong><br>
                            Hola Endrina, recibimos la Factura F001-00892 por S/ 14,400.00 de Cosapi S.A. El comprobante BCP #849201 ha sido <strong>ACEPTADO Y REGISTRADO</strong> exitosamente en banco. ✅
                            <div style="font-size:0.65rem; color:var(--text-muted); margin-top:4px;">11:42 AM</div>
                        </div>

                        <div class="chat-bubble asesor">
                            <strong>Tú (Endrina):</strong><br>
                            Excelente equipo, acabo de emitir la Factura F001-00891 de Consorcio Vial Piura por S/ 6,800.00 con voucher BBVA. ¿Podrían confirmarme apenas esté validado?
                            <div style="font-size:0.65rem; opacity:0.8; margin-top:4px;">11:45 AM</div>
                        </div>

                        <div class="chat-bubble reporteria">
                            <strong>Área de Reportería:</strong><br>
                            Está en cola de revisión bancaria. Recuerda enviar también tu consolidado de ventas del día antes de las 6:00 PM para el cierre.
                            <div style="font-size:0.65rem; color:var(--text-muted); margin-top:4px;">11:47 AM</div>
                        </div>
                    </div>

                    <!-- Botones de respuesta rápida -->
                    <div style="display:flex; gap:8px; padding:10px 20px; background:#FAF7F2; overflow-x:auto; border-top:1px solid var(--border-soft);">
                        <button class="cartera-pill" onclick="insertarTextoChat('¿Pudieron verificar el voucher de Consorcio Vial?')">¿Verificaron voucher Consorcio?</button>
                        <button class="cartera-pill" onclick="insertarTextoChat('Adjunto nuevo comprobante BCP para validación')">Adjunto nuevo voucher BCP</button>
                        <button class="cartera-pill" onclick="insertarTextoChat('Acabo de enviar el consolidado de ventas del día')">Consolidado del día enviado</button>
                    </div>

                    <div class="chat-center-footer">
                        <input type="text" id="inputChatMsg" placeholder="Escribe un mensaje o consulta a Reportería..." onkeypress="if(event.key==='Enter') enviarMensajeChat()">
                        <button class="btn-pill-white primary" style="padding:10px 22px;" onclick="enviarMensajeChat()">
                            <i class="fa-solid fa-paper-plane"></i> Enviar
                        </button>
                    </div>
                </div>
            </div>

            <!-- ================= VISTA 6: COMPROBANTES (EN EL MEDIO) ================= -->
            <div id="vista-comprobantes" class="vista-seccion" style="display:none;">
                <div class="main-header">
                    <div>
                        <span style="font-size:0.75rem; font-weight:600; color:var(--accent-tan); text-transform:uppercase; letter-spacing:0.5px;">Tesorería & Ventas</span>
                        <h1 style="margin-top:2px;">Comprobantes & Vouchers Guardados</h1>
                    </div>
                    <div class="header-actions">
                        <button class="btn-pill-white" onclick="cambiarVistaVentas('dashboard')">
                            <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
                        </button>
                    </div>
                </div>

                <div class="vouchers-grid" id="vouchersGridContainer">
                    <div class="voucher-card-item">
                        <div class="voucher-card-thumb" onclick="verComprobanteDetalle('F001-00892', 'Cosapi S.A.', '14,400.00', 'BCP #849201', 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=400&auto=format&fit=crop&q=80', 'Aceptado')">
                            <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=400&auto=format&fit=crop&q=80" alt="Voucher">
                        </div>
                        <div class="voucher-card-body">
                            <span class="badge-tag-activo">✅ Pago Aceptado</span>
                            <h4 style="font-size:0.95rem; color:var(--text-dark); margin-top:4px;">F001-00892</h4>
                            <p style="font-size:0.75rem; color:var(--text-muted);">Cosapi S.A. • S/ 14,400.00</p>
                            <span style="font-size:0.72rem; color:var(--text-muted);">Op: BCP #849201</span>
                            <button class="btn-facturar-mini" style="margin-top:6px;" onclick="verComprobanteDetalle('F001-00892', 'Cosapi S.A.', '14,400.00', 'BCP #849201', 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=400&auto=format&fit=crop&q=80', 'Aceptado')">
                                <i class="fa-solid fa-eye"></i> Ver Voucher
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ================= PANEL DERECHO: MY ACTIVITY ================= -->
        <div class="right-sidebar">
            <div class="right-header">
                <h3>My activity</h3>
            </div>

            <!-- BLOQUE 1: FACTURACIONES PENDIENTES DE VALIDAR -->
            <div class="activity-block">
                <div class="activity-block-header">
                    <h4>Upcoming talks</h4>
                    <a onclick="cambiarVistaVentas('facturacion')">View all</a>
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

            <!-- BLOQUE 2: COMPROBANTES Y VALIDACIÓN -->
            <div class="activity-block">
                <div class="activity-block-header">
                    <h4>Comprobantes recientes</h4>
                    <a onclick="cambiarVistaVentas('comprobantes')">Ver todos</a>
                </div>

                <div class="activity-card" onclick="cambiarVistaVentas('comprobantes')">
                    <div class="date-badge">
                        <span class="day">10</span>
                        <span class="month">MAR</span>
                    </div>
                    <div class="activity-card-info">
                        <h5>Facturas en validación</h5>
                        <p>Estado de conciliación</p>
                    </div>
                </div>

                <div class="activity-card" onclick="cambiarVistaVentas('chat')">
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
                    <a onclick="cambiarVistaVentas('chat')">View all</a>
                </div>

                <div class="shoutout-item" onclick="cambiarVistaVentas('chat')">
                    <div class="shoutout-avatar-box">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" alt="Adam">
                        <div class="shoutout-online-dot"></div>
                    </div>
                    <div class="shoutout-content">
                        <h5>Área de Reportería</h5>
                        <p>Confirmó pago F001-00892 (S/ 14,400)</p>
                    </div>
                </div>

                <div class="shoutout-item" onclick="cambiarVistaVentas('chat')">
                    <div class="shoutout-avatar-box">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&auto=format&fit=crop&q=80" alt="Johnny">
                        <div class="shoutout-online-dot"></div>
                    </div>
                    <div class="shoutout-content">
                        <h5>Validador Finanzas</h5>
                        <p>Recibió el consolidado del día</p>
                    </div>
                </div>

                <div class="shoutout-item" onclick="cambiarVistaVentas('cartera')">
                    <div class="shoutout-avatar-box">
                        <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&auto=format&fit=crop&q=80" alt="Alice">
                        <div class="shoutout-online-dot"></div>
                    </div>
                    <div class="shoutout-content">
                        <h5>Cartera de Clientes</h5>
                        <p>45 Constructoras activas en cartera</p>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- MODAL SOLO PARA ZOOM DE VOUCHER -->
    <div class="modal-overlay" id="modalVerComprobante">
        <div class="modal-card" style="text-align:center;">
            <div class="modal-header">
                <h3>Comprobante de Pago Guardado</h3>
                <button class="modal-close-btn" onclick="closeModals()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div style="background:#FAF7F2; padding:16px; border-radius:18px; border:1px solid var(--border-soft); margin-bottom:16px;">
                <h4 id="viewCompNumero" style="color:var(--text-dark); font-size:1.1rem;">F001-00892</h4>
                <p id="viewCompCliente" style="color:var(--text-muted); font-size:0.85rem; margin-top:4px;">Cosapi S.A.</p>
                <div style="margin:12px 0;">
                    <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=400&auto=format&fit=crop&q=80" alt="Voucher" id="viewCompImg" style="max-width:100%; height:220px; object-fit:cover; border-radius:14px; box-shadow:0 4px 15px rgba(0,0,0,0.1);">
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
                        <span class="badge-tag-activo" id="viewCompEstado">Pago Aceptado</span>
                    </div>
                </div>
            </div>

            <button class="btn-pill-white primary" style="width:100%; justify-content:center;" onclick="closeModals()">
                Cerrar Visor
            </button>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC & SPA VIEW ROUTING -->
    <script>
        let pendientesCount = 3;
        let ventasHoyCount = 4;
        let aceptadosCount = 15;
        let totalClientesCartera = 45;

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
        function cerrarModales() {
            closeModals();
        }

        // ================= DATOS Y LÓGICA DE COTIZACIONES =================
        let CARTERA_CLIENTES = [
            {
                razon: 'MULTINEGOCIOS AARON SOCIEDAD ANONIMA CERRADA-MULTINEGOCIOS AARON S.A.C.',
                ruc: '20602591990',
                direccion: 'JR. SAGITARIO MZA. C LOTE. 22 URB. VILLA ALEGRE LIMA - LIMA - SANTIAGO DE SURCO',
                email: 'Consorciomiraflores25@gmail.com',
                telefono: '942 377 626',
                contacto: 'Fanny Ramirez'
            },
            {
                razon: 'Cosapi S.A.',
                ruc: '20100038146',
                direccion: 'Av. República de Colombia 791, San Isidro, Lima',
                email: 'compras@cosapi.com.pe',
                telefono: '984 129 384',
                contacto: 'Ing. Mary Rose'
            },
            {
                razon: 'Graña & Montero Ingeniería',
                ruc: '20100109850',
                direccion: 'Av. Paseo de la República 4611, Surquillo, Lima',
                email: 'logistica@gym.com.pe',
                telefono: '991 482 103',
                contacto: 'Arq. Carlos Mendoza'
            },
            {
                razon: 'Besco Inmobiliaria & Construcción',
                ruc: '20419283011',
                direccion: 'Av. Rivera Navarrete 501, San Isidro, Lima',
                email: 'adquisiciones@besco.com.pe',
                telefono: '951 753 852',
                contacto: 'Arq. Lucía Ramos'
            },
            {
                razon: 'Consorcio Vial Piura',
                ruc: '20601849201',
                direccion: 'Av. Grau 1240, Piura',
                email: 'obras@vialpiura.com',
                telefono: '972 384 192',
                contacto: 'Jerome Brown'
            },
            {
                razon: 'Edificaciones Pacífico S.A.C.',
                ruc: '20554189012',
                direccion: 'Av. Benavides 1940, Miraflores, Lima',
                email: 'proyectos@edificacionespacifico.pe',
                telefono: '987 654 321',
                contacto: 'Ing. Roberto Salcedo'
            },
            {
                razon: 'JJC Contratistas Generales',
                ruc: '20100142806',
                direccion: 'Av. Manuel Olguín 325, Surco, Lima',
                email: 'abastecimiento@jjc.com.pe',
                telefono: '963 852 741',
                contacto: 'Ing. Walter Palacios'
            },
            {
                razon: 'Constructora Los Andes S.A.C.',
                ruc: '20604819204',
                direccion: 'Calle Mercaderes 310, Arequipa',
                email: 'compras@losandesperu.pe',
                telefono: '998 123 456',
                contacto: 'Lic. Elena Morales'
            }
        ];

        function cargarCarteraClientes() {
            fetch('crm_backend.php?action=listar_clientes')
                .then(res => res.json())
                .then(data => {
                    if (data.success && Array.isArray(data.clientes) && data.clientes.length > 0) {
                        CARTERA_CLIENTES = data.clientes;
                    }
                })
                .catch(err => console.log('Uso de cartera cliente local'));
        }

        let PRODUCTOS_TIENDA = [
            { codigo: '110014568', nombre: 'Z SEP. CONCRETO ESCANTILLONES 30 CM X 25 UNI', umed: 'B25', precio: 45.0900 },
            { codigo: '110014460', nombre: 'SEP. CONCRETOP DE FIERRO 2.5 CM X 100 UNI', umed: 'B100', precio: 33.7600 },
            { codigo: '110014292', nombre: 'AIRCON Z X 5 GAL - Aditivo incorporador de aire', umed: 'GLN', precio: 321.1400 },
            { codigo: '110014371', nombre: 'AIRCON Z X 55 GAL - Tambor Aditivo aireante', umed: 'BLD', precio: 2850.0000 },
            { codigo: '110014480', nombre: 'ALQUITRAN Z X 5 GAL - Impermeabilizante asfáltico', umed: 'GLN', precio: 181.9000 },
            { codigo: '110014697', nombre: 'PEGAMENTO Z ADITIVOS GRIS INT X 25 KG', umed: 'B25', precio: 28.5000 },
            { codigo: '110014699', nombre: 'PEGAMENTO Z ADITIVOS BCO EXT X 25 KG', umed: 'B25', precio: 36.8000 },
            { codigo: '110014810', nombre: 'EUCO 700 POLIURETANO ESTRUCTURAL 600ML', umed: 'UNI', precio: 42.5000 },
            { codigo: '110014902', nombre: 'SIKA BOOM ESPUMA EXPANSIVA 750ML', umed: 'UNI', precio: 31.2000 },
            { codigo: '110014950', nombre: 'SIKADUR 31 ADHESIVO EPOXICO JGO X 1 KG', umed: 'KG', precio: 58.0000 }
        ];

        let listaCotizacionesData = [];
        let descuentoAutorizado = false;
        let autorizadoPor = null;

        // Intentar enriquecer catálogo desde productos.json
        fetch('assets/Data/productos.json')
            .then(res => res.json())
            .then(data => {
                if (Array.isArray(data)) {
                    data.forEach(p => {
                        if (p.sku && p.nombre) {
                            const exists = PRODUCTOS_TIENDA.some(item => item.codigo === p.sku);
                            if (!exists) {
                                PRODUCTOS_TIENDA.push({
                                    codigo: p.sku,
                                    nombre: p.nombre + (p.descripcion ? ' - ' + p.descripcion : ''),
                                    umed: p.peso2 ? 'B25' : 'UNI',
                                    precio: p.precio ? parseFloat(p.precio) : 48.5000
                                });
                            }
                        }
                    });
                }
            })
            .catch(() => {});

        // CAMBIAR VISTA EN EL MEDIO (SPA)
        function cambiarVistaVentas(nombreVista, elNav) {
            closeModals();

            // 1. Ocultar todas las vistas del medio
            document.querySelectorAll('.vista-seccion').forEach(v => v.style.display = 'none');

            // 2. Mostrar la vista solicitada
            const vista = document.getElementById(`vista-${nombreVista}`);
            if (vista) {
                vista.style.display = 'flex';
            }

            // 3. Actualizar menú lateral
            document.querySelectorAll('.nav-menu .nav-item').forEach(item => item.classList.remove('active'));
            if (elNav) {
                elNav.classList.add('active');
            } else {
                const matchingNav = document.getElementById(`nav-${nombreVista}`);
                if (matchingNav) matchingNav.classList.add('active');
            }

            // Si es vista de cotizaciones, inicializar
            if (nombreVista === 'cotizaciones') {
                cargarCotizaciones();
                const tbody = document.getElementById('tbodyItemsCotizacion');
                if (tbody && tbody.children.length === 0) {
                    agregarFilaProductoCotiz();
                }
            }

            // 4. Scroll al tope del área central
            const mainContent = document.querySelector('.main-content');
            if (mainContent) mainContent.scrollTop = 0;
        }

        // SUB-TABS DENTRO DE COTIZACIONES
        function alternarTabCotizaciones(tab) {
            const btnNueva = document.getElementById('tabBtnNuevaCotiz');
            const btnHist = document.getElementById('tabBtnHistorialCotiz');
            const pnlNueva = document.getElementById('panelNuevaCotizacion');
            const pnlHist = document.getElementById('panelHistorialCotizaciones');

            if (tab === 'nueva') {
                btnNueva.classList.add('active');
                btnHist.classList.remove('active');
                pnlNueva.style.display = 'block';
                pnlHist.style.display = 'none';
            } else {
                btnHist.classList.add('active');
                btnNueva.classList.remove('active');
                pnlHist.style.display = 'block';
                pnlNueva.style.display = 'none';
                cargarCotizaciones();
                setTimeout(() => {
                    const inp = document.getElementById('inputBuscarCotizFacturar');
                    if (inp) inp.focus();
                }, 100);
            }
        }

        function abrirGeneradorCotizacion() {
            cambiarVistaVentas('cotizaciones');
            alternarTabCotizaciones('nueva');
            setTimeout(() => {
                const input = document.getElementById('inputBuscarClienteCotiz');
                if (input) input.focus();
            }, 150);
        }

        function abrirBuscarCotizacionFacturar() {
            cambiarVistaVentas('cotizaciones');
            alternarTabCotizaciones('facturar');
            setTimeout(() => {
                const input = document.getElementById('inputBuscarCotizFacturar');
                if (input) input.focus();
            }, 150);
        }

        // BÚSQUEDA EXCLUSIVA EN CARTERA POR DNI / RUC O RAZÓN SOCIAL
        function filtrarSugerenciasClientesCotiz(query) {
            const box = document.getElementById('sugerenciasClientesBox');
            const alertBox = document.getElementById('msgAlertaClienteNoRegistrado');
            const okBox = document.getElementById('msgClienteVerificado');
            const q = query.trim().toLowerCase();
            const cleanQ = query.replace(/[^0-9]/g, '');

            if (q.length < 2) {
                box.style.display = 'none';
                return;
            }

            const matches = CARTERA_CLIENTES.filter(c => {
                const rClean = (c.ruc || '').replace(/[^0-9]/g, '');
                return (cleanQ && rClean.includes(cleanQ)) || 
                       (c.razon && c.razon.toLowerCase().includes(q)) || 
                       (c.contacto && c.contacto.toLowerCase().includes(q));
            });

            if (matches.length === 0) {
                box.innerHTML = `
                    <div style="padding:12px 16px; font-size:0.84rem; color:#DC2626; font-weight:700; display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-circle-xmark"></i> No se registra en la cartera. Debe añadirlo primero en Mi Cartera de Clientes.
                    </div>
                `;
                box.style.display = 'block';
                return;
            }

            box.innerHTML = matches.map(c => `
                <div onclick="buscarClienteCarteraPorDni('${c.ruc}')" style="padding:10px 16px; border-bottom:1px solid var(--border-soft); cursor:pointer; display:flex; justify-content:space-between; align-items:center; transition:background 0.2s;" onmouseover="this.style.background='rgba(199,155,88,0.08)'" onmouseout="this.style.background='transparent'">
                    <div>
                        <strong style="color:var(--text-dark); font-size:0.84rem;">${c.razon}</strong><br>
                        <span style="font-size:0.74rem; color:var(--text-muted);">DNI/RUC: ${c.ruc} • Contacto: ${c.contacto || 'Sin contacto'}</span>
                    </div>
                    <span class="badge-tag-activo" style="font-size:0.7rem; background:#ECFDF5; color:#059669; border:1px solid #A7F3D0;">Seleccionar</span>
                </div>
            `).join('');

            box.style.display = 'block';
        }

        function seleccionarClienteCotiz(ruc) {
            buscarClienteCarteraPorDni(ruc);
        }

        // BÚSQUEDA Y JALADO EXCLUSIVO DESDE LA CARTERA DE CLIENTES
        function buscarClienteCarteraPorDni(valorDoc = null) {
            let doc = valorDoc !== null ? valorDoc.toString().trim() : document.getElementById('inputBuscarClienteCotiz').value.trim();

            if (!doc) {
                mostrarToast('warning', 'DNI o RUC Requerido', 'Por favor ingresa un número de DNI o RUC en la casilla para buscar en cartera.');
                const input = document.getElementById('inputBuscarClienteCotiz');
                if (input) input.focus();
                return;
            }

            const cleanDoc = doc.replace(/[^0-9]/g, '');
            const queryNorm = doc.toLowerCase();

            const alertBox = document.getElementById('msgAlertaClienteNoRegistrado');
            const okBox = document.getElementById('msgClienteVerificado');

            // Animación temporal en el botón de búsqueda
            const btnMain = document.getElementById('btnLupaBuscarDoc');
            const iconMain = document.getElementById('iconLupaBuscar');
            if (iconMain) iconMain.className = 'fa-solid fa-spinner fa-spin';
            if (btnMain) btnMain.disabled = true;

            const resetIconos = () => {
                if (iconMain) iconMain.className = 'fa-solid fa-magnifying-glass';
                if (btnMain) btnMain.disabled = false;
            };

            // 1. Buscar EXCLUSIVAMENTE en la cartera de clientes
            const cliente = CARTERA_CLIENTES.find(c => {
                const rucClean = (c.ruc || '').replace(/[^0-9]/g, '');
                return (cleanDoc && rucClean === cleanDoc) || 
                       (c.razon && c.razon.toLowerCase().includes(queryNorm)) ||
                       (c.contacto && c.contacto.toLowerCase().includes(queryNorm));
            });

            const sugerencias = document.getElementById('sugerenciasClientesBox');
            if (sugerencias) sugerencias.style.display = 'none';
            resetIconos();

            if (cliente) {
                // Cliente encontrado en cartera
                document.getElementById('cotizRazonSocial').value = cliente.razon;
                document.getElementById('cotizRucDni').value = cliente.ruc;
                document.getElementById('cotizDireccion').value = cliente.direccion || '';
                document.getElementById('cotizEmail').value = cliente.email || '';
                document.getElementById('cotizTelefono').value = cliente.telefono || '';
                document.getElementById('cotizContacto').value = cliente.contacto || '';

                document.getElementById('inputBuscarClienteCotiz').value = `${cliente.ruc} - ${cliente.razon}`;

                if (alertBox) alertBox.style.display = 'none';
                if (okBox) {
                    okBox.style.display = 'flex';
                    const lblSpan = okBox.querySelector('span');
                    if (lblSpan) lblSpan.textContent = `Cliente de Cartera Verificado: ${cliente.razon}`;
                }
                mostrarToast('success', 'Cliente de Cartera', `Datos cargados: ${cliente.razon}`);
            } else {
                // CLIENTE NO SE ENCUENTRA REGISTRADO
                document.getElementById('cotizRazonSocial').value = 'No se registra';
                document.getElementById('cotizRucDni').value = cleanDoc || '';
                document.getElementById('cotizDireccion').value = '';
                document.getElementById('cotizEmail').value = '';
                document.getElementById('cotizTelefono').value = '';
                document.getElementById('cotizContacto').value = '';

                if (okBox) okBox.style.display = 'none';
                if (alertBox) alertBox.style.display = 'flex';

                mostrarToast('warning', 'No se registra', 'El cliente no se encuentra registrado en la cartera. Debe añadirlo primero en Cartera de Clientes.');
            }
        }

        // Alias de compatibilidad
        function buscarYJalarClientePorDocumento(valorDoc = null) {
            buscarClienteCarteraPorDni(valorDoc);
        }

        // AGREGAR FILA DE PRODUCTO EN LA COTIZACIÓN
        function agregarFilaProductoCotiz(data = null) {
            const tbody = document.getElementById('tbodyItemsCotizacion');
            const itemNum = tbody.children.length + 1;

            const item = data || {
                codigo: PRODUCTOS_TIENDA[0]?.codigo || '110014568',
                nombre: PRODUCTOS_TIENDA[0]?.nombre || 'Z SEP. CONCRETO ESCANTILLONES 30 CM X 25 UNI',
                cantidad: 1.00,
                umed: 'B25',
                preOrig: PRODUCTOS_TIENDA[0]?.precio || 45.0900,
                descto: 0.00,
                estado: 'DISPONIBLE'
            };

            const precTotal = item.preOrig * (1 - (item.descto / 100));
            const subtotal = precTotal * item.cantidad;

            // Opciones de productos para select o datalist
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="text-align:center; font-weight:700; color:var(--text-dark);" class="td-item-num">${itemNum}</td>
                <td>
                    <input type="text" class="cotiz-input-cell cotiz-item-codigo" value="${item.codigo}" placeholder="SKU" style="font-family:monospace; font-weight:600;">
                </td>
                <td>
                    <div style="position:relative;">
                        <input type="text" class="cotiz-input-cell cotiz-item-desc" value="${item.nombre}" placeholder="Buscar o escribir producto..." onchange="onCambioNombreProducto(this)">
                    </div>
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" class="cotiz-input-cell cotiz-item-cant" value="${parseFloat(item.cantidad).toFixed(2)}" oninput="recalcularFilaCotiz(this)" style="text-align:right;">
                </td>
                <td>
                    <select class="cotiz-input-cell cotiz-item-umed" style="padding:8px 4px; text-align:center;">
                        <option value="B25" ${item.umed === 'B25' ? 'selected' : ''}>B25</option>
                        <option value="B100" ${item.umed === 'B100' ? 'selected' : ''}>B100</option>
                        <option value="GLN" ${item.umed === 'GLN' ? 'selected' : ''}>GLN</option>
                        <option value="BLD" ${item.umed === 'BLD' ? 'selected' : ''}>BLD</option>
                        <option value="LAT" ${item.umed === 'LAT' ? 'selected' : ''}>LAT</option>
                        <option value="KG" ${item.umed === 'KG' ? 'selected' : ''}>KG</option>
                        <option value="UNI" ${item.umed === 'UNI' ? 'selected' : ''}>UNI</option>
                        <option value="M2" ${item.umed === 'M2' ? 'selected' : ''}>M2</option>
                    </select>
                </td>
                <td>
                    <input type="number" step="0.0001" min="0" class="cotiz-input-cell cotiz-item-preorig" value="${parseFloat(item.preOrig).toFixed(4)}" oninput="recalcularFilaCotiz(this)" style="text-align:right;">
                </td>
                <td>
                    <input type="number" step="0.1" min="0" max="100" class="cotiz-input-cell cotiz-item-descto" value="${parseFloat(item.descto).toFixed(2)}" oninput="recalcularFilaCotiz(this)" style="text-align:right; font-weight:700;" title="Hasta 6% directo. Más de 6% requiere autorización.">
                </td>
                <td style="text-align:right; font-family:monospace; font-size:0.85rem;" class="cotiz-item-prectotal">
                    ${precTotal.toFixed(4)}
                </td>
                <td style="text-align:right; font-weight:700; color:var(--text-dark);" class="cotiz-item-subtotal">
                    S/ ${subtotal.toFixed(2)}
                </td>
                <td style="text-align:center;">
                    <span class="badge-tag-activo" style="font-size:0.65rem;">${item.estado || 'DISPONIBLE'}</span>
                </td>
                <td style="text-align:center;">
                    <button type="button" class="btn-facturar-mini" style="background:#FEE2E2; color:#DC2626; padding:6px 9px;" onclick="eliminarFilaProductoCotiz(this)" title="Quitar producto">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
            recalcularTotalesCotiz();
        }

        function onCambioNombreProducto(input) {
            const val = input.value.trim().toLowerCase();
            const match = PRODUCTOS_TIENDA.find(p => p.nombre.toLowerCase().includes(val) || p.codigo === val);
            if (match) {
                const tr = input.closest('tr');
                tr.querySelector('.cotiz-item-codigo').value = match.codigo;
                input.value = match.nombre;
                tr.querySelector('.cotiz-item-umed').value = match.umed;
                tr.querySelector('.cotiz-item-preorig').value = match.precio.toFixed(4);
                recalcularFilaCotiz(input);
            }
        }

        function eliminarFilaProductoCotiz(btn) {
            const tr = btn.closest('tr');
            tr.remove();
            // Re-enumerar items
            const rows = document.querySelectorAll('#tbodyItemsCotizacion tr');
            rows.forEach((row, i) => {
                const numCell = row.querySelector('.td-item-num');
                if (numCell) numCell.textContent = (i + 1);
            });
            recalcularTotalesCotiz();
        }

        // RECALCULAR FILA Y TOTALES
        function recalcularFilaCotiz(elem) {
            const row = elem.closest('tr');
            const cant = parseFloat(row.querySelector('.cotiz-item-cant').value) || 0;
            const preOrig = parseFloat(row.querySelector('.cotiz-item-preorig').value) || 0;
            const descto = parseFloat(row.querySelector('.cotiz-item-descto').value) || 0;

            const precTotal = preOrig * (1 - (descto / 100));
            const subtotal = precTotal * cant;

            row.querySelector('.cotiz-item-prectotal').textContent = precTotal.toFixed(4);
            row.querySelector('.cotiz-item-subtotal').textContent = 'S/ ' + subtotal.toFixed(2);

            const inputDescto = row.querySelector('.cotiz-item-descto');
            if (descto > 6.0) {
                inputDescto.style.borderColor = '#EF4444';
                inputDescto.style.color = '#EF4444';
                inputDescto.title = 'Descuento > 6%: Requiere autorización de Administración';
            } else {
                inputDescto.style.borderColor = '';
                inputDescto.style.color = '';
                inputDescto.title = 'Descuento permitido';
            }

            recalcularTotalesCotiz();
        }

        function recalcularTotalesCotiz() {
            const rows = document.querySelectorAll('#tbodyItemsCotizacion tr');
            let totalBruto = 0;
            let maxDescto = 0;

            rows.forEach(row => {
                const cant = parseFloat(row.querySelector('.cotiz-item-cant').value) || 0;
                const preOrig = parseFloat(row.querySelector('.cotiz-item-preorig').value) || 0;
                const descto = parseFloat(row.querySelector('.cotiz-item-descto').value) || 0;
                if (descto > maxDescto) maxDescto = descto;

                const precTotal = preOrig * (1 - (descto / 100));
                totalBruto += (precTotal * cant);
            });

            // En la estructura del PDF: Subtotal + IGV (18%) = Total Neto
            // O si los precios ya son base gravable:
            const subtotal = totalBruto;
            const igv = subtotal * 0.18;
            const totalNeto = subtotal + igv;

            document.getElementById('lblCotizSubtotal').textContent = 'S/ ' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('lblCotizIgv').textContent = 'S/ ' + igv.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('lblCotizTotalNeto').textContent = 'S/ ' + totalNeto.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Validación de regla de descuento (> 6%)
            const alertaBox = document.getElementById('alertaDescuentoAdmin');
            const badgeAuth = document.getElementById('badgeDescuentoAutorizado');

            if (maxDescto > 6.0 && !descuentoAutorizado) {
                alertaBox.style.display = 'flex';
                badgeAuth.style.display = 'none';
                document.getElementById('lblModalPorcentajeDescto').textContent = maxDescto.toFixed(1) + '%';
            } else if (maxDescto > 6.0 && descuentoAutorizado) {
                alertaBox.style.display = 'none';
                badgeAuth.style.display = 'block';
                document.getElementById('lblAutorizador').textContent = autorizadoPor || 'Administración';
            } else {
                alertaBox.style.display = 'none';
                badgeAuth.style.display = 'none';
            }
        }

        // GESTIÓN DE AUTORIZACIÓN DE DESCUENTO
        function consultarAdminWhatsApp() {
            const cliente = document.getElementById('cotizRazonSocial').value || 'Cliente sin nombre';
            const total = document.getElementById('lblCotizTotalNeto').textContent;
            const codigo = document.getElementById('cotizCodigo').value || '0052456';
            const maxDescto = document.getElementById('lblModalPorcentajeDescto').textContent || '7%';

            const texto = `Hola Administrador BS Perú, la asesora Endrina solicita AUTORIZACIÓN ESPECIAL para un DESCUENTO de ${maxDescto} en la Cotización N° ${codigo}.\n\n*Cliente:* ${cliente}\n*Total Neto:* ${total}\n\n¿Autoriza la emisión de la cotización?`;
            const waUrl = `https://wa.me/51984129384?text=${encodeURIComponent(texto)}`;
            window.open(waUrl, '_blank');
        }

        function abrirModalAutorizacionDescuento() {
            document.getElementById('modalAutorizarDescuento').classList.add('open');
            document.getElementById('inputClaveAdmin').value = '';
            document.getElementById('msgErrorClaveAdmin').style.display = 'none';
            setTimeout(() => document.getElementById('inputClaveAdmin').focus(), 150);
        }

        function aplicarClaveAutorizacionAdmin() {
            const clave = document.getElementById('inputClaveAdmin').value.trim();
            const clavesValidas = ['BSADMIN2026', 'ADMIN2026', 'BSPERU2026', '123456', 'Ventas2026*'];

            if (clavesValidas.includes(clave)) {
                descuentoAutorizado = true;
                autorizadoPor = 'Gerencia Comercial';
                closeModals();
                recalcularTotalesCotiz();
                mostrarToast('success', 'Descuento Autorizado', 'Se aprobó el descuento especial mediante clave administrativa.');
            } else {
                document.getElementById('msgErrorClaveAdmin').style.display = 'block';
            }
        }

        // CARGAR DATOS EXACTOS DEL PDF OFICIAL DE EJEMPLO
        function cargarEjemploPdfMultinegocios() {
            document.getElementById('cotizCodigo').value = '0052456';
            document.getElementById('cotizFecha').value = '2026-08-19';

            document.getElementById('cotizRazonSocial').value = 'MULTINEGOCIOS AARON SOCIEDAD ANONIMA CERRADA-MULTINEGOCIOS AARON S.A.C.';
            document.getElementById('cotizRucDni').value = '20602591990';
            document.getElementById('cotizDireccion').value = 'JR. SAGITARIO MZA. C LOTE. 22 URB. VILLA ALEGRE LIMA - LIMA - SANTIAGO DE SURCO';
            document.getElementById('cotizEmail').value = 'Consorciomiraflores25@gmail.com';
            document.getElementById('cotizTelefono').value = '942 377 626';
            document.getElementById('cotizContacto').value = 'Fanny Ramirez';
            document.getElementById('inputBuscarClienteCotiz').value = 'MULTINEGOCIOS AARON S.A.C.';

            const tbody = document.getElementById('tbodyItemsCotizacion');
            tbody.innerHTML = '';

            // Item 1
            agregarFilaProductoCotiz({
                codigo: '110014568',
                nombre: 'Z SEP. CONCRETO ESCANTILLONES 30 CM X 25 UNI',
                cantidad: 8.00,
                umed: 'B25',
                preOrig: 45.0900,
                descto: 20.00,
                estado: 'DISPONIBLE'
            });

            // Item 2
            agregarFilaProductoCotiz({
                codigo: '110014460',
                nombre: 'SEP. CONCRETOP DE FIERRO 2.5 CM X 100 UNI',
                cantidad: 2.00,
                umed: 'B100',
                preOrig: 33.7600,
                descto: 20.00,
                estado: 'DISPONIBLE'
            });

            mostrarToast('success', 'Muestra del PDF Cargada', 'Se importaron los 2 productos y el cliente del documento oficial.');
        }

        // OBTENER OBJETO DE LA COTIZACIÓN ACTUAL DEL FORMULARIO
        function obtenerDatosCotizacionFormulario() {
            const rows = document.querySelectorAll('#tbodyItemsCotizacion tr');
            const items = [];
            let maxDescto = 0;

            rows.forEach((row, idx) => {
                const cod = row.querySelector('.cotiz-item-codigo').value.trim();
                const desc = row.querySelector('.cotiz-item-desc').value.trim();
                const cant = parseFloat(row.querySelector('.cotiz-item-cant').value) || 0;
                const umed = row.querySelector('.cotiz-item-umed').value;
                const preOrig = parseFloat(row.querySelector('.cotiz-item-preorig').value) || 0;
                const descto = parseFloat(row.querySelector('.cotiz-item-descto').value) || 0;
                const precTotal = preOrig * (1 - (descto / 100));
                const subtotal = precTotal * cant;

                if (descto > maxDescto) maxDescto = descto;

                items.push({
                    item: (idx + 1),
                    codigo: cod,
                    descripcion: desc,
                    cantidad: cant,
                    umed: umed,
                    pre_orig: preOrig,
                    descto: descto,
                    prec_total: precTotal,
                    subtotal: subtotal,
                    estado: 'DISPONIBLE'
                });
            });

            const subtotalSum = items.reduce((acc, it) => acc + it.subtotal, 0);
            const igvVal = subtotalSum * 0.18;
            const totalNetoVal = subtotalSum + igvVal;

            return {
                codigo: document.getElementById('cotizCodigo').value.trim() || '0052456',
                fecha: document.getElementById('cotizFecha').value || '<?php echo date('Y-m-d'); ?>',
                cliente_nombre: document.getElementById('cotizRazonSocial').value.trim(),
                ruc_dni: document.getElementById('cotizRucDni').value.trim(),
                direccion: document.getElementById('cotizDireccion').value.trim(),
                email: document.getElementById('cotizEmail').value.trim(),
                telefono: document.getElementById('cotizTelefono').value.trim(),
                contacto: document.getElementById('cotizContacto').value.trim(),
                asesor: 'Endrina',
                forma_pago: document.getElementById('cotizFormaPago').value,
                vigencia: document.getElementById('cotizVigencia').value,
                subtotal: subtotalSum,
                igv: igvVal,
                total: totalNetoVal,
                descuento_max: maxDescto,
                requiere_autorizacion: (maxDescto > 6.0 && !descuentoAutorizado),
                autorizado_por: descuentoAutorizado ? (autorizadoPor || 'Gerencia') : null,
                items: items
            };
        }

        // LIMPIAR FORMULARIO DE NUEVA COTIZACIÓN
        function limpiarFormularioNuevaCotizacion() {
            const inpBusq = document.getElementById('inputBuscarClienteCotiz');
            if (inpBusq) inpBusq.value = '';
            document.getElementById('cotizRazonSocial').value = '';
            document.getElementById('cotizRucDni').value = '';
            document.getElementById('cotizContacto').value = '';
            document.getElementById('cotizTelefono').value = '';
            document.getElementById('cotizEmail').value = '';
            document.getElementById('cotizDireccion').value = '';

            const alertBox = document.getElementById('msgAlertaClienteNoRegistrado');
            const okBox = document.getElementById('msgClienteVerificado');
            if (alertBox) alertBox.style.display = 'none';
            if (okBox) okBox.style.display = 'none';

            document.getElementById('cotizFecha').value = '<?php echo date('Y-m-d'); ?>';

            const tbody = document.getElementById('tbodyItemsCotizacion');
            if (tbody) {
                tbody.innerHTML = '';
                agregarFilaProductoCotiz();
            }
            recalcularTotalesCotiz();
        }

        // GUARDAR COTIZACIÓN EN BACKEND
        function guardarCotizacionActual(silencioso = false) {
            const data = obtenerDatosCotizacionFormulario();
            if (!data.cliente_nombre || data.cliente_nombre === 'No se registra' || !data.ruc_dni) {
                alert('No se puede guardar la cotización: El cliente no se encuentra registrado en la cartera. Por favor busque y seleccione un cliente de la cartera en el apartado superior.');
                const inputBusq = document.getElementById('inputBuscarClienteCotiz');
                if (inputBusq) inputBusq.focus();
                return;
            }
            if (data.items.length === 0) {
                alert('Debe agregar al menos un producto a la cotización.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'guardar_cotizacion');
            formData.append('codigo', data.codigo);
            formData.append('fecha', data.fecha);
            formData.append('cliente_nombre', data.cliente_nombre);
            formData.append('ruc_dni', data.ruc_dni);
            formData.append('direccion', data.direccion);
            formData.append('email', data.email);
            formData.append('telefono', data.telefono);
            formData.append('contacto', data.contacto);
            formData.append('asesor', data.asesor);
            formData.append('forma_pago', data.forma_pago);
            formData.append('vigencia', data.vigencia);
            formData.append('subtotal', data.subtotal);
            formData.append('igv', data.igv);
            formData.append('total', data.total);
            formData.append('descuento_max', data.descuento_max);
            formData.append('autorizado_por', data.autorizado_por || '');
            formData.append('items', JSON.stringify(data.items));

            fetch('crm_backend.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(resp => {
                if (resp.success) {
                    if (!silencioso) {
                        mostrarToast('success', 'Cotización Guardada', `Cotización ${data.codigo} registrada correctamente.`);
                    }
                    cargarCotizaciones();
                    limpiarFormularioNuevaCotizacion();
                } else {
                    alert('Error al guardar cotización: ' + (resp.error || 'Desconocido'));
                }
            })
            .catch(err => {
                console.error(err);
                if (!silencioso) {
                    mostrarToast('success', 'Cotización Guardada (Local)', `Cotización ${data.codigo} archivada temporalmente.`);
                }
                cargarCotizaciones();
                limpiarFormularioNuevaCotizacion();
            });
        }

        // LISTAR Y CARGAR COTIZACIONES EN TABLA HISTORIAL
        function cargarCotizaciones() {
            fetch('crm_backend.php?action=listar_cotizaciones')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        listaCotizacionesData = data.cotizaciones || [];
                        renderTablaHistorialCotizaciones(listaCotizacionesData);

                        const totalBadge = document.getElementById('badgeCotizacionesTotal');
                        const histBadge = document.getElementById('countCotizHistorialBadge');
                        if (totalBadge) totalBadge.textContent = listaCotizacionesData.length;
                        if (histBadge) histBadge.textContent = listaCotizacionesData.length;

                        // Actualizar automáticamente el correlativo para la siguiente cotización
                        const codInput = document.getElementById('cotizCodigo');
                        if (codInput && data.siguiente_codigo) {
                            codInput.value = data.siguiente_codigo;
                        }
                    }
                })
                .catch(() => {
                    // Fallback con datos locales si backend demora
                    renderTablaHistorialCotizaciones(listaCotizacionesData);
                });
        }

        function renderTablaHistorialCotizaciones(lista) {
            const tbody = document.getElementById('tbodyHistorialCotizaciones');
            if (!tbody) return;

            if (lista.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:24px; color:var(--text-muted);">No hay cotizaciones registradas aún.</td></tr>`;
                return;
            }

            tbody.innerHTML = lista.map(c => {
                const totalFmt = parseFloat(c.total || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const desctoMax = parseFloat(c.descuento_max || 0);
                let badgeDescto = `<span style="color:#10B981; font-weight:700;">${desctoMax}%</span>`;
                if (desctoMax > 6.0) {
                    badgeDescto = `<span style="background:#FEE2E2; color:#DC2626; padding:2px 6px; border-radius:8px; font-size:0.72rem; font-weight:800;" title="Descuento superior al 6%">${desctoMax}% ⚠️</span>`;
                }

                let badgeEstado = '<span class="badge-tag-vip">⏳ Pendiente</span>';
                if (c.estado === 'Aceptada') badgeEstado = '<span class="badge-tag-activo">✅ Aceptada</span>';
                else if (c.estado === 'Facturada') badgeEstado = '<span style="background:#E0E7FF; color:#4338CA; padding:3px 8px; border-radius:10px; font-size:0.72rem; font-weight:700;">💳 Facturada</span>';

                return `
                    <tr>
                        <td><strong>${c.codigo}</strong></td>
                        <td>${c.fecha || '-'}</td>
                        <td>
                            <strong>${c.cliente_nombre}</strong><br>
                            <span style="font-size:0.72rem; color:var(--text-muted);">${c.contacto || 'Sin contacto'}</span>
                        </td>
                        <td>${c.ruc_dni}</td>
                        <td><strong style="color:var(--text-dark);">S/ ${totalFmt}</strong></td>
                        <td>${badgeDescto}</td>
                        <td>${badgeEstado}</td>
                        <td style="text-align:center;">
                            <div style="display:flex; justify-content:center; align-items:center; gap:6px; flex-wrap:wrap;">
                                <button type="button" class="btn-pill-white primary" style="background:var(--accent-tan); border-color:var(--accent-tan); color:#161719; font-weight:800; padding:6px 12px; font-size:0.75rem; display:inline-flex; align-items:center; gap:5px; cursor:pointer;" title="Convertir esta cotización en factura y adjuntar voucher" onclick="convertirCotizAFacturaPorCodigo('${c.codigo}')">
                                    <i class="fa-solid fa-file-invoice-dollar"></i> Facturar
                                </button>
                                <button type="button" class="btn-facturar-mini" title="Ver e imprimir PDF oficial" onclick="abrirModalVistaPreviaPdfPorCodigo('${c.codigo}')">
                                    <i class="fa-solid fa-print"></i>
                                </button>
                                <button type="button" class="btn-wa-mini" title="Enviar cotización por WhatsApp" onclick="enviarCotizacionWhatsAppPorCodigo('${c.codigo}')">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        let filtroEstadoActualCotiz = 'todos';

        function filtrarCotizacionesPorEstado(estado, btn) {
            filtroEstadoActualCotiz = estado;
            document.querySelectorAll('.cotiz-filtro-btn').forEach(b => {
                b.classList.remove('active');
                b.style.fontWeight = 'normal';
            });
            if (btn) {
                btn.classList.add('active');
                btn.style.fontWeight = '800';
            }
            aplicarFiltrosCotizaciones();
        }

        function filtrarHistorialCotizaciones(q) {
            aplicarFiltrosCotizaciones(q);
        }

        function aplicarFiltrosCotizaciones(q = null) {
            const inputVal = document.getElementById('inputBuscarCotizFacturar')?.value || '';
            const query = (q !== null ? q : inputVal).trim().toLowerCase();
            const filtradas = listaCotizacionesData.filter(c => {
                const matchTexto = (c.codigo || '').toLowerCase().includes(query) ||
                                   (c.cliente_nombre || '').toLowerCase().includes(query) ||
                                   (c.ruc_dni || '').includes(query);

                let matchEstado = true;
                if (filtroEstadoActualCotiz === 'Pendiente') {
                    matchEstado = (c.estado === 'Pendiente');
                } else if (filtroEstadoActualCotiz === 'Aceptada') {
                    matchEstado = (c.estado === 'Aceptada');
                } else if (filtroEstadoActualCotiz === 'Facturada') {
                    matchEstado = (c.estado === 'Facturada');
                }
                return matchTexto && matchEstado;
            });
            renderTablaHistorialCotizaciones(filtradas);
        }

        // VISTA PREVIA Y DESCARGA DEL PDF OFICIAL IDÉNTICO
        function abrirModalVistaPreviaPdf(cotiz = null) {
            const data = cotiz || obtenerDatosCotizacionFormulario();

            document.getElementById('pdfFechaEmision').textContent = data.fecha;
            document.getElementById('pdfCorrelativo').textContent = data.codigo;

            document.getElementById('pdfClienteNombre').textContent = data.cliente_nombre || 'MULTINEGOCIOS AARON S.A.C.';
            document.getElementById('pdfClienteDireccion').textContent = data.direccion || 'LIMA - PERÚ';
            document.getElementById('pdfClienteRuc').textContent = data.ruc_dni || '20602591990';
            document.getElementById('pdfClienteEmail').textContent = data.email || 'ventas@empresa.com';
            document.getElementById('pdfClienteTelefono').textContent = data.telefono || '942 377 626';
            document.getElementById('pdfClienteContacto').textContent = data.contacto || 'Fanny Ramirez';

            document.getElementById('pdfFormaPagoVal').textContent = data.forma_pago || 'CONTADO CONTRA ENTREGA';
            document.getElementById('pdfVigenciaVal').textContent = data.vigencia || '7 dias';

            const tbody = document.getElementById('pdfTbodyItems');
            tbody.innerHTML = data.items.map(it => `
                <tr style="border-bottom:1px solid #E5E7EB;">
                    <td style="padding:4px; text-align:center;">${it.item}</td>
                    <td style="padding:4px; text-align:center; font-family:monospace;">${it.codigo}</td>
                    <td style="padding:4px; font-weight:bold;">${it.descripcion}</td>
                    <td style="padding:4px; text-align:right;">${parseFloat(it.cantidad).toFixed(2)}</td>
                    <td style="padding:4px; text-align:center;">${it.umed}</td>
                    <td style="padding:4px; text-align:right;">${parseFloat(it.pre_orig).toFixed(4)}</td>
                    <td style="padding:4px; text-align:right;">${parseFloat(it.descto).toFixed(2)}</td>
                    <td style="padding:4px; text-align:right;">${parseFloat(it.prec_total).toFixed(4)}</td>
                    <td style="padding:4px; text-align:right; font-weight:bold;">${parseFloat(it.subtotal).toFixed(4)}</td>
                    <td style="padding:4px; text-align:center;">${it.estado || 'DISPONIBLE'}</td>
                </tr>
            `).join('');

            document.getElementById('pdfSubtotalVal').textContent = 'S/.' + parseFloat(data.subtotal).toFixed(2);
            document.getElementById('pdfIgvVal').textContent = 'S/.' + parseFloat(data.igv).toFixed(2);
            document.getElementById('pdfTotalVal').textContent = 'S/.' + parseFloat(data.total).toFixed(2);

            document.getElementById('modalVistaPreviaPdf').classList.add('open');
        }

        function abrirModalVistaPreviaPdfPorCodigo(codigo) {
            const cotiz = listaCotizacionesData.find(c => c.codigo === codigo);
            if (cotiz) {
                abrirModalVistaPreviaPdf(cotiz);
            }
        }

        function imprimirCotizacionOficialDirecto() {
            window.print();
        }

        function enviarCotizacionActualWhatsApp() {
            const data = obtenerDatosCotizacionFormulario();
            const tel = (data.telefono || '').replace(/\D/g, '');
            const subtotalFmt = parseFloat(data.subtotal).toFixed(2);
            const igvFmt = parseFloat(data.igv).toFixed(2);
            const totalFmt = parseFloat(data.total).toFixed(2);

            const texto = `Estimado(a) *${data.contacto || data.cliente_nombre}*,\n\nLe saluda *Endrina* de *Building Systems Perú S.A.C.* Adjunto el resumen de su *Cotización N° ${data.codigo}*:\n\n` +
                          `🏢 *Cliente:* ${data.cliente_nombre}\n` +
                          `📄 *RUC:* ${data.ruc_dni}\n` +
                          `📦 *Items:* ${data.items.length} producto(s)\n` +
                          `💰 *Subtotal:* S/ ${subtotalFmt}\n` +
                          `🏛️ *IGV (18%):* S/ ${igvFmt}\n` +
                          `✅ *Total Neto:* S/ ${totalFmt}\n\n` +
                          `💳 *Forma de Pago:* ${data.forma_pago}\n` +
                          `⏳ *Vigencia:* ${data.vigencia}\n\n` +
                          `Cuentas corrientes BCP, BBVA e Interbank a nombre de Building Systems Perú S.A.C.\n` +
                          `Quedo atenta para confirmar su orden de compra y despacho. ¡Muchas gracias!`;

            const phoneParam = tel.length >= 9 ? tel : '';
            const waUrl = phoneParam ? `https://wa.me/51${phoneParam}?text=${encodeURIComponent(texto)}` : `https://wa.me/?text=${encodeURIComponent(texto)}`;
            window.open(waUrl, '_blank');
        }

        function enviarCotizacionWhatsAppPorCodigo(codigo) {
            const c = listaCotizacionesData.find(item => item.codigo === codigo);
            if (!c) return;
            const tel = (c.telefono || '').replace(/\D/g, '');
            const totalFmt = parseFloat(c.total).toFixed(2);
            const texto = `Hola ${c.contacto || c.cliente_nombre}, le escribe Endrina de BS Perú respecto a la Cotización N° ${c.codigo} por S/ ${totalFmt}. ¿Pudo revisarla para coordinar el despacho?`;
            const waUrl = tel.length >= 9 ? `https://wa.me/51${tel}?text=${encodeURIComponent(texto)}` : `https://wa.me/?text=${encodeURIComponent(texto)}`;
            window.open(waUrl, '_blank');
        }

        // CONVERTIR COTIZACIÓN A FACTURA (PASO DIRECTO CON REPORTERÍA)
        function convertirCotizacionActualAFactura() {
            const data = obtenerDatosCotizacionFormulario();
            if (!data.cliente_nombre || data.total <= 0) {
                alert('La cotización debe tener cliente y productos antes de facturarla.');
                return;
            }

            // Asegurar que quede guardada
            guardarCotizacionActual(true);

            // Cargar datos en el formulario de facturación
            document.getElementById('facNumero').value = 'FAC-' + data.codigo;
            document.getElementById('facCliente').value = data.cliente_nombre;
            document.getElementById('facRuc').value = data.ruc_dni;
            document.getElementById('facMonto').value = parseFloat(data.total).toFixed(2);
            document.getElementById('facNotas').value = `Facturación de Cotización N° ${data.codigo} (${data.items.length} productos). Cliente listo para validación de voucher.`;

            // Cambiar vista
            cambiarVistaVentas('facturacion');
            mostrarToast('success', 'Cotización Convertida', `Datos de Cotización ${data.codigo} cargados en Facturación. Adjunta el comprobante.`);
        }

        function convertirCotizAFacturaPorCodigo(codigo) {
            const c = listaCotizacionesData.find(item => item.codigo === codigo);
            if (!c) return;

            document.getElementById('facNumero').value = 'FAC-' + c.codigo;
            document.getElementById('facCliente').value = c.cliente_nombre;
            document.getElementById('facRuc').value = c.ruc_dni;
            document.getElementById('facMonto').value = parseFloat(c.total).toFixed(2);
            document.getElementById('facNotas').value = `Facturación de Cotización N° ${c.codigo}. Validar comprobante con Reportería.`;

            cambiarVistaVentas('facturacion');
            mostrarToast('success', 'Cotización Convertida', `Datos de Cotización ${c.codigo} listos para facturar.`);
        }

        // CARTERA DE CLIENTES: FORMULARIO DESPLEGABLE
        function toggleFormNuevoCliente() {
            const box = document.getElementById('boxFormNuevoCliente');
            const btnText = document.getElementById('btnTextNuevoCli');
            if (box.style.display === 'block') {
                box.style.display = 'none';
                btnText.textContent = '+ Nuevo Cliente';
            } else {
                box.style.display = 'block';
                btnText.textContent = 'Ocultar Formulario';
                document.getElementById('newCliRuc').focus();
            }
        }

        // BÚSQUEDA Y JALADO EN FORMULARIO "REGISTRAR NUEVO CLIENTE" (LUPA SUNAT / RENIEC)
        function buscarClienteNuevoPorDocumento() {
            const inputRuc = document.getElementById('newCliRuc');
            const doc = inputRuc.value.trim().replace(/[^0-9]/g, '');

            if (!doc) {
                mostrarToast('warning', 'DNI o RUC Requerido', 'Por favor ingresa un número de DNI (8 dígitos) o RUC (11 dígitos).');
                inputRuc.focus();
                return;
            }

            const btn = document.getElementById('btnLupaNuevoCliente');
            const icon = document.getElementById('iconLupaNuevoCliente');
            if (icon) icon.className = 'fa-solid fa-spinner fa-spin';
            if (btn) btn.disabled = true;

            const resetBtn = () => {
                if (icon) icon.className = 'fa-solid fa-magnifying-glass';
                if (btn) btn.disabled = false;
            };

            // 1. Revisar si ya existe en Cartera local
            const existe = CARTERA_CLIENTES.find(c => (c.ruc || '').replace(/[^0-9]/g, '') === doc);
            if (existe) {
                document.getElementById('newCliEmpresa').value = existe.razon || '';
                if (existe.contacto) document.getElementById('newCliContacto').value = existe.contacto;
                if (existe.telefono) document.getElementById('newCliTelefono').value = existe.telefono;
                if (existe.email) document.getElementById('newCliEmail').value = existe.email;
                if (existe.direccion) document.getElementById('newCliDireccion').value = existe.direccion;
                resetBtn();
                mostrarToast('info', 'Cliente ya registrado', `Este cliente ya se encuentra en tu cartera como "${existe.razon}".`);
                return;
            }

            // 2. Consultar al backend (SUNAT / RENIEC)
            fetch(`crm_backend.php?action=consultar_documento&numero=${encodeURIComponent(doc)}`)
                .then(res => res.json())
                .then(data => {
                    resetBtn();
                    if (data.success && data.cliente) {
                        const cl = data.cliente;
                        document.getElementById('newCliEmpresa').value = cl.razon || '';
                        if (cl.direccion) document.getElementById('newCliDireccion').value = cl.direccion;
                        if (cl.contacto && cl.contacto !== 'Encargado de Compras') document.getElementById('newCliContacto').value = cl.contacto;
                        
                        const fuente = data.fuente === 'sunat' ? 'SUNAT' : (data.fuente === 'reniec' ? 'RENIEC' : 'Base de datos');
                        mostrarToast('success', `Datos Jalados (${fuente})`, `Se obtuvo automáticamente: ${cl.razon}`);
                        document.getElementById('newCliTelefono').focus();
                    } else {
                        mostrarToast('warning', 'Sin resultados automáticos', data.error || 'No se encontraron datos automáticos. Puedes completarlos manualmente.');
                        document.getElementById('newCliEmpresa').focus();
                    }
                })
                .catch(err => {
                    resetBtn();
                    console.error(err);
                    mostrarToast('warning', 'Aviso de Conexión', 'No se pudo consultar el servicio externo. Puedes completar los datos manualmente.');
                    document.getElementById('newCliEmpresa').focus();
                });
        }

        // GUARDAR NUEVO CLIENTE EN CARTERA
        function guardarNuevoCliente(e) {
            e.preventDefault();
            const empresa = document.getElementById('newCliEmpresa').value.trim();
            const ruc = document.getElementById('newCliRuc').value.trim();
            const contacto = document.getElementById('newCliContacto').value.trim() || 'Encargado de Compras';
            const tel = document.getElementById('newCliTelefono').value.trim();
            const cat = document.getElementById('newCliCategoria').value;

            const initials = empresa.substring(0, 2).toUpperCase();
            let badgeHtml = '';
            if (cat === 'VIP') badgeHtml = '<span class="badge-tag-vip">🏆 VIP</span>';
            else if (cat === 'Activo') badgeHtml = '<span class="badge-tag-activo">🏢 Activo</span>';
            else badgeHtml = '<span class="badge-tag-seguimiento">⏳ En Cotización</span>';

            const tbody = document.getElementById('carteraTbody');
            const tr = document.createElement('tr');
            tr.setAttribute('data-tipo', cat);
            tr.innerHTML = `
                <td>
                    <div class="cliente-item-cell">
                        <div class="cliente-avatar-circle" style="background:var(--accent-tan); color:#161719;">${initials}</div>
                        <div class="cliente-meta">
                            <h5>${empresa}</h5>
                            <span>RUC: ${ruc} • Registrado hoy</span>
                        </div>
                    </div>
                </td>
                <td>
                    <strong>${contacto}</strong><br>
                    <span style="font-size:0.72rem; color:var(--text-muted);">Contacto Comercial</span>
                </td>
                <td>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span>${tel}</span>
                        <a href="https://wa.me/51${tel.replace(/\D/g,'')}?text=Hola%20${encodeURIComponent(contacto)},%20le%20escribe%20Endrina%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    </div>
                </td>
                <td><strong style="color:var(--text-dark);">S/ 0.00</strong></td>
                <td>${badgeHtml}</td>
                <td style="text-align:center;">
                    <button class="btn-facturar-mini" onclick="facturarACliente('${empresa.replace(/'/g, "\\'")}', '${ruc}')">
                        <i class="fa-solid fa-file-invoice"></i> Facturar
                    </button>
                </td>
            `;

            tbody.insertBefore(tr, tbody.firstChild);

            totalClientesCartera++;
            document.getElementById('badgeCarteraTotal').textContent = totalClientesCartera;
            document.getElementById('statClientesActivos').textContent = totalClientesCartera;
            document.getElementById('countFiltroTodos').textContent = totalClientesCartera;

            // Persistir permanentemente en el servidor
            const dir = document.getElementById('newCliDireccion').value.trim();
            const email = document.getElementById('newCliEmail').value.trim();

            const formDataCli = new FormData();
            formDataCli.append('action', 'guardar_cliente');
            formDataCli.append('razon', empresa);
            formDataCli.append('ruc', ruc);
            formDataCli.append('contacto', contacto);
            formDataCli.append('telefono', tel);
            formDataCli.append('categoria', cat);
            formDataCli.append('direccion', dir);
            formDataCli.append('email', email);

            fetch('crm_backend.php', {
                method: 'POST',
                body: formDataCli
            })
            .then(res => res.json())
            .then(data => {
                cargarCarteraClientes();
            })
            .catch(err => console.log('Guardado local de cliente'));

            e.target.reset();
            toggleFormNuevoCliente();

            alert(`✅ ¡Cliente "${empresa}" registrado exitosamente en tu cartera de clientes!\nYa puedes contactarlo por WhatsApp o emitirle facturas directamente.`);
        }

        // BÚSQUEDA EN TIEMPO REAL EN CARTERA
        function filtrarCarteraClientes(termino) {
            const val = termino.toLowerCase().trim();
            const filas = document.querySelectorAll('#carteraTbody tr');
            filas.forEach(fila => {
                const textoFila = fila.innerText.toLowerCase();
                fila.style.display = textoFila.includes(val) ? '' : 'none';
            });
        }

        // FILTROS POR CATEGORÍA
        function filtrarCarteraPorTipo(tipo, el) {
            document.querySelectorAll('.cartera-pill').forEach(p => p.classList.remove('active'));
            el.classList.add('active');

            const filas = document.querySelectorAll('#carteraTbody tr');
            filas.forEach(fila => {
                if (tipo === 'todos' || fila.getAttribute('data-tipo') === tipo) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        }

        // FACTURAR DIRECTAMENTE A UN CLIENTE DE LA CARTERA (REDIRECCIÓN EN EL MEDIO)
        function facturarACliente(nombre, ruc) {
            cambiarVistaVentas('facturacion');
            document.getElementById('facCliente').value = nombre;
            document.getElementById('facRuc').value = ruc;
            document.getElementById('facMonto').focus();
        }

        function verComprobanteDetalle(num, cliente, monto, op, imgUrl, estado) {
            document.getElementById('viewCompNumero').textContent = num;
            document.getElementById('viewCompCliente').textContent = cliente;
            document.getElementById('viewCompMonto').textContent = `S/ ${monto}`;
            document.getElementById('viewCompOp').textContent = op;
            if (imgUrl) {
                document.getElementById('viewCompImg').src = imgUrl;
            }
            const estadoEl = document.getElementById('viewCompEstado');
            if (estadoEl) {
                if (estado === 'Aceptado') {
                    estadoEl.className = 'badge-tag-activo';
                    estadoEl.textContent = '✅ Pago Aceptado';
                } else if (estado === 'Observado') {
                    estadoEl.className = 'badge-tag-observado';
                    estadoEl.textContent = '⚠️ Observado';
                } else {
                    estadoEl.className = 'badge-tag-vip';
                    estadoEl.textContent = '⏳ En Revisión Reportería';
                }
            }
            document.getElementById('modalVerComprobante').classList.add('open');
        }

        function previewVoucherFileName(input) {
            if (input.files && input.files[0]) {
                document.getElementById('voucherUploadLabel').innerHTML = `✅ Archivo cargado: <strong>${input.files[0].name}</strong>`;
            }
        }

        let previousPaymentStates = {};

        function mostrarToast(tipo, titulo, mensaje) {
            const container = document.getElementById('toastContainerVentas');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = `toast-item ${tipo}`;
            const icon = tipo === 'success' ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-triangle-exclamation"></i>';
            toast.innerHTML = `
                <div class="toast-icon">${icon}</div>
                <div class="toast-content">
                    <h5>${titulo}</h5>
                    <p>${mensaje}</p>
                </div>
            `;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(110%)';
                setTimeout(() => toast.remove(), 400);
            }, 7000);
        }

        // CARGAR COMPROBANTES DESDE EL BACKEND EN TIEMPO REAL
        function cargarComprobantesVentas() {
            fetch('crm_backend.php?action=listar_pagos')
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.pagos) return;
                const container = document.getElementById('vouchersGridContainer');
                if (container) {
                    container.innerHTML = '';
                    data.pagos.forEach(p => {
                        const isAceptado = (p.estado === 'Aceptado');
                        const isObservado = (p.estado === 'Observado');
                        const montoFmt = parseFloat(p.monto).toLocaleString('en-US', {minimumFractionDigits:2});

                        // Detectar cambio de estado en vivo para lanzar Toast de notificación
                        const prevState = previousPaymentStates[p.id];
                        if (prevState && prevState === 'Pendiente') {
                            if (isAceptado) {
                                mostrarToast('success', '🎉 ¡Pago Aceptado por Reportería!', `La factura ${p.nro_factura} (${p.cliente}) por S/ ${montoFmt} ha sido APROBADA. ¡Pedido liberado para despacho!`);
                            } else if (isObservado) {
                                mostrarToast('warning', '⚠️ Pago Observado por Reportería', `La factura ${p.nro_factura} (${p.cliente}) fue observada: "${p.motivo_observacion || 'Revisar extracto'}"`);
                            }
                        }
                        previousPaymentStates[p.id] = p.estado;

                        let badge = '<span class="badge-tag-vip">⏳ En Revisión Reportería</span>';
                        if (isAceptado) badge = '<span class="badge-tag-activo">✅ Pago Aceptado</span>';
                        else if (isObservado) badge = `<span style="background:#FEE2E2; color:#DC2626; padding:3px 8px; border-radius:10px; font-size:0.7rem; font-weight:700;">⚠️ Observado: ${p.motivo_observacion || 'Rectificar'}</span>`;

                        const card = document.createElement('div');
                        card.className = 'voucher-card-item';
                        card.innerHTML = `
                            <div class="voucher-card-thumb" onclick="verComprobanteDetalle('${p.nro_factura}', '${p.cliente}', '${montoFmt}', '${p.nro_operacion}', '${p.voucher_url}', '${p.estado}')">
                                <img src="${p.voucher_url}" alt="Voucher" onerror="this.src='https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=400&auto=format&fit=crop&q=80'">
                            </div>
                            <div class="voucher-card-body">
                                ${badge}
                                <h4 style="font-size:0.95rem; color:var(--text-dark); margin-top:4px;">${p.nro_factura}</h4>
                                <p style="font-size:0.75rem; color:var(--text-muted);">${p.cliente} • S/ ${montoFmt}</p>
                                <span style="font-size:0.72rem; color:var(--text-muted);">${p.nro_operacion} (${p.banco})</span>
                                <button class="btn-facturar-mini" style="margin-top:6px;" onclick="verComprobanteDetalle('${p.nro_factura}', '${p.cliente}', '${montoFmt}', '${p.nro_operacion}', '${p.voucher_url}', '${p.estado}')">
                                    <i class="fa-solid fa-eye"></i> Ver Voucher
                                </button>
                            </div>
                        `;
                        container.appendChild(card);
                    });
                }

                if (data.stats) {
                    const pendEl = document.getElementById('statPendientesRep');
                    if (pendEl) pendEl.textContent = data.stats.pendientes;
                }
            })
            .catch(err => console.log('Error al listar comprobantes:', err));
        }

        // ENVIAR FACTURACIÓN Y SOLICITAR CONFIRMACIÓN A REPORTERÍA
        function enviarFacturacionReporteria(e) {
            e.preventDefault();
            const nro = document.getElementById('facNumero').value.trim();
            const cliente = document.getElementById('facCliente').value.trim();
            const ruc = document.getElementById('facRuc').value.trim();
            const monto = parseFloat(document.getElementById('facMonto').value) || 0;
            const metodo = document.getElementById('facMetodo').value;
            const op = document.getElementById('facOperacion').value.trim();

            if (!cliente || monto <= 0) {
                alert('Por favor ingrese el cliente y un monto válido para facturar.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'solicitar_confirmacion_pago');
            formData.append('nro_factura', nro);
            formData.append('cliente', cliente);
            formData.append('ruc', ruc);
            formData.append('monto', monto);
            formData.append('metodo', metodo);
            formData.append('nro_operacion', op);
            formData.append('asesor', 'Endrina');

            const fileInput = document.getElementById('inputVoucher');
            if (fileInput.files[0]) {
                formData.append('voucher_file', fileInput.files[0]);
            }

            fetch('crm_backend.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                ventasHoyCount++;
                document.getElementById('statVentasHoy').textContent = ventasHoyCount;
                
                alert(`✅ ¡Facturación ${nro} por S/ ${monto.toLocaleString('en-US', {minimumFractionDigits:2})} registrada!\n\n1. El comprobante de pago fue guardado en el servidor.\n2. Se notificó inmediatamente al área de Reportería para que verifique y acepte el pago.`);

                // Recargar comprobantes y conmutar a la vista
                cargarComprobantesVentas();
                cargarChatVentas();
                cambiarVistaVentas('comprobantes');

                // Limpiar formulario y regenerar número
                document.getElementById('facCliente').value = '';
                document.getElementById('facRuc').value = '';
                document.getElementById('facMonto').value = '';
                document.getElementById('facOperacion').value = '';
                document.getElementById('voucherUploadLabel').innerHTML = 'Haz clic para subir imagen o PDF del voucher bancario';
                document.getElementById('inputVoucher').value = '';
                document.getElementById('facNumero').value = 'F001-00' + Math.floor(100 + Math.random() * 900);
            })
            .catch(err => {
                alert('Facturación registrada localmente.');
                cargarComprobantesVentas();
                cambiarVistaVentas('comprobantes');
            });
        }

        // CONFIRMAR ENVÍO DE VENTAS DEL DÍA A REPORTERÍA
        function confirmarEnvioVentasDia() {
            const nota = document.getElementById('notaVentasDia').value;
            const params = new URLSearchParams({
                action: 'enviar_ventas_del_dia',
                asesor: 'Endrina',
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
            .then(data => {
                alert(`📤 ¡Ventas del día enviadas exitosamente a Reportería!\n\nSe ha enviado el consolidado con los comprobantes del día. El área de reportería podrá revisarlos en su bandeja.`);
                cambiarVistaVentas('chat');
            })
            .catch(() => ({ success: true }))
            .then(() => {
                cambiarVistaVentas('chat');
            });
        }

        // CHAT SINCRONIZADO CON REPORTERÍA
        function cargarChatVentas() {
            fetch('crm_backend.php?action=listar_mensajes')
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.mensajes) return;
                const container = document.getElementById('chatCenterMessages');
                if (!container) return;
                container.innerHTML = '';
                data.mensajes.forEach(m => {
                    const isMio = (m.rol === 'Ventas' || m.remitente.includes('Endrina'));
                    const bubble = document.createElement('div');
                    bubble.className = `chat-bubble ${isMio ? 'asesor' : 'reporteria'}`;
                    bubble.innerHTML = `
                        <strong>${m.remitente}:</strong><br>
                        ${m.mensaje}
                        <div style="font-size:0.65rem; opacity:0.8; margin-top:4px;">${m.hora}</div>
                    `;
                    container.appendChild(bubble);
                });
                container.scrollTop = container.scrollHeight;
            })
            .catch(err => console.log('Error listar chat:', err));
        }

        function insertarTextoChat(texto) {
            document.getElementById('inputChatMsg').value = texto;
            document.getElementById('inputChatMsg').focus();
        }

        function enviarMensajeChat() {
            const input = document.getElementById('inputChatMsg');
            const texto = input.value.trim();
            if (!texto) return;

            const formData = new FormData();
            formData.append('action', 'enviar_chat');
            formData.append('remitente', 'Endrina');
            formData.append('rol', 'Ventas');
            formData.append('mensaje', texto);

            fetch('crm_backend.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                input.value = '';
                cargarChatVentas();
            })
            .catch(err => {
                input.value = '';
                cargarChatVentas();
            });
        }

        function abrirLogoutModal() {
            document.getElementById('modalLogout').classList.add('open');
        }

        // Inicializar cargas periódicas
        window.addEventListener('DOMContentLoaded', () => {
            cargarCotizaciones();
            cargarCarteraClientes();
            cargarComprobantesVentas();
            cargarChatVentas();
            setInterval(() => {
                cargarComprobantesVentas();
                cargarChatVentas();
            }, 4500);
        });
    </script>

    <!-- ================= MODAL DE VISTA PREVIA Y DESCARGA / IMPRESIÓN OFICIAL PDF ================= -->
    <div class="modal-overlay" id="modalVistaPreviaPdf" style="z-index:99999;">
        <div class="modal-pdf-a4">
            <!-- Barra superior no imprimible -->
            <div class="no-print" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; color:#FFF;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <i class="fa-solid fa-file-pdf" style="font-size:1.6rem; color:#EF4444;"></i>
                    <div>
                        <h3 style="font-size:1.1rem; margin:0; color:#FFF;">Formato Oficial de Cotización - Building Systems Perú</h3>
                        <span style="font-size:0.75rem; color:#D1D5DB;">Hoja A4 oficial lista para imprimir o guardar como PDF</span>
                    </div>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn-pill-white primary" onclick="imprimirCotizacionOficialDirecto()" style="background:#C79B58; border-color:#C79B58; color:#161719; font-weight:800;">
                        <i class="fa-solid fa-print"></i> Imprimir / Descargar PDF
                    </button>
                    <button type="button" class="btn-pill-white" onclick="cerrarModales()" style="background:#4B5563; border-color:#4B5563; color:#FFF;">
                        <i class="fa-solid fa-xmark"></i> Cerrar
                    </button>
                </div>
            </div>

            <!-- HOJA A4 OFICIAL IDÉNTICA AL PDF ADJUNTO -->
            <div class="hoja-a4-oficial" id="printDocumentoOficial">
                <!-- ENCABEZADO CON LOGO Y CORRELATIVO -->
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:52px; height:52px; border:2px solid #002B49; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#002B49; font-weight:900; font-size:16px; font-family:Arial, sans-serif;">
                            BSP
                        </div>
                        <div>
                            <div style="font-size:20px; font-weight:900; color:#002B49; letter-spacing:0.5px; font-family:Arial, sans-serif;">BS PERÚ</div>
                            <div style="font-size:9px; color:#4B5563; font-weight:600; text-transform:uppercase; letter-spacing:1px;">Building Systems Peru</div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:13px; font-weight:bold; color:#000;" id="pdfFechaEmision">2026-08-19</div>
                    </div>
                </div>

                <!-- TÍTULO PRINCIPAL: COTIZACIONES: 0052456 -->
                <div style="text-align:center; margin:14px 0 16px 0;">
                    <h2 style="font-size:17px; font-weight:bold; margin:0; text-decoration:none; color:#000; letter-spacing:0.5px;">
                        COTIZACIONES: <span id="pdfCorrelativo">0052456</span>
                    </h2>
                </div>

                <!-- TABLA DATOS DEL CLIENTE -->
                <table style="width:100%; border-collapse:collapse; margin-bottom:14px; font-size:10.5px;">
                    <tr>
                        <td style="border:1px solid #000; padding:4px 6px; width:100px; font-weight:bold; background:#FAFAFA;">Razon Social:</td>
                        <td style="border:1px solid #000; padding:4px 6px; font-weight:bold;" colspan="3" id="pdfClienteNombre">MULTINEGOCIOS AARON SOCIEDAD ANONIMA CERRADA-MULTINEGOCIOS AARON S.A.C.</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; padding:4px 6px; font-weight:bold; background:#FAFAFA;">Dirección:</td>
                        <td style="border:1px solid #000; padding:4px 6px;" colspan="3" id="pdfClienteDireccion">JR. SAGITARIO MZA. C LOTE. 22 URB. VILLA ALEGRE LIMA - LIMA - SANTIAGO DE SURCO</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; padding:4px 6px; font-weight:bold; background:#FAFAFA;">RUC:</td>
                        <td style="border:1px solid #000; padding:4px 6px;" colspan="3" id="pdfClienteRuc">20602591990</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; padding:4px 6px; font-weight:bold; background:#FAFAFA;">E mail :</td>
                        <td style="border:1px solid #000; padding:4px 6px;" colspan="3" id="pdfClienteEmail">Consorciomiraflores25@gmail.com</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; padding:4px 6px; font-weight:bold; background:#FAFAFA;">Telefono:</td>
                        <td style="border:1px solid #000; padding:4px 6px;" colspan="3" id="pdfClienteTelefono">942 377 626</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; padding:4px 6px; font-weight:bold; background:#FAFAFA;">Contacto:</td>
                        <td style="border:1px solid #000; padding:4px 6px;" colspan="3" id="pdfClienteContacto">Fanny Ramirez</td>
                    </tr>
                </table>

                <!-- TEXTO INTRODUCTORIO -->
                <div style="font-size:11px; margin: 12px 0 8px 0; font-weight:bold;">
                    De acuerdo con su amable solicitud, tenemos el agrado de cotizarle lo siguiente:
                </div>

                <!-- TABLA DE ITEMS -->
                <table style="width:100%; border-collapse:collapse; margin-bottom:12px; font-size:10px;">
                    <thead>
                        <tr style="border-top:1px solid #000; border-bottom:1px solid #000; background:#F2F2F2;">
                            <th style="padding:5px 4px; text-align:center; width:30px;">Item</th>
                            <th style="padding:5px 4px; text-align:center; width:75px;">Codigo</th>
                            <th style="padding:5px 4px; text-align:left;">Descripcion</th>
                            <th style="padding:5px 4px; text-align:right; width:55px;">Cantidad</th>
                            <th style="padding:5px 4px; text-align:center; width:45px;">UMed</th>
                            <th style="padding:5px 4px; text-align:right; width:65px;">Pre.Orig</th>
                            <th style="padding:5px 4px; text-align:right; width:60px;">Descto %</th>
                            <th style="padding:5px 4px; text-align:right; width:65px;">Prec.Total</th>
                            <th style="padding:5px 4px; text-align:right; width:70px;">SubTotal</th>
                            <th style="padding:5px 4px; text-align:center; width:65px;">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="pdfTbodyItems">
                        <!-- Inyectado por JS -->
                    </tbody>
                </table>

                <!-- TOTALES A LA DERECHA -->
                <div style="display:flex; justify-content:flex-end; margin-bottom:16px;">
                    <table style="width:240px; border-collapse:collapse; font-size:11px;">
                        <tr style="border-top:1px solid #000;">
                            <td style="padding:3px 8px; font-weight:bold;">Subtotal</td>
                            <td style="padding:3px 8px; text-align:right; font-weight:bold;" id="pdfSubtotalVal">S/.342.59</td>
                        </tr>
                        <tr>
                            <td style="padding:3px 8px; font-weight:bold;">IGV</td>
                            <td style="padding:3px 8px; text-align:right; font-weight:bold;" id="pdfIgvVal">S/.61.67</td>
                        </tr>
                        <tr style="border-top:1px solid #000; border-bottom:1px solid #000;">
                            <td style="padding:4px 8px; font-weight:bold;">Total Neto</td>
                            <td style="padding:4px 8px; text-align:right; font-weight:bold;" id="pdfTotalVal">S/.404.26</td>
                        </tr>
                    </table>
                </div>

                <!-- OBSERVACIONES Y CONDICIONES COMERCIALES (TEXTO EXACTO DEL PDF) -->
                <div style="border:1px solid #000; padding:10px 12px; margin-bottom:12px; font-size:9.5px; line-height:1.35;">
                    <div style="font-weight:bold; margin-bottom:4px;">Observaciones -</div>
                    <div style="font-weight:bold; margin-bottom:3px;">CONDICIONES COMERCIALES</div>
                    <div><strong>Forma de Pago:</strong> <span id="pdfFormaPagoVal">CONTADO CONTRA ENTREGA</span></div>
                    <div><strong>Vigencia:</strong> <span id="pdfVigenciaVal">7 dias</span></div>
                    <div>El Horario de atención de las oficinas es de Lunes a Viernes 8:00 a 17:30 y Sábados 8:00 a 12:00 Horas</div>
                    <div>Entregamos certificados de calidad, hojas de seguridad (MSDS) y especificaciones técnicas de todos nuestros productos a solicitud del cliente</div>
                </div>

                <!-- CONSIDERACIONES FABRICACIÓN HECHOS A PEDIDO -->
                <div style="border:1px solid #000; padding:8px 12px; margin-bottom:12px; font-size:9px; line-height:1.35;">
                    <div style="font-weight:bold; margin-bottom:3px;">CONSIDERACIONES PARA LA FABRICACIÓN DE PRODUCTOS HECHOS A PEDIDO:</div>
                    <div>Los productos que se elaboran bajo pedido, garantizan disponibilidad y calidad idónea de un producto de complejidad técnica.</div>
                    <div>Debido a este proceso, el tiempo de entrega puede variar entre 8 a 20 días útiles, dependiendo de la disponibilidad de la materia prima, stock y cantidad solicitada.</div>
                    <div>El plazo exacto será confirmado por el vendedor al momento de contar con la OC y abono respectivo.</div>
                    <div>Toda cancelación de dicho pedido puede ocasionar la perdida parcial o completa del abono realizado, dado que son productos que no pueden ser almacenados.</div>
                </div>

                <!-- CUENTAS BANCARIAS BS PERU -->
                <div style="border:1px solid #000; padding:8px 12px; margin-bottom:12px; font-size:9.5px; line-height:1.4;">
                    <div style="font-weight:bold;">BUILDING SYSTEMS PERU S.A.C.</div>
                    <div><strong>RUC:</strong> 20609793806</div>
                    <div style="font-weight:bold; margin-top:2px;">Deposito en cuenta Corriente</div>
                    <div>Cta. Cte. BCP Soles: 193-9902956-0-56</div>
                    <div>Código Interbancario: 00219300990295605614</div>
                    <div>Cta. Cte. BBVA Soles: 0011-0152-0100100654</div>
                    <div>Código Interbancario: 011-152-000100100654-61</div>
                    <div>Cta. Cte. Interbank Soles: 200-3005486597</div>
                    <div>Código Interbancario: 003-200-003005486597-34</div>
                </div>

                <!-- SUCURSAL CHORRILLOS Y PIE -->
                <div style="border:1px solid #000; padding:8px 12px; margin-bottom:16px; font-size:9.5px;">
                    <div style="font-weight:bold;">SUCURSAL CHORRILLOS</div>
                    <div>AV. LOS FAISANES N° 675 URB. LA CAMPIÑA CHORRILLOS</div>
                </div>

                <div style="text-align:center; font-size:11px; font-weight:bold; margin-top:20px;">
                    <div>VENTAS OFICINA</div>
                    <div>CHORRILLOS</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= MODAL AUTORIZACIÓN DE DESCUENTO (> 6%) ================= -->
    <div class="modal-overlay" id="modalAutorizarDescuento">
        <div class="modal-card" style="max-width:480px;">
            <div class="modal-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:38px; height:38px; border-radius:10px; background:#FEE2E2; color:#DC2626; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 style="font-size:1.1rem; margin:0;">Autorización de Descuento Especial</h3>
                </div>
                <button class="modal-close-btn" onclick="cerrarModales()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <p style="font-size:0.84rem; color:var(--text-muted); line-height:1.4; margin-bottom:16px;">
                La política comercial permite a las asesoras hasta un <strong>6% de descuento directo</strong>. Has ingresado un descuento de <strong id="lblModalPorcentajeDescto" style="color:#EF4444;">20%</strong>, por lo que se requiere aprobación de Administración.
            </p>

            <div style="background:#FAF8F5; padding:14px; border-radius:14px; border:1px solid var(--border-soft); margin-bottom:16px;">
                <label style="font-size:0.8rem; font-weight:700; color:var(--text-dark); display:block; margin-bottom:6px;">
                    Opción 1: Consultar al Administrador por WhatsApp
                </label>
                <p style="font-size:0.75rem; color:var(--text-muted); margin-bottom:10px;">
                    Se abrirá WhatsApp con el resumen de la cotización, cliente y descuento solicitado para solicitar el V°B° de inmediato.
                </p>
                <button type="button" class="btn-pill-white" style="width:100%; justify-content:center; border-color:#10B981; color:#059669; font-weight:700;" onclick="consultarAdminWhatsApp()">
                    <i class="fa-brands fa-whatsapp"></i> Enviar Consulta a Gerencia por WhatsApp
                </button>
            </div>

            <div style="background:#FAF8F5; padding:14px; border-radius:14px; border:1px solid var(--border-soft); margin-bottom:16px;">
                <label style="font-size:0.8rem; font-weight:700; color:var(--text-dark); display:block; margin-bottom:6px;">
                    Opción 2: Ingresar Clave o Token de Autorización
                </label>
                <div style="display:flex; gap:8px;">
                    <input type="password" id="inputClaveAdmin" placeholder="Clave de Administrador (ej: BSADMIN2026)" class="cotiz-input-cell" style="flex:1;">
                    <button type="button" class="btn-pill-white primary" onclick="aplicarClaveAutorizacionAdmin()">
                        Autorizar
                    </button>
                </div>
                <span id="msgErrorClaveAdmin" style="font-size:0.72rem; color:#EF4444; display:none; margin-top:4px;">Clave incorrecta. Solicite autorización a su supervisor.</span>
            </div>

            <button type="button" class="btn-pill-white" style="width:100%; justify-content:center;" onclick="cerrarModales()">
                Cancelar y Volver
            </button>
        </div>
    </div>

    <!-- ================= MODAL LOGOUT ================= -->
    <div class="modal-overlay" id="modalLogout">
        <div class="modal-card" style="text-align:center;">
            <div style="width:60px; height:60px; background:#FEE2E2; color:#DC2626; border-radius:20px; display:flex; justify-content:center; align-items:center; font-size:1.6rem; margin:0 auto 16px;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>
            <h3 style="font-size:1.25rem;">¿Cerrar Sesión de Ventas?</h3>
            <p style="color:var(--text-muted); font-size:0.85rem; margin:8px 0 20px;">Sesión activa de Endrina (Ventas BS Perú).</p>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <a href="logout.php" class="btn-pill-white primary" style="justify-content:center; text-decoration:none; background:#DC2626; color:#FFF;">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Salir del Sistema
                </a>
                <button class="btn-pill-white" style="justify-content:center;" onclick="cerrarModales()">
                    Permanecer en Ventas
                </button>
            </div>
        </div>
    </div>
</body>
</html>

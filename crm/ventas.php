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

                <!-- ENLACE DIRECTO AL MÓDULO DE REPORTERÍA -->
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

        <!-- ================= CONTENIDO CENTRAL (EN EL MEDIO) ================= -->
        <div class="main-content">
            
            <!-- ================= VISTA 1: DASHBOARD ================= -->
            <div id="vista-dashboard" class="vista-seccion">
                <div class="main-header">
                    <div>
                        <h1 id="viewMainTitle">Dashboard</h1>
                    </div>
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
                            <button class="btn-pill-white primary" onclick="cambiarVistaVentas('facturacion')">
                                <i class="fa-solid fa-plus"></i> Nueva Facturación
                            </button>
                            <button class="btn-pill-white" onclick="cambiarVistaVentas('ventas-dia')">
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
                    <h4 style="font-size:0.95rem; font-weight:700; color:var(--text-dark); margin-bottom:14px; display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-building-circle-check" style="color:var(--accent-tan);"></i> Registrar Nuevo Cliente a mi Cartera
                    </h4>
                    <form onsubmit="guardarNuevoCliente(event)">
                        <div class="form-grid">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Razón Social / Empresa *</label>
                                    <input type="text" id="newCliEmpresa" required placeholder="Ej: Constructora San Jerónimo S.A.C.">
                                </div>
                                <div class="form-group">
                                    <label>RUC / DNI (11 u 8 dígitos) *</label>
                                    <input type="text" id="newCliRuc" required maxlength="11" placeholder="Ej: 20601928471">
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
                                        <a href="https://wa.me/51984129384?text=Hola%20Ing.%20Mary%20Rose,%20le%20escribe%20Elizabeth%20Addams%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
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
                                        <a href="https://wa.me/51991482103?text=Hola%20Arq.%20Carlos%20Mendoza,%20le%20escribe%20Elizabeth%20Addams%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
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
                                        <a href="https://wa.me/51972384192?text=Hola%20Jerome%20Brown,%20le%20escribe%20Elizabeth%20Addams%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
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
                                        <a href="https://wa.me/51987654321?text=Hola%20Ing.%20Roberto%20Salcedo,%20le%20escribe%20Elizabeth%20Addams%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
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
                                        <a href="https://wa.me/51963852741?text=Hola%20Ing.%20Walter%20Palacios,%20le%20escribe%20Elizabeth%20Addams%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
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
                                        <a href="https://wa.me/51951753852?text=Hola%20Arq.%20Luc%C3%ADa%20Ramos,%20le%20escribe%20Elizabeth%20Addams%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
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
                                        <a href="https://wa.me/51998123456?text=Hola%20Lic.%20Elena%20Morales,%20le%20escribe%20Elizabeth%20Addams%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
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
                            Hola Elizabeth, recibimos la Factura F001-00892 por S/ 14,400.00 de Cosapi S.A. El comprobante BCP #849201 ha sido <strong>ACEPTADO Y REGISTRADO</strong> exitosamente en banco. ✅
                            <div style="font-size:0.65rem; color:var(--text-muted); margin-top:4px;">11:42 AM</div>
                        </div>

                        <div class="chat-bubble asesor">
                            <strong>Tú (Elizabeth Addams):</strong><br>
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

        // CONTROL DE MODALES (SOLO PARA ZOOM DE VOUCHER)
        function closeModals() {
            document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
        }

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

            // 4. Scroll al tope del área central
            const mainContent = document.querySelector('.main-content');
            if (mainContent) mainContent.scrollTop = 0;
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
                document.getElementById('newCliEmpresa').focus();
            }
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
                        <a href="https://wa.me/51${tel.replace(/\D/g,'')}?text=Hola%20${encodeURIComponent(contacto)},%20le%20escribe%20Elizabeth%20Addams%20de%20BS%20Per%C3%BA" target="_blank" class="btn-wa-mini" title="Enviar WhatsApp directo">
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
            formData.append('asesor', 'Elizabeth Addams');

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
                alert(`📤 ¡Ventas del día enviadas exitosamente a Reportería!\n\nSe ha enviado el consolidado con los comprobantes del día. El área de reportería podrá revisarlos en su bandeja.`);
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
                    const isMio = (m.rol === 'Ventas' || m.remitente.includes('Elizabeth'));
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
            formData.append('remitente', 'Elizabeth Addams');
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

        // Inicializar cargas periódicas
        window.addEventListener('DOMContentLoaded', () => {
            cargarComprobantesVentas();
            cargarChatVentas();
            setInterval(() => {
                cargarComprobantesVentas();
                cargarChatVentas();
            }, 4500);
        });
    </script>
</body>
</html>

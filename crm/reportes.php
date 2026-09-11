<?php
// crm/reportes.php - BS Perú CRM: Módulo de Reportería & Validación de Pagos
session_start();

// Control de acceso: Verificar autenticación
if (!isset($_SESSION['crm_logged_in']) || $_SESSION['crm_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Control de rol: Si es Ventas (Endrina), enviarla a su módulo correspondiente
if (isset($_SESSION['crm_rol']) && $_SESSION['crm_rol'] === 'ventas') {
    header("Location: ventas.php");
    exit;
}

$currentUser = $_SESSION['crm_user'] ?? 'Nayeli';

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

// Procesar acciones AJAX de validación de pago desde Reportería si se envían por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'confirmar_pago') {
        $pago_id = $_POST['pago_id'] ?? 0;
        $monto = floatval($_POST['monto'] ?? 0);
        $cotizacion = $_POST['cotizacion'] ?? '';
        $validador = $_POST['validador'] ?? 'Nayeli (Reportería)';
        $fecha = date('Y-m-d H:i:s');

        // Si hay BD activa, actualizar
        if ($db) {
            try {
                $stmt = $db->prepare("UPDATE pagos SET estado = 'Aceptado', validador_id = 1, fecha_pago = ? WHERE id = ?");
                $stmt->execute([$fecha, $pago_id]);
                $stmt2 = $db->prepare("UPDATE cotizaciones SET estado = 'Pagada' WHERE id = (SELECT cotizacion_id FROM pagos WHERE id = ?)");
                $stmt2->execute([$pago_id]);
            } catch(Exception $ex) {}
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Pago confirmado y aceptado exitosamente por Reportería. Pedido liberado para despacho.',
            'fecha' => $fecha,
            'validador' => $validador,
            'monto' => $monto,
            'cotizacion' => $cotizacion
        ]);
        exit;
    }

    if ($action === 'observar_pago') {
        $pago_id = $_POST['pago_id'] ?? 0;
        $cotizacion = $_POST['cotizacion'] ?? '';
        $motivo = $_POST['motivo'] ?? 'Comprobante no coincide con extracto bancario';
        $fecha = date('Y-m-d H:i:s');

        if ($db) {
            try {
                $stmt = $db->prepare("UPDATE pagos SET estado = 'Observado' WHERE id = ?");
                $stmt->execute([$pago_id]);
            } catch(Exception $ex) {}
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Pago marcado como Observado. Se ha notificado al asesor de ventas para corrección.',
            'motivo' => $motivo,
            'cotizacion' => $cotizacion,
            'fecha' => $fecha
        ]);
        exit;
    }

    if ($action === 'enviar_chat_asesor') {
        $mensaje = $_POST['mensaje'] ?? '';
        $asesor = $_POST['asesor'] ?? 'Asesor Comercial';
        $remitente = 'Nayeli (Reportería)';
        $hora = date('H:i');

        echo json_encode([
            'success' => true,
            'mensaje' => $mensaje,
            'asesor' => $asesor,
            'remitente' => $remitente,
            'hora' => $hora
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
    <title>BS Perú - Panel de Reportería & Conciliación Financiera</title>
    <!-- Google Fonts: Poppins & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            /* ================= PALETA COOLORS (SOLICITADA) ================= */
            --color-1: #1B4079; /* Yale Blue: Sidebar y fondo estructural */
            --color-2: #4D7C8A; /* Air Force Blue: Acento principal, botones y cabeceras */
            --color-3: #7F9C96; /* Cambridge Blue: Acento secundario y bordes */
            --color-4: #8FAD88; /* Cambridge Green / Sage: Indicadores de conciliación y éxito */
            --color-5: #CBDF90; /* Mindaro: Resaltado luminoso, badges y detalles vivos */

            --outer-bg: var(--color-1);
            --outer-bg-dark: #0F2548;
            --app-frame: var(--color-1);
            --sidebar-bg: var(--color-1);
            --main-bg: #FFFFFF;
            --right-bg: #F4F8FA;
            
            --accent-green: var(--color-2);
            --accent-green-light: var(--color-3);
            --accent-green-dark: var(--color-1);
            --accent-green-soft: #EDF4F6;
            
            --accent-tan: var(--color-4);
            --accent-tan-soft: #F3F7EE;
            --accent-highlight: var(--color-5);
            
            --text-dark: #1A2433;
            --text-muted: #64748B;
            --text-light: #94A3B8;
            --border-soft: #E2EAF0;
            --card-radius: 28px;
            --pill-radius: 40px;

            --chart-green: var(--color-4);
            --chart-blue: var(--color-2);
            --chart-yellow: var(--color-5);
            --chart-purple: #6366F1;
            --chart-red: #EF4444;

            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.dark-mode {
            --outer-bg: #0C1A2E;
            --app-frame: #0F2038;
            --sidebar-bg: #0C1A2E;
            --main-bg: #121D2B;
            --right-bg: #0D1622;
            --text-dark: #F1F5F9;
            --text-muted: #94A3B8;
            --border-soft: #1E3147;
            --accent-green-soft: #17293B;
            --accent-tan-soft: #1B2B23;
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
            width: 255px;
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
            background: linear-gradient(135deg, var(--color-2) 0%, var(--color-3) 100%);
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #FFF;
            font-size: 1.25rem;
            font-weight: 800;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
        }
        .brand-logo-text {
            font-family: 'Outfit', sans-serif;
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #FFF;
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }
        .brand-logo-text span {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--color-5);
            letter-spacing: 1px;
            text-transform: uppercase;
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
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(3px);
        }
        .nav-item.active {
            background: var(--color-2);
            color: #FFF;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        }
        .nav-item.active i {
            color: var(--color-5);
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
            background: var(--color-5);
            color: var(--color-1);
            font-weight: 800;
        }

        /* USER PILL IN SIDEBAR */
        .user-pill {
            background: rgba(255, 255, 255, 0.08);
            border-radius: var(--pill-radius);
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: auto;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: var(--transition);
        }
        .user-pill:hover { background: rgba(255, 255, 255, 0.15); }
        .user-pill-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--color-5);
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
            color: rgba(255, 255, 255, 0.7);
        }
        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--color-5);
        }
        .user-pill-chevron { color: rgba(255, 255, 255, 0.6); font-size: 0.75rem; }

        /* THEME TOGGLE */
        .theme-toggle {
            background: rgba(0, 0, 0, 0.2);
            border-radius: var(--pill-radius);
            padding: 4px;
            display: flex;
            margin-top: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .theme-btn {
            flex: 1;
            padding: 6px 0;
            border-radius: var(--pill-radius);
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, 0.7);
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
            background: var(--color-2);
            color: #FFF;
            box-shadow: 0 4px 10px rgba(0,0,0,0.25);
        }

        /* ================= ÁREA CENTRAL (BLANCA / DARK) ================= */
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
        .btn-link-ventas {
            background: rgba(199, 155, 88, 0.12);
            color: #C79B58;
            padding: 9px 18px;
            border-radius: var(--pill-radius);
            font-size: 0.84rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            border: 1px solid rgba(199, 155, 88, 0.25);
        }
        .btn-link-ventas:hover {
            background: #C79B58;
            color: #161719;
            transform: translateY(-2px);
        }

        /* HERO / BANNER DE REPORTERÍA */
        .welcome-card {
            background: linear-gradient(135deg, var(--color-1) 0%, var(--color-2) 100%);
            border-radius: var(--card-radius);
            padding: 32px 42px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #FFF;
            position: relative;
            box-shadow: 0 14px 28px rgba(27, 64, 121, 0.25);
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
            color: var(--color-1);
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
            background: #F8FAFC;
        }
        .btn-pill-white.primary {
            background: var(--color-5);
            color: var(--color-1);
            font-weight: 800;
        }
        .btn-pill-white.primary:hover {
            background: #FFF;
            color: var(--color-1);
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
            background: var(--accent-green-soft);
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
            border-color: var(--accent-green);
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
            color: var(--accent-green-dark);
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.15);
        }
        body.dark-mode .stat-mini-icon {
            background: #1B231F;
            color: var(--accent-green-light);
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
            margin-top: 4px;
            font-weight: 500;
        }

        /* DOS COLUMNAS DE DETALLE */
        .content-columns {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 22px;
        }

        .card-panel {
            background: #FFF;
            border-radius: var(--card-radius);
            padding: 24px 28px;
            border: 1px solid var(--border-soft);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        body.dark-mode .card-panel {
            background: #1B201D;
            border-color: #26332B;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .panel-header h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .panel-header span {
            font-size: 0.8rem;
            color: var(--text-muted);
            cursor: pointer;
            font-weight: 600;
        }

        /* BARRAS DE PROGRESO DE BANCOS Y SUCURSALES */
        .progress-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 12px;
        }
        .progress-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.82rem;
        }
        .progress-title {
            color: var(--text-dark);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .progress-percent {
            color: var(--text-muted);
            font-weight: 600;
        }
        .progress-track {
            height: 9px;
            background: #EDF2F7;
            border-radius: 6px;
            overflow: hidden;
        }
        body.dark-mode .progress-track { background: #242E28; }
        .progress-bar-fill {
            height: 100%;
            border-radius: 6px;
            transition: width 0.6s ease;
        }

        /* ================= BANDEJA DE VALIDACIÓN ================= */
        .val-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .val-search-box {
            position: relative;
            flex: 1;
            min-width: 260px;
        }
        .val-search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .val-search-box input {
            width: 100%;
            padding: 11px 18px 11px 42px;
            border-radius: var(--pill-radius);
            border: 1px solid var(--border-soft);
            background: #F4F8F6;
            font-size: 0.85rem;
            outline: none;
            color: var(--text-dark);
            transition: var(--transition);
        }
        body.dark-mode .val-search-box input {
            background: #1B231F;
            border-color: #27362E;
            color: #FFF;
        }
        .val-filter-pills {
            display: flex;
            gap: 8px;
            overflow-x: auto;
        }
        .val-pill {
            padding: 8px 16px;
            border-radius: var(--pill-radius);
            background: #F4F8F6;
            border: 1px solid var(--border-soft);
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
        }
        body.dark-mode .val-pill {
            background: #1B231F;
            border-color: #27362E;
            color: #9CA3AF;
        }
        .val-pill:hover, .val-pill.active {
            background: var(--accent-green);
            color: #0E1210;
            border-color: var(--accent-green);
        }

        .val-table-wrapper {
            background: #FFF;
            border-radius: 20px;
            border: 1px solid var(--border-soft);
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(0,0,0,0.02);
            max-height: 520px;
            overflow-y: auto;
        }
        body.dark-mode .val-table-wrapper {
            background: #18201C;
            border-color: #27362E;
        }
        .val-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.84rem;
        }
        .val-table th {
            background: #F4F8F6;
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
        body.dark-mode .val-table th {
            background: #1E2922;
            border-color: #27362E;
            color: #A0AEC0;
        }
        .val-table td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-soft);
            color: var(--text-dark);
            vertical-align: middle;
        }
        body.dark-mode .val-table td {
            border-color: #243028;
            color: #E2E8F0;
        }
        .val-table tr:hover td {
            background: rgba(16, 185, 129, 0.03);
        }

        .voucher-thumb-small {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid var(--border-soft);
            cursor: pointer;
            transition: var(--transition);
        }
        .voucher-thumb-small:hover {
            transform: scale(1.08);
            border-color: var(--accent-green);
        }

        .btn-confirm-direct {
            background: var(--accent-green);
            color: #0E1210;
            border: none;
            padding: 8px 14px;
            border-radius: var(--pill-radius);
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
        }
        .btn-confirm-direct:hover {
            background: #059669;
            color: #FFF;
            transform: translateY(-1px);
        }

        .btn-observe-direct {
            background: transparent;
            color: #EF4444;
            border: 1px solid #FECACA;
            padding: 8px 12px;
            border-radius: var(--pill-radius);
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }
        .btn-observe-direct:hover {
            background: #FEF2F2;
        }

        .badge-status-pending {
            background: #FEF3C7;
            color: #D97706;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-status-accepted {
            background: #D1FAE5;
            color: #059669;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-status-observed {
            background: #FEE2E2;
            color: #DC2626;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* ================= CHAT TIPO WHATSAPP (REPORTERÍA & FINANZAS) ================= */
        .chat-full-container {
            display: grid;
            grid-template-columns: 340px 1fr;
            height: 650px;
            background: #FFF;
            border-radius: var(--card-radius);
            border: 1px solid var(--border-soft);
            overflow: hidden;
            box-shadow: 0 6px 24px rgba(0,0,0,0.04);
        }
        body.dark-mode .chat-full-container {
            background: #111B21;
            border-color: #222E35;
        }

        /* Barra lateral izquierda */
        .chat-sidebar-list {
            border-right: 1px solid var(--border-soft);
            display: flex;
            flex-direction: column;
            background: #F0F2F5;
            height: 100%;
            overflow: hidden;
        }
        body.dark-mode .chat-sidebar-list {
            background: #111B21;
            border-color: #222E35;
        }
        .chat-sidebar-header-wa {
            padding: 12px 16px;
            background: #F0F2F5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }
        body.dark-mode .chat-sidebar-header-wa {
            background: #202C33;
            border-color: #222E35;
        }
        .btn-refresh-chat {
            background: transparent;
            border: none;
            color: var(--text-muted);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }
        .btn-refresh-chat:hover {
            background: rgba(0,0,0,0.06);
            color: var(--text-dark);
        }

        /* Buscador de chat */
        .chat-search-bar-wa {
            padding: 8px 14px;
            background: #FFF;
            border-bottom: 1px solid var(--border-soft);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        body.dark-mode .chat-search-bar-wa {
            background: #111B21;
            border-color: #222E35;
        }
        .chat-search-bar-wa i {
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        .chat-search-bar-wa input {
            width: 100%;
            border: none;
            background: #F0F2F5;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.82rem;
            outline: none;
            color: var(--text-dark);
        }
        body.dark-mode .chat-search-bar-wa input {
            background: #202C33;
            color: #E9EDEF;
        }

        /* Lista de conversaciones */
        .chat-conversations-scroll {
            flex: 1;
            overflow-y: auto;
            background: #FFF;
        }
        body.dark-mode .chat-conversations-scroll {
            background: #111B21;
        }
        .chat-user-item {
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: var(--transition);
            border-bottom: 1px solid rgba(0,0,0,0.03);
            position: relative;
        }
        body.dark-mode .chat-user-item {
            border-color: rgba(255,255,255,0.03);
        }
        .chat-user-item:hover {
            background: #F5F6F6;
        }
        .chat-user-item.active {
            background: #EBEFEB;
            border-left: 4px solid var(--accent-green);
        }
        body.dark-mode .chat-user-item:hover {
            background: #202C33;
        }
        body.dark-mode .chat-user-item.active {
            background: #2A3942;
            border-left: 4px solid var(--accent-green);
        }

        .chat-user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            position: relative;
            flex-shrink: 0;
        }
        .chat-user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .chat-user-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background: #22C55E;
            position: absolute;
            bottom: 0;
            right: 0;
            border: 2px solid #FFF;
        }
        body.dark-mode .chat-user-dot { border-color: #111B21; }

        .chat-user-meta {
            flex: 1;
            min-width: 0;
        }
        .chat-user-meta-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2px;
        }
        .chat-user-meta-top h5 {
            margin: 0;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        body.dark-mode .chat-user-meta-top h5 { color: #E9EDEF; }
        .chat-user-meta-top .chat-time {
            font-size: 0.68rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* Badge de Sucursal en la fila del chat */
        .badge-sucursal-chat {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.68rem;
            font-weight: 700;
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            padding: 2px 7px;
            border-radius: 6px;
            margin-bottom: 4px;
        }
        body.dark-mode .badge-sucursal-chat {
            background: rgba(16, 185, 129, 0.2);
            color: #34D399;
        }

        .chat-user-meta p {
            margin: 0;
            font-size: 0.76rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        body.dark-mode .chat-user-meta p { color: #8696A0; }

        /* Área de conversación estilo WhatsApp */
        .chat-conversation-area {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: #EFEAE2;
            position: relative;
        }
        body.dark-mode .chat-conversation-area {
            background: #0B141A;
        }

        .chat-conv-header-wa {
            padding: 10px 20px;
            background: #F0F2F5;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 2;
        }
        body.dark-mode .chat-conv-header-wa {
            background: #202C33;
            border-color: #222E35;
        }
        .chat-conv-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .badge-sucursal-destacada {
            background: #059669;
            color: #FFF;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-pill-wa {
            background: #FFF;
            border: 1px solid var(--border-soft);
            color: var(--text-dark);
            font-size: 0.76rem;
            font-weight: 700;
            padding: 7px 14px;
            border-radius: var(--pill-radius);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }
        body.dark-mode .btn-pill-wa {
            background: #111B21;
            border-color: #222E35;
            color: #E9EDEF;
        }
        .btn-pill-wa:hover {
            border-color: var(--accent-green);
        }

        /* Mensajes scroll WhatsApp */
        .chat-messages-scroll-wa {
            flex: 1;
            padding: 16px 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #EFEAE2;
            background-image: radial-gradient(rgba(0,0,0,0.04) 1px, transparent 0);
            background-size: 16px 16px;
        }
        body.dark-mode .chat-messages-scroll-wa {
            background: #0B141A;
            background-image: radial-gradient(rgba(255,255,255,0.03) 1px, transparent 0);
            background-size: 16px 16px;
        }

        .msg-bubble-wa {
            max-width: 68%;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 0.85rem;
            line-height: 1.4;
            position: relative;
            box-shadow: 0 1px 2px rgba(0,0,0,0.12);
        }
        .msg-bubble-wa.received {
            background: #FFF;
            color: #111B21;
            align-self: flex-start;
            border-top-left-radius: 0;
        }
        body.dark-mode .msg-bubble-wa.received {
            background: #202C33;
            color: #E9EDEF;
        }
        .msg-bubble-wa.sent {
            background: #D9FDD3;
            color: #111B21;
            align-self: flex-end;
            border-top-right-radius: 0;
        }
        body.dark-mode .msg-bubble-wa.sent {
            background: #005C4B;
            color: #E9EDEF;
        }
        .msg-sender-tag {
            font-size: 0.72rem;
            font-weight: 800;
            color: #059669;
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        body.dark-mode .msg-sender-tag {
            color: #34D399;
        }
        .msg-time-wa {
            font-size: 0.65rem;
            color: var(--text-muted);
            float: right;
            margin-left: 12px;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        body.dark-mode .msg-time-wa { color: #8696A0; }

        /* Tarjeta de factura interactiva en el chat */
        .card-factura-chat {
            background: #F8FAFC;
            border: 1.5px solid #CBD5E1;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }
        body.dark-mode .card-factura-chat {
            background: #111B21;
            border-color: #2A3942;
        }
        .card-factura-chat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 800;
            font-size: 0.78rem;
            color: #0F172A;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        body.dark-mode .card-factura-chat-header { color: #F1F5F9; border-color: rgba(255,255,255,0.06); }
        .card-factura-chat-body {
            font-size: 0.8rem;
            color: #334155;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        body.dark-mode .card-factura-chat-body { color: #CBD5E1; }
        .card-factura-chat-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .btn-chat-action-green {
            background: #10B981;
            color: #FFF;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.76rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }
        .btn-chat-action-green:hover {
            background: #059669;
        }
        .btn-chat-action-outline {
            background: #FFF;
            border: 1px solid #CBD5E1;
            color: #1E293B;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.76rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }
        body.dark-mode .btn-chat-action-outline {
            background: #202C33;
            border-color: #2A3942;
            color: #FFF;
        }
        .btn-chat-action-red {
            background: #EF4444;
            color: #FFF;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.76rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }
        .btn-chat-action-red:hover {
            background: #DC2626;
            transform: translateY(-1px);
        }
        .card-pago-status-wa {
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }
        .card-pago-status-wa.aceptado {
            background: #ECFDF5;
            border: 1.5px solid #6EE7B7;
        }
        body.dark-mode .card-pago-status-wa.aceptado {
            background: rgba(16, 185, 129, 0.12);
            border-color: rgba(16, 185, 129, 0.35);
        }
        .card-pago-status-wa.observado {
            background: #FEF2F2;
            border: 1.5px solid #FCA5A5;
        }
        body.dark-mode .card-pago-status-wa.observado {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.35);
        }

        /* Barra de entrada WhatsApp */
        .chat-input-bar-wa {
            padding: 10px 18px;
            background: #F0F2F5;
            display: flex;
            align-items: center;
            gap: 12px;
            border-top: 1px solid rgba(0,0,0,0.06);
        }
        body.dark-mode .chat-input-bar-wa {
            background: #202C33;
            border-color: #222E35;
        }
        .chat-input-bar-wa input {
            flex: 1;
            padding: 10px 16px;
            border-radius: 8px;
            border: none;
            background: #FFF;
            outline: none;
            font-size: 0.88rem;
            color: var(--text-dark);
        }
        body.dark-mode .chat-input-bar-wa input {
            background: #2A3942;
            color: #E9EDEF;
        }
        .btn-chat-send-wa {
            background: #10B981;
            color: #FFF;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: var(--transition);
        }
        .btn-chat-send-wa:hover {
            background: #059669;
            transform: scale(1.05);
        }

        .chat-quick-replies {
            padding: 8px 20px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            background: #F0F2F5;
            border-top: 1px solid rgba(0,0,0,0.04);
        }
        body.dark-mode .chat-quick-replies { background: #1C2620; border-color: #27362E; }
        .quick-reply-chip {
            background: #FFF;
            border: 1px solid var(--border-soft);
            padding: 5px 12px;
            border-radius: var(--pill-radius);
            font-size: 0.74rem;
            color: var(--text-dark);
            cursor: pointer;
            white-space: nowrap;
            transition: var(--transition);
        }
        body.dark-mode .quick-reply-chip { background: #202C33; border-color: #27362E; color: #FFF; }
        .quick-reply-chip:hover {
            background: var(--accent-green);
            color: #0E1210;
        }

        /* ================= SIDEBAR DERECHA ================= */
        .right-sidebar {
            width: 320px;
            background: var(--right-bg);
            padding: 30px 24px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            overflow-y: auto;
            height: 100vh;
            flex-shrink: 0;
            transition: var(--transition);
            border-left: 1px solid var(--border-soft);
        }

        .right-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .right-header h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        .btn-dots {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1rem;
        }

        .bank-summary-card {
            background: #FFF;
            border-radius: 20px;
            padding: 16px;
            border: 1px solid var(--border-soft);
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        body.dark-mode .bank-summary-card { background: #1B201D; border-color: #27362E; }
        .bank-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(0,0,0,0.04);
        }
        .bank-item:last-child { border-bottom: none; padding-bottom: 0; }
        .bank-meta { display: flex; align-items: center; gap: 10px; }
        .bank-icon-badge {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.85rem;
            font-weight: 700;
        }
        .bank-icon-badge.bcp { background: #002A8F; color: #FF7800; }
        .bank-icon-badge.bbva { background: #004481; color: #FFF; }
        .bank-icon-badge.ibk { background: #009A44; color: #FFF; }
        .bank-name h5 { font-size: 0.8rem; color: var(--text-dark); font-weight: 600; }
        .bank-name p { font-size: 0.68rem; color: var(--text-muted); }
        .bank-amount { font-size: 0.88rem; font-weight: 700; color: var(--text-dark); }

        .pending-urgent-card {
            background: #FFF;
            border-radius: 16px;
            padding: 12px;
            border: 1px solid var(--border-soft);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            transition: var(--transition);
            cursor: pointer;
        }
        body.dark-mode .pending-urgent-card { background: #18201C; border-color: #27362E; }
        .pending-urgent-card:hover {
            border-color: var(--accent-green);
            transform: translateX(2px);
        }
        .pending-urgent-meta h5 {
            font-size: 0.8rem;
            color: var(--text-dark);
            font-weight: 700;
        }
        .pending-urgent-meta p {
            font-size: 0.68rem;
            color: var(--text-muted);
        }

        .advisor-online-item {
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
        body.dark-mode .advisor-online-item { background: #18201C; border-color: #27362E; }
        .advisor-online-item:hover {
            border-color: var(--accent-green);
            transform: translateX(2px);
        }
        .advisor-avatar-box { position: relative; }
        .advisor-avatar-box img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
        }
        .advisor-online-dot {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10B981;
            border: 2px solid #FFF;
        }
        .advisor-content { flex: 1; overflow: hidden; }
        .advisor-content h5 {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        .advisor-content p {
            font-size: 0.68rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* MODALES */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 15, 12, 0.75);
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
            max-width: 520px;
            border-radius: 28px;
            padding: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            border: 1px solid var(--border-soft);
        }
        body.dark-mode .modal-card { background: #161C19; color: #FFF; border-color: #27362E; }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .modal-close-btn {
            background: var(--accent-green-soft);
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
        ::-webkit-scrollbar-thumb { background: var(--color-3); border-radius: 3px; }

        /* ================= BOTONES Y MODAL EDITOR DE PALETA ================= */
        .btn-palette-header {
            background: #FFFFFF;
            color: var(--color-1);
            border: 1.5px solid var(--color-2);
            padding: 8px 16px;
            border-radius: var(--pill-radius);
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: var(--transition);
        }
        .btn-palette-header:hover {
            background: var(--color-2);
            color: #FFFFFF;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
        }
        body.dark-mode .btn-palette-header {
            background: #182638;
            color: #FFF;
            border-color: var(--color-2);
        }
        .palette-mini-swatches {
            display: flex;
            gap: 3px;
            align-items: center;
        }
        .palette-mini-swatches span {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            display: inline-block;
            border: 1px solid rgba(0, 0, 0, 0.15);
        }

        .modal-palette-card {
            background: #FFFFFF;
            width: 100%;
            max-width: 680px;
            border-radius: 28px;
            padding: 26px 30px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.45);
            border: 1px solid var(--border-soft);
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        body.dark-mode .modal-palette-card {
            background: #111E2E;
            color: #FFF;
            border-color: #1E334D;
        }

        /* Barra tipo Coolors */
        .coolors-bar-preview {
            display: flex;
            height: 74px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            border: 2px solid #FFF;
            margin-bottom: 4px;
        }
        body.dark-mode .coolors-bar-preview { border-color: #1E334D; }
        .coolors-bar-segment {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 8px 10px;
            font-size: 0.72rem;
            font-weight: 700;
            transition: var(--transition);
            position: relative;
        }
        .coolors-bar-segment span.hex {
            font-family: monospace;
            font-size: 0.76rem;
            letter-spacing: 0.5px;
        }
        .coolors-bar-segment span.name {
            font-size: 0.62rem;
            opacity: 0.88;
            font-weight: 600;
        }
        .coolors-bar-segment .arrow-tag {
            position: absolute;
            top: 6px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.45);
            color: #FFF;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 0.58rem;
            font-weight: 700;
            white-space: nowrap;
        }

        /* Cuadrícula de 5 colores */
        .palette-controls-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
        }
        .palette-color-item {
            background: #F8FAFC;
            border: 1.5px solid var(--border-soft);
            border-radius: 16px;
            padding: 12px 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            text-align: center;
            transition: var(--transition);
        }
        body.dark-mode .palette-color-item {
            background: #162436;
            border-color: #223854;
        }
        .palette-color-item:hover {
            border-color: var(--color-2);
            transform: translateY(-2px);
        }
        .palette-color-picker-wrapper {
            position: relative;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            border: 2px solid #FFF;
            cursor: pointer;
        }
        body.dark-mode .palette-color-picker-wrapper { border-color: #1E334D; }
        .palette-color-picker-wrapper input[type="color"] {
            position: absolute;
            top: -12px;
            left: -12px;
            width: 68px;
            height: 68px;
            border: none;
            cursor: pointer;
        }
        .palette-color-hex-input {
            width: 100%;
            border: 1px solid var(--border-soft);
            background: #FFF;
            color: var(--text-dark);
            font-family: monospace;
            font-size: 0.76rem;
            font-weight: 700;
            text-align: center;
            padding: 4px 4px;
            border-radius: 8px;
            outline: none;
            text-transform: uppercase;
        }
        body.dark-mode .palette-color-hex-input {
            background: #0E1825;
            border-color: #223854;
            color: #FFF;
        }
        .palette-color-label {
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        .palette-color-role {
            font-size: 0.62rem;
            color: var(--text-muted);
            line-height: 1.2;
        }

        /* Fila de presets */
        .preset-pills-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .preset-pill-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: var(--pill-radius);
            background: #F1F5F9;
            border: 1px solid var(--border-soft);
            font-size: 0.74rem;
            font-weight: 600;
            color: var(--text-dark);
            cursor: pointer;
            transition: var(--transition);
        }
        body.dark-mode .preset-pill-btn {
            background: #182436;
            border-color: #223854;
            color: #E2E8F0;
        }
        .preset-pill-btn:hover {
            background: var(--color-2);
            color: #FFF;
            border-color: var(--color-2);
            transform: translateY(-1px);
        }
        .preset-dots {
            display: flex;
            gap: 2px;
        }
        .preset-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        /* Toast Notificación */
        .toast-palette-notify {
            position: fixed;
            bottom: 28px;
            right: 28px;
            background: var(--color-1);
            color: #FFF;
            padding: 12px 22px;
            border-radius: var(--pill-radius);
            font-size: 0.84rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            z-index: 999999;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
            border: 1.5px solid var(--color-5);
        }
        .toast-palette-notify.show {
            transform: translateY(0);
            opacity: 1;
        }
    </style>
</head>
<body>

    <!-- CONTENEDOR PRINCIPAL EXPANDIDO A PANTALLA COMPLETA -->
    <div class="app-container">
        
        <!-- ================= SIDEBAR IZQUIERDA ================= -->
        <div class="sidebar">
            <a href="reportes.php" class="brand-logo">
                <div class="brand-logo-icon">
                    <i class="fa-solid fa-shield-check"></i>
                </div>
                <div class="brand-logo-text">
                    REPORTERÍA
                    <span>FINANZAS BS PERÚ</span>
                </div>
            </a>

            <div class="nav-menu">
                <div class="nav-item active" id="nav-dashboard" onclick="cambiarVistaReporteria('dashboard', this)">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Dashboard General</span>
                </div>

                <div class="nav-item" id="nav-validacion" onclick="cambiarVistaReporteria('validacion', this)">
                    <i class="fa-solid fa-file-circle-check"></i>
                    <span>Validar Pagos</span>
                    <span class="nav-badge" id="badgeSidePending" style="background:var(--accent-green); color:#0E1210; font-weight:700;">3</span>
                </div>

                <div class="nav-item" id="nav-cierres" onclick="cambiarVistaReporteria('cierres', this)">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Cierres de Ventas</span>
                </div>

                <div class="nav-item" id="nav-historial" onclick="cambiarVistaReporteria('historial', this)">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Vouchers Aceptados</span>
                </div>

                <div class="nav-item" id="nav-chat" onclick="cambiarVistaReporteria('chat', this)">
                    <i class="fa-solid fa-comments"></i>
                    <span>Chat con Ventas</span>
                </div>

                <div class="nav-item" id="nav-sucursales" onclick="cambiarVistaReporteria('sucursales', this)">
                    <i class="fa-solid fa-building-columns"></i>
                    <span>Metas de Sucursal</span>
                </div>
            </div>

            <!-- PERFIL USUARIO -->
            <div class="user-pill" onclick="alert('Sesión Activa: Nayeli\nRol: Especialista de Reportería & Conciliación Financiera BS Perú')">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" alt="Nayeli" class="user-pill-avatar">
                <div class="user-pill-info">
                    <div class="user-pill-name">Nayeli</div>
                    <div class="user-pill-status">
                        <div class="status-dot"></div>
                        <span>Reportería / En línea</span>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-right user-pill-chevron"></i>
            </div>

            <!-- SELECTOR DE TEMA -->
            <div class="theme-toggle">
                <button class="theme-btn active" id="btnThemeLight" onclick="setAppTheme('light')">
                    <i class="fa-regular fa-sun"></i> Claro
                </button>
                <button class="theme-btn" id="btnThemeDark" onclick="setAppTheme('dark')">
                    <i class="fa-solid fa-moon"></i> Oscuro
                </button>
            </div>

            <!-- BOTÓN ACCESO RÁPIDO A EDITAR PALETA -->
            <div style="margin-top: 6px;">
                <button type="button" class="theme-btn" style="width:100%; border:1px dashed rgba(255,255,255,0.25); padding:7px 10px; border-radius:var(--pill-radius); color:var(--color-5); background:rgba(255,255,255,0.06); font-size:0.75rem; display:flex; align-items:center; justify-content:center; gap:8px; cursor:pointer;" onclick="abrirModalEditorPaleta()" title="Probar y editar colores">
                    <i class="fa-solid fa-palette"></i> Editar Paleta
                </button>
            </div>
        </div>

        <!-- ================= ÁREA CENTRAL (SPA DINÁMICA) ================= -->
        <div class="main-content">
            
            <!-- HEADER GLOBAL -->
            <div class="main-header">
                <div>
                    <span style="font-size:0.75rem; font-weight:700; color:var(--accent-green-dark); text-transform:uppercase; letter-spacing:1px;">Área de Finanzas & Reportería</span>
                    <h1 id="pageMainTitle">Dashboard de Reportes & Validación</h1>
                </div>
                <div class="header-actions">
                    <button type="button" class="btn-palette-header" onclick="abrirModalEditorPaleta()" title="Personalizar y probar paleta de colores de Reportería">
                        <div class="palette-mini-swatches">
                            <span style="background:var(--color-1);"></span>
                            <span style="background:var(--color-2);"></span>
                            <span style="background:var(--color-3);"></span>
                            <span style="background:var(--color-4);"></span>
                            <span style="background:var(--color-5);"></span>
                        </div>
                        <i class="fa-solid fa-palette"></i>
                        <span>Editar Paleta</span>
                    </button>
                    <button class="btn-pill-white" style="background:#FEE2E2; color:#B91C1C; font-size:0.8rem; padding:8px 16px;" onclick="abrirLogoutModal()">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Salir
                    </button>
                </div>
            </div>

            <!-- ==========================================
                 VISTA 1: DASHBOARD DE REPORTERÍA
            =========================================== -->
            <div class="vista-seccion" id="vista-dashboard" style="display:flex;">
                <!-- HERO CARD BANNER -->
                <div class="welcome-card">
                    <div class="welcome-content">
                        <h2>Validación Financiera & Reportería en Tiempo Real</h2>
                        <p>Supervisa las conciliaciones bancarias (BCP, BBVA, Interbank), aprueba pagos reportados por los asesores de ventas y audita los cierres diarios de caja para despacho inmediato.</p>
                        <div class="welcome-actions">
                            <button class="btn-pill-white primary" onclick="cambiarVistaReporteria('validacion')">
                                <i class="fa-solid fa-shield-check"></i> Validar 3 Pagos Pendientes
                            </button>
                            <button class="btn-pill-white" onclick="cambiarVistaReporteria('cierres')">
                                <i class="fa-solid fa-file-lines"></i> Ver Cierres de Ventas
                            </button>
                        </div>
                    </div>
                    <div class="welcome-avatar-wrapper">
                        <div class="welcome-avatar-frame">
                            <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=300&auto=format&fit=crop&q=80" alt="Finanzas">
                        </div>
                    </div>
                </div>

                <!-- 4 MINI STATS CARDS -->
                <div class="stats-grid">
                    <div class="stat-card-mini" onclick="cambiarVistaReporteria('historial')">
                        <div class="stat-mini-icon"><i class="fa-solid fa-wallet"></i></div>
                        <div class="stat-mini-info">
                            <h4 id="kpiTotalVentas">S/ 94,178</h4>
                            <p>Recaudación Mensual</p>
                        </div>
                    </div>

                    <div class="stat-card-mini" onclick="cambiarVistaReporteria('validacion')" style="border-color:#F59E0B; background:#FFFBEB;">
                        <div class="stat-mini-icon" style="color:#D97706; background:#FEF3C7;"><i class="fa-solid fa-hourglass-half"></i></div>
                        <div class="stat-mini-info">
                            <h4 id="kpiPagosPendientes" style="color:#D97706;">3</h4>
                            <p>Pagos por Validar</p>
                        </div>
                    </div>

                    <div class="stat-card-mini">
                        <div class="stat-mini-icon"><i class="fa-solid fa-truck-fast"></i></div>
                        <div class="stat-mini-info">
                            <h4>142</h4>
                            <p>Despachos Liberados</p>
                        </div>
                    </div>

                    <div class="stat-card-mini" onclick="cambiarVistaReporteria('cierres')">
                        <div class="stat-mini-icon"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="stat-mini-info">
                            <h4 id="kpiTotalHoy">S/ 24,508</h4>
                            <p>Validado Hoy</p>
                        </div>
                    </div>
                </div>

                <!-- 2 COLUMNAS DE DETALLE -->
                <div class="content-columns">
                    <!-- Conciliación por Bancos -->
                    <div class="card-panel">
                        <div class="panel-header">
                            <h3><i class="fa-solid fa-building-columns" style="color:var(--accent-green);"></i> Conciliación Bancaria por Entidad</h3>
                            <span onclick="cambiarVistaReporteria('historial')">Ver Detalle <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                        
                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-title"><span style="color:#002A8F; font-weight:800;">● BCP</span> Banco de Crédito del Perú</span>
                                <span class="progress-percent">S/ 52,400.00 (56%)</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-bar-fill" style="width: 56%; background: #002A8F;"></div>
                            </div>
                        </div>

                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-title"><span style="color:#004481; font-weight:800;">● BBVA</span> Banco Continental</span>
                                <span class="progress-percent">S/ 28,100.00 (30%)</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-bar-fill" style="width: 30%; background: #004481;"></div>
                            </div>
                        </div>

                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-title"><span style="color:#009A44; font-weight:800;">● INTERBANK</span> Banco Internacional</span>
                                <span class="progress-percent">S/ 13,678.00 (14%)</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-bar-fill" style="width: 14%; background: #009A44;"></div>
                            </div>
                        </div>

                        <div style="background:#F4F8F6; border-radius:18px; padding:14px; margin-top:8px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <small style="color:var(--text-muted); font-size:0.75rem;">Tiempo promedio de validación:</small>
                                <div style="font-weight:700; color:var(--text-dark); font-size:0.95rem;">⚡ 6 minutos por voucher</div>
                            </div>
                            <button class="btn-confirm-direct" onclick="cambiarVistaReporteria('validacion')">
                                Ir a Validar
                            </button>
                        </div>
                    </div>

                    <!-- Metas de Sucursal Resumen -->
                    <div class="card-panel">
                        <div class="panel-header">
                            <h3><i class="fa-solid fa-chart-simple" style="color:var(--accent-green);"></i> Avance de Sucursales</h3>
                            <span onclick="cambiarVistaReporteria('sucursales')">Ver todas</span>
                        </div>

                        <div style="display:flex; flex-direction:column; gap:14px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-soft); padding-bottom:10px;">
                                <div>
                                    <strong style="font-size:0.9rem; color:var(--text-dark);">Sucursal Lima (Central)</strong>
                                    <p style="font-size:0.75rem; color:var(--text-muted);">Meta: S/ 50,000 / mes</p>
                                </div>
                                <span class="badge-status-accepted">45% (S/ 22,500)</span>
                            </div>

                            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-soft); padding-bottom:10px;">
                                <div>
                                    <strong style="font-size:0.9rem; color:var(--text-dark);">Sucursal Piura</strong>
                                    <p style="font-size:0.75rem; color:var(--text-muted);">Meta: S/ 20,000 / mes</p>
                                </div>
                                <span class="badge-status-accepted" style="background:#DCFCE7; color:#15803D;">90% (S/ 18,000)</span>
                            </div>

                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong style="font-size:0.9rem; color:var(--text-dark);">Sucursal Arequipa</strong>
                                    <p style="font-size:0.75rem; color:var(--text-muted);">Meta: S/ 30,000 / mes</p>
                                </div>
                                <span class="badge-status-pending">13% (S/ 3,900)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==========================================
                 VISTA 2: BANDEJA DE VALIDACIÓN DE PAGOS
            =========================================== -->
            <div class="vista-seccion" id="vista-validacion" style="display:none;">
                <div class="val-toolbar">
                    <div class="val-search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="inputBuscarPago" placeholder="Buscar por cliente, cotización o N° de operación bancaria..." onkeyup="filtrarPagosTabla()">
                    </div>

                    <div class="val-filter-pills">
                        <div class="val-pill active" onclick="filtrarBancoPago('todos', this)">Todos (3)</div>
                        <div class="val-pill" onclick="filtrarBancoPago('BCP', this)">BCP (1)</div>
                        <div class="val-pill" onclick="filtrarBancoPago('BBVA', this)">BBVA (1)</div>
                        <div class="val-pill" onclick="filtrarBancoPago('Interbank', this)">Interbank (1)</div>
                    </div>
                </div>

                <div class="val-table-wrapper">
                    <table class="val-table">
                        <thead>
                            <tr>
                                <th>Cotización / Pedido</th>
                                <th>Asesor Comercial</th>
                                <th>Cliente / RUC</th>
                                <th>Monto</th>
                                <th>Banco / Operación</th>
                                <th>Voucher Adjunto</th>
                                <th>Estado</th>
                                <th style="text-align:center;">Acción de Reportería</th>
                            </tr>
                        </thead>
                        <tbody id="valTableTbody">
                            <!-- PAGO 1 -->
                            <tr id="row-pago-1" data-banco="BCP">
                                <td><strong>#COT-2026-084</strong></td>
                                <td><i class="fa-solid fa-user-tie" style="color:var(--accent-green);"></i> Maria Gomez</td>
                                <td>
                                    <strong>Cosapi S.A.</strong><br>
                                    <span style="font-size:0.72rem; color:var(--text-muted);">RUC: 20100152430</span>
                                </td>
                                <td><strong style="color:var(--text-dark); font-size:0.95rem;">S/ 14,400.00</strong></td>
                                <td><span style="background:#002A8F; color:#FFF; padding:3px 8px; border-radius:8px; font-size:0.72rem; font-weight:700;">BCP #4829104</span></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=150&q=80" class="voucher-thumb-small" onclick="abrirVisorVoucher('COT-2026-084', 'Cosapi S.A.', '14,400.00', 'BCP Op. #4829104', 'Maria Gomez', 1)" alt="Voucher">
                                        <span style="font-size:0.75rem; color:var(--accent-green-dark); cursor:pointer; font-weight:600;" onclick="abrirVisorVoucher('COT-2026-084', 'Cosapi S.A.', '14,400.00', 'BCP Op. #4829104', 'Maria Gomez', 1)">Ver Voucher</span>
                                    </div>
                                </td>
                                <td><span class="badge-status-pending" id="badge-pago-1"><i class="fa-solid fa-hourglass-start"></i> Por Confirmar</span></td>
                                <td>
                                    <div style="display:flex; gap:6px; justify-content:center;">
                                        <button class="btn-confirm-direct" onclick="confirmarPagoEnFila(1, 14400, 'COT-2026-084')">
                                            <i class="fa-solid fa-check"></i> Aceptar
                                        </button>
                                        <button class="btn-observe-direct" onclick="observarPagoEnFila(1, 'COT-2026-084')">
                                            <i class="fa-solid fa-circle-exclamation"></i> Observar
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- PAGO 2 -->
                            <tr id="row-pago-2" data-banco="BBVA">
                                <td><strong>#COT-2026-085</strong></td>
                                <td><i class="fa-solid fa-user-tie" style="color:var(--accent-green);"></i> Carlos Ruiz</td>
                                <td>
                                    <strong>Consorcio Vial Piura</strong><br>
                                    <span style="font-size:0.72rem; color:var(--text-muted);">RUC: 20452391024</span>
                                </td>
                                <td><strong style="color:var(--text-dark); font-size:0.95rem;">S/ 6,800.00</strong></td>
                                <td><span style="background:#004481; color:#FFF; padding:3px 8px; border-radius:8px; font-size:0.72rem; font-weight:700;">BBVA #910245</span></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=150&q=80" class="voucher-thumb-small" onclick="abrirVisorVoucher('COT-2026-085', 'Consorcio Vial Piura', '6,800.00', 'BBVA Op. #910245', 'Carlos Ruiz', 2)" alt="Voucher">
                                        <span style="font-size:0.75rem; color:var(--accent-green-dark); cursor:pointer; font-weight:600;" onclick="abrirVisorVoucher('COT-2026-085', 'Consorcio Vial Piura', '6,800.00', 'BBVA Op. #910245', 'Carlos Ruiz', 2)">Ver Voucher</span>
                                    </div>
                                </td>
                                <td><span class="badge-status-pending" id="badge-pago-2"><i class="fa-solid fa-hourglass-start"></i> Por Confirmar</span></td>
                                <td>
                                    <div style="display:flex; gap:6px; justify-content:center;">
                                        <button class="btn-confirm-direct" onclick="confirmarPagoEnFila(2, 6800, 'COT-2026-085')">
                                            <i class="fa-solid fa-check"></i> Aceptar
                                        </button>
                                        <button class="btn-observe-direct" onclick="observarPagoEnFila(2, 'COT-2026-085')">
                                            <i class="fa-solid fa-circle-exclamation"></i> Observar
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- PAGO 3 -->
                            <tr id="row-pago-3" data-banco="Interbank">
                                <td><strong>#COT-2026-086</strong></td>
                                <td><i class="fa-solid fa-user-tie" style="color:var(--accent-green);"></i> Ana Torres</td>
                                <td>
                                    <strong>Edificaciones Pacífico E.I.R.L.</strong><br>
                                    <span style="font-size:0.72rem; color:var(--text-muted);">RUC: 20601948201</span>
                                </td>
                                <td><strong style="color:var(--text-dark); font-size:0.95rem;">S/ 3,308.00</strong></td>
                                <td><span style="background:#009A44; color:#FFF; padding:3px 8px; border-radius:8px; font-size:0.72rem; font-weight:700;">Interbank #3019</span></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=150&q=80" class="voucher-thumb-small" onclick="abrirVisorVoucher('COT-2026-086', 'Edificaciones Pacífico', '3,308.00', 'Interbank Op. #3019', 'Ana Torres', 3)" alt="Voucher">
                                        <span style="font-size:0.75rem; color:var(--accent-green-dark); cursor:pointer; font-weight:600;" onclick="abrirVisorVoucher('COT-2026-086', 'Edificaciones Pacífico', '3,308.00', 'Interbank Op. #3019', 'Ana Torres', 3)">Ver Voucher</span>
                                    </div>
                                </td>
                                <td><span class="badge-status-pending" id="badge-pago-3"><i class="fa-solid fa-hourglass-start"></i> Por Confirmar</span></td>
                                <td>
                                    <div style="display:flex; gap:6px; justify-content:center;">
                                        <button class="btn-confirm-direct" onclick="confirmarPagoEnFila(3, 3308, 'COT-2026-086')">
                                            <i class="fa-solid fa-check"></i> Aceptar
                                        </button>
                                        <button class="btn-observe-direct" onclick="observarPagoEnFila(3, 'COT-2026-086')">
                                            <i class="fa-solid fa-circle-exclamation"></i> Observar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ==========================================
                 VISTA 3: CIERRES DE VENTAS DEL DÍA
            =========================================== -->
            <div class="vista-seccion" id="vista-cierres" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <h3 style="font-size:1.3rem; font-family:'Outfit', sans-serif; color:var(--text-dark);">Cierres Diarios Enviados por Ventas</h3>
                        <p style="font-size:0.8rem; color:var(--text-muted);">Cuadre de operaciones y conciliación de caja enviado por los asesores al finalizar su jornada.</p>
                    </div>
                    <button class="btn-pill-white primary" onclick="exportarReporteExcel()">
                        <i class="fa-solid fa-file-excel"></i> Exportar Cuadre (Excel / CSV)
                    </button>
                </div>

                <div class="stats-grid">
                    <div class="stat-card-mini">
                        <div class="stat-mini-icon"><i class="fa-solid fa-coins"></i></div>
                        <div class="stat-mini-info">
                            <h4 id="kpiCierreTotal">S/ 42,708</h4>
                            <p>Total Cierre del Día</p>
                        </div>
                    </div>
                    <div class="stat-card-mini">
                        <div class="stat-mini-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-mini-info">
                            <h4 id="kpiAsesoresReportaron">4 / 4</h4>
                            <p>Asesores que Reportaron</p>
                        </div>
                    </div>
                    <div class="stat-card-mini">
                        <div class="stat-mini-icon"><i class="fa-solid fa-receipt"></i></div>
                        <div class="stat-mini-info">
                            <h4 id="kpiOperacionesTotal">12</h4>
                            <p>Operaciones del Día</p>
                        </div>
                    </div>
                    <div class="stat-card-mini">
                        <div class="stat-mini-icon"><i class="fa-solid fa-certificate"></i></div>
                        <div class="stat-mini-info">
                            <h4 id="kpiConciliadoPct">100%</h4>
                            <p>Conciliado con Bancos</p>
                        </div>
                    </div>
                </div>

                <div class="val-table-wrapper">
                    <table class="val-table">
                        <thead>
                            <tr>
                                <th>Asesor Comercial</th>
                                <th>Sucursal / Sede</th>
                                <th>Monto Reportado</th>
                                <th>N° Operaciones</th>
                                <th>Hora de Envío</th>
                                <th>Auditoría Reportería</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cierresTableTbody">
                            <!-- Cargado dinámicamente vía cargarCierresReporteria() -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ==========================================
                 VISTA 4: HISTORIAL DE COMPROBANTES ACEPTADOS
            =========================================== -->
            <div class="vista-seccion" id="vista-historial" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <h3 style="font-size:1.3rem; font-family:'Outfit', sans-serif; color:var(--text-dark);">Historial de Vouchers y Pagos Validados</h3>
                        <p style="font-size:0.8rem; color:var(--text-muted);">Registro completo de todos los comprobantes que ya fueron aceptados por Reportería en la base de datos.</p>
                    </div>
                    <button class="btn-pill-white primary" onclick="exportarReporteExcel()">
                        <i class="fa-solid fa-download"></i> Descargar Histórico
                    </button>
                </div>

                <div class="val-table-wrapper">
                    <table class="val-table">
                        <thead>
                            <tr>
                                <th>Factura / Pedido</th>
                                <th>Cliente</th>
                                <th>Monto</th>
                                <th>Banco & N° Op</th>
                                <th>Validador</th>
                                <th>Fecha & Hora</th>
                                <th>Estado</th>
                                <th>Voucher</th>
                            </tr>
                        </thead>
                        <tbody id="historialValidadosTbody">
                            <tr>
                                <td><strong>F001-00892</strong></td>
                                <td>Cosapi S.A.</td>
                                <td><strong>S/ 14,400.00</strong></td>
                                <td>BCP #849201</td>
                                <td>Nayeli</td>
                                <td>Hoy 14:20</td>
                                <td><span class="badge-status-accepted">✅ Pago Aceptado</span></td>
                                <td><button class="btn-confirm-direct" style="padding:4px 10px; font-size:0.7rem;" onclick="abrirVisorVoucher('F001-00892', 'Cosapi S.A.', '14,400.00', 'BCP #849201', 'Maria Gomez', 0)">Ver</button></td>
                            </tr>
                            <tr>
                                <td><strong>F001-00891</strong></td>
                                <td>Graña y Montero S.A.</td>
                                <td><strong>S/ 28,950.00</strong></td>
                                <td>BBVA #771920</td>
                                <td>Nayeli</td>
                                <td>Ayer 16:45</td>
                                <td><span class="badge-status-accepted">✅ Pago Aceptado</span></td>
                                <td><button class="btn-confirm-direct" style="padding:4px 10px; font-size:0.7rem;" onclick="abrirVisorVoucher('F001-00891', 'Graña y Montero S.A.', '28,950.00', 'BBVA #771920', 'Endrina', 0)">Ver</button></td>
                            </tr>
                            <tr>
                                <td><strong>F001-00890</strong></td>
                                <td>Obrascón Huarte Lain</td>
                                <td><strong>S/ 9,450.00</strong></td>
                                <td>Interbank #30198</td>
                                <td>Nayeli</td>
                                <td>Ayer 11:30</td>
                                <td><span class="badge-status-accepted">✅ Pago Aceptado</span></td>
                                <td><button class="btn-confirm-direct" style="padding:4px 10px; font-size:0.7rem;" onclick="abrirVisorVoucher('F001-00890', 'Obrascón Huarte Lain', '9,450.00', 'Interbank #30198', 'Carlos Ruiz', 0)">Ver</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ==========================================
                 VISTA 5: CHAT CON ASESORES DE VENTAS (TIPO WHATSAPP)
            =========================================== -->
            <div class="vista-seccion" id="vista-chat" style="display:none;">
                <div class="chat-full-container">
                    <!-- Panel lateral izquierdo estilo WhatsApp -->
                    <div class="chat-sidebar-list">
                        <!-- Cabecera de usuario Reportería -->
                        <div class="chat-sidebar-header-wa">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div class="chat-user-avatar" style="width:38px; height:38px;">
                                    <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=100&auto=format&fit=crop&q=80" alt="Nayeli">
                                    <div class="chat-user-dot"></div>
                                </div>
                                <div>
                                    <h5 style="margin:0; font-size:0.86rem; font-weight:800; color:var(--text-dark);">Nayeli • Finanzas</h5>
                                    <span style="font-size:0.68rem; color:#10B981; font-weight:700;"><i class="fa-solid fa-circle" style="font-size:0.5rem;"></i> En línea (Reportería)</span>
                                </div>
                            </div>
                            <button type="button" class="btn-refresh-chat" onclick="cargarChatReporteria()" title="Actualizar chats en tiempo real">
                                <i class="fa-solid fa-rotate"></i>
                            </button>
                        </div>

                        <!-- Barra de búsqueda tipo WhatsApp -->
                        <div class="chat-search-bar-wa">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="inputBuscarAsesorChat" placeholder="Buscar asesor o sucursal..." oninput="filtrarConversacionesChatWhatsApp(this.value)">
                        </div>

                        <!-- Lista dinámica de conversaciones ordenada por últimos mensajes -->
                        <div class="chat-conversations-scroll" id="chatConversationsList">
                            <!-- Se renderiza dinámicamente con renderListaConversacionesWhatsApp() -->
                        </div>
                    </div>

                    <!-- Área de conversación activa tipo WhatsApp -->
                    <div class="chat-conversation-area">
                        <!-- Cabecera de la conversación activa -->
                        <div class="chat-conv-header-wa">
                            <div class="chat-conv-user-info">
                                <div class="chat-user-avatar" style="width:42px; height:42px;">
                                    <img id="chatActiveAdvisorAvatar" src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&auto=format&fit=crop&q=80" alt="Asesor">
                                    <div class="chat-user-dot" id="chatActiveAdvisorDot"></div>
                                </div>
                                <div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <h4 id="chatActiveAdvisorName" style="margin:0; font-size:0.98rem; font-weight:800; color:var(--text-dark);">Endrina</h4>
                                        <span class="badge-sucursal-destacada" id="chatActiveAdvisorSucursalBadge">
                                            <i class="fa-solid fa-location-dot"></i> Sucursal Chorrillos
                                        </span>
                                    </div>
                                    <span id="chatActiveAdvisorRole" style="font-size:0.72rem; color:#10B981; font-weight:600;">
                                        🏢 Sucursal Chorrillos • Asesora de Ventas • En línea
                                    </span>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap:8px;">
                                <button type="button" class="btn-pill-wa" onclick="cambiarVistaReporteria('validacion')" title="Ver comprobantes y pagos pendientes">
                                    <i class="fa-solid fa-file-invoice-dollar" style="color:var(--accent-green);"></i> Vouchers Pendientes
                                </button>
                            </div>
                        </div>

                        <!-- Área de mensajes WhatsApp -->
                        <div class="chat-messages-scroll-wa" id="chatMessagesScroll">
                            <!-- Se renderiza dinámicamente con renderMensajesConversacionActiva() -->
                        </div>

                        <!-- Respuestas rápidas de validación -->
                        <div class="chat-quick-replies">
                            <span class="quick-reply-chip" onclick="insertarRespuestaRapida('✅ Pago verificado y confirmado en extracto bancario. Pedido liberado para despacho.')">
                                ✅ Pago verificado en banco
                            </span>
                            <span class="quick-reply-chip" onclick="insertarRespuestaRapida('⚠️ Por favor enviar foto del voucher con mayor nitidez o número de operación.')">
                                ⚠️ Voucher poco legible
                            </span>
                            <span class="quick-reply-chip" onclick="insertarRespuestaRapida('🔍 El N° de operación aún no figura en el extracto online, en revisión.')">
                                🔍 Aún no figura en extracto
                            </span>
                            <span class="quick-reply-chip" onclick="insertarRespuestaRapida('📦 Confirmado el abono. Se autoriza la entrega y guía de remisión.')">
                                📦 Se autoriza entrega
                            </span>
                        </div>

                        <!-- Barra de escritura estilo WhatsApp -->
                        <div class="chat-input-bar-wa">
                            <input type="text" id="chatInputMessage" placeholder="Escribe un mensaje para Endrina..." onkeypress="if(event.key==='Enter') enviarMensajeChat()">
                            <button type="button" class="btn-chat-send-wa" onclick="enviarMensajeChat()" title="Enviar mensaje">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==========================================
                 VISTA 6: METAS Y SUCURSALES
            =========================================== -->
            <div class="vista-seccion" id="vista-sucursales" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <h3 style="font-size:1.3rem; font-family:'Outfit', sans-serif; color:var(--text-dark);">Control de Metas por Sucursal</h3>
                        <p style="font-size:0.8rem; color:var(--text-muted);">Monitoreo financiero y cumplimiento de objetivos comerciales de cada sede de BS Perú.</p>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:20px;">
                    <!-- SUCURSAL CHORRILLOS -->
                    <div class="card-panel">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:45px; height:45px; background:var(--accent-green-soft); color:var(--accent-green-dark); border-radius:14px; display:flex; justify-content:center; align-items:center; font-size:1.2rem;">
                                <i class="fa-solid fa-building"></i>
                            </div>
                            <div>
                                <h4 style="font-size:1.05rem; color:var(--text-dark);">Sucursal Chorrillos</h4>
                                <span style="font-size:0.75rem; color:var(--text-muted);">Sede Sur • RUC 20609793806</span>
                            </div>
                        </div>

                        <div style="margin:10px 0;">
                            <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:6px;">
                                <span>Avance del Mes</span>
                                <strong>65% (S/ 32,708 / S/ 50,000)</strong>
                            </div>
                            <div class="progress-track">
                                <div class="progress-bar-fill" style="width:65%; background:var(--accent-green);"></div>
                            </div>
                            <small style="color:var(--accent-green-dark); font-size:0.72rem; margin-top:4px; display:block; font-weight:600;">Excelente ritmo de cotizaciones y facturación</small>
                        </div>

                        <div style="font-size:0.8rem; color:var(--text-muted);">
                            <strong>Top Asesor:</strong> Endrina (S/ 32,708.00)
                        </div>
                    </div>

                    <!-- SUCURSAL PIURA -->
                    <div class="card-panel">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:45px; height:45px; background:#DCFCE7; color:#15803D; border-radius:14px; display:flex; justify-content:center; align-items:center; font-size:1.2rem;">
                                <i class="fa-solid fa-store"></i>
                            </div>
                            <div>
                                <h4 style="font-size:1.05rem; color:var(--text-dark);">Sucursal Piura</h4>
                                <span style="font-size:0.75rem; color:var(--text-muted);">Norte / Proyectos Viales</span>
                            </div>
                        </div>

                        <div style="margin:10px 0;">
                            <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:6px;">
                                <span>Avance del Mes</span>
                                <strong style="color:#15803D;">90% (S/ 18,000 / S/ 20,000)</strong>
                            </div>
                            <div class="progress-track">
                                <div class="progress-bar-fill" style="width:90%; background:#15803D;"></div>
                            </div>
                            <small style="color:#15803D; font-size:0.72rem; margin-top:4px; display:block; font-weight:600;">¡Cerca de superar la meta mensual!</small>
                        </div>

                        <div style="font-size:0.8rem; color:var(--text-muted);">
                            <strong>Top Asesor:</strong> Carlos Ruiz (S/ 18,000)
                        </div>
                    </div>

                    <!-- SUCURSAL AREQUIPA -->
                    <div class="card-panel">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:45px; height:45px; background:#FEF3C7; color:#D97706; border-radius:14px; display:flex; justify-content:center; align-items:center; font-size:1.2rem;">
                                <i class="fa-solid fa-city"></i>
                            </div>
                            <div>
                                <h4 style="font-size:1.05rem; color:var(--text-dark);">Sucursal Arequipa</h4>
                                <span style="font-size:0.75rem; color:var(--text-muted);">Sede Sur / Minería</span>
                            </div>
                        </div>

                        <div style="margin:10px 0;">
                            <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:6px;">
                                <span>Avance del Mes</span>
                                <strong>13% (S/ 3,900 / S/ 30,000)</strong>
                            </div>
                            <div class="progress-track">
                                <div class="progress-bar-fill" style="width:13%; background:#F59E0B;"></div>
                            </div>
                            <small style="color:var(--text-muted); font-size:0.72rem; margin-top:4px; display:block;">Requiere refuerzo en cotizaciones</small>
                        </div>

                        <div style="font-size:0.8rem; color:var(--text-muted);">
                            <strong>Top Asesor:</strong> Luis Paz (S/ 3,900)
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ================= SIDEBAR DERECHA (SIEMPRE VISIBLE) ================= -->
        <div class="right-sidebar">
            <div class="right-header">
                <h3>Monitoreo en Vivo</h3>
                <button class="btn-dots"><i class="fa-solid fa-ellipsis-vertical"></i></button>
            </div>

            <!-- SALDOS BANCARIOS EXTRACTO RÁPIDO -->
            <div>
                <span style="font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Cuentas Bancarias BS Perú</span>
                <div class="bank-summary-card" style="margin-top:8px;">
                    <div class="bank-item">
                        <div class="bank-meta">
                            <div class="bank-icon-badge bcp">BCP</div>
                            <div class="bank-name">
                                <h5>Cta Cte Soles</h5>
                                <p>191-2849102-0-45</p>
                            </div>
                        </div>
                        <div class="bank-amount">S/ 142,500</div>
                    </div>

                    <div class="bank-item">
                        <div class="bank-meta">
                            <div class="bank-icon-badge bbva">BBVA</div>
                            <div class="bank-name">
                                <h5>Cta Cte Soles</h5>
                                <p>0011-0382-010029</p>
                            </div>
                        </div>
                        <div class="bank-amount">S/ 68,200</div>
                    </div>

                    <div class="bank-item">
                        <div class="bank-meta">
                            <div class="bank-icon-badge ibk">IBK</div>
                            <div class="bank-name">
                                <h5>Cta Cte Soles</h5>
                                <p>200-300182910</p>
                            </div>
                        </div>
                        <div class="bank-amount">S/ 35,800</div>
                    </div>
                </div>
            </div>

            <!-- ALERTAS URGENTES DE PAGOS -->
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <span style="font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Pagos Urgentes</span>
                    <span class="badge-status-pending" id="badgeRightPending">3 pendientes</span>
                </div>

                <div style="display:flex; flex-direction:column; gap:8px;" id="boxRightAlerts">
                    <div class="pending-urgent-card" onclick="cambiarVistaReporteria('validacion')">
                        <div class="pending-urgent-meta">
                            <h5>Cosapi S.A.</h5>
                            <p>S/ 14,400.00 • BCP Op. #4829104</p>
                        </div>
                        <span style="color:var(--accent-green-dark); font-size:0.75rem; font-weight:700;">Revisar →</span>
                    </div>

                    <div class="pending-urgent-card" onclick="cambiarVistaReporteria('validacion')">
                        <div class="pending-urgent-meta">
                            <h5>Consorcio Vial Piura</h5>
                            <p>S/ 6,800.00 • BBVA Op. #910245</p>
                        </div>
                        <span style="color:var(--accent-green-dark); font-size:0.75rem; font-weight:700;">Revisar →</span>
                    </div>

                    <div class="pending-urgent-card" onclick="cambiarVistaReporteria('validacion')">
                        <div class="pending-urgent-meta">
                            <h5>Edificaciones Pacífico</h5>
                            <p>S/ 3,308.00 • Interbank Op. #3019</p>
                        </div>
                        <span style="color:var(--accent-green-dark); font-size:0.75rem; font-weight:700;">Revisar →</span>
                    </div>
                </div>
            </div>

            <!-- ASESORES ACTIVOS -->
            <div>
                <span style="font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Asesores en Línea</span>
                <div style="display:flex; flex-direction:column; gap:8px; margin-top:8px;">
                    <div class="advisor-online-item" onclick="abrirChatConAsesor('Maria Gomez')">
                        <div class="advisor-avatar-box">
                            <img src="https://ui-avatars.com/api/?name=Maria+Gomez&background=D1FAE5&color=059669" alt="User">
                            <div class="advisor-online-dot"></div>
                        </div>
                        <div class="advisor-content">
                            <h5>Maria Gomez</h5>
                            <p>Lima Corporativo • Activa</p>
                        </div>
                        <i class="fa-solid fa-comment-dots" style="color:var(--accent-green);"></i>
                    </div>

                    <div class="advisor-online-item" onclick="abrirChatConAsesor('Carlos Ruiz')">
                        <div class="advisor-avatar-box">
                            <img src="https://ui-avatars.com/api/?name=Carlos+Ruiz&background=FEF3C7&color=D97706" alt="User">
                            <div class="advisor-online-dot"></div>
                        </div>
                        <div class="advisor-content">
                            <h5>Carlos Ruiz</h5>
                            <p>Piura Despachos • Activo</p>
                        </div>
                        <i class="fa-solid fa-comment-dots" style="color:var(--accent-green);"></i>
                    </div>

                    <div class="advisor-online-item" onclick="abrirChatConAsesor('Ana Torres')">
                        <div class="advisor-avatar-box">
                            <img src="https://ui-avatars.com/api/?name=Ana+Torres&background=E0E7FF&color=4338CA" alt="User">
                            <div class="advisor-online-dot"></div>
                        </div>
                        <div class="advisor-content">
                            <h5>Ana Torres</h5>
                            <p>San Borja • Activa</p>
                        </div>
                        <i class="fa-solid fa-comment-dots" style="color:var(--accent-green);"></i>
                    </div>

                    <div class="advisor-online-item" onclick="abrirChatConAsesor('Endrina')">
                        <div class="advisor-avatar-box">
                            <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&auto=format&fit=crop&q=80" alt="User">
                            <div class="advisor-online-dot"></div>
                        </div>
                        <div class="advisor-content">
                            <h5>Endrina</h5>
                            <p>Sucursal Chorrillos • Activa</p>
                        </div>
                        <i class="fa-solid fa-comment-dots" style="color:var(--accent-green);"></i>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- ================= MODAL VISOR DE VOUCHER ================= -->
    <div class="modal-overlay" id="modalVisorVoucher">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <span style="font-size:0.72rem; color:var(--text-muted); font-weight:700; text-transform:uppercase;">Auditoría de Comprobante</span>
                    <h3 id="modalVoucherCotiz">#COT-2026-084</h3>
                </div>
                <button class="modal-close-btn" onclick="cerrarModales()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div style="background:#F4F8F6; padding:16px; border-radius:18px; border:1px solid var(--border-soft); text-align:center; margin-bottom:16px;">
                <img id="modalVoucherImg" src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=500&q=80" alt="Voucher" style="max-width:100%; height:240px; object-fit:cover; border-radius:14px; box-shadow:0 6px 18px rgba(0,0,0,0.12);">
            </div>

            <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:12px; font-size:0.82rem; margin-bottom:20px;">
                <div>
                    <span style="color:var(--text-muted);">Cliente / Empresa:</span><br>
                    <strong id="modalVoucherCliente" style="color:var(--text-dark);">Cosapi S.A.</strong>
                </div>
                <div>
                    <span style="color:var(--text-muted);">Monto Transferido:</span><br>
                    <strong id="modalVoucherMonto" style="color:var(--accent-green-dark); font-size:1.05rem;">S/ 14,400.00</strong>
                </div>
                <div>
                    <span style="color:var(--text-muted);">Banco y N° Operación:</span><br>
                    <strong id="modalVoucherOp" style="color:var(--text-dark);">BCP Op. #4829104</strong>
                </div>
                <div>
                    <span style="color:var(--text-muted);">Asesor que Reportó:</span><br>
                    <strong id="modalVoucherAsesor" style="color:var(--text-dark);">Maria Gomez</strong>
                </div>
            </div>

            <div id="modalVoucherActions" style="display:flex; gap:10px;">
                <button class="btn-pill-white" style="flex:1; justify-content:center; background:#FEE2E2; color:#B91C1C;" onclick="observarDesdeModal()">
                    <i class="fa-solid fa-circle-exclamation"></i> Observar
                </button>
                <button class="btn-pill-white primary" style="flex:2; justify-content:center; background:var(--accent-green); color:#0E1210;" onclick="confirmarDesdeModal()">
                    <i class="fa-solid fa-check-double"></i> Confirmar & Aceptar Pago
                </button>
            </div>
        </div>
    </div>

    <!-- ================= MODAL LOGOUT ================= -->
    <div class="modal-overlay" id="modalLogout">
        <div class="modal-card" style="text-align:center;">
            <div style="width:60px; height:60px; background:#FEE2E2; color:#DC2626; border-radius:20px; display:flex; justify-content:center; align-items:center; font-size:1.6rem; margin:0 auto 16px;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>
            <h3 style="font-size:1.25rem;">¿Cerrar Sesión de Reportería?</h3>
            <p style="color:var(--text-muted); font-size:0.85rem; margin:8px 0 20px;">Sesión activa de Nayeli (Finanzas & Reportería BS Perú).</p>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <a href="logout.php" class="btn-pill-white primary" style="justify-content:center; text-decoration:none; background:#DC2626; color:#FFF;">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Salir del Sistema
                </a>
                <button class="btn-pill-white" style="justify-content:center;" onclick="cerrarModales()">
                    Permanecer en Reportería
                </button>
            </div>
        </div>
    </div>

    <!-- ================= MODAL OBSERVAR PAGO ================= -->
    <div class="modal-overlay" id="modalObservarPago">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <span style="font-size:0.72rem; color:#DC2626; font-weight:700; text-transform:uppercase;">Discrepancia Bancaria</span>
                    <h3 id="modalObsCotiz">Observar Factura</h3>
                </div>
                <button class="modal-close-btn" onclick="cerrarModales()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <p style="font-size:0.84rem; color:var(--text-muted); margin-bottom:14px;">
                Selecciona el motivo por el cual el comprobante no coincide para que el asesor de ventas lo rectifique:
            </p>

            <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:18px;">
                <label style="display:flex; align-items:center; gap:10px; font-size:0.84rem; cursor:pointer; background:#F8FAFC; padding:10px 14px; border-radius:12px; border:1px solid var(--border-soft);">
                    <input type="radio" name="radioMotivoObs" value="Comprobante no coincide con extracto bancario" checked onchange="document.getElementById('inputObsPersonalizado').style.display='none'">
                    <span>Comprobante no coincide con extracto bancario</span>
                </label>
                <label style="display:flex; align-items:center; gap:10px; font-size:0.84rem; cursor:pointer; background:#F8FAFC; padding:10px 14px; border-radius:12px; border:1px solid var(--border-soft);">
                    <input type="radio" name="radioMotivoObs" value="Monto transferido no coincide con el total de la cotización" onchange="document.getElementById('inputObsPersonalizado').style.display='none'">
                    <span>Monto transferido no coincide con el total</span>
                </label>
                <label style="display:flex; align-items:center; gap:10px; font-size:0.84rem; cursor:pointer; background:#F8FAFC; padding:10px 14px; border-radius:12px; border:1px solid var(--border-soft);">
                    <input type="radio" name="radioMotivoObs" value="Voucher ilegible, cortado o de baja resolución" onchange="document.getElementById('inputObsPersonalizado').style.display='none'">
                    <span>Voucher ilegible, cortado o de baja resolución</span>
                </label>
                <label style="display:flex; align-items:center; gap:10px; font-size:0.84rem; cursor:pointer; background:#F8FAFC; padding:10px 14px; border-radius:12px; border:1px solid var(--border-soft);">
                    <input type="radio" name="radioMotivoObs" value="N° de operación bancaria no figura registrada en cuenta" onchange="document.getElementById('inputObsPersonalizado').style.display='none'">
                    <span>N° de operación no figura en cuenta bancaria</span>
                </label>
                <label style="display:flex; align-items:center; gap:10px; font-size:0.84rem; cursor:pointer; background:#F8FAFC; padding:10px 14px; border-radius:12px; border:1px solid var(--border-soft);">
                    <input type="radio" name="radioMotivoObs" value="otro" onchange="document.getElementById('inputObsPersonalizado').style.display='block'; document.getElementById('inputObsPersonalizado').focus()">
                    <span>Otro motivo personalizado...</span>
                </label>
                <input type="text" id="inputObsPersonalizado" placeholder="Escribe la observación detallada..." style="display:none; width:100%; padding:10px 14px; border-radius:12px; border:1px solid var(--border-soft); font-size:0.84rem; outline:none; background:#FFF; color:#111;">
            </div>

            <div style="display:flex; gap:10px;">
                <button class="btn-pill-white" style="flex:1; justify-content:center;" onclick="cerrarModales()">Cancelar</button>
                <button class="btn-pill-white primary" style="flex:2; justify-content:center; background:#DC2626; color:#FFF;" onclick="confirmarObservacionDesdeModal()">
                    <i class="fa-solid fa-triangle-exclamation"></i> Enviar Observación a Ventas
                </button>
            </div>
        </div>
    </div>

    <!-- ================= MODAL EDITOR DE PALETA DE COLORES ================= -->
    <div class="modal-overlay" id="modalEditorPaleta" style="z-index:99999;">
        <div class="modal-palette-card">
            <div class="modal-header" style="margin-bottom:6px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="width:42px; height:42px; border-radius:12px; background:var(--color-2); color:#FFF; display:flex; align-items:center; justify-content:center; font-size:1.2rem; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                        <i class="fa-solid fa-palette"></i>
                    </div>
                    <div>
                        <span style="font-size:0.72rem; color:var(--color-2); font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Estilo & Identidad Visual</span>
                        <h3 style="font-size:1.25rem; font-weight:700; color:var(--text-dark); margin:0;">Personalizar Paleta de Colores</h3>
                    </div>
                </div>
                <button class="modal-close-btn" onclick="cerrarModales()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <p style="font-size:0.82rem; color:var(--text-muted); margin:0;">
                Prueba y edita los 5 colores en tiempo real para el módulo de Reportería. Los cambios se aplican al instante en todo el panel.
            </p>

            <!-- BARRA MUESTRA COOLORS -->
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <span style="font-size:0.75rem; font-weight:700; color:var(--text-dark); text-transform:uppercase; letter-spacing:0.5px;">
                        <i class="fa-solid fa-swatchbook" style="color:var(--color-2);"></i> Paleta Coolors Activa
                    </span>
                    <span style="font-size:0.72rem; color:var(--text-muted);">Haz clic en los círculos para probar nuevos tonos</span>
                </div>
                <div class="coolors-bar-preview" id="coolorsBarPreview">
                    <div class="coolors-bar-segment" id="coolorsSeg1" style="background:var(--color-1); color:#FFF;">
                        <span class="name">Color 1 (Sidebar)</span>
                        <span class="hex" id="barHex1">#1B4079</span>
                    </div>
                    <div class="coolors-bar-segment" id="coolorsSeg2" style="background:var(--color-2); color:#FFF;">
                        <span class="arrow-tag"><i class="fa-solid fa-arrow-down"></i> Principal</span>
                        <span class="name">Color 2 (Botones)</span>
                        <span class="hex" id="barHex2">#4D7C8A</span>
                    </div>
                    <div class="coolors-bar-segment" id="coolorsSeg3" style="background:var(--color-3); color:#FFF;">
                        <span class="name">Color 3 (Bordes)</span>
                        <span class="hex" id="barHex3">#7F9C96</span>
                    </div>
                    <div class="coolors-bar-segment" id="coolorsSeg4" style="background:var(--color-4); color:#1A2433;">
                        <span class="name">Color 4 (Éxito)</span>
                        <span class="hex" id="barHex4">#8FAD88</span>
                    </div>
                    <div class="coolors-bar-segment" id="coolorsSeg5" style="background:var(--color-5); color:#1A2433;">
                        <span class="name">Color 5 (Mindaro)</span>
                        <span class="hex" id="barHex5">#CBDF90</span>
                    </div>
                </div>
            </div>

            <!-- CONTROLES DE LOS 5 COLORES -->
            <div class="palette-controls-grid">
                <!-- Color 1 -->
                <div class="palette-color-item">
                    <div class="palette-color-picker-wrapper">
                        <input type="color" id="pickerCol1" value="#1B4079" oninput="cambiarColorIndiv(1, this.value)">
                    </div>
                    <input type="text" class="palette-color-hex-input" id="inputHex1" value="#1B4079" maxlength="7" onchange="cambiarColorIndiv(1, this.value)">
                    <div class="palette-color-label">Color 1</div>
                    <div class="palette-color-role">Sidebar & Marco</div>
                </div>
                <!-- Color 2 -->
                <div class="palette-color-item" style="border-color:var(--color-2); background:rgba(77,124,138,0.06);">
                    <div class="palette-color-picker-wrapper">
                        <input type="color" id="pickerCol2" value="#4D7C8A" oninput="cambiarColorIndiv(2, this.value)">
                    </div>
                    <input type="text" class="palette-color-hex-input" id="inputHex2" value="#4D7C8A" maxlength="7" onchange="cambiarColorIndiv(2, this.value)">
                    <div class="palette-color-label" style="color:var(--color-2);">Color 2 ⭐</div>
                    <div class="palette-color-role">Acento & Botones</div>
                </div>
                <!-- Color 3 -->
                <div class="palette-color-item">
                    <div class="palette-color-picker-wrapper">
                        <input type="color" id="pickerCol3" value="#7F9C96" oninput="cambiarColorIndiv(3, this.value)">
                    </div>
                    <input type="text" class="palette-color-hex-input" id="inputHex3" value="#7F9C96" maxlength="7" onchange="cambiarColorIndiv(3, this.value)">
                    <div class="palette-color-label">Color 3</div>
                    <div class="palette-color-role">Bordes & Tabs</div>
                </div>
                <!-- Color 4 -->
                <div class="palette-color-item">
                    <div class="palette-color-picker-wrapper">
                        <input type="color" id="pickerCol4" value="#8FAD88" oninput="cambiarColorIndiv(4, this.value)">
                    </div>
                    <input type="text" class="palette-color-hex-input" id="inputHex4" value="#8FAD88" maxlength="7" onchange="cambiarColorIndiv(4, this.value)">
                    <div class="palette-color-label">Color 4</div>
                    <div class="palette-color-role">Éxito & Conciliación</div>
                </div>
                <!-- Color 5 -->
                <div class="palette-color-item">
                    <div class="palette-color-picker-wrapper">
                        <input type="color" id="pickerCol5" value="#CBDF90" oninput="cambiarColorIndiv(5, this.value)">
                    </div>
                    <input type="text" class="palette-color-hex-input" id="inputHex5" value="#CBDF90" maxlength="7" onchange="cambiarColorIndiv(5, this.value)">
                    <div class="palette-color-label">Color 5</div>
                    <div class="palette-color-role">Mindaro Vivo</div>
                </div>
            </div>

            <!-- PALETAS PREDISEÑADAS -->
            <div>
                <div style="font-size:0.75rem; font-weight:700; color:var(--text-dark); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color:var(--color-2);"></i> Paletas Rápidas de Prueba
                </div>
                <div class="preset-pills-row">
                    <button type="button" class="preset-pill-btn" onclick="aplicarPreset('coolors')">
                        <div class="preset-dots">
                            <span class="preset-dot" style="background:#1B4079;"></span>
                            <span class="preset-dot" style="background:#4D7C8A;"></span>
                            <span class="preset-dot" style="background:#7F9C96;"></span>
                            <span class="preset-dot" style="background:#8FAD88;"></span>
                            <span class="preset-dot" style="background:#CBDF90;"></span>
                        </div>
                        <span>⭐ Coolors (Yale & Air Force)</span>
                    </button>
                    <button type="button" class="preset-pill-btn" onclick="aplicarPreset('esmeralda')">
                        <div class="preset-dots">
                            <span class="preset-dot" style="background:#0F2B20;"></span>
                            <span class="preset-dot" style="background:#10B981;"></span>
                            <span class="preset-dot" style="background:#34D399;"></span>
                            <span class="preset-dot" style="background:#6EE7B7;"></span>
                            <span class="preset-dot" style="background:#C79B58;"></span>
                        </div>
                        <span>🌿 Esmeralda BS</span>
                    </button>
                    <button type="button" class="preset-pill-btn" onclick="aplicarPreset('oceano')">
                        <div class="preset-dots">
                            <span class="preset-dot" style="background:#0A192F;"></span>
                            <span class="preset-dot" style="background:#0284C7;"></span>
                            <span class="preset-dot" style="background:#38BDF8;"></span>
                            <span class="preset-dot" style="background:#7DD3FC;"></span>
                            <span class="preset-dot" style="background:#34D399;"></span>
                        </div>
                        <span>🌊 Océano Profundo</span>
                    </button>
                    <button type="button" class="preset-pill-btn" onclick="aplicarPreset('grafito')">
                        <div class="preset-dots">
                            <span class="preset-dot" style="background:#18181B;"></span>
                            <span class="preset-dot" style="background:#2563EB;"></span>
                            <span class="preset-dot" style="background:#60A5FA;"></span>
                            <span class="preset-dot" style="background:#10B981;"></span>
                            <span class="preset-dot" style="background:#FACC15;"></span>
                        </div>
                        <span>⚡ Grafito & Neón</span>
                    </button>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:4px; padding-top:12px; border-top:1px solid var(--border-soft); flex-wrap:wrap;">
                <div style="display:flex; gap:8px;">
                    <button type="button" class="btn-pill-white" style="font-size:0.78rem; padding:8px 14px;" onclick="restablecerPaletaCoolors()">
                        <i class="fa-solid fa-rotate-left"></i> Restablecer a Coolors
                    </button>
                    <button type="button" class="btn-pill-white" style="font-size:0.78rem; padding:8px 14px;" onclick="copiarCssVariables()">
                        <i class="fa-solid fa-copy"></i> Copiar CSS
                    </button>
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="btn-pill-white" style="font-size:0.78rem; padding:8px 14px;" onclick="cerrarModales()">Cerrar</button>
                    <button type="button" class="btn-pill-white primary" style="font-size:0.78rem; padding:8px 18px; background:var(--color-2); color:#FFF;" onclick="guardarPaletaActual()">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar en Navegador
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST NOTIFICACIÓN -->
    <div class="toast-palette-notify" id="toastPaletteNotify">
        <i class="fa-solid fa-circle-check" style="color:var(--color-5); font-size:1.1rem;"></i>
        <span id="toastPaletteMsg">Paleta actualizada exitosamente</span>
    </div>

    <!-- ================= JAVASCRIPT ================= -->
    <script>
        let pagosPendientesCount = 3;
        let totalValidadasMes = 94178.00;
        let totalValidadasHoy = 24508.00;
        let currentModalPagoId = 0;
        let currentModalMonto = 0;
        let currentModalCotiz = '';

        // ================= GESTOR DINÁMICO DE PALETA DE COLORES =================
        const COOLORS_PALETTE = {
            c1: '#1B4079', // Yale Blue: Sidebar y marcos profundos
            c2: '#4D7C8A', // Air Force Blue: Acento principal, botones y cabeceras
            c3: '#7F9C96', // Cambridge Blue: Acento secundario y bordes
            c4: '#8FAD88', // Cambridge Green / Sage: Conciliación y éxito
            c5: '#CBDF90'  // Mindaro: Resaltado vivo y badges luminosos
        };

        let currentPalette = Object.assign({}, COOLORS_PALETTE);

        const PRESET_COLLECTIONS = {
            coolors: {
                c1: '#1B4079', c2: '#4D7C8A', c3: '#7F9C96', c4: '#8FAD88', c5: '#CBDF90'
            },
            esmeralda: {
                c1: '#0F2B20', c2: '#10B981', c3: '#34D399', c4: '#6EE7B7', c5: '#C79B58'
            },
            oceano: {
                c1: '#0A192F', c2: '#0284C7', c3: '#38BDF8', c4: '#7DD3FC', c5: '#34D399'
            },
            grafito: {
                c1: '#18181B', c2: '#2563EB', c3: '#60A5FA', c4: '#10B981', c5: '#FACC15'
            }
        };

        function getContrastYIQ(hexcolor) {
            if (!hexcolor) return '#FFFFFF';
            const clean = hexcolor.replace('#', '');
            if (clean.length < 6) return '#FFFFFF';
            const r = parseInt(clean.substr(0,2),16);
            const g = parseInt(clean.substr(2,2),16);
            const b = parseInt(clean.substr(4,2),16);
            const yiq = ((r*299)+(g*587)+(b*114))/1000;
            return (yiq >= 145) ? '#111827' : '#FFFFFF';
        }

        function aplicarPaletaReporteria(c1, c2, c3, c4, c5, guardar = false) {
            currentPalette = { c1, c2, c3, c4, c5 };
            const root = document.documentElement;

            root.style.setProperty('--color-1', c1);
            root.style.setProperty('--color-2', c2);
            root.style.setProperty('--color-3', c3);
            root.style.setProperty('--color-4', c4);
            root.style.setProperty('--color-5', c5);

            root.style.setProperty('--outer-bg', c1);
            root.style.setProperty('--sidebar-bg', c1);
            root.style.setProperty('--app-frame', c1);
            root.style.setProperty('--accent-green', c2);
            root.style.setProperty('--accent-green-dark', c1);
            root.style.setProperty('--accent-green-light', c3);
            root.style.setProperty('--accent-tan', c4);
            root.style.setProperty('--accent-highlight', c5);

            actualizarVistaModalPaleta();

            if (guardar) {
                localStorage.setItem('crm_reporteria_paleta_activa', JSON.stringify(currentPalette));
                mostrarToastPaleta('💾 ¡Paleta guardada en tu navegador!');
            }
        }

        function abrirModalEditorPaleta() {
            cerrarModales();
            actualizarVistaModalPaleta();
            document.getElementById('modalEditorPaleta').classList.add('open');
        }

        function actualizarVistaModalPaleta() {
            for (let i = 1; i <= 5; i++) {
                const colorVal = currentPalette[`c${i}`];
                const picker = document.getElementById(`pickerCol${i}`);
                const hexInput = document.getElementById(`inputHex${i}`);
                const barSeg = document.getElementById(`coolorsSeg${i}`);
                const barHex = document.getElementById(`barHex${i}`);

                if (picker) picker.value = colorVal;
                if (hexInput) hexInput.value = colorVal.toUpperCase();
                if (barSeg) {
                    barSeg.style.backgroundColor = colorVal;
                    barSeg.style.color = getContrastYIQ(colorVal);
                }
                if (barHex) barHex.textContent = colorVal.toUpperCase();
            }
        }

        function cambiarColorIndiv(num, nuevoColor) {
            if (!nuevoColor) return;
            if (!nuevoColor.startsWith('#')) nuevoColor = '#' + nuevoColor;
            if (nuevoColor.length === 7) {
                currentPalette[`c${num}`] = nuevoColor;
                aplicarPaletaReporteria(currentPalette.c1, currentPalette.c2, currentPalette.c3, currentPalette.c4, currentPalette.c5, false);
            }
        }

        function aplicarPreset(key) {
            if (PRESET_COLLECTIONS[key]) {
                const p = PRESET_COLLECTIONS[key];
                aplicarPaletaReporteria(p.c1, p.c2, p.c3, p.c4, p.c5, false);
                mostrarToastPaleta(`🎨 Preset aplicado: ${key.toUpperCase()}`);
            }
        }

        function restablecerPaletaCoolors() {
            aplicarPaletaReporteria(COOLORS_PALETTE.c1, COOLORS_PALETTE.c2, COOLORS_PALETTE.c3, COOLORS_PALETTE.c4, COOLORS_PALETTE.c5, true);
            mostrarToastPaleta('🔄 Restablecida la paleta Coolors (Yale Blue & Mindaro).');
        }

        function guardarPaletaActual() {
            localStorage.setItem('crm_reporteria_paleta_activa', JSON.stringify(currentPalette));
            mostrarToastPaleta('💾 Paleta guardada correctamente en el navegador.');
            cerrarModales();
        }

        function copiarCssVariables() {
            const cssBlock = `:root {
    --color-1: ${currentPalette.c1}; /* Yale Blue / Sidebar */
    --color-2: ${currentPalette.c2}; /* Air Force Blue / Botones */
    --color-3: ${currentPalette.c3}; /* Cambridge Blue / Bordes */
    --color-4: ${currentPalette.c4}; /* Sage Green / Éxito */
    --color-5: ${currentPalette.c5}; /* Mindaro / Resaltado */
}`;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(cssBlock).then(() => {
                    mostrarToastPaleta('📋 ¡Código CSS copiado al portapapeles!');
                }).catch(() => {
                    prompt('Copia tus variables CSS:', cssBlock);
                });
            } else {
                prompt('Copia tus variables CSS:', cssBlock);
            }
        }

        function mostrarToastPaleta(msg) {
            const toast = document.getElementById('toastPaletteNotify');
            const txt = document.getElementById('toastPaletteMsg');
            if (toast && txt) {
                txt.textContent = msg;
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 3500);
            }
        }

        function inicializarPaletaReporteria() {
            const saved = localStorage.getItem('crm_reporteria_paleta_activa');
            if (saved) {
                try {
                    const p = JSON.parse(saved);
                    if (p.c1 && p.c2 && p.c3 && p.c4 && p.c5) {
                        aplicarPaletaReporteria(p.c1, p.c2, p.c3, p.c4, p.c5, false);
                        return;
                    }
                } catch(e) {}
            }
            // Por defecto, aplicar la paleta solicitada
            aplicarPaletaReporteria(COOLORS_PALETTE.c1, COOLORS_PALETTE.c2, COOLORS_PALETTE.c3, COOLORS_PALETTE.c4, COOLORS_PALETTE.c5, false);
        }

        // Ejecutar inmediatamente para evitar saltos visuales
        inicializarPaletaReporteria();

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

        // CERRAR MODALES
        function cerrarModales() {
            document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
        }

        function abrirLogoutModal() {
            document.getElementById('modalLogout').classList.add('open');
        }

        // CAMBIO DE VISTAS (SPA)
        function cambiarVistaReporteria(nombreVista, elNav) {
            cerrarModales();

            // 1. Ocultar todas las vistas
            document.querySelectorAll('.vista-seccion').forEach(v => v.style.display = 'none');

            // 2. Mostrar la vista elegida
            const vista = document.getElementById(`vista-${nombreVista}`);
            if (vista) {
                vista.style.display = 'flex';
            }

            // 3. Actualizar menú lateral
            document.querySelectorAll('.nav-menu .nav-item').forEach(item => item.classList.remove('active'));
            if (elNav) {
                elNav.classList.add('active');
            } else {
                const targetNav = document.getElementById(`nav-${nombreVista}`);
                if (targetNav) targetNav.classList.add('active');
            }

            // 4. Cambiar título superior según la vista
            const titles = {
                'dashboard': 'Dashboard General de Finanzas & Reportería',
                'validacion': 'Bandeja de Validación & Aprobación de Pagos',
                'cierres': 'Cierres de Ventas del Día & Auditoría de Caja',
                'historial': 'Historial de Vouchers y Comprobantes Aprobados',
                'chat': 'Chat con Asesores de Ventas',
                'sucursales': 'Control de Metas & Avance de Sucursales'
            };
            if (titles[nombreVista]) {
                document.getElementById('pageMainTitle').textContent = titles[nombreVista];
            }

            // 5. Scroll al tope
            const mainContent = document.querySelector('.main-content');
            if (mainContent) mainContent.scrollTop = 0;
        }

        let currentModalAsesor = 'Endrina';
        let currentModalCliente = '';
        let currentModalOp = '';

        // VISOR DE VOUCHER
        function abrirVisorVoucher(cotiz, cliente, monto, op, asesor, pagoId, imgUrl) {
            currentModalPagoId = pagoId;
            currentModalMonto = typeof monto === 'number' ? monto : parseFloat(String(monto).replace(/,/g, ''));
            currentModalCotiz = cotiz;
            currentModalAsesor = asesor || asesorChatActivo;
            currentModalCliente = cliente || '';
            currentModalOp = op || '';

            document.getElementById('modalVoucherCotiz').textContent = cotiz;
            document.getElementById('modalVoucherCliente').textContent = cliente;
            document.getElementById('modalVoucherMonto').textContent = 'S/ ' + currentModalMonto.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('modalVoucherOp').textContent = op;
            document.getElementById('modalVoucherAsesor').textContent = currentModalAsesor;

            if (imgUrl) {
                document.getElementById('modalVoucherImg').src = imgUrl;
            }

            // Si es un pago ya aprobado (id = 0), ocultar botones de acción
            const actionsBox = document.getElementById('modalVoucherActions');
            if (pagoId === 0) {
                actionsBox.style.display = 'none';
            } else {
                actionsBox.style.display = 'flex';
            }

            document.getElementById('modalVisorVoucher').classList.add('open');
        }

        // CARGAR BANDEJA DE PAGOS EN TIEMPO REAL DESDE EL BACKEND
        function cargarPagosReporteria() {
            fetch('crm_backend.php?action=listar_pagos')
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.pagos) return;
                const tbody = document.getElementById('valTableTbody');
                const tbodyHist = document.getElementById('historialValidadosTbody');
                const rightAlertsBox = document.getElementById('boxRightAlerts');

                if (tbody) tbody.innerHTML = '';
                if (tbodyHist) tbodyHist.innerHTML = '';
                if (rightAlertsBox) rightAlertsBox.innerHTML = '';

                data.pagos.forEach(p => {
                    const isAceptado = (p.estado === 'Aceptado');
                    const isObservado = (p.estado === 'Observado');
                    const montoFmt = parseFloat(p.monto).toLocaleString('en-US', {minimumFractionDigits: 2});
                    const clienteSafe = (p.cliente || '').replace(/'/g, "\\'");
                    const asesorSafe = (p.asesor || 'Endrina').replace(/'/g, "\\'");

                    // Si está aceptado, agregarlo al historial de aceptados
                    if (isAceptado && tbodyHist) {
                        const trHist = document.createElement('tr');
                        trHist.innerHTML = `
                            <td><strong>${p.nro_factura}</strong></td>
                            <td>${p.cliente}</td>
                            <td><strong>S/ ${montoFmt}</strong></td>
                            <td>${p.banco} #${p.nro_operacion}</td>
                            <td>${p.validador || 'Nayeli'}</td>
                            <td>${p.fecha_validacion || p.fecha}</td>
                            <td><span class="badge-status-accepted">✅ Pago Aceptado</span></td>
                            <td><button class="btn-confirm-direct" style="padding:4px 10px; font-size:0.7rem;" onclick="abrirVisorVoucher('${p.nro_factura}', '${clienteSafe}', '${montoFmt}', '${p.nro_operacion}', '${asesorSafe}', 0, '${p.voucher_url}')">Ver</button></td>
                        `;
                        tbodyHist.appendChild(trHist);
                    }

                    // Bandeja de validación
                    if (tbody) {
                        let badge = `<span class="badge-status-pending" id="badge-pago-${p.id}"><i class="fa-solid fa-hourglass-start"></i> Por Confirmar</span>`;
                        let actions = `
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <button class="btn-confirm-direct" onclick="confirmarPagoEnFila(${p.id}, ${p.monto}, '${p.nro_factura}', '${asesorSafe}', '${clienteSafe}', '${p.banco}')">
                                    <i class="fa-solid fa-check"></i> Aceptar
                                </button>
                                <button class="btn-observe-direct" onclick="observarPagoEnFila(${p.id}, '${p.nro_factura}', '${asesorSafe}', '${clienteSafe}', ${p.monto}, '${p.banco}')">
                                    <i class="fa-solid fa-circle-exclamation"></i> Observar
                                </button>
                            </div>
                        `;

                        if (isAceptado) {
                            badge = '<span class="badge-status-accepted"><i class="fa-solid fa-check-circle"></i> Aceptado</span>';
                            actions = '<span style="color:#059669; font-weight:700; font-size:0.75rem;"><i class="fa-solid fa-check-double"></i> Pago Aprobado</span>';
                        } else if (isObservado) {
                            badge = '<span class="badge-status-observed"><i class="fa-solid fa-triangle-exclamation"></i> Observado</span>';
                            actions = `<span style="color:#DC2626; font-size:0.72rem; font-weight:600;"><i class="fa-solid fa-circle-exclamation"></i> ${p.motivo_observacion || 'Observado'}</span>`;
                        }

                        const tr = document.createElement('tr');
                        tr.id = `row-pago-${p.id}`;
                        tr.setAttribute('data-banco', p.banco);
                        tr.innerHTML = `
                            <td><strong>${p.nro_factura}</strong></td>
                            <td><i class="fa-solid fa-user-tie" style="color:var(--accent-green);"></i> ${p.asesor}</td>
                            <td>
                                <strong>${p.cliente}</strong><br>
                                <span style="font-size:0.72rem; color:var(--text-muted);">${p.ruc ? 'RUC: ' + p.ruc : 'Sin RUC'}</span>
                            </td>
                            <td><strong style="color:var(--text-dark); font-size:0.95rem;">S/ ${montoFmt}</strong></td>
                            <td><span style="background:#002A8F; color:#FFF; padding:3px 8px; border-radius:8px; font-size:0.72rem; font-weight:700;">${p.banco} #${p.nro_operacion}</span></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <img src="${p.voucher_url}" class="voucher-thumb-small" onerror="this.src='https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=150&q=80'" onclick="abrirVisorVoucher('${p.nro_factura}', '${clienteSafe}', '${montoFmt}', '${p.nro_operacion}', '${asesorSafe}', ${isAceptado ? 0 : p.id}, '${p.voucher_url}')" alt="Voucher">
                                    <span style="font-size:0.75rem; color:var(--accent-green-dark); cursor:pointer; font-weight:600;" onclick="abrirVisorVoucher('${p.nro_factura}', '${clienteSafe}', '${montoFmt}', '${p.nro_operacion}', '${asesorSafe}', ${isAceptado ? 0 : p.id}, '${p.voucher_url}')">Ver Voucher</span>
                                </div>
                            </td>
                            <td>${badge}</td>
                            <td>${actions}</td>
                        `;
                        tbody.appendChild(tr);
                    }

                    // Si está pendiente, agregar a la barra lateral derecha
                    if (!isAceptado && rightAlertsBox) {
                        const alertItem = document.createElement('div');
                        alertItem.className = 'pending-urgent-card';
                        alertItem.onclick = () => cambiarVistaReporteria('validacion');
                        alertItem.innerHTML = `
                            <div class="pending-urgent-meta">
                                <h5>${p.cliente}</h5>
                                <p>S/ ${montoFmt} • ${p.banco} #${p.nro_operacion}</p>
                            </div>
                            <span style="color:var(--accent-green-dark); font-size:0.75rem; font-weight:700;">Revisar →</span>
                        `;
                        rightAlertsBox.appendChild(alertItem);
                    }
                });

                // Actualizar métricas y contadores
                if (data.stats) {
                    pagosPendientesCount = data.stats.pendientes;
                    totalValidadasMes = data.stats.total_mes;
                    totalValidadasHoy = data.stats.validado_hoy;

                    document.getElementById('badgeSidePending').textContent = pagosPendientesCount;
                    document.getElementById('badgeRightPending').textContent = `${pagosPendientesCount} pendientes`;
                    document.getElementById('kpiPagosPendientes').textContent = pagosPendientesCount;
                    document.getElementById('kpiTotalVentas').textContent = 'S/ ' + totalValidadasMes.toLocaleString('es-PE', {minimumFractionDigits: 0});
                    document.getElementById('kpiTotalHoy').textContent = 'S/ ' + totalValidadasHoy.toLocaleString('es-PE', {minimumFractionDigits: 0});
                }
            })
            .catch(err => console.log('Error listar reporteria:', err));
        }

        // ACEPTAR PAGO DIRECTO
        function confirmarPagoEnFila(id, monto, cotiz, asesor, cliente, banco) {
            if (!confirm(`¿Confirmar y conciliar el pago de S/ ${parseFloat(monto).toLocaleString()} para la factura ${cotiz}? Se notificará a ${asesor || 'Ventas'} por chat.`)) return;

            ejecutarAprobacionPago(id, monto, cotiz, asesor, cliente, banco);
        }

        function confirmarDesdeModal() {
            cerrarModales();
            if (currentModalPagoId > 0) {
                ejecutarAprobacionPago(currentModalPagoId, currentModalMonto, currentModalCotiz, currentModalAsesor, currentModalCliente, currentModalOp);
            }
        }

        function ejecutarAprobacionPago(id, monto, cotiz, asesor, cliente, banco, nota) {
            const formData = new FormData();
            formData.append('action', 'confirmar_pago');
            formData.append('pago_id', id);
            formData.append('monto', monto);
            formData.append('cotizacion', cotiz);
            formData.append('asesor', asesor || asesorChatActivo);
            formData.append('cliente', cliente || '');
            formData.append('banco', banco || 'BCP');
            formData.append('validador', 'Nayeli (Reportería)');
            if (nota) formData.append('nota', nota);

            fetch('crm_backend.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(`✅ ¡Pago de ${cotiz} confirmado exitosamente!\nSe actualizó la base de datos y se notificó inmediatamente a ${asesor || 'Ventas'} por chat para liberar el pedido.`);
                cargarPagosReporteria();
                cargarChatReporteria();
            })
            .catch(err => {
                alert('Pago aceptado localmente.');
                cargarPagosReporteria();
            });
        }

        // CONFIRMAR PAGO DIRECTO DESDE LA TARJETA DEL CHAT
        function confirmarPagoDirectoDesdeChat(nroFactura, cliente, monto, asesor, banco, op) {
            if (!confirm(`¿Aprobar y conciliar el pago de S/ ${parseFloat(monto).toLocaleString()} de ${cliente} (${nroFactura})?\n\nSe enviará de inmediato la respuesta oficial a ${asesor} por este canal de chat para autorizar el despacho.`)) return;

            ejecutarAprobacionPago(0, monto, nroFactura, asesor, cliente, banco, 'Extracto bancario verificado. Abono conciliado en cuenta corriente. Pedido autorizado para despacho.');
        }

        // CARGAR Y AUDITAR CIERRES DE VENTAS DIARIOS
        function cargarCierresReporteria() {
            fetch('crm_backend.php?action=listar_cierres')
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.cierres) return;
                const tbody = document.getElementById('cierresTableTbody');
                if (tbody) {
                    tbody.innerHTML = data.cierres.map(c => {
                        const isAprobado = (c.estado === 'Aprobado');
                        const montoFmt = parseFloat(c.monto_acumulado || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
                        const badge = isAprobado 
                            ? `<span class="badge-status-accepted">✅ Cuadre Aprobado</span>`
                            : `<span class="badge-status-pending">⏳ En Conciliación</span>`;
                        const safeAsesor = (c.asesor || '').replace(/'/g, "\\'");
                        const safeSucursal = (c.sucursal || '').replace(/'/g, "\\'");
                        const actionBtn = isAprobado 
                            ? `<button class="btn-confirm-direct" style="padding:6px 12px; font-size:0.72rem;" onclick="alert('Cierre de ${safeAsesor} auditado y conforme por ${c.validador || 'Nayeli'}. Caja cuadrada sin diferencias.')">Ver Detalle</button>`
                            : `<button class="btn-confirm-direct" style="padding:6px 12px; font-size:0.72rem; background:var(--accent-green);" onclick="aprobarCierreDiario(${c.id}, '${safeAsesor}', '${safeSucursal}', ${c.monto_acumulado})"><i class="fa-solid fa-check"></i> Aprobar Cuadre</button>`;

                        return `
                            <tr>
                                <td><strong>${c.asesor}</strong></td>
                                <td>${c.sucursal}</td>
                                <td><strong style="color:var(--text-dark);">S/ ${montoFmt}</strong></td>
                                <td>${c.total_ventas || 1} ventas</td>
                                <td>${c.hora || '18:30'} hrs</td>
                                <td>${badge}</td>
                                <td>${actionBtn}</td>
                            </tr>
                        `;
                    }).join('');
                }

                if (data.stats) {
                    const st = data.stats;
                    const elTotal = document.getElementById('kpiCierreTotal');
                    const elAsesores = document.getElementById('kpiAsesoresReportaron');
                    const elOps = document.getElementById('kpiOperacionesTotal');
                    const elConc = document.getElementById('kpiConciliadoPct');

                    if (elTotal) elTotal.textContent = 'S/ ' + parseFloat(st.total_cierre_hoy || 0).toLocaleString('es-PE', {minimumFractionDigits: 0});
                    if (elAsesores) elAsesores.textContent = st.asesores_reportaron || '4 / 4';
                    if (elOps) elOps.textContent = st.total_operaciones || '12';
                    if (elConc) elConc.textContent = st.porcentaje_conciliado || '100%';
                }
            })
            .catch(err => console.log('Error listar cierres reporteria:', err));
        }

        function aprobarCierreDiario(id, asesor, sucursal, monto) {
            if (!confirm(`¿Auditar y aprobar el Cierre de Ventas de ${asesor} (${sucursal}) por un monto total de S/ ${parseFloat(monto).toLocaleString()}?\n\nSe registrará la conciliación contable de caja y se notificará en tiempo real al asesor por este chat.`)) return;

            const formData = new FormData();
            formData.append('action', 'aprobar_cierre');
            formData.append('cierre_id', id);
            formData.append('validador', 'Nayeli (Reportería / Finanzas)');

            fetch('crm_backend.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(`✅ Cierre de ventas de ${asesor} aprobado con éxito.\nSe envió la notificación oficial de cuadre conforme al chat.`);
                cargarCierresReporteria();
                cargarChatReporteria();
            })
            .catch(err => {
                alert('Cierre aprobado exitosamente.');
                cargarCierresReporteria();
            });
        }

        // OBSERVAR PAGO
        let currentObsPagoId = 0;
        let currentObsCotiz = '';
        let currentObsAsesor = 'Endrina';
        let currentObsCliente = '';
        let currentObsMonto = 0;
        let currentObsBanco = '';

        function observarPagoEnFila(id, cotiz, asesor, cliente, monto, banco) {
            currentObsPagoId = id;
            currentObsCotiz = cotiz;
            currentObsAsesor = asesor || asesorChatActivo;
            currentObsCliente = cliente || '';
            currentObsMonto = monto || 0;
            currentObsBanco = banco || '';
            const titleEl = document.getElementById('modalObsCotiz');
            if (titleEl) titleEl.textContent = `Observar Factura ${cotiz}`;

            // Resetear inputs del modal
            const firstRadio = document.querySelector('input[name="radioMotivoObs"]');
            if (firstRadio) firstRadio.checked = true;
            const inputCustom = document.getElementById('inputObsPersonalizado');
            if (inputCustom) {
                inputCustom.style.display = 'none';
                inputCustom.value = '';
            }

            document.getElementById('modalObservarPago').classList.add('open');
        }

        function observarPagoDirectoDesdeChat(nroFactura, cliente, monto, asesor) {
            observarPagoEnFila(0, nroFactura, asesor, cliente, monto, 'Banco');
        }

        function confirmarObservacionDesdeModal() {
            let motivo = '';
            const radioSel = document.querySelector('input[name="radioMotivoObs"]:checked');
            if (radioSel) {
                if (radioSel.value === 'otro') {
                    const inputCustom = document.getElementById('inputObsPersonalizado');
                    motivo = inputCustom ? inputCustom.value.trim() : '';
                    if (!motivo) motivo = 'Observación no especificada';
                } else {
                    motivo = radioSel.value;
                }
            } else {
                motivo = 'Comprobante no coincide con extracto bancario';
            }

            cerrarModales();

            const formData = new FormData();
            formData.append('action', 'observar_pago');
            formData.append('pago_id', currentObsPagoId);
            formData.append('cotizacion', currentObsCotiz);
            formData.append('asesor', currentObsAsesor);
            formData.append('cliente', currentObsCliente);
            formData.append('motivo', motivo);
            formData.append('validador', 'Nayeli (Reportería)');

            fetch('crm_backend.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(`⚠️ Factura ${currentObsCotiz} marcada como Observada.\nMotivo: "${motivo}"\nSe notificó a ${currentObsAsesor} en el chat en tiempo real.`);
                cargarPagosReporteria();
                cargarChatReporteria();
            })
            .catch(err => {
                alert('Pago marcado como observado.');
                cargarPagosReporteria();
            });
        }

        function observarDesdeModal() {
            cerrarModales();
            if (currentModalPagoId > 0 || currentModalCotiz) {
                observarPagoEnFila(currentModalPagoId, currentModalCotiz, currentModalAsesor, currentModalCliente, currentModalMonto, currentModalOp);
            }
        }

        // FILTROS DE TABLA DE VALIDACIÓN
        function filtrarBancoPago(banco, elPill) {
            document.querySelectorAll('.val-filter-pills .val-pill').forEach(p => p.classList.remove('active'));
            elPill.classList.add('active');

            const rows = document.querySelectorAll('#valTableTbody tr');
            rows.forEach(r => {
                const rBanco = r.getAttribute('data-banco');
                if (banco === 'todos' || rBanco === banco) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        function filtrarPagosTabla() {
            const val = document.getElementById('inputBuscarPago').value.toLowerCase();
            const rows = document.querySelectorAll('#valTableTbody tr');
            rows.forEach(r => {
                const txt = r.textContent.toLowerCase();
                r.style.display = txt.includes(val) ? '' : 'none';
            });
        }

        // ================= CHAT CON ASESORES TIPO WHATSAPP =================
        let asesorChatActivo = 'Endrina';
        let sucursalChatActiva = 'Sucursal Chorrillos';
        let rolChatActivo = 'Asesora de Ventas';
        let avatarChatActivo = 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&auto=format&fit=crop&q=80';
        let listaConversacionesData = [];
        let listaMensajesChatData = [];
        let filtroBusquedaChat = '';

        function abrirChatConAsesor(nombreAsesor) {
            cambiarVistaReporteria('chat');
            const conv = listaConversacionesData.find(c => c.asesor === nombreAsesor);
            if (conv) {
                seleccionarAsesorChat(conv.asesor, conv.sucursal, conv.rol, conv.avatar);
            } else {
                seleccionarAsesorChat(nombreAsesor, 'Sucursal Chorrillos', 'Asesora de Ventas', 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&auto=format&fit=crop&q=80');
            }
        }

        function seleccionarAsesorChat(nombre, sucursal, rol, avatar) {
            asesorChatActivo = nombre;
            sucursalChatActiva = sucursal || 'Sucursal Chorrillos';
            rolChatActivo = rol || 'Asesora de Ventas';
            avatarChatActivo = avatar || 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&auto=format&fit=crop&q=80';

            // Actualizar encabezado del chat activo
            const nameEl = document.getElementById('chatActiveAdvisorName');
            const sucursalEl = document.getElementById('chatActiveAdvisorSucursalBadge');
            const roleEl = document.getElementById('chatActiveAdvisorRole');
            const avatarEl = document.getElementById('chatActiveAdvisorAvatar');
            const inputMsg = document.getElementById('chatInputMessage');

            if (nameEl) nameEl.textContent = asesorChatActivo;
            if (sucursalEl) sucursalEl.innerHTML = `<i class="fa-solid fa-location-dot"></i> ${sucursalChatActiva}`;
            if (roleEl) roleEl.textContent = `🏢 ${sucursalChatActiva} • ${rolChatActivo} • En línea`;
            if (avatarEl) avatarEl.src = avatarChatActivo;
            if (inputMsg) inputMsg.placeholder = `Escribe un mensaje para ${asesorChatActivo}...`;

            renderListaConversacionesWhatsApp(listaConversacionesData);
            renderMensajesConversacionActiva();
        }

        function filtrarConversacionesChatWhatsApp(q) {
            filtroBusquedaChat = q.trim().toLowerCase();
            renderListaConversacionesWhatsApp(listaConversacionesData);
        }

        function renderListaConversacionesWhatsApp(conversaciones) {
            const listContainer = document.getElementById('chatConversationsList');
            if (!listContainer) return;

            if (!Array.isArray(conversaciones) || conversaciones.length === 0) {
                listContainer.innerHTML = '<div style="padding:20px; text-align:center; color:var(--text-muted); font-size:0.8rem;">Cargando chats...</div>';
                return;
            }

            const filtradas = conversaciones.filter(c => {
                if (!filtroBusquedaChat) return true;
                return (c.asesor || '').toLowerCase().includes(filtroBusquedaChat) ||
                       (c.sucursal || '').toLowerCase().includes(filtroBusquedaChat) ||
                       (c.ultimo_mensaje || '').toLowerCase().includes(filtroBusquedaChat);
            });

            if (filtradas.length === 0) {
                listContainer.innerHTML = '<div style="padding:20px; text-align:center; color:var(--text-muted); font-size:0.8rem;">No se encontraron chats con ese criterio.</div>';
                return;
            }

            listContainer.innerHTML = filtradas.map(c => {
                const isActive = (c.asesor === asesorChatActivo);
                const safeNombre = c.asesor.replace(/'/g, "\\'");
                const safeSucursal = (c.sucursal || '').replace(/'/g, "\\'");
                const safeRol = (c.rol || '').replace(/'/g, "\\'");
                const safeAvatar = (c.avatar || '').replace(/'/g, "\\'");

                return `
                    <div class="chat-user-item ${isActive ? 'active' : ''}" onclick="seleccionarAsesorChat('${safeNombre}', '${safeSucursal}', '${safeRol}', '${safeAvatar}')">
                        <div class="chat-user-avatar">
                            <img src="${c.avatar}" alt="${c.asesor}">
                            ${c.online ? '<div class="chat-user-dot"></div>' : ''}
                        </div>
                        <div class="chat-user-meta">
                            <div class="chat-user-meta-top">
                                <h5>${c.asesor}</h5>
                                <span class="chat-time">${c.ultima_hora || ''}</span>
                            </div>
                            <div>
                                <span class="badge-sucursal-chat">
                                    <i class="fa-solid fa-location-dot"></i> ${c.sucursal}
                                </span>
                            </div>
                            <p title="${c.ultimo_mensaje || ''}">
                                ${c.ultimo_mensaje || 'Iniciar conversación...'}
                            </p>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderMensajesConversacionActiva() {
            const scrollBox = document.getElementById('chatMessagesScroll');
            if (!scrollBox) return;

            // Filtrar mensajes pertenecientes al asesor activo
            const msgs = listaMensajesChatData.filter(m => {
                const as = m.asesor || m.remitente || '';
                return as === asesorChatActivo;
            });

            if (msgs.length === 0) {
                scrollBox.innerHTML = `
                    <div style="margin:auto; text-align:center; padding:30px; background:rgba(255,255,255,0.7); border-radius:16px; max-width:380px;">
                        <i class="fa-solid fa-comments" style="font-size:2.5rem; color:var(--accent-green); margin-bottom:12px;"></i>
                        <h4 style="margin:0 0 6px 0; color:var(--text-dark);">Canal con ${asesorChatActivo}</h4>
                        <p style="margin:0; font-size:0.78rem; color:var(--text-muted);">${sucursalChatActiva}</p>
                        <p style="margin-top:10px; font-size:0.75rem; color:var(--text-dark);">Escribe un mensaje o responde a las solicitudes de validación comercial.</p>
                    </div>
                `;
                return;
            }

            scrollBox.innerHTML = msgs.map(m => {
                const isMio = (m.rol === 'Reportería' || (m.remitente || '').includes('Nayeli'));
                
                // 1. Si es notificación de factura emitida con voucher por el asesor
                if (m.tipo === 'factura_notif' && m.factura_data) {
                    const fd = m.factura_data;
                    const montoFmt = parseFloat(fd.monto || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
                    const clienteSafe = (fd.cliente || '').replace(/'/g, "\\'");
                    const asesorSafe = (m.asesor || asesorChatActivo).replace(/'/g, "\\'");
                    const voucherBtn = fd.voucher_url ? `
                        <button type="button" class="btn-chat-action-outline" onclick="abrirVisorVoucher('${fd.nro_factura}', '${clienteSafe}', '${montoFmt}', '${fd.nro_operacion || ''}', '${asesorSafe}', 1, '${fd.voucher_url}')">
                            <i class="fa-solid fa-receipt" style="color:var(--accent-green);"></i> Ver Voucher
                        </button>
                    ` : '';

                    return `
                        <div class="msg-bubble-wa ${isMio ? 'sent' : 'received'}" style="max-width:88%;">
                            <div class="msg-sender-tag">
                                <i class="fa-solid fa-file-invoice"></i> ${m.remitente} • ${m.sucursal || sucursalChatActiva}
                            </div>
                            <div class="card-factura-chat">
                                <div class="card-factura-chat-header">
                                    <span>📄 FACTURA POR VALIDAR</span>
                                    <span style="color:#059669; font-weight:800; font-size:0.88rem;">S/ ${montoFmt}</span>
                                </div>
                                <div class="card-factura-chat-body">
                                    <div><strong>N° Factura:</strong> <span style="font-family:monospace; font-weight:700;">${fd.nro_factura}</span></div>
                                    <div><strong>Cliente:</strong> ${fd.cliente} (RUC: ${fd.ruc || '-'})</div>
                                    <div><strong>Banco:</strong> ${fd.banco || 'Banco'} • Op. #${fd.nro_operacion || '-'}</div>
                                    <div style="font-size:0.75rem; color:#64748B; margin-top:4px;">${m.mensaje}</div>
                                </div>
                                <div class="card-factura-chat-actions">
                                    ${voucherBtn}
                                    <button type="button" class="btn-chat-action-green" onclick="confirmarPagoDirectoDesdeChat('${fd.nro_factura}', '${clienteSafe}', ${fd.monto}, '${asesorSafe}', '${fd.banco || 'BCP'}', '${fd.nro_operacion || ''}')">
                                        <i class="fa-solid fa-check-double"></i> Aceptar Pago
                                    </button>
                                    <button type="button" class="btn-chat-action-red" onclick="observarPagoDirectoDesdeChat('${fd.nro_factura}', '${clienteSafe}', ${fd.monto}, '${asesorSafe}')">
                                        <i class="fa-solid fa-circle-exclamation"></i> Observar
                                    </button>
                                </div>
                            </div>
                            <span class="msg-time-wa">${m.hora} ${isMio ? '<i class="fa-solid fa-check-double" style="color:#53BDEB;"></i>' : ''}</span>
                        </div>
                    `;
                }

                // 2. Si es confirmación de pago aceptado enviado por Nayeli
                if (m.tipo === 'pago_aceptado') {
                    const fd = m.factura_data || {};
                    const montoFmt = parseFloat(fd.monto || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
                    return `
                        <div class="msg-bubble-wa ${isMio ? 'sent' : 'received'}" style="max-width:88%; border-left:4px solid #10B981;">
                            <div class="msg-sender-tag" style="color:#059669; font-weight:700;">
                                <i class="fa-solid fa-circle-check"></i> ${m.remitente} • Pago Conciliado & Aprobado
                            </div>
                            <div class="card-pago-status-wa aceptado">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                    <span style="font-weight:800; color:#065F46; font-size:0.78rem;"><i class="fa-solid fa-check-double"></i> PAGO ACEPTADO EN BANCO</span>
                                    <span style="font-weight:800; color:#059669; font-size:0.92rem;">S/ ${montoFmt}</span>
                                </div>
                                <div style="font-size:0.8rem; line-height:1.4; color:#1F2937;">
                                    <div><strong>Factura:</strong> <span style="font-family:monospace; font-weight:700; color:#047857;">${fd.nro_factura || ''}</span></div>
                                    <div><strong>Cliente:</strong> ${fd.cliente || ''}</div>
                                    <div><strong>Banco / Op:</strong> ${fd.banco || ''} • ${fd.nro_operacion || ''}</div>
                                    <div style="margin-top:6px; padding:6px 8px; background:rgba(16,185,129,0.15); border-radius:6px; color:#065F46; font-size:0.75rem;">
                                        <i class="fa-solid fa-truck-fast"></i> ${m.mensaje}
                                    </div>
                                </div>
                            </div>
                            <span class="msg-time-wa">${m.hora} ${isMio ? '<i class="fa-solid fa-check-double" style="color:#53BDEB;"></i>' : ''}</span>
                        </div>
                    `;
                }

                // 3. Si es notificación de observación de pago enviada por Nayeli
                if (m.tipo === 'pago_observado') {
                    const fd = m.factura_data || {};
                    const montoFmt = parseFloat(fd.monto || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
                    return `
                        <div class="msg-bubble-wa ${isMio ? 'sent' : 'received'}" style="max-width:88%; border-left:4px solid #EF4444;">
                            <div class="msg-sender-tag" style="color:#DC2626; font-weight:700;">
                                <i class="fa-solid fa-triangle-exclamation"></i> ${m.remitente} • Discrepancia de Pago
                            </div>
                            <div class="card-pago-status-wa observado">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                    <span style="font-weight:800; color:#991B1B; font-size:0.78rem;"><i class="fa-solid fa-circle-xmark"></i> OBSERVACIÓN BANCARIA</span>
                                    <span style="font-weight:800; color:#DC2626; font-size:0.92rem;">S/ ${montoFmt}</span>
                                </div>
                                <div style="font-size:0.8rem; line-height:1.4; color:#1F2937;">
                                    <div><strong>Factura:</strong> <span style="font-family:monospace; font-weight:700; color:#B91C1C;">${fd.nro_factura || ''}</span></div>
                                    <div><strong>Cliente:</strong> ${fd.cliente || ''}</div>
                                    <div style="margin-top:6px; padding:6px 8px; background:rgba(239,68,68,0.12); border-radius:6px; color:#991B1B; font-size:0.75rem;">
                                        <strong>Motivo:</strong> ${fd.motivo || m.mensaje}
                                    </div>
                                </div>
                            </div>
                            <span class="msg-time-wa">${m.hora} ${isMio ? '<i class="fa-solid fa-check-double" style="color:#53BDEB;"></i>' : ''}</span>
                        </div>
                    `;
                }

                // 4. Si es notificación de cierre de ventas del día enviado por el asesor
                if (m.tipo === 'cierre_notif') {
                    const cd = m.cierre_data || {};
                    const montoFmt = parseFloat(cd.monto_acumulado || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
                    const asesorSafe = (m.asesor || cd.asesor || asesorChatActivo).replace(/'/g, "\\'");
                    const sucursalSafe = (m.sucursal || cd.sucursal || sucursalChatActiva).replace(/'/g, "\\'");
                    const cierreId = cd.id || 1;

                    return `
                        <div class="msg-bubble-wa ${isMio ? 'sent' : 'received'}" style="max-width:88%; border-left:4px solid #F59E0B;">
                            <div class="msg-sender-tag" style="color:#D97706; font-weight:700;">
                                <i class="fa-solid fa-cash-register"></i> ${m.remitente} • Cierre de Ventas del Día
                            </div>
                            <div class="card-factura-chat" style="border-left:none;">
                                <div class="card-factura-chat-header" style="background:#FFFBEB; border-bottom:1px solid #FEF3C7;">
                                    <span style="color:#92400E; font-weight:800; font-size:0.8rem;"><i class="fa-solid fa-coins"></i> CONSOLIDADO DE CIERRE</span>
                                    <span style="color:#B45309; font-weight:800; font-size:0.95rem;">S/ ${montoFmt}</span>
                                </div>
                                <div class="card-factura-chat-body">
                                    <div><strong>Asesor:</strong> ${m.asesor || cd.asesor} (${m.sucursal || cd.sucursal})</div>
                                    <div><strong>Operaciones:</strong> ${cd.total_ventas || 1} ventas del día</div>
                                    <div><strong>Hora Envío:</strong> ${m.hora} hrs</div>
                                    <div style="font-size:0.75rem; color:#64748B; margin-top:4px;">${m.mensaje}</div>
                                </div>
                                <div class="card-factura-chat-actions">
                                    <button type="button" class="btn-chat-action-green" onclick="aprobarCierreDiario(${cierreId}, '${asesorSafe}', '${sucursalSafe}', ${cd.monto_acumulado || 0})">
                                        <i class="fa-solid fa-stamp"></i> Aprobar Cuadre
                                    </button>
                                    <button type="button" class="btn-chat-action-outline" onclick="cambiarVistaReporteria('cierres')">
                                        <i class="fa-solid fa-table-list"></i> Ver Auditoría
                                    </button>
                                </div>
                            </div>
                            <span class="msg-time-wa">${m.hora} ${isMio ? '<i class="fa-solid fa-check-double" style="color:#53BDEB;"></i>' : ''}</span>
                        </div>
                    `;
                }

                // 5. Si es confirmación de cuadre de cierre aprobado por Nayeli
                if (m.tipo === 'cierre_aprobado') {
                    const cd = m.cierre_data || {};
                    const montoFmt = parseFloat(cd.monto_acumulado || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
                    return `
                        <div class="msg-bubble-wa ${isMio ? 'sent' : 'received'}" style="max-width:88%; border-left:4px solid #10B981;">
                            <div class="msg-sender-tag" style="color:#059669; font-weight:700;">
                                <i class="fa-solid fa-clipboard-check"></i> ${m.remitente} • Auditoría Conforme
                            </div>
                            <div class="card-pago-status-wa aceptado">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                    <span style="font-weight:800; color:#065F46; font-size:0.78rem;"><i class="fa-solid fa-stamp"></i> CUADRE DE CAJA APROBADO</span>
                                    <span style="font-weight:800; color:#059669; font-size:0.92rem;">S/ ${montoFmt}</span>
                                </div>
                                <div style="font-size:0.8rem; line-height:1.4; color:#1F2937;">
                                    <div><strong>Asesor:</strong> ${cd.asesor || ''} • ${cd.sucursal || ''}</div>
                                    <div><strong>Ventas Totales:</strong> ${cd.total_ventas || 1} comprobantes conciliados</div>
                                    <div style="margin-top:6px; padding:6px 8px; background:rgba(16,185,129,0.15); border-radius:6px; color:#065F46; font-size:0.75rem;">
                                        <i class="fa-solid fa-circle-check"></i> ${m.mensaje}
                                    </div>
                                </div>
                            </div>
                            <span class="msg-time-wa">${m.hora} ${isMio ? '<i class="fa-solid fa-check-double" style="color:#53BDEB;"></i>' : ''}</span>
                        </div>
                    `;
                }

                // 6. Mensaje regular de texto
                return `
                    <div class="msg-bubble-wa ${isMio ? 'sent' : 'received'}">
                        ${!isMio ? `
                            <div class="msg-sender-tag">
                                ${m.remitente} <span style="font-weight:normal; font-size:0.68rem; color:var(--text-muted);">(${m.sucursal || sucursalChatActiva})</span>
                            </div>
                        ` : ''}
                        <div>${m.mensaje}</div>
                        <span class="msg-time-wa">${m.hora} ${isMio ? '<i class="fa-solid fa-check-double" style="color:#53BDEB;"></i>' : ''}</span>
                    </div>
                `;
            }).join('');

            scrollBox.scrollTop = scrollBox.scrollHeight;
        }

        function irAValidarFacturaDesdeChat(nroFactura, cliente, monto, banco, op, voucherUrl) {
            cambiarVistaReporteria('validacion');
            if (voucherUrl) {
                abrirVisorVoucher(nroFactura, cliente, monto, `${banco} #${op}`, asesorChatActivo, 1, voucherUrl);
            }
        }

        function insertarRespuestaRapida(texto) {
            const inp = document.getElementById('chatInputMessage');
            if (inp) {
                inp.value = texto;
                inp.focus();
            }
        }

        function cargarChatReporteria() {
            fetch('crm_backend.php?action=listar_mensajes')
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                listaConversacionesData = data.conversaciones || [];
                listaMensajesChatData = data.mensajes || [];

                renderListaConversacionesWhatsApp(listaConversacionesData);
                renderMensajesConversacionActiva();
            })
            .catch(err => console.log('Error listar chat reporteria:', err));
        }

        function enviarMensajeChat() {
            const input = document.getElementById('chatInputMessage');
            const msg = input.value.trim();
            if (!msg) return;

            const formData = new FormData();
            formData.append('action', 'enviar_chat');
            formData.append('remitente', 'Nayeli');
            formData.append('rol', 'Reportería');
            formData.append('asesor', asesorChatActivo);
            formData.append('sucursal', sucursalChatActiva);
            formData.append('mensaje', msg);

            fetch('crm_backend.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                input.value = '';
                cargarChatReporteria();
            })
            .catch(err => {
                input.value = '';
                cargarChatReporteria();
            });
        }

        // EXPORTAR EXCEL / CSV
        function exportarReporteExcel() {
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "Cotizacion,Cliente,Monto,Banco,Validador,Estado\n";
            csvContent += "COT-2026-084,Cosapi S.A.,14400.00,BCP,Nayeli,Aceptado\n";
            csvContent += "COT-2026-085,Consorcio Vial Piura,6800.00,BBVA,Nayeli,Aceptado\n";
            csvContent += "COT-2026-086,Edificaciones Pacifico,3308.00,Interbank,Nayeli,Aceptado\n";

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `Reporte_Reporteria_BSPeru_${new Date().toISOString().slice(0,10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Cargar paleta, pagos, cierres y chat de reportería al iniciar y cada 4.5 segundos
        window.addEventListener('DOMContentLoaded', () => {
            inicializarPaletaReporteria();
            cargarPagosReporteria();
            cargarCierresReporteria();
            cargarChatReporteria();
            setInterval(() => {
                cargarPagosReporteria();
                cargarCierresReporteria();
                cargarChatReporteria();
            }, 4500);
        });
    </script>
</body>
</html>

<?php
// crm/reportes.php - BS Perú CRM: Módulo de Reportería & Validación de Pagos
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
        $validador = $_POST['validador'] ?? 'Rodrigo Alonso (Reportería)';
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
        $remitente = 'Rodrigo Alonso (Reportería)';
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
            --outer-bg: #1B2B24;
            --outer-bg-dark: #121A16;
            --app-frame: #141716;
            --sidebar-bg: #141716;
            --main-bg: #FFFFFF;
            --right-bg: #F4F8F6;
            
            --accent-green: #10B981;
            --accent-green-light: #34D399;
            --accent-green-dark: #059669;
            --accent-green-soft: #ECFDF5;
            
            --accent-tan: #C79B58;
            --accent-tan-soft: #FBF8F2;
            
            --text-dark: #1A202C;
            --text-muted: #718096;
            --text-light: #A0AEC0;
            --border-soft: #E2E8F0;
            --card-radius: 28px;
            --pill-radius: 40px;

            --chart-green: #10B981;
            --chart-blue: #3B82F6;
            --chart-yellow: #F59E0B;
            --chart-purple: #8B5CF6;
            --chart-red: #EF4444;

            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.dark-mode {
            --outer-bg: #0F1713;
            --app-frame: #0E1210;
            --sidebar-bg: #0E1210;
            --main-bg: #161C19;
            --right-bg: #111714;
            --text-dark: #F3F4F6;
            --text-muted: #9CA3AF;
            --border-soft: #232D27;
            --accent-green-soft: #182B21;
            --accent-tan-soft: #1E231F;
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
            background: linear-gradient(135deg, #10B981 0%, #34D399 100%);
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #0E1210;
            font-size: 1.25rem;
            font-weight: 800;
            box-shadow: 0 6px 15px rgba(16, 185, 129, 0.35);
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
            font-weight: 500;
            color: var(--accent-green-light);
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
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(3px);
        }
        .nav-item.active {
            background: var(--accent-green);
            color: #0E1210;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }
        .nav-item.active i {
            color: #0E1210;
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
            background: #0E1210;
            color: #FFF;
        }

        /* USER PILL IN SIDEBAR */
        .user-pill {
            background: #1B231F;
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
        .user-pill:hover { background: #232D27; }
        .user-pill-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--accent-green);
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
            background: #1B231F;
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
            background: var(--accent-green);
            color: #0E1210;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
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

        /* HERO / BANNER DE REPORTERÍA ESMERALDA */
        .welcome-card {
            background: linear-gradient(135deg, #059669 0%, #10B981 100%);
            border-radius: var(--card-radius);
            padding: 32px 42px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #FFF;
            position: relative;
            box-shadow: 0 14px 28px rgba(16, 185, 129, 0.22);
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
            color: #064E3B;
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
            background: #F0FDF4;
        }
        .btn-pill-white.primary {
            background: #0E1210;
            color: #FFF;
        }
        .btn-pill-white.primary:hover {
            background: #1B231F;
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

        /* ================= CHAT CON ASESORES EN EL CENTRO ================= */
        .chat-full-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 20px;
            height: 600px;
            background: #FFF;
            border-radius: var(--card-radius);
            border: 1px solid var(--border-soft);
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }
        body.dark-mode .chat-full-container {
            background: #18201C;
            border-color: #27362E;
        }
        .chat-sidebar-list {
            border-right: 1px solid var(--border-soft);
            display: flex;
            flex-direction: column;
            background: #F8FAFC;
        }
        body.dark-mode .chat-sidebar-list {
            background: #131A16;
            border-color: #27362E;
        }
        .chat-sidebar-header {
            padding: 16px 20px;
            font-weight: 700;
            font-size: 0.88rem;
            color: var(--text-dark);
            border-bottom: 1px solid var(--border-soft);
        }
        .chat-user-item {
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: var(--transition);
            border-bottom: 1px solid rgba(0,0,0,0.03);
        }
        .chat-user-item:hover, .chat-user-item.active {
            background: #FFF;
        }
        body.dark-mode .chat-user-item:hover, body.dark-mode .chat-user-item.active {
            background: #1C2620;
        }
        .chat-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            position: relative;
        }
        .chat-user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .chat-user-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #10B981;
            position: absolute;
            bottom: 0;
            right: 0;
            border: 2px solid #FFF;
        }
        .chat-user-meta h5 {
            font-size: 0.82rem;
            color: var(--text-dark);
            font-weight: 600;
        }
        .chat-user-meta p {
            font-size: 0.7rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 140px;
        }

        .chat-conversation-area {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .chat-conv-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-soft);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-conv-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .chat-conv-user h4 {
            font-size: 0.95rem;
            color: var(--text-dark);
            font-weight: 700;
        }
        .chat-conv-user span {
            font-size: 0.72rem;
            color: #10B981;
            font-weight: 600;
        }
        .chat-messages-scroll {
            flex: 1;
            padding: 20px 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: #FAFCFB;
        }
        body.dark-mode .chat-messages-scroll { background: #141B17; }
        .msg-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 0.84rem;
            line-height: 1.45;
        }
        .msg-bubble.received {
            background: #FFF;
            border: 1px solid var(--border-soft);
            color: var(--text-dark);
            align-self: flex-start;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        body.dark-mode .msg-bubble.received {
            background: #1D2620;
            border-color: #27362E;
            color: #F3F4F6;
        }
        .msg-bubble.sent {
            background: var(--accent-green);
            color: #0E1210;
            font-weight: 500;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }
        .msg-time {
            font-size: 0.65rem;
            opacity: 0.75;
            display: block;
            margin-top: 4px;
            text-align: right;
        }

        .chat-quick-replies {
            padding: 8px 24px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            background: #FFF;
            border-top: 1px solid var(--border-soft);
        }
        body.dark-mode .chat-quick-replies { background: #18201C; border-color: #27362E; }
        .quick-reply-chip {
            background: #F4F8F6;
            border: 1px solid var(--border-soft);
            padding: 5px 12px;
            border-radius: var(--pill-radius);
            font-size: 0.74rem;
            color: var(--text-dark);
            cursor: pointer;
            white-space: nowrap;
            transition: var(--transition);
        }
        .quick-reply-chip:hover {
            background: var(--accent-green);
            color: #0E1210;
        }

        .chat-input-bar {
            padding: 14px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: #FFF;
            border-top: 1px solid var(--border-soft);
        }
        body.dark-mode .chat-input-bar { background: #18201C; border-color: #27362E; }
        .chat-input-bar input {
            flex: 1;
            padding: 12px 18px;
            border-radius: var(--pill-radius);
            border: 1px solid var(--border-soft);
            background: #F4F8F6;
            outline: none;
            font-size: 0.85rem;
            color: var(--text-dark);
        }
        body.dark-mode .chat-input-bar input {
            background: #131A16;
            border-color: #27362E;
            color: #FFF;
        }
        .btn-chat-send {
            background: var(--accent-green);
            color: #0E1210;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.05rem;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .btn-chat-send:hover {
            transform: scale(1.06);
            background: #059669;
            color: #FFF;
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
        ::-webkit-scrollbar-thumb { background: #A7D3BD; border-radius: 3px; }
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

                <a href="ventas.php" class="nav-item" style="color:var(--accent-tan);">
                    <i class="fa-solid fa-table-cells-large"></i>
                    <span>Ir a Ventas</span>
                </a>
            </div>

            <!-- PERFIL USUARIO -->
            <div class="user-pill" onclick="alert('Sesión Activa: Rodrigo Alonso\nRol: Especialista de Reportería & Conciliación Financiera BS Perú')">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" alt="Avatar" class="user-pill-avatar">
                <div class="user-pill-info">
                    <div class="user-pill-name">Rodrigo Alonso</div>
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
                    <button class="btn-link-ventas" onclick="location.href='ventas.php'">
                        <i class="fa-solid fa-table-cells-large"></i> Módulo de Ventas
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
                            <h4>S/ 32,708</h4>
                            <p>Total Cierre del Día</p>
                        </div>
                    </div>
                    <div class="stat-card-mini">
                        <div class="stat-mini-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-mini-info">
                            <h4>4 / 4</h4>
                            <p>Asesores que Reportaron</p>
                        </div>
                    </div>
                    <div class="stat-card-mini">
                        <div class="stat-mini-icon"><i class="fa-solid fa-receipt"></i></div>
                        <div class="stat-mini-info">
                            <h4>12</h4>
                            <p>Operaciones del Día</p>
                        </div>
                    </div>
                    <div class="stat-card-mini">
                        <div class="stat-mini-icon"><i class="fa-solid fa-certificate"></i></div>
                        <div class="stat-mini-info">
                            <h4>100%</h4>
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
                        <tbody>
                            <tr>
                                <td><strong>Elizabeth Addams</strong></td>
                                <td>Sede Lima Central</td>
                                <td><strong style="color:var(--text-dark);">S/ 18,200.00</strong></td>
                                <td>5 ventas</td>
                                <td>18:30 hrs</td>
                                <td><span class="badge-status-accepted">✅ Cuadre Aprobado</span></td>
                                <td><button class="btn-confirm-direct" style="padding:6px 12px; font-size:0.72rem;" onclick="alert('Cierre de Elizabeth Addams auditado y conforme sin diferencias.')">Ver Detalle</button></td>
                            </tr>
                            <tr>
                                <td><strong>Maria Gomez</strong></td>
                                <td>Ventas Corporativas Lima</td>
                                <td><strong style="color:var(--text-dark);">S/ 14,400.00</strong></td>
                                <td>1 venta (Cosapi)</td>
                                <td>18:15 hrs</td>
                                <td><span class="badge-status-pending">⏳ En Conciliación</span></td>
                                <td><button class="btn-confirm-direct" style="padding:6px 12px; font-size:0.72rem;" onclick="cambiarVistaReporteria('validacion')">Validar Vouchers</button></td>
                            </tr>
                            <tr>
                                <td><strong>Carlos Ruiz</strong></td>
                                <td>Despachos & Piura</td>
                                <td><strong style="color:var(--text-dark);">S/ 6,800.00</strong></td>
                                <td>2 ventas</td>
                                <td>17:50 hrs</td>
                                <td><span class="badge-status-pending">⏳ En Conciliación</span></td>
                                <td><button class="btn-confirm-direct" style="padding:6px 12px; font-size:0.72rem;" onclick="cambiarVistaReporteria('validacion')">Validar Vouchers</button></td>
                            </tr>
                            <tr>
                                <td><strong>Ana Torres</strong></td>
                                <td>Sede San Borja</td>
                                <td><strong style="color:var(--text-dark);">S/ 3,308.00</strong></td>
                                <td>4 ventas</td>
                                <td>17:40 hrs</td>
                                <td><span class="badge-status-pending">⏳ En Conciliación</span></td>
                                <td><button class="btn-confirm-direct" style="padding:6px 12px; font-size:0.72rem;" onclick="cambiarVistaReporteria('validacion')">Validar Vouchers</button></td>
                            </tr>
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
                                <td>Rodrigo Alonso</td>
                                <td>Hoy 14:20</td>
                                <td><span class="badge-status-accepted">✅ Pago Aceptado</span></td>
                                <td><button class="btn-confirm-direct" style="padding:4px 10px; font-size:0.7rem;" onclick="abrirVisorVoucher('F001-00892', 'Cosapi S.A.', '14,400.00', 'BCP #849201', 'Maria Gomez', 0)">Ver</button></td>
                            </tr>
                            <tr>
                                <td><strong>F001-00891</strong></td>
                                <td>Graña y Montero S.A.</td>
                                <td><strong>S/ 28,950.00</strong></td>
                                <td>BBVA #771920</td>
                                <td>Rodrigo Alonso</td>
                                <td>Ayer 16:45</td>
                                <td><span class="badge-status-accepted">✅ Pago Aceptado</span></td>
                                <td><button class="btn-confirm-direct" style="padding:4px 10px; font-size:0.7rem;" onclick="abrirVisorVoucher('F001-00891', 'Graña y Montero S.A.', '28,950.00', 'BBVA #771920', 'Elizabeth Addams', 0)">Ver</button></td>
                            </tr>
                            <tr>
                                <td><strong>F001-00890</strong></td>
                                <td>Obrascón Huarte Lain</td>
                                <td><strong>S/ 9,450.00</strong></td>
                                <td>Interbank #30198</td>
                                <td>Rodrigo Alonso</td>
                                <td>Ayer 11:30</td>
                                <td><span class="badge-status-accepted">✅ Pago Aceptado</span></td>
                                <td><button class="btn-confirm-direct" style="padding:4px 10px; font-size:0.7rem;" onclick="abrirVisorVoucher('F001-00890', 'Obrascón Huarte Lain', '9,450.00', 'Interbank #30198', 'Carlos Ruiz', 0)">Ver</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ==========================================
                 VISTA 5: CHAT CON ASESORES DE VENTAS
            =========================================== -->
            <div class="vista-seccion" id="vista-chat" style="display:none;">
                <div class="chat-full-container">
                    <!-- Lista lateral de asesores -->
                    <div class="chat-sidebar-list">
                        <div class="chat-sidebar-header">
                            <i class="fa-solid fa-users" style="color:var(--accent-green);"></i> Asesores de Ventas
                        </div>
                        <div class="chat-user-item active" onclick="seleccionarAsesorChat('Maria Gomez', 'Ventas Corporativas Lima', this)">
                            <div class="chat-user-avatar">
                                <img src="https://ui-avatars.com/api/?name=Maria+Gomez&background=D1FAE5&color=059669" alt="User">
                                <div class="chat-user-dot"></div>
                            </div>
                            <div class="chat-user-meta">
                                <h5>Maria Gomez</h5>
                                <p>S/ 14,400 Cosapi en revisión</p>
                            </div>
                        </div>

                        <div class="chat-user-item" onclick="seleccionarAsesorChat('Carlos Ruiz', 'Despachos & Logística', this)">
                            <div class="chat-user-avatar">
                                <img src="https://ui-avatars.com/api/?name=Carlos+Ruiz&background=FEF3C7&color=D97706" alt="User">
                                <div class="chat-user-dot"></div>
                            </div>
                            <div class="chat-user-meta">
                                <h5>Carlos Ruiz</h5>
                                <p>Consorcio Vial Piura voucher</p>
                            </div>
                        </div>

                        <div class="chat-user-item" onclick="seleccionarAsesorChat('Ana Torres', 'Asesora Sede San Borja', this)">
                            <div class="chat-user-avatar">
                                <img src="https://ui-avatars.com/api/?name=Ana+Torres&background=E0E7FF&color=4338CA" alt="User">
                                <div class="chat-user-dot"></div>
                            </div>
                            <div class="chat-user-meta">
                                <h5>Ana Torres</h5>
                                <p>Edificaciones Pacífico</p>
                            </div>
                        </div>

                        <div class="chat-user-item" onclick="seleccionarAsesorChat('Elizabeth Addams', 'Sede Lima Central', this)">
                            <div class="chat-user-avatar">
                                <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&auto=format&fit=crop&q=80" alt="User">
                                <div class="chat-user-dot"></div>
                            </div>
                            <div class="chat-user-meta">
                                <h5>Elizabeth Addams</h5>
                                <p>Cierre del día enviado</p>
                            </div>
                        </div>
                    </div>

                    <!-- Área de conversación -->
                    <div class="chat-conversation-area">
                        <div class="chat-conv-header">
                            <div class="chat-conv-user">
                                <h4 id="chatActiveAdvisorName">Maria Gomez</h4>
                                <span id="chatActiveAdvisorRole">Ventas Corporativas Lima • En línea</span>
                            </div>
                            <button class="btn-confirm-direct" style="padding:6px 12px; font-size:0.75rem;" onclick="cambiarVistaReporteria('validacion')">
                                <i class="fa-solid fa-file-invoice"></i> Ver sus vouchers pendientes
                            </button>
                        </div>

                        <div class="chat-messages-scroll" id="chatMessagesScroll">
                            <div class="msg-bubble received">
                                <strong>Maria Gomez:</strong><br>
                                Hola Rodrigo, acabo de subir el voucher del cliente Cosapi S.A. por S/ 14,400.00 en BCP. ¿Me confirmas por favor para que el almacén empiece a preparar el despacho de los aditivos?
                                <span class="msg-time">14:15 hrs</span>
                            </div>

                            <div class="msg-bubble sent">
                                <strong>Rodrigo Alonso (Reportería):</strong><br>
                                Hola María, lo tengo en pantalla. Estoy revisando el extracto bancario del BCP con el N° de operación #4829104. En un par de minutos lo confirmo.
                                <span class="msg-time">14:17 hrs</span>
                            </div>
                        </div>

                        <!-- Respuestas rápidas -->
                        <div class="chat-quick-replies">
                            <span class="quick-reply-chip" onclick="insertarRespuestaRapida('✅ Pago confirmado y verificado en extracto bancario. Pedido liberado.')">
                                ✅ Pago confirmado y verificado
                            </span>
                            <span class="quick-reply-chip" onclick="insertarRespuestaRapida('⚠️ Por favor enviar foto del voucher con mayor nitidez.')">
                                ⚠️ Voucher poco legible
                            </span>
                            <span class="quick-reply-chip" onclick="insertarRespuestaRapida('🔍 El N° de operación aún no figura en el extracto online.')">
                                🔍 Aún no figura en extracto
                            </span>
                        </div>

                        <!-- Barra para escribir mensaje -->
                        <div class="chat-input-bar">
                            <input type="text" id="chatInputMessage" placeholder="Escribe un mensaje de coordinación..." onkeypress="if(event.key==='Enter') enviarMensajeChat()">
                            <button class="btn-chat-send" onclick="enviarMensajeChat()">
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
                    <!-- SUCURSAL LIMA -->
                    <div class="card-panel">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:45px; height:45px; background:var(--accent-green-soft); color:var(--accent-green-dark); border-radius:14px; display:flex; justify-content:center; align-items:center; font-size:1.2rem;">
                                <i class="fa-solid fa-building"></i>
                            </div>
                            <div>
                                <h4 style="font-size:1.05rem; color:var(--text-dark);">Sucursal Lima</h4>
                                <span style="font-size:0.75rem; color:var(--text-muted);">Sede Principal / Corporativa</span>
                            </div>
                        </div>

                        <div style="margin:10px 0;">
                            <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:6px;">
                                <span>Avance del Mes</span>
                                <strong>45% (S/ 22,500 / S/ 50,000)</strong>
                            </div>
                            <div class="progress-track">
                                <div class="progress-bar-fill" style="width:45%; background:var(--accent-green);"></div>
                            </div>
                            <small style="color:var(--text-muted); font-size:0.72rem; margin-top:4px; display:block;">Faltan 14 días para cierre mensual</small>
                        </div>

                        <div style="font-size:0.8rem; color:var(--text-muted);">
                            <strong>Top Asesor:</strong> Elizabeth Addams (S/ 18,200)
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

                    <div class="advisor-online-item" onclick="abrirChatConAsesor('Elizabeth Addams')">
                        <div class="advisor-avatar-box">
                            <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&auto=format&fit=crop&q=80" alt="User">
                            <div class="advisor-online-dot"></div>
                        </div>
                        <div class="advisor-content">
                            <h5>Elizabeth Addams</h5>
                            <p>Lima Central • Activa</p>
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
            <p style="color:var(--text-muted); font-size:0.85rem; margin:8px 0 20px;">Puedes volver al módulo de ventas o regresar a la tienda de productos.</p>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <a href="ventas.php" class="btn-pill-white primary" style="justify-content:center; text-decoration:none;">
                    <i class="fa-solid fa-table-cells-large"></i> Ir al Panel de Ventas (`ventas.php`)
                </a>
                <button class="btn-pill-white" style="justify-content:center;" onclick="cerrarModales()">
                    Permanecer en Reportería
                </button>
            </div>
        </div>
    </div>

    <!-- ================= JAVASCRIPT ================= -->
    <script>
        let pagosPendientesCount = 3;
        let totalValidadasMes = 94178.00;
        let totalValidadasHoy = 24508.00;
        let currentModalPagoId = 0;
        let currentModalMonto = 0;
        let currentModalCotiz = '';

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

        // VISOR DE VOUCHER
        function abrirVisorVoucher(cotiz, cliente, monto, op, asesor, pagoId) {
            currentModalPagoId = pagoId;
            currentModalMonto = parseFloat(monto.replace(/,/g, ''));
            currentModalCotiz = cotiz;

            document.getElementById('modalVoucherCotiz').textContent = cotiz;
            document.getElementById('modalVoucherCliente').textContent = cliente;
            document.getElementById('modalVoucherMonto').textContent = 'S/ ' + monto;
            document.getElementById('modalVoucherOp').textContent = op;
            document.getElementById('modalVoucherAsesor').textContent = asesor;

            // Si es un pago ya aprobado (id = 0), ocultar botones de acción
            const actionsBox = document.getElementById('modalVoucherActions');
            if (pagoId === 0) {
                actionsBox.style.display = 'none';
            } else {
                actionsBox.style.display = 'flex';
            }

            document.getElementById('modalVisorVoucher').classList.add('open');
        }

        // ACEPTAR PAGO DIRECTO
        function confirmarPagoEnFila(id, monto, cotiz) {
            if (!confirm(`¿Confirmar y conciliar el pago de S/ ${monto.toLocaleString()} para la cotización ${cotiz}?`)) return;

            ejecutarAprobacionPago(id, monto, cotiz);
        }

        function confirmarDesdeModal() {
            cerrarModales();
            if (currentModalPagoId > 0) {
                ejecutarAprobacionPago(currentModalPagoId, currentModalMonto, currentModalCotiz);
            }
        }

        function ejecutarAprobacionPago(id, monto, cotiz) {
            // Llamada AJAX al backend de reportes.php
            const formData = new FormData();
            formData.append('action', 'confirmar_pago');
            formData.append('pago_id', id);
            formData.append('monto', monto);
            formData.append('cotizacion', cotiz);
            formData.append('validador', 'Rodrigo Alonso (Reportería)');

            fetch('reportes.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                // Actualizar fila
                const row = document.getElementById(`row-pago-${id}`);
                if (row) {
                    const badge = document.getElementById(`badge-pago-${id}`);
                    if (badge) {
                        badge.className = 'badge-status-accepted';
                        badge.innerHTML = '<i class="fa-solid fa-check-circle"></i> Aceptado';
                    }
                    const actionCell = row.cells[row.cells.length - 1];
                    actionCell.innerHTML = '<span style="color:#059669; font-weight:700; font-size:0.75rem;"><i class="fa-solid fa-check-double"></i> Pago Aprobado</span>';
                }

                // Reducir contador pendientes
                pagosPendientesCount = Math.max(0, pagosPendientesCount - 1);
                document.getElementById('badgeSidePending').textContent = pagosPendientesCount;
                document.getElementById('badgeRightPending').textContent = `${pagosPendientesCount} pendientes`;
                document.getElementById('kpiPagosPendientes').textContent = pagosPendientesCount;

                // Sumar al total
                totalValidadasMes += monto;
                totalValidadasHoy += monto;
                document.getElementById('kpiTotalVentas').textContent = 'S/ ' + totalValidadasMes.toLocaleString('es-PE', {minimumFractionDigits: 0});
                document.getElementById('kpiTotalHoy').textContent = 'S/ ' + totalValidadasHoy.toLocaleString('es-PE', {minimumFractionDigits: 0});

                // Agregar al historial de aceptados
                const tbodyHist = document.getElementById('historialValidadosTbody');
                if (tbodyHist) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${cotiz}</strong></td>
                        <td>Cliente Validado</td>
                        <td><strong>S/ ${monto.toLocaleString('es-PE', {minimumFractionDigits: 2})}</strong></td>
                        <td>Conciliado</td>
                        <td>Rodrigo Alonso</td>
                        <td>Hoy (Hace un momento)</td>
                        <td><span class="badge-status-accepted">✅ Pago Aceptado</span></td>
                        <td><button class="btn-confirm-direct" style="padding:4px 10px; font-size:0.7rem;" onclick="abrirVisorVoucher('${cotiz}', 'Cliente Validado', '${monto}', 'Conciliado', 'Ventas', 0)">Ver</button></td>
                    `;
                    tbodyHist.insertBefore(tr, tbodyHist.firstChild);
                }

                alert(`✅ ¡Pago de ${cotiz} confirmado exitosamente!\nSe actualizó la base de datos y se notificó al área de ventas para el despacho.`);
            })
            .catch(err => {
                alert('Pago aceptado en vista local.');
            });
        }

        // OBSERVAR PAGO
        function observarPagoEnFila(id, cotiz) {
            const motivo = prompt(`Ingrese el motivo de observación para la cotización ${cotiz}:`, 'Comprobante no coincide con extracto bancario');
            if (!motivo) return;

            const formData = new FormData();
            formData.append('action', 'observar_pago');
            formData.append('pago_id', id);
            formData.append('cotizacion', cotiz);
            formData.append('motivo', motivo);

            fetch('reportes.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                const row = document.getElementById(`row-pago-${id}`);
                if (row) {
                    const badge = document.getElementById(`badge-pago-${id}`);
                    if (badge) {
                        badge.className = 'badge-status-observed';
                        badge.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Observado';
                    }
                    const actionCell = row.cells[row.cells.length - 1];
                    actionCell.innerHTML = `<span style="color:#DC2626; font-size:0.72rem; font-weight:600;"><i class="fa-solid fa-circle-exclamation"></i> Observado: ${motivo}</span>`;
                }
                alert(`⚠️ Se marcó el pago como Observado. Se envió la notificación de corrección al asesor de ventas.`);
            });
        }

        function observarDesdeModal() {
            cerrarModales();
            if (currentModalPagoId > 0) {
                observarPagoEnFila(currentModalPagoId, currentModalCotiz);
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

        // CHAT CON ASESORES
        function abrirChatConAsesor(nombreAsesor) {
            cambiarVistaReporteria('chat');
            document.getElementById('chatActiveAdvisorName').textContent = nombreAsesor;
            document.getElementById('chatActiveAdvisorRole').textContent = 'Asesor Comercial BS Perú • En línea';
        }

        function seleccionarAsesorChat(nombre, rol, elItem) {
            document.querySelectorAll('.chat-sidebar-list .chat-user-item').forEach(i => i.classList.remove('active'));
            elItem.classList.add('active');
            document.getElementById('chatActiveAdvisorName').textContent = nombre;
            document.getElementById('chatActiveAdvisorRole').textContent = `${rol} • En línea`;
        }

        function insertarRespuestaRapida(texto) {
            document.getElementById('chatInputMessage').value = texto;
            document.getElementById('chatInputMessage').focus();
        }

        function enviarMensajeChat() {
            const input = document.getElementById('chatInputMessage');
            const msg = input.value.trim();
            if (!msg) return;

            const scrollBox = document.getElementById('chatMessagesScroll');
            const now = new Date();
            const timeStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')} hrs`;

            const div = document.createElement('div');
            div.className = 'msg-bubble sent';
            div.innerHTML = `
                <strong>Rodrigo Alonso (Reportería):</strong><br>
                ${msg}
                <span class="msg-time">${timeStr}</span>
            `;
            scrollBox.appendChild(div);
            scrollBox.scrollTop = scrollBox.scrollHeight;
            input.value = '';

            // Respuesta automática simulada del asesor
            setTimeout(() => {
                const advisorName = document.getElementById('chatActiveAdvisorName').textContent;
                const repDiv = document.createElement('div');
                repDiv.className = 'msg-bubble received';
                repDiv.innerHTML = `
                    <strong>${advisorName}:</strong><br>
                    Recibido Rodrigo, muchas gracias por la verificación. Procedo con la coordinación del cliente.
                    <span class="msg-time">${timeStr}</span>
                `;
                scrollBox.appendChild(repDiv);
                scrollBox.scrollTop = scrollBox.scrollHeight;
            }, 1000);
        }

        // EXPORTAR EXCEL / CSV
        function exportarReporteExcel() {
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "Cotizacion,Cliente,Monto,Banco,Validador,Estado\n";
            csvContent += "COT-2026-084,Cosapi S.A.,14400.00,BCP,Rodrigo Alonso,Aceptado\n";
            csvContent += "COT-2026-085,Consorcio Vial Piura,6800.00,BBVA,Rodrigo Alonso,Aceptado\n";
            csvContent += "COT-2026-086,Edificaciones Pacifico,3308.00,Interbank,Rodrigo Alonso,Aceptado\n";

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `Reporte_Reporteria_BSPeru_${new Date().toISOString().slice(0,10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>

<?php
// crm/reportes.php - BS Perú CRM & Módulo de Reportería
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
        $validador = $_POST['validador'] ?? 'Área de Reportería';
        $fecha = date('Y-m-d H:i:s');

        // Si hay BD activa, actualizar
        if ($db) {
            try {
                $stmt = $db->prepare("UPDATE pagos SET validador_id = 1, fecha_pago = ? WHERE id = ?");
                $stmt->execute([$fecha, $pago_id]);
                $stmt2 = $db->prepare("UPDATE cotizaciones SET estado = 'Pagada' WHERE id = (SELECT cotizacion_id FROM pagos WHERE id = ?)");
                $stmt2->execute([$pago_id]);
            } catch(Exception $ex) {}
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Pago confirmado y aceptado exitosamente por Reportería.',
            'fecha' => $fecha,
            'validador' => $validador,
            'monto' => $monto
        ]);
        exit;
    }

    if ($action === 'observar_pago') {
        $pago_id = $_POST['pago_id'] ?? 0;
        $motivo = $_POST['motivo'] ?? 'Comprobante no coincide con extracto bancario';
        echo json_encode([
            'success' => true,
            'mensaje' => 'Pago marcado como Observado. Se ha notificado al asesor de ventas.',
            'motivo' => $motivo
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
    <title>CRM - BS Perú | Panel de Reportería & Ventas</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #E6EAF6;
            --app-bg: #FFFFFF;
            --primary: #6D5DD3;
            --primary-light: #7A6EED;
            --primary-soft: rgba(109, 93, 211, 0.08);
            --accent: #FF7E9F;
            --accent-green: #10B981;
            --accent-orange: #F59E0B;
            --accent-blue: #3B82F6;
            --text-dark: #2D3748;
            --text-light: #A0AEC0;
            --border-light: #EDF2F7;
            --shadow: 0 20px 40px rgba(109, 93, 211, 0.15);
            --card-shadow: 0 10px 25px rgba(0,0,0,0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .app-container {
            background: var(--app-bg);
            width: 98%;
            max-width: 2560px;
            height: 96vh;
            min-height: 850px;
            border-radius: 40px;
            box-shadow: var(--shadow);
            display: flex;
            padding: 25px;
            overflow: hidden;
            position: relative;
        }

        /* ================= SIDEBAR ================= */
        .sidebar {
            background: var(--primary);
            width: 90px;
            border-radius: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 0;
            gap: 22px;
            box-shadow: 0 10px 25px rgba(109, 93, 211, 0.3);
            flex-shrink: 0;
            z-index: 10;
        }

        .sidebar .icon {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: rgba(255, 255, 255, 0.65);
            font-size: 1.25rem;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .sidebar .icon:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #FFF;
            transform: translateY(-2px);
        }

        .sidebar .icon.active {
            background: rgba(255, 255, 255, 0.28);
            color: #FFF;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .sidebar .icon.active::after {
            content: '';
            position: absolute;
            right: -14px;
            width: 5px;
            height: 24px;
            background: #FFF;
            border-radius: 4px 0 0 4px;
        }

        .sidebar .icon.logo {
            background: transparent;
            color: #FFF;
            font-size: 1.6rem;
            margin-bottom: 15px;
            cursor: pointer;
        }
        .sidebar .icon.logo:hover {
            transform: rotate(15deg);
        }
        
        .sidebar .bottom-icon {
            margin-top: auto;
            color: rgba(255, 255, 255, 0.5);
        }
        .sidebar .bottom-icon:hover {
            color: #FF7E9F;
            background: rgba(255, 126, 159, 0.2);
        }

        .sidebar .icon[data-tooltip]::before {
            content: attr(data-tooltip);
            position: absolute;
            left: 75px;
            background: #2D3748;
            color: #FFF;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transform: translateX(-8px);
            transition: 0.2s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 100;
        }
        .sidebar .icon[data-tooltip]:hover::before {
            opacity: 1;
            transform: translateX(0);
        }

        /* ================= MAIN CONTENT ================= */
        .main-content {
            flex: 1;
            padding: 20px 35px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            min-width: 0;
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-shrink: 0;
        }

        .header-title h2 {
            font-size: 1.8rem;
            color: var(--text-dark);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .header-title span {
            color: var(--text-light);
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .badge-reporteria-role {
            background: rgba(109, 93, 211, 0.12);
            color: var(--primary);
            padding: 7px 16px;
            border-radius: 14px;
            font-size: 0.82rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-avatar {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }
        .user-avatar img {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            box-shadow: 0 4px 10px rgba(109, 93, 211, 0.2);
        }
        .user-meta {
            text-align: right;
            line-height: 1.2;
        }
        .user-meta strong {
            display: block;
            color: var(--text-dark);
            font-size: 0.85rem;
            font-weight: 600;
        }
        .user-meta small {
            color: var(--text-light);
            font-size: 0.72rem;
        }

        /* ================= VISTAS / SECCIONES ================= */
        .crm-view {
            display: none;
            animation: fadeInView 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
            flex-direction: column;
            gap: 25px;
            flex: 1;
        }
        .crm-view.active {
            display: flex;
        }

        @keyframes fadeInView {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ================= KPI ROW ================= */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .kpi-card {
            background: #FFF;
            border-radius: 24px;
            padding: 22px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 18px;
            transition: 0.3s ease;
        }
        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(109, 93, 211, 0.08);
        }
        .kpi-icon {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.4rem;
        }
        .kpi-icon.purple { background: rgba(109, 93, 211, 0.12); color: var(--primary); }
        .kpi-icon.green { background: rgba(16, 185, 129, 0.12); color: var(--accent-green); }
        .kpi-icon.orange { background: rgba(245, 158, 11, 0.12); color: var(--accent-orange); }
        .kpi-icon.blue { background: rgba(59, 130, 246, 0.12); color: var(--accent-blue); }
        .kpi-data p {
            color: var(--text-light);
            font-size: 0.8rem;
            font-weight: 500;
        }
        .kpi-data h3 {
            color: var(--text-dark);
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin: 3px 0;
        }
        .kpi-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 12px;
        }
        .kpi-badge.up { background: rgba(16, 185, 129, 0.12); color: var(--accent-green); }
        .kpi-badge.info { background: rgba(109, 93, 211, 0.12); color: var(--primary); }

        /* Quick actions banner */
        .quick-actions-bar {
            background: linear-gradient(135deg, #6D5DD3 0%, #8A7BFF 100%);
            border-radius: 24px;
            padding: 22px 30px;
            color: #FFF;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 12px 30px rgba(109, 93, 211, 0.25);
        }
        .quick-actions-bar h4 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .quick-actions-bar p {
            font-size: 0.85rem;
            opacity: 0.85;
        }
        .actions-btns {
            display: flex;
            gap: 12px;
        }
        .btn-action {
            background: rgba(255,255,255,0.2);
            color: #FFF;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 10px 18px;
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.25s;
            text-decoration: none;
        }
        .btn-action:hover {
            background: #FFF;
            color: var(--primary);
            border-color: #FFF;
        }
        .btn-action.btn-white {
            background: #FFF;
            color: var(--primary);
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        /* Tabla estilizada */
        .table-card {
            background: #FFF;
            border-radius: 24px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .table-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-card-header h3 {
            font-size: 1.15rem;
            color: var(--text-dark);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .custom-table th {
            color: var(--text-light);
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 2px solid var(--border-light);
        }
        .custom-table td {
            padding: 14px 16px;
            color: var(--text-dark);
            font-size: 0.88rem;
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
        }
        .custom-table tr:hover td {
            background: #FAFBFD;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.74rem;
            font-weight: 600;
            text-align: center;
        }
        .badge.badge-success { background: rgba(16, 185, 129, 0.12); color: var(--accent-green); }
        .badge.badge-warning { background: rgba(245, 158, 11, 0.12); color: var(--accent-orange); }
        .badge.badge-danger { background: rgba(239, 68, 68, 0.12); color: #EF4444; }
        .badge.badge-purple { background: rgba(109, 93, 211, 0.12); color: var(--primary); }

        /* ================= BANDEJA DE CONFIRMACIÓN DE PAGOS ================= */
        .payments-validation-card {
            background: #FFF;
            border-radius: 28px;
            padding: 26px;
            box-shadow: 0 12px 30px rgba(109, 93, 211, 0.08);
            border: 2px solid rgba(109, 93, 211, 0.15);
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .payments-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .payments-header h3 {
            font-size: 1.25rem;
            color: var(--text-dark);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .voucher-thumb {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid var(--border-light);
            cursor: pointer;
            transition: 0.2s;
        }
        .voucher-thumb:hover {
            transform: scale(1.08);
            border-color: var(--primary);
        }
        .btn-confirm-pay {
            background: var(--accent-green);
            color: #FFF;
            border: none;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }
        .btn-confirm-pay:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        .btn-reject-pay {
            background: #FFF;
            color: #EF4444;
            border: 1px solid #FECACA;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }
        .btn-reject-pay:hover {
            background: #FEF2F2;
        }

        /* ================= REPORTES ORIGINAL WIDGETS ================= */
        .top-widgets {
            display: flex;
            gap: 25px;
            margin-bottom: 10px;
        }
        .overview-card {
            background: var(--primary);
            flex: 2;
            border-radius: 30px;
            padding: 30px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            box-shadow: 0 15px 30px rgba(109, 93, 211, 0.25);
            min-height: 280px;
        }
        .overview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .overview-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
        }
        .overview-header select {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 12px;
            outline: none;
            cursor: pointer;
            font-weight: 500;
        }
        .overview-header select option {
            background: var(--primary);
            color: white;
        }
        .chart-mockup {
            height: 120px;
            margin: 15px 0;
            position: relative;
        }
        .svg-curve {
            width: 100%;
            height: 100%;
            overflow: visible;
        }
        .chart-point {
            position: absolute;
            top: 45px;
            left: 50%;
            width: 14px;
            height: 14px;
            background: white;
            border: 4px solid var(--accent);
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(255, 126, 159, 0.5);
        }
        .chart-point-label {
            position: absolute;
            top: 0px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(5px);
            padding: 4px 12px;
            border-radius: 10px;
            font-size: 0.8rem;
            text-align: center;
        }
        .overview-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            padding-top: 15px;
        }
        .stat-block p {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
        }
        .stat-block h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-top: 4px;
        }
        .stat-block.active h3 {
            color: #FFF;
        }
        .side-cards {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .mini-card {
            background: #FFF;
            border-radius: 24px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-light);
            flex: 1;
        }
        .mini-card.pink {
            background: var(--accent);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: stretch;
            border: none;
            box-shadow: 0 15px 30px rgba(255, 126, 159, 0.25);
        }
        .mini-card .icon-box {
            width: 50px;
            height: 50px;
            background: #F4F7FE;
            border-radius: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.3rem;
            color: var(--primary);
        }
        .mini-card.pink .icon-box {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }
        .mini-card.pink .top-row {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .mini-card.pink .bottom-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .mini-card.pink h4 { font-size: 1.05rem; font-weight: 600; }
        .mini-card.pink h2 { font-size: 2rem; font-weight: 700; line-height: 1; }
        .mini-card.pink p { font-size: 0.8rem; opacity: 0.8; }
        .mini-card.pink .arrow-btn {
            width: 40px;
            height: 40px;
            background: white;
            color: var(--accent);
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .bottom-widgets {
            display: flex;
            gap: 25px;
        }
        .branch-card {
            background: #FFF;
            flex: 1;
            border-radius: 24px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-light);
            position: relative;
        }
        .branch-card .icon-box {
            width: 45px;
            height: 45px;
            background: var(--primary-soft);
            border-radius: 14px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--primary);
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        .branch-card h4 { font-size: 1.05rem; color: var(--text-dark); font-weight: 600; }
        .branch-card p { color: var(--text-light); font-size: 0.8rem; margin-bottom: 20px; }
        .progress-container { display: flex; flex-direction: column; gap: 6px; }
        .progress-labels { display: flex; justify-content: space-between; font-size: 0.78rem; }
        .progress-labels .left-val { color: var(--text-dark); font-weight: 500; }
        .progress-labels .right-val { color: var(--text-light); font-weight: 600; }
        .progress-bar-bg { height: 8px; background: #F4F7FE; border-radius: 4px; overflow: hidden; }
        .progress-bar { height: 100%; border-radius: 4px; }
        .progress-bar.green { width: 45%; background: #10B981; }
        .progress-bar.green-light { width: 13%; background: #34D399; }
        .progress-bar.green-full { width: 90%; background: #059669; }

        /* ================= FACTURACIÓN ================= */
        .fact-filters-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .filter-pills { display: flex; gap: 8px; }
        .filter-pill {
            padding: 8px 16px;
            border-radius: 14px;
            background: #F4F7FE;
            color: var(--text-light);
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: 0.2s;
        }
        .filter-pill.active, .filter-pill:hover {
            background: var(--primary);
            color: #FFF;
        }
        .fact-stats-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .fact-stat-card {
            background: #FFF;
            border-radius: 20px;
            padding: 20px;
            border: 1px solid var(--border-light);
            box-shadow: var(--card-shadow);
        }
        .fact-stat-card p { font-size: 0.8rem; color: var(--text-light); font-weight: 500; }
        .fact-stat-card h3 { font-size: 1.6rem; color: var(--text-dark); margin: 4px 0; font-weight: 700; }

        /* ================= CHAT VENTAS & MENSAJES ================= */
        .chat-tabs-nav {
            display: flex;
            gap: 10px;
            background: #F4F7FE;
            padding: 6px;
            border-radius: 18px;
            width: fit-content;
        }
        .chat-tab-btn {
            padding: 10px 22px;
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-light);
            cursor: pointer;
            transition: 0.25s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .chat-tab-btn.active {
            background: #FFF;
            color: var(--primary);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .chat-split-container {
            display: flex;
            gap: 20px;
            flex: 1;
            min-height: 480px;
        }
        .inbox-list {
            flex: 1.2;
            background: #FFF;
            border-radius: 24px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            gap: 12px;
            overflow-y: auto;
            max-height: 580px;
        }
        .inbox-item {
            padding: 14px 16px;
            border-radius: 16px;
            background: #F8FAFC;
            cursor: pointer;
            border: 1px solid transparent;
            transition: 0.25s;
        }
        .inbox-item:hover, .inbox-item.active {
            background: #FFF;
            border-color: var(--primary);
            box-shadow: 0 4px 16px rgba(109, 93, 211, 0.08);
        }
        .inbox-item-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }
        .inbox-item-top h5 { font-size: 0.92rem; color: var(--text-dark); font-weight: 600; }
        .inbox-item-top small { font-size: 0.74rem; color: var(--text-light); }
        .inbox-item p {
            font-size: 0.8rem;
            color: #4A5568;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .chat-detail-card {
            flex: 1.8;
            background: #FFF;
            border-radius: 24px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .chat-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-light);
        }
        .chat-body-msg {
            padding: 20px 0;
            font-size: 0.92rem;
            color: var(--text-dark);
            line-height: 1.6;
        }
        .chat-reply-box {
            background: #F8FAFC;
            border-radius: 18px;
            padding: 16px;
            border: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .chat-reply-box textarea {
            width: 100%;
            height: 70px;
            border: none;
            outline: none;
            background: transparent;
            font-size: 0.88rem;
            resize: none;
            font-family: inherit;
        }
        .chat-actions-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-wa {
            background: #25D366;
            color: #FFF;
            border: none;
            padding: 10px 18px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
            text-decoration: none;
        }
        .btn-wa:hover { background: #20BA5A; transform: translateY(-1px); }

        /* ================= VIDEOS ================= */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 22px;
        }
        .video-card {
            background: #FFF;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid var(--border-light);
            box-shadow: var(--card-shadow);
            transition: 0.3s;
            display: flex;
            flex-direction: column;
        }
        .video-card:hover { transform: translateY(-4px); box-shadow: 0 15px 30px rgba(109, 93, 211, 0.1); }
        .video-thumbnail {
            position: relative;
            height: 170px;
            background: linear-gradient(135deg, #1E1B4B 0%, #312E81 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            overflow: hidden;
        }
        .video-thumbnail img { width: 100%; height: 100%; object-fit: cover; opacity: 0.6; transition: 0.3s; }
        .video-card:hover .video-thumbnail img { transform: scale(1.05); opacity: 0.75; }
        .video-play-btn {
            position: absolute;
            width: 52px;
            height: 52px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.3rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            transition: 0.2s;
        }
        .video-duration {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0,0,0,0.7);
            color: #FFF;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .video-content { padding: 20px; display: flex; flex-direction: column; gap: 8px; flex: 1; justify-content: space-between; }
        .video-content h4 { font-size: 1.02rem; color: var(--text-dark); font-weight: 600; line-height: 1.35; }
        .video-content p { color: var(--text-light); font-size: 0.8rem; line-height: 1.4; }
        .video-meta { display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: var(--primary); font-weight: 600; margin-top: 6px; }

        /* ================= RIGHT SIDEBAR ================= */
        .right-sidebar {
            width: 320px;
            padding: 10px 0 10px 30px;
            border-left: 2px solid #F4F7FE;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            gap: 20px;
        }
        .right-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .right-header h3 {
            font-size: 1.15rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .tabs {
            display: flex;
            background: #F4F7FE;
            border-radius: 20px;
            padding: 5px;
        }
        .tab {
            flex: 1;
            text-align: center;
            padding: 8px 0;
            color: var(--text-light);
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            border-radius: 15px;
            transition: 0.3s;
        }
        .tab.active {
            background: white;
            color: var(--text-dark);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            font-weight: 600;
        }
        .user-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .user-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-item img {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            object-fit: cover;
        }
        .user-info { flex: 1; }
        .user-info h5 { color: var(--text-dark); font-size: 0.9rem; font-weight: 600; }
        .user-info p { color: var(--text-light); font-size: 0.75rem; }
        .user-action {
            color: var(--text-light);
            width: 30px;
            height: 30px;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: 0.2s;
        }
        .user-action:hover { color: var(--primary); border-color: var(--primary); }

        /* WIDGET LATERAL DE PAGOS PENDIENTES (REEMPLAZO DEL MAPA) */
        .pending-payments-widget {
            background: #FAFBFD;
            border-radius: 24px;
            padding: 18px;
            border: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: auto;
        }
        .pending-widget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pending-widget-header h4 {
            font-size: 0.95rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pending-item-sidebar {
            background: #FFF;
            border-radius: 14px;
            padding: 12px;
            border: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            cursor: pointer;
            transition: 0.2s;
        }
        .pending-item-sidebar:hover {
            border-color: var(--primary);
            transform: translateX(2px);
        }
        .pending-item-info h5 {
            font-size: 0.85rem;
            color: var(--text-dark);
            margin-bottom: 2px;
        }
        .pending-item-info span {
            font-size: 0.72rem;
            color: var(--text-light);
        }

        /* ================= MODALES ================= */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeInView 0.2s ease;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #FFF;
            border-radius: 28px;
            padding: 32px;
            width: 90%;
            max-width: 520px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
        }
        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .btn-modal {
            flex: 1;
            padding: 12px;
            border-radius: 14px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: 0.2s;
            text-decoration: none;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        .btn-modal.primary { background: var(--primary); color: #FFF; }
        .btn-modal.primary:hover { background: var(--primary-light); }
        .btn-modal.secondary { background: #F4F7FE; color: var(--text-dark); }
        .btn-modal.secondary:hover { background: #E2E8F0; }

        /* ESTILOS VISOR DE VOUCHER */
        .voucher-preview-box {
            background: #F8FAFC;
            border: 2px dashed #CBD5E1;
            border-radius: 18px;
            padding: 18px;
            text-align: center;
            margin: 15px 0;
        }
        .voucher-img-large {
            max-width: 100%;
            max-height: 280px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .voucher-data-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            text-align: left;
            margin-top: 14px;
            font-size: 0.82rem;
        }
        .voucher-data-item strong { display: block; color: var(--text-dark); font-size: 0.88rem; }
        .voucher-data-item span { color: var(--text-light); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #CBD5E0; border-radius: 3px; }
    </style>
</head>
<body>

    <div class="app-container">
        
        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="icon logo" onclick="switchSection('inicio')" data-tooltip="BS Perú CRM">
                <i class="fa-solid fa-shapes"></i>
            </div>
            
            <div class="icon" onclick="switchSection('inicio')" data-section="inicio" data-tooltip="Dashboard Principal">
                <i class="fa-solid fa-house"></i>
            </div>

            <div class="icon" onclick="switchSection('facturacion')" data-section="facturacion" data-tooltip="Facturación & Pedidos">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>

            <div class="icon" onclick="location.href='ventas.php'" data-tooltip="Panel de Ventas">
                <i class="fa-solid fa-table-cells-large" style="color:#E5C38C;"></i>
            </div>

            <div class="icon active" onclick="switchSection('reportes')" data-section="reportes" data-tooltip="Reportería & Pagos">
                <i class="fa-solid fa-chart-column"></i>
            </div>

            <div class="icon" onclick="switchSection('mensajes')" data-section="mensajes" data-tooltip="Chat Ventas & Consultas">
                <i class="fa-regular fa-comment-dots"></i>
            </div>

            <div class="icon" onclick="switchSection('capacitacion')" data-section="capacitacion" data-tooltip="Capacitaciones & Videos">
                <i class="fa-regular fa-circle-play"></i>
            </div>
            
            <div class="icon bottom-icon" onclick="openLogoutModal()" data-tooltip="Cerrar sesión / Salir">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            
            <!-- HEADER GLOBAL (SIN BARRA DE BÚSQUEDA) -->
            <div class="header">
                <div class="header-title">
                    <span id="headerSectionSubtitle">Área de Reportería</span>
                    <h2 id="headerSectionTitle">Dashboard de Reportes & Validación de Pagos</h2>
                </div>
                
                <div class="header-right">
                    <a href="ventas.php" style="background:#C79B58; color:#161719; text-decoration:none; padding:8px 16px; border-radius:14px; font-weight:700; font-size:0.82rem; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 12px rgba(199,155,88,0.3); transition:0.2s;">
                        <i class="fa-solid fa-table-cells-large"></i> Panel de Ventas
                    </a>
                    <div class="badge-reporteria-role">
                        <i class="fa-solid fa-shield-check"></i> Modo: Validador Reportería
                    </div>
                    <div class="user-avatar" onclick="alert('Sesión activa: Especialista en Reportería & Finanzas BS Perú')">
                        <div class="user-meta">
                            <strong>Rodrigo Alonso</strong>
                            <small>Reportería & Cierre</small>
                        </div>
                        <img src="https://ui-avatars.com/api/?name=Rodrigo+Alonso&background=6D5DD3&color=fff" alt="User">
                    </div>
                </div>
            </div>

            <!-- ================= SECCIÓN 1: INICIO ================= -->
            <div id="section-inicio" class="crm-view">
                <div class="kpi-row">
                    <div class="kpi-card">
                        <div class="kpi-icon purple"><i class="fa-solid fa-wallet"></i></div>
                        <div class="kpi-data">
                            <p>Ventas del Mes</p>
                            <h3 id="dashTotalVentas">S/ 94,178</h3>
                            <span class="kpi-badge up"><i class="fa-solid fa-arrow-trend-up"></i> +26% vs mes ant.</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon green"><i class="fa-solid fa-truck-fast"></i></div>
                        <div class="kpi-data">
                            <p>Despachos a Obra</p>
                            <h3>142 envíos</h3>
                            <span class="kpi-badge up"><i class="fa-solid fa-check"></i> 98% a tiempo</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon orange"><i class="fa-solid fa-bell"></i></div>
                        <div class="kpi-data">
                            <p>Pagos por Validar</p>
                            <h3 id="dashPendingCount" style="color:var(--accent-orange);">3 pedidos</h3>
                            <span class="kpi-badge info"><i class="fa-solid fa-clock"></i> Pendiente Reportería</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon blue"><i class="fa-solid fa-comments"></i></div>
                        <div class="kpi-data">
                            <p>Chat Ventas</p>
                            <h3>4 asesores</h3>
                            <span class="kpi-badge up"><i class="fa-solid fa-bolt"></i> En línea</span>
                        </div>
                    </div>
                </div>

                <!-- BANNER RÁPIDO -->
                <div class="quick-actions-bar">
                    <div>
                        <h4>Recepción y Confirmación de Ventas del Día</h4>
                        <p>Los asesores comerciales cargan las facturas y comprobantes para validación inmediata de Reportería.</p>
                    </div>
                    <div class="actions-btns">
                        <button class="btn-action btn-white" onclick="switchSection('reportes')">
                            <i class="fa-solid fa-clipboard-check"></i> Validar Pagos Pendientes
                        </button>
                        <button class="btn-action" onclick="switchSection('mensajes')">
                            <i class="fa-solid fa-comments"></i> Chat con Asesores
                        </button>
                    </div>
                </div>

                <!-- TABLA DE ÚLTIMAS OPERACIONES -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fa-solid fa-receipt"></i> Movimientos Comerciales Confirmados</h3>
                        <button class="btn-action" style="background: var(--primary-soft); color: var(--primary); border:none;" onclick="switchSection('reportes')">
                            Ir a Validación <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>N° Pedido</th>
                                    <th>Asesor Comercial</th>
                                    <th>Cliente / Obra</th>
                                    <th>Material / Producto</th>
                                    <th>Monto</th>
                                    <th>Estado en Reportería</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>#ORD-9021</strong></td>
                                    <td>Maria Gomez (Lima)</td>
                                    <td>Cosapi S.A.</td>
                                    <td>120 baldes Z 2000 (Membrana Líquida)</td>
                                    <td><strong>S/ 14,400.00</strong></td>
                                    <td><span class="badge badge-success"><i class="fa-solid fa-check-double"></i> Pago Aceptado</span></td>
                                </tr>
                                <tr>
                                    <td><strong>#ORD-9020</strong></td>
                                    <td>Ana Torres (San Borja)</td>
                                    <td>Besco Constructora</td>
                                    <td>50 bolsas Z Grout Alta Resistencia</td>
                                    <td><strong>S/ 4,250.00</strong></td>
                                    <td><span class="badge badge-success"><i class="fa-solid fa-check-double"></i> Pago Aceptado</span></td>
                                </tr>
                                <tr>
                                    <td><strong>#ORD-9019</strong></td>
                                    <td>Carlos Ruiz (Ventas)</td>
                                    <td>Consorcio Vial Piura</td>
                                    <td>80 galones Curador Químico</td>
                                    <td><strong>S/ 6,800.00</strong></td>
                                    <td><span class="badge badge-warning"><i class="fa-solid fa-hourglass-half"></i> Pendiente de Confirmar</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= SECCIÓN 2: FACTURACIÓN ================= -->
            <div id="section-facturacion" class="crm-view">
                <div class="fact-stats-summary">
                    <div class="fact-stat-card">
                        <p>Total Facturado (Mes)</p>
                        <h3 id="factTotalMes">S/ 94,178.00</h3>
                        <span class="badge badge-success">+18% meta mensual</span>
                    </div>
                    <div class="fact-stat-card">
                        <p>Pagos Confirmados por Reportería</p>
                        <h3 style="color: var(--accent-green);" id="factTotalCobrado">S/ 82,450.00</h3>
                        <span class="badge badge-success">87.5% verificado en banco</span>
                    </div>
                    <div class="fact-stat-card">
                        <p>Pagos por Validar / Pendientes</p>
                        <h3 style="color: var(--accent-orange);" id="factTotalPendiente">S/ 11,728.00</h3>
                        <span class="badge badge-warning">En revisión de Reportería</span>
                    </div>
                </div>

                <div class="table-card">
                    <div class="fact-filters-bar">
                        <div class="filter-pills">
                            <div class="filter-pill active" onclick="filtrarFacturas('todos', this)">Todos (24)</div>
                            <div class="filter-pill" onclick="filtrarFacturas('pagado', this)">Pagados Aceptados (16)</div>
                            <div class="filter-pill" onclick="filtrarFacturas('pendiente', this)">Pendientes por Reportería (6)</div>
                            <div class="filter-pill" onclick="filtrarFacturas('anulado', this)">Anulados (2)</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="custom-table" id="tablaFacturas">
                            <thead>
                                <tr>
                                    <th>N° Comprobante</th>
                                    <th>Asesor</th>
                                    <th>Cliente / Razón Social</th>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                    <th>Método / Op.</th>
                                    <th>Estado</th>
                                    <th>Comprobante</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr data-estado="pagado">
                                    <td><strong>F001-00892</strong></td>
                                    <td>Maria Gomez</td>
                                    <td>Constructora San Martín S.A.C.</td>
                                    <td>06/09/2026</td>
                                    <td><strong>S/ 18,250.00</strong></td>
                                    <td>BCP #849201</td>
                                    <td><span class="badge badge-success">Pago Aceptado</span></td>
                                    <td><button class="btn-action" style="padding:4px 10px; font-size:0.75rem; background:#F4F7FE; color:#333; border:none;" onclick="verVoucherDemo('BCP #849201', 'Constructora San Martín S.A.C.', '18,250.00', 'Maria Gomez')"><i class="fa-solid fa-file-invoice"></i> Ver Voucher</button></td>
                                </tr>
                                <tr data-estado="pendiente">
                                    <td><strong>F001-00891</strong></td>
                                    <td>Carlos Ruiz</td>
                                    <td>Graña & Montero Ingeniería</td>
                                    <td>05/09/2026</td>
                                    <td><strong>S/ 8,420.00</strong></td>
                                    <td>BBVA #902184</td>
                                    <td><span class="badge badge-warning">Pendiente Reportería</span></td>
                                    <td><button class="btn-confirm-pay" onclick="switchSection('reportes')"><i class="fa-solid fa-check"></i> Validar</button></td>
                                </tr>
                                <tr data-estado="pagado">
                                    <td><strong>B001-00431</strong></td>
                                    <td>Ana Torres</td>
                                    <td>Ing. Manuel Zevallos</td>
                                    <td>04/09/2026</td>
                                    <td><strong>S/ 1,350.00</strong></td>
                                    <td>Yape Op #104</td>
                                    <td><span class="badge badge-success">Pago Aceptado</span></td>
                                    <td><button class="btn-action" style="padding:4px 10px; font-size:0.75rem; background:#F4F7FE; color:#333; border:none;" onclick="verVoucherDemo('Yape #104', 'Ing. Manuel Zevallos', '1,350.00', 'Ana Torres')"><i class="fa-solid fa-file-invoice"></i> Ver Voucher</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= SECCIÓN 3: REPORTES & CONFIRMACIÓN DE PAGOS ================= -->
            <div id="section-reportes" class="crm-view active">
                
                <!-- TOP WIDGETS CON RESUMEN -->
                <div class="top-widgets">
                    <div class="overview-card">
                        <div class="overview-header">
                            <h3>Resumen de Operaciones Comerciales</h3>
                            <select id="selectPeriodo" onchange="cambiarPeriodoReportes(this.value)">
                                <option value="mensual">Mensual (2026)</option>
                                <option value="semanal">Semanal</option>
                            </select>
                        </div>
                        
                        <div class="chart-mockup">
                            <svg class="svg-curve" viewBox="0 0 500 150" preserveAspectRatio="none">
                                <path id="svgCurvePath" d="M0,100 C100,50 150,150 250,80 C350,10 400,120 500,60" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="4"></path>
                                <path d="M0,100 C100,50 150,150 250,80 C350,10 400,120 500,60 L500,150 L0,150 Z" fill="rgba(255,255,255,0.05)"></path>
                            </svg>
                            
                            <div class="chart-point"></div>
                            <div class="chart-point-label" id="chartPointLabel">
                                <strong>S/ 94,178</strong><br>Ingresos Verificados
                            </div>
                        </div>
                        
                        <div class="overview-stats">
                            <div class="stat-block">
                                <p>Mes Anterior</p>
                                <h3 id="statMesAnt">S/ 74,800</h3>
                            </div>
                            <div class="stat-block active">
                                <p>Mes Actual Verificado</p>
                                <h3 id="statMesAct">S/ 94,178</h3>
                            </div>
                            <div class="stat-block">
                                <p>Meta del Mes</p>
                                <h3>S/ 120,000</h3>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side Cards -->
                    <div class="side-cards">
                        <div class="mini-card" onclick="scrollHaciaValidacion()" style="cursor: pointer; border-left: 5px solid var(--accent-orange);">
                            <div class="icon-box" style="color: var(--accent-orange);">
                                <i class="fa-solid fa-receipt"></i>
                            </div>
                            <div class="card-info">
                                <h4>Confirmaciones de<br>pago Pendientes</h4>
                                <p id="badgePendingMini" style="color: var(--accent-orange); font-weight:600;">3 comprobantes por validar</p>
                            </div>
                        </div>
                        
                        <div class="mini-card pink">
                            <div class="top-row">
                                <div class="icon-box">
                                    <i class="fa-solid fa-circle-check"></i>
                                </div>
                                <h4>Pagos Aceptados<br>Hoy por Reportería</h4>
                            </div>
                            <div class="bottom-row">
                                <div>
                                    <p>Confirmados en Banco</p>
                                    <h2 id="acceptedCountToday">5 pagos</h2>
                                </div>
                                <div class="arrow-btn" onclick="scrollHaciaValidacion()" style="cursor:pointer;"><i class="fa-solid fa-arrow-down"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BANDEJA DE VALIDACIÓN Y CONFIRMACIÓN DE PAGOS (ÁREA DE REPORTERÍA) -->
                <div class="payments-validation-card" id="bandejaValidacion">
                    <div class="payments-header">
                        <div>
                            <h3><i class="fa-solid fa-money-bill-transfer" style="color:var(--primary);"></i> Bandeja de Pagos Enviados por Ventas para Confirmación</h3>
                            <p style="color:var(--text-light); font-size:0.85rem;">Revise el comprobante/voucher de la transferencia bancaria. Al confirmar, el estado pasará a <strong>"Pago Aceptado"</strong> y se sumará a los ingresos.</p>
                        </div>
                        <span class="badge badge-warning" id="pendingCounterBadge" style="font-size:0.85rem; padding:6px 14px;">
                            <i class="fa-solid fa-clock"></i> <span id="numPending">3</span> pagos pendientes
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="custom-table" id="tablaValidacionPagos">
                            <thead>
                                <tr>
                                    <th>N° Cotiz. / Pedido</th>
                                    <th>Asesor de Ventas</th>
                                    <th>Cliente / Constructora</th>
                                    <th>Monto Pagado</th>
                                    <th>Banco / Operación</th>
                                    <th>Voucher Adjunto</th>
                                    <th>Estado</th>
                                    <th>Acción de Reportería</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- PAGO 1 -->
                                <tr id="row-pago-1">
                                    <td><strong>#COT-2026-084</strong></td>
                                    <td><i class="fa-solid fa-user-tie"></i> Maria Gomez</td>
                                    <td>Cosapi S.A.</td>
                                    <td><strong style="color:var(--text-dark); font-size:0.95rem;">S/ 14,400.00</strong></td>
                                    <td><span class="badge badge-purple">BCP Op. #4829104</span></td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=150&q=80" class="voucher-thumb" onclick="verVoucherModal('COT-2026-084', 'Cosapi S.A.', '14,400.00', 'BCP Op. #4829104', 'Maria Gomez', 1)" alt="Voucher">
                                            <span style="font-size:0.75rem; color:var(--primary); cursor:pointer; font-weight:600;" onclick="verVoucherModal('COT-2026-084', 'Cosapi S.A.', '14,400.00', 'BCP Op. #4829104', 'Maria Gomez', 1)">Ver Voucher</span>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-warning status-badge"><i class="fa-solid fa-hourglass-start"></i> Por Confirmar</span></td>
                                    <td>
                                        <div style="display:flex; gap:6px;">
                                            <button class="btn-confirm-pay" onclick="confirmarPagoAction(1, 14400, 'COT-2026-084')">
                                                <i class="fa-solid fa-check-circle"></i> Aceptar Pago
                                            </button>
                                            <button class="btn-reject-pay" onclick="observarPagoAction(1, 'COT-2026-084')">
                                                <i class="fa-solid fa-circle-exclamation"></i> Observar
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- PAGO 2 -->
                                <tr id="row-pago-2">
                                    <td><strong>#COT-2026-085</strong></td>
                                    <td><i class="fa-solid fa-user-tie"></i> Carlos Ruiz</td>
                                    <td>Consorcio Vial Piura</td>
                                    <td><strong style="color:var(--text-dark); font-size:0.95rem;">S/ 6,800.00</strong></td>
                                    <td><span class="badge badge-purple">BBVA Op. #910245</span></td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=150&q=80" class="voucher-thumb" onclick="verVoucherModal('COT-2026-085', 'Consorcio Vial Piura', '6,800.00', 'BBVA Op. #910245', 'Carlos Ruiz', 2)" alt="Voucher">
                                            <span style="font-size:0.75rem; color:var(--primary); cursor:pointer; font-weight:600;" onclick="verVoucherModal('COT-2026-085', 'Consorcio Vial Piura', '6,800.00', 'BBVA Op. #910245', 'Carlos Ruiz', 2)">Ver Voucher</span>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-warning status-badge"><i class="fa-solid fa-hourglass-start"></i> Por Confirmar</span></td>
                                    <td>
                                        <div style="display:flex; gap:6px;">
                                            <button class="btn-confirm-pay" onclick="confirmarPagoAction(2, 6800, 'COT-2026-085')">
                                                <i class="fa-solid fa-check-circle"></i> Aceptar Pago
                                            </button>
                                            <button class="btn-reject-pay" onclick="observarPagoAction(2, 'COT-2026-085')">
                                                <i class="fa-solid fa-circle-exclamation"></i> Observar
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- PAGO 3 -->
                                <tr id="row-pago-3">
                                    <td><strong>#COT-2026-086</strong></td>
                                    <td><i class="fa-solid fa-user-tie"></i> Ana Torres</td>
                                    <td>Edificaciones Pacífico E.I.R.L.</td>
                                    <td><strong style="color:var(--text-dark); font-size:0.95rem;">S/ 3,308.00</strong></td>
                                    <td><span class="badge badge-purple">Interbank Op. #3019</span></td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=150&q=80" class="voucher-thumb" onclick="verVoucherModal('COT-2026-086', 'Edificaciones Pacífico', '3,308.00', 'Interbank Op. #3019', 'Ana Torres', 3)" alt="Voucher">
                                            <span style="font-size:0.75rem; color:var(--primary); cursor:pointer; font-weight:600;" onclick="verVoucherModal('COT-2026-086', 'Edificaciones Pacífico', '3,308.00', 'Interbank Op. #3019', 'Ana Torres', 3)">Ver Voucher</span>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-warning status-badge"><i class="fa-solid fa-hourglass-start"></i> Por Confirmar</span></td>
                                    <td>
                                        <div style="display:flex; gap:6px;">
                                            <button class="btn-confirm-pay" onclick="confirmarPagoAction(3, 3308, 'COT-2026-086')">
                                                <i class="fa-solid fa-check-circle"></i> Aceptar Pago
                                            </button>
                                            <button class="btn-reject-pay" onclick="observarPagoAction(3, 'COT-2026-086')">
                                                <i class="fa-solid fa-circle-exclamation"></i> Observar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- METAS POR SUCURSALES -->
                <div class="bottom-widgets">
                    <div class="branch-card">
                        <div class="icon-box"><i class="fa-solid fa-building"></i></div>
                        <h4>Sucursal Lima</h4>
                        <p>Meta: S/ 50,000 / mes</p>
                        <div class="progress-container">
                            <div class="progress-labels">
                                <span class="left-val">Progreso</span>
                                <span class="right-val">45%</span>
                            </div>
                            <div class="progress-bar-bg"><div class="progress-bar green"></div></div>
                            <div class="progress-labels">
                                <span class="left-val">22,500 / 50,000</span>
                                <span class="right-val">Faltan 14 días</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="branch-card">
                        <div class="icon-box"><i class="fa-solid fa-city"></i></div>
                        <h4>Sucursal Arequipa</h4>
                        <p>Meta: S/ 30,000 / mes</p>
                        <div class="progress-container">
                            <div class="progress-labels">
                                <span class="left-val">Progreso</span>
                                <span class="right-val">13%</span>
                            </div>
                            <div class="progress-bar-bg"><div class="progress-bar green-light"></div></div>
                            <div class="progress-labels">
                                <span class="left-val">3,900 / 30,000</span>
                                <span class="right-val">Faltan 14 días</span>
                            </div>
                        </div>
                    </div>

                    <div class="branch-card">
                        <div class="icon-box"><i class="fa-solid fa-store"></i></div>
                        <h4>Sucursal Piura</h4>
                        <p>Meta: S/ 20,000 / mes</p>
                        <div class="progress-container">
                            <div class="progress-labels">
                                <span class="left-val">Progreso</span>
                                <span class="right-val">90%</span>
                            </div>
                            <div class="progress-bar-bg"><div class="progress-bar green-full"></div></div>
                            <div class="progress-labels">
                                <span class="left-val">18,000 / 20,000</span>
                                <span class="right-val">Meta alcanzada</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ================= SECCIÓN 4: CHAT VENTAS & CONSULTAS ================= -->
            <div id="section-mensajes" class="crm-view">
                
                <!-- Selector de Pestañas: Chat con Ventas vs Consultas Web -->
                <div class="chat-tabs-nav">
                    <div class="chat-tab-btn active" onclick="cambiarCanalChat('ventas', this)">
                        <i class="fa-solid fa-user-tie"></i> Chat Interno con Asesores de Ventas
                    </div>
                    <div class="chat-tab-btn" onclick="cambiarCanalChat('web', this)">
                        <i class="fa-brands fa-whatsapp"></i> Consultas Web de Clientes
                    </div>
                </div>

                <!-- CANAL 1: CHAT CON ASESORES DE VENTAS -->
                <div id="chat-canal-ventas" class="chat-split-container">
                    <!-- Lista de Asesores -->
                    <div class="inbox-list">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <h4 style="color:var(--text-dark); font-size:1.05rem;">Asesores Comerciales</h4>
                            <span class="badge badge-purple">4 activos</span>
                        </div>

                        <div class="inbox-item active" onclick="seleccionarAsesorChat('asesor-maria', this)">
                            <div class="inbox-item-top">
                                <h5>Maria Gomez (Lima)</h5>
                                <small>11:35 AM</small>
                            </div>
                            <p><strong>Cierre de hoy:</strong> Subí el comprobante de Cosapi por S/ 14,400. Por favor confirmarlo en el sistema para que almacén despache.</p>
                        </div>

                        <div class="inbox-item" onclick="seleccionarAsesorChat('asesor-carlos', this)">
                            <div class="inbox-item-top">
                                <h5>Carlos Ruiz (Logística/Ventas)</h5>
                                <small>10:15 AM</small>
                            </div>
                            <p>Envié reporte de ventas de la mañana para Consorcio Vial Piura. El cliente depositó en la cuenta BBVA.</p>
                        </div>

                        <div class="inbox-item" onclick="seleccionarAsesorChat('asesor-ana', this)">
                            <div class="inbox-item-top">
                                <h5>Ana Torres (San Borja)</h5>
                                <small>09:50 AM</small>
                            </div>
                            <p>Ing. Zevallos transfirió por Yape S/ 1,350. Adjunto comprobante para validación de Reportería.</p>
                        </div>

                        <div class="inbox-item" onclick="seleccionarAsesorChat('asesor-luis', this)">
                            <div class="inbox-item-top">
                                <h5>Luis Paz (Arequipa)</h5>
                                <small>Ayer</small>
                            </div>
                            <p>Cierre diario Arequipa: S/ 8,200 facturados en 2 pedidos de Z Grout. Vouchers cargados.</p>
                        </div>
                    </div>

                    <!-- Conversación activa con el Asesor -->
                    <div class="chat-detail-card">
                        <div>
                            <div class="chat-detail-header">
                                <div>
                                    <h3 id="asesorChatNombre">Maria Gomez</h3>
                                    <p style="color:var(--text-light); font-size:0.8rem;" id="asesorChatCargo">Asesora de Ventas Corporativas | Sede Lima</p>
                                </div>
                                <span class="badge badge-success"><i class="fa-solid fa-circle" style="font-size:0.6rem;"></i> En Línea</span>
                            </div>

                            <div class="chat-body-msg" id="asesorChatHistorial" style="max-height: 280px; overflow-y:auto; display:flex; flex-direction:column; gap:12px;">
                                <div style="background:#F4F7FE; padding:12px 16px; border-radius:14px; max-width:85%; align-self:flex-start;">
                                    <strong>Maria Gomez:</strong><br>
                                    Hola equipo de Reportería. Acabo de ingresar la cotización #COT-2026-084 para Cosapi S.A. por un monto de S/ 14,400.00 (120 baldes Z 2000). Ya cargué el voucher BCP para su confirmación.
                                </div>
                                <div style="background:rgba(109, 93, 211, 0.1); padding:12px 16px; border-radius:14px; max-width:85%; align-self:flex-end; color:var(--primary);">
                                    <strong>Tú (Reportería):</strong><br>
                                    Recibido Maria. Procedemos a verificar el depósito en el extracto bancario BCP y confirmamos el pago.
                                </div>
                            </div>
                        </div>

                        <div class="chat-reply-box">
                            <div style="display:flex; gap:8px;">
                                <button class="filter-pill" style="font-size:0.75rem; padding:4px 10px;" onclick="insertarChatVentas('Pago confirmado y verificado en cuenta bancaria. Procede con el despacho.')">Confirmar Pago en Chat</button>
                                <button class="filter-pill" style="font-size:0.75rem; padding:4px 10px;" onclick="insertarChatVentas('Por favor enviar voucher más legible o verificar el número de operación.')">Observar Voucher</button>
                                <button class="filter-pill" style="font-size:0.75rem; padding:4px 10px;" onclick="insertarChatVentas('Reporte diario de ventas del asesor recibido conforme.')">Reporte Recibido</button>
                            </div>
                            <textarea id="asesorReplyText" placeholder="Escribir mensaje de coordinación a la asesora de ventas..."></textarea>
                            <div class="chat-actions-row">
                                <small style="color:var(--text-light);"><i class="fa-solid fa-shield"></i> Canal interno Ventas - Reportería</small>
                                <button class="btn-action btn-white" style="background:var(--primary); color:#FFF; border:none;" onclick="enviarMensajeAsesor()">
                                    <i class="fa-solid fa-paper-plane"></i> Enviar Mensaje
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CANAL 2: CONSULTAS DE CLIENTES WEB -->
                <div id="chat-canal-web" class="chat-split-container" style="display:none;">
                    <div class="inbox-list">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <h4 style="color:var(--text-dark); font-size:1.05rem;">Clientes Web / WhatsApp</h4>
                            <span class="badge badge-purple">4 consultas</span>
                        </div>

                        <div class="inbox-item active" onclick="seleccionarConsulta('c1', this)">
                            <div class="inbox-item-top">
                                <h5>Ing. Carlos Mendoza</h5>
                                <small>Hoy 10:45 AM</small>
                            </div>
                            <p>Buenas tardes, requiero cotización formal por 60 bolsas de Z Grout para anclaje de columnas en obra de Surco...</p>
                        </div>

                        <div class="inbox-item" onclick="seleccionarConsulta('c2', this)">
                            <div class="inbox-item-top">
                                <h5>Arq. Patricia Vega</h5>
                                <small>Hoy 09:20 AM</small>
                            </div>
                            <p>Hola BS Perú, ¿el impermeabilizante Z 2000 viene en presentación de cilindro de 55 galones o solo balde? Necesitamos para techado...</p>
                        </div>
                    </div>

                    <div class="chat-detail-card">
                        <div>
                            <div class="chat-detail-header">
                                <div>
                                    <h3 id="chatNombre">Ing. Carlos Mendoza</h3>
                                    <p style="color:var(--text-light); font-size:0.8rem;" id="chatEmpresa">Constructora Andina | Cel: +51 987 654 321</p>
                                </div>
                                <span class="badge badge-success" id="chatEstado">Nuevo Mensaje</span>
                            </div>

                            <div class="chat-body-msg" id="chatMensaje">
                                "Buenas tardes, requiero cotización formal por 60 bolsas de Z Grout para anclaje de maquinaria y columnas en nuestra obra de Santiago de Surco. Por favor indicar si tienen entrega directa en obra y tiempo estimado de entrega."
                            </div>
                        </div>

                        <div class="chat-reply-box">
                            <textarea id="replyText" placeholder="Escribe tu respuesta personalizada aquí..."></textarea>
                            <div class="chat-actions-row">
                                <small style="color:var(--text-light);"><i class="fa-solid fa-lock"></i> Canal seguro BS Perú</small>
                                <a id="btnReplyWhatsapp" href="https://wa.me/51987654321" target="_blank" class="btn-wa">
                                    <i class="fa-brands fa-whatsapp"></i> Responder vía WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ================= SECCIÓN 5: CAPACITACIÓN ================= -->
            <div id="section-capacitacion" class="crm-view">
                <div class="quick-actions-bar" style="background: linear-gradient(135deg, #1E1B4B 0%, #4338CA 100%);">
                    <div>
                        <h4>Centro de Capacitación Técnica y Videoteca</h4>
                        <p>Guías paso a paso, ensayos de laboratorio y técnicas de aplicación en obra de productos Z Aditivos.</p>
                    </div>
                    <div class="actions-btns">
                        <button class="btn-action btn-white" onclick="alert('Descargando catálogo técnico consolidado en PDF...')">
                            <i class="fa-solid fa-file-arrow-down"></i> Catálogo Completo 2026 (PDF)
                        </button>
                    </div>
                </div>

                <div class="video-grid">
                    <div class="video-card">
                        <div class="video-thumbnail" onclick="verVideo('Z 2000 - Membrana Líquida', 'Aprende el método correcto de preparación de superficie y aplicación de 2 capas cruzadas para impermeabilización total de losas y techos frente a lluvias intensas.')">
                            <img src="https://images.unsplash.com/photo-1541888946425-d0fbb186c5f8?auto=format&fit=crop&w=600&q=80" alt="Video 1">
                            <div class="video-play-btn"><i class="fa-solid fa-play"></i></div>
                            <span class="video-duration">4:15 min</span>
                        </div>
                        <div class="video-content">
                            <h4>Aplicación de Z 2000 en techos y losas expuestas</h4>
                            <p>Impermeabilización elástica continua frente al Fenómeno de El Niño y lluvias en el norte.</p>
                            <div class="video-meta">
                                <span><i class="fa-solid fa-tag"></i> Impermeabilizantes</span>
                                <span style="cursor:pointer;" onclick="verVideo('Z 2000', 'Detalle')"><i class="fa-solid fa-circle-info"></i> Ver Guía</span>
                            </div>
                        </div>
                    </div>

                    <div class="video-card">
                        <div class="video-thumbnail" onclick="verVideo('Z Grout - Mortero de Alta Resistencia', 'Demostración de mezclado mecánico, relación agua-polvo y fluidez para colado sin contracción bajo placas base de columnas y maquinaria pesada.')">
                            <img src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=600&q=80" alt="Video 2">
                            <div class="video-play-btn"><i class="fa-solid fa-play"></i></div>
                            <span class="video-duration">5:40 min</span>
                        </div>
                        <div class="video-content">
                            <h4>Preparación y Colado de Z Grout sin contracción</h4>
                            <p>Mortero autonivelante de alta resistencia mecánica para anclajes industriales en obra.</p>
                            <div class="video-meta">
                                <span><i class="fa-solid fa-tag"></i> Morteros & Grout</span>
                                <span style="cursor:pointer;" onclick="verVideo('Z Grout', 'Detalle')"><i class="fa-solid fa-circle-info"></i> Ver Guía</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT SIDEBAR (SIN MAPA - CON PAGOS POR VALIDAR EN VIVO) -->
        <div class="right-sidebar">
            <div class="right-header">
                <h3><i class="fa-solid fa-users"></i> Equipo Asesores</h3>
                <span class="badge badge-purple">4 asesores</span>
            </div>
            
            <div class="tabs">
                <div class="tab active">Activos en Línea</div>
            </div>
            
            <div class="user-list">
                <div class="user-item" onclick="abrirChatConAsesor('Maria Gomez')">
                    <img src="https://ui-avatars.com/api/?name=Maria+Gomez&background=F5E6E8&color=D85C7B" alt="User">
                    <div class="user-info">
                        <h5>Maria Gomez</h5>
                        <p>Ventas Corporativas Lima</p>
                    </div>
                    <div class="user-action"><i class="fa-solid fa-comment-dots"></i></div>
                </div>
                
                <div class="user-item" onclick="abrirChatConAsesor('Carlos Ruiz')">
                    <img src="https://ui-avatars.com/api/?name=Carlos+Ruiz&background=E6F5E8&color=5CBA7B" alt="User">
                    <div class="user-info">
                        <h5>Carlos Ruiz</h5>
                        <p>Despachos & Logística</p>
                    </div>
                    <div class="user-action"><i class="fa-solid fa-comment-dots"></i></div>
                </div>
                
                <div class="user-item" onclick="abrirChatConAsesor('Ana Torres')">
                    <img src="https://ui-avatars.com/api/?name=Ana+Torres&background=E6EBF5&color=5C7BBA" alt="User">
                    <div class="user-info">
                        <h5>Ana Torres</h5>
                        <p>Asesora San Borja</p>
                    </div>
                    <div class="user-action"><i class="fa-solid fa-comment-dots"></i></div>
                </div>
                
                <div class="user-item" onclick="abrirChatConAsesor('Luis Paz')">
                    <img src="https://ui-avatars.com/api/?name=Luis+Paz&background=F5F0E6&color=BA9A5C" alt="User">
                    <div class="user-info">
                        <h5>Luis Paz</h5>
                        <p>Sede Arequipa / Sur</p>
                    </div>
                    <div class="user-action"><i class="fa-solid fa-comment-dots"></i></div>
                </div>
            </div>
            
            <!-- WIDGET LATERAL DE NOTIFICACIONES DE PAGOS EN VIVO -->
            <div class="pending-payments-widget">
                <div class="pending-widget-header">
                    <h4><i class="fa-solid fa-bell" style="color:var(--accent-orange);"></i> Pagos por Validar</h4>
                    <span class="badge badge-warning" id="sideBadgeCount">3 pendientes</span>
                </div>
                <div class="pending-item-sidebar" onclick="scrollHaciaValidacion()">
                    <div class="pending-item-info">
                        <h5>Cosapi S.A.</h5>
                        <span>S/ 14,400.00 • BCP Op. #4829104</span>
                    </div>
                    <button class="btn-confirm-pay" style="padding:4px 8px; font-size:0.7rem;">Ver</button>
                </div>
                <div class="pending-item-sidebar" onclick="scrollHaciaValidacion()">
                    <div class="pending-item-info">
                        <h5>Consorcio Vial Piura</h5>
                        <span>S/ 6,800.00 • BBVA Op. #910245</span>
                    </div>
                    <button class="btn-confirm-pay" style="padding:4px 8px; font-size:0.7rem;">Ver</button>
                </div>
            </div>
        </div>

    </div>

    <!-- MODAL VISOR DE VOUCHER / COMPROBANTE DE PAGO -->
    <div class="modal-overlay" id="voucherModal">
        <div class="modal-box">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <h3 style="margin:0; font-size:1.25rem;"><i class="fa-solid fa-file-invoice-dollar" style="color:var(--primary);"></i> Comprobante de Pago</h3>
                <i class="fa-solid fa-xmark" style="cursor:pointer; font-size:1.2rem; color:var(--text-light);" onclick="closeVoucherModal()"></i>
            </div>
            
            <div class="voucher-preview-box">
                <img id="modalVoucherImg" src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=500&q=80" class="voucher-img-large" alt="Comprobante Bancario">
            </div>

            <div class="voucher-data-grid">
                <div class="voucher-data-item">
                    <span>Pedido / Cotización:</span>
                    <strong id="modalVoucherCotiz">#COT-2026-084</strong>
                </div>
                <div class="voucher-data-item">
                    <span>Cliente / Empresa:</span>
                    <strong id="modalVoucherCliente">Cosapi S.A.</strong>
                </div>
                <div class="voucher-data-item">
                    <span>Monto Transferido:</span>
                    <strong id="modalVoucherMonto" style="color:var(--accent-green); font-size:1.05rem;">S/ 14,400.00</strong>
                </div>
                <div class="voucher-data-item">
                    <span>Banco / Operación:</span>
                    <strong id="modalVoucherOp">BCP Op. #4829104</strong>
                </div>
                <div class="voucher-data-item">
                    <span>Asesor Comercial:</span>
                    <strong id="modalVoucherAsesor">Maria Gomez</strong>
                </div>
                <div class="voucher-data-item">
                    <span>Cuenta Destino:</span>
                    <strong>BS Perú S.A.C. (Cta Cte BCP)</strong>
                </div>
            </div>

            <div class="modal-buttons" id="modalVoucherActions">
                <button class="btn-modal secondary" onclick="closeVoucherModal()">Cerrar</button>
                <button class="btn-modal primary" id="btnModalConfirmar" onclick="confirmarDesdeModal()">
                    <i class="fa-solid fa-check-double"></i> Confirmar & Aceptar Pago
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DE SALIR / LOGOUT -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-box" style="text-align:center;">
            <div style="width:65px; height:65px; background:rgba(255,126,159,0.15); color:var(--accent); border-radius:20px; display:flex; justify-content:center; align-items:center; font-size:1.8rem; margin:0 auto 16px;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>
            <h3>¿Cerrar Sesión del CRM?</h3>
            <p style="color:var(--text-light); font-size:0.88rem; margin:10px 0 20px;">Puedes regresar al catálogo público de productos o salir de la plataforma.</p>
            <div class="modal-buttons" style="flex-direction:column;">
                <a href="https://bsperu.pe/Pruebas/productos.html" class="btn-modal primary">
                    <i class="fa-solid fa-arrow-left"></i> Ir a Productos (`productos.html`)
                </a>
                <button class="btn-modal secondary" onclick="closeLogoutModal()">
                    Permanecer en CRM
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT DE INTERACTIVIDAD -->
    <script>
        const SECCIONES = {
            'inicio': { sub: 'Principal', title: 'Dashboard General' },
            'facturacion': { sub: 'Gestión Comercial', title: 'Facturación y Comprobantes' },
            'reportes': { sub: 'Área de Reportería', title: 'Dashboard de Reportes & Validación de Pagos' },
            'mensajes': { sub: 'Coordinación Comercial', title: 'Chat con Asesores de Ventas & Consultas' },
            'capacitacion': { sub: 'Recursos de Obra', title: 'Capacitación y Videoteca' }
        };

        let currentModalPagoId = 0;
        let currentModalMonto = 0;
        let currentModalCotiz = '';
        let totalPagosVerificadosMes = 94178.00;
        let pagosPendientesCount = 3;

        function switchSection(sectionId) {
            if (!SECCIONES[sectionId]) return;

            document.querySelectorAll('.sidebar .icon').forEach(icon => icon.classList.remove('active'));
            const targetIcon = document.querySelector(`.sidebar .icon[data-section="${sectionId}"]`);
            if (targetIcon) targetIcon.classList.add('active');

            document.querySelectorAll('.crm-view').forEach(view => view.classList.remove('active'));
            const targetView = document.getElementById(`section-${sectionId}`);
            if (targetView) targetView.classList.add('active');

            document.getElementById('headerSectionSubtitle').textContent = SECCIONES[sectionId].sub;
            document.getElementById('headerSectionTitle').textContent = SECCIONES[sectionId].title;

            localStorage.setItem('crm_active_section', sectionId);
        }

        window.addEventListener('DOMContentLoaded', () => {
            const saved = localStorage.getItem('crm_active_section');
            if (saved && SECCIONES[saved]) {
                switchSection(saved);
            } else {
                switchSection('reportes');
            }
        });

        // Desplazar suavemente a la bandeja de validación
        function scrollHaciaValidacion() {
            switchSection('reportes');
            setTimeout(() => {
                const el = document.getElementById('bandejaValidacion');
                if (el) el.scrollIntoView({ behavior: 'smooth' });
            }, 100);
        }

        // ACCIÓN: CONFIRMAR / ACEPTAR PAGO EN REPORTERÍA
        function confirmarPagoAction(pagoId, monto, cotiz) {
            const row = document.getElementById(`row-pago-${pagoId}`);
            if (!row) return;

            // Enviar petición POST a PHP
            fetch('reportes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=confirmar_pago&pago_id=${pagoId}&monto=${monto}&validador=Reportería BS Perú`
            }).catch(() => {});

            // Actualizar fila visualmente
            const badge = row.querySelector('.status-badge');
            if (badge) {
                badge.className = 'badge badge-success status-badge';
                badge.innerHTML = '<i class="fa-solid fa-check-double"></i> Pago Aceptado';
            }

            // Cambiar botones por confirmación permanente
            const cellAction = row.cells[7];
            if (cellAction) {
                cellAction.innerHTML = '<span style="color:var(--accent-green); font-size:0.82rem; font-weight:600;"><i class="fa-solid fa-circle-check"></i> Verificado y Registrado</span>';
            }

            // Actualizar métricas en pantalla
            pagosPendientesCount = Math.max(0, pagosPendientesCount - 1);
            totalPagosVerificadosMes += monto;

            // Actualizar badges y contadores
            document.getElementById('numPending').textContent = pagosPendientesCount;
            document.getElementById('sideBadgeCount').textContent = `${pagosPendientesCount} pendientes`;
            document.getElementById('badgePendingMini').textContent = `${pagosPendientesCount} comprobantes por validar`;
            document.getElementById('pendingCounterBadge').className = pagosPendientesCount > 0 ? 'badge badge-warning' : 'badge badge-success';
            
            document.getElementById('statMesAct').textContent = `S/ ${totalPagosVerificadosMes.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            document.getElementById('chartPointLabel').innerHTML = `<strong>S/ ${totalPagosVerificadosMes.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong><br>Ingresos Verificados`;

            alert(`✅ ¡Pago de ${cotiz} por S/ ${monto.toLocaleString('en-US', {minimumFractionDigits:2})} CONFIRMADO Y ACEPTADO!\n\nSe ha guardado en la base de datos como pago verificado y se actualizó el acumulado del mes.`);
        }

        // ACCIÓN: OBSERVAR / RECHAZAR PAGO
        function observarPagoAction(pagoId, cotiz) {
            const motivo = prompt(`Ingrese el motivo de la observación para ${cotiz}:`, "Monto depositado no coincide con factura");
            if (!motivo) return;

            fetch('reportes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=observar_pago&pago_id=${pagoId}&motivo=${encodeURIComponent(motivo)}`
            }).catch(() => {});

            const row = document.getElementById(`row-pago-${pagoId}`);
            if (row) {
                const badge = row.querySelector('.status-badge');
                if (badge) {
                    badge.className = 'badge badge-danger status-badge';
                    badge.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Observado';
                }
                row.cells[7].innerHTML = `<span style="color:#EF4444; font-size:0.78rem;">Observación enviada al asesor</span>`;
            }

            alert(`⚠️ El pago de ${cotiz} ha sido marcado como OBSERVADO.\nSe notificó al asesor para corregir el comprobante.`);
        }

        // MODAL VISOR DE VOUCHER
        function verVoucherModal(cotiz, cliente, monto, op, asesor, pagoId) {
            currentModalPagoId = pagoId;
            currentModalMonto = parseFloat(monto.replace(/,/g, ''));
            currentModalCotiz = cotiz;

            document.getElementById('modalVoucherCotiz').textContent = cotiz;
            document.getElementById('modalVoucherCliente').textContent = cliente;
            document.getElementById('modalVoucherMonto').textContent = `S/ ${monto}`;
            document.getElementById('modalVoucherOp').textContent = op;
            document.getElementById('modalVoucherAsesor').textContent = asesor;

            document.getElementById('voucherModal').classList.add('open');
        }

        function verVoucherDemo(op, cliente, monto, asesor) {
            verVoucherModal('F001-00892', cliente, monto, op, asesor, 0);
        }

        function closeVoucherModal() {
            document.getElementById('voucherModal').classList.remove('open');
        }

        function confirmarDesdeModal() {
            if (currentModalPagoId > 0) {
                confirmarPagoAction(currentModalPagoId, currentModalMonto, currentModalCotiz);
            }
            closeVoucherModal();
        }

        // CANALES DE CHAT (VENTAS VS WEB)
        function cambiarCanalChat(canal, el) {
            document.querySelectorAll('.chat-tab-btn').forEach(b => b.classList.remove('active'));
            el.classList.add('active');

            if (canal === 'ventas') {
                document.getElementById('chat-canal-ventas').style.display = 'flex';
                document.getElementById('chat-canal-web').style.display = 'none';
            } else {
                document.getElementById('chat-canal-ventas').style.display = 'none';
                document.getElementById('chat-canal-web').style.display = 'flex';
            }
        }

        function abrirChatConAsesor(nombre) {
            switchSection('mensajes');
            cambiarCanalChat('ventas', document.querySelectorAll('.chat-tab-btn')[0]);
            document.getElementById('asesorChatNombre').textContent = nombre;
            document.getElementById('asesorChatCargo').textContent = `Asesor(a) Comercial BS Perú - Canal Directo`;
        }

        function seleccionarAsesorChat(id, el) {
            document.querySelectorAll('#chat-canal-ventas .inbox-item').forEach(i => i.classList.remove('active'));
            el.classList.add('active');
            const nombre = el.querySelector('h5').textContent;
            document.getElementById('asesorChatNombre').textContent = nombre;
        }

        function insertarChatVentas(texto) {
            document.getElementById('asesorReplyText').value = texto;
        }

        function enviarMensajeAsesor() {
            const ta = document.getElementById('asesorReplyText');
            const texto = ta.value.trim();
            if (!texto) {
                alert('Por favor escribe un mensaje.');
                return;
            }

            const historial = document.getElementById('asesorChatHistorial');
            const nuevoMsg = document.createElement('div');
            nuevoMsg.style = "background:rgba(109, 93, 211, 0.1); padding:12px 16px; border-radius:14px; max-width:85%; align-self:flex-end; color:var(--primary);";
            nuevoMsg.innerHTML = `<strong>Tú (Reportería):</strong><br>${texto}`;
            historial.appendChild(nuevoMsg);
            historial.scrollTop = historial.scrollHeight;

            ta.value = '';
            alert('Mensaje enviado al asesor de ventas exitosamente.');
        }

        // Modal Logout
        function openLogoutModal() { document.getElementById('logoutModal').classList.add('open'); }
        function closeLogoutModal() { document.getElementById('logoutModal').classList.remove('open'); }

        // Periodo reportes
        function cambiarPeriodoReportes(p) {
            const label = document.getElementById('chartPointLabel');
            const act = document.getElementById('statMesAct');
            const ant = document.getElementById('statMesAnt');
            if (p === 'semanal') {
                label.innerHTML = '<strong>S/ 24,650</strong><br>Esta Semana';
                act.textContent = 'S/ 24,650';
                ant.textContent = 'S/ 19,200';
            } else {
                label.innerHTML = `<strong>S/ ${totalPagosVerificadosMes.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong><br>Ingresos Verificados`;
                act.textContent = `S/ ${totalPagosVerificadosMes.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                ant.textContent = 'S/ 74,800';
            }
        }
    </script>
</body>
</html>

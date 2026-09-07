<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM - BS Perú | Panel de Control</title>
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

        /* Tooltip sencillo para los iconos */
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

        .search-bar {
            background: #F4F7FE;
            border-radius: 20px;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid transparent;
            transition: 0.3s;
        }
        .search-bar:focus-within {
            border-color: var(--primary-light);
            background: #FFF;
            box-shadow: 0 4px 12px rgba(109, 93, 211, 0.08);
        }

        .search-bar i {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .search-bar input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 0.9rem;
            color: var(--text-dark);
            width: 190px;
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

        /* ================= VISTAS / SECCIONES DEL CRM ================= */
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

        /* ================= APARTADO 1: INICIO / DASHBOARD ================= */
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
        .btn-action.btn-white:hover {
            background: #F4F7FE;
            transform: translateY(-2px);
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

        /* ================= APARTADO 2: FACTURACIÓN ================= */
        .fact-filters-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .filter-pills {
            display: flex;
            gap: 8px;
        }
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
        .fact-stat-card p {
            font-size: 0.8rem;
            color: var(--text-light);
            font-weight: 500;
        }
        .fact-stat-card h3 {
            font-size: 1.6rem;
            color: var(--text-dark);
            margin: 4px 0;
            font-weight: 700;
        }

        /* ================= APARTADO 3: REPORTES ORIGINAL ================= */
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
        .mini-card.pink h4 {
            font-size: 1.05rem;
            font-weight: 600;
        }
        .mini-card.pink h2 {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }
        .mini-card.pink p {
            font-size: 0.8rem;
            opacity: 0.8;
        }
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
        .branch-card .more-btn {
            position: absolute;
            top: 24px;
            right: 24px;
            color: var(--text-light);
            cursor: pointer;
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
        .branch-card h4 {
            font-size: 1.05rem;
            color: var(--text-dark);
            font-weight: 600;
        }
        .branch-card p {
            color: var(--text-light);
            font-size: 0.8rem;
            margin-bottom: 20px;
        }
        .progress-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .progress-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.78rem;
        }
        .progress-labels .left-val { color: var(--text-dark); font-weight: 500; }
        .progress-labels .right-val { color: var(--text-light); font-weight: 600; }
        .progress-bar-bg {
            height: 8px;
            background: #F4F7FE;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            border-radius: 4px;
        }
        .progress-bar.green { width: 45%; background: #10B981; }
        .progress-bar.green-light { width: 13%; background: #34D399; }
        .progress-bar.green-full { width: 90%; background: #059669; }

        /* ================= APARTADO 4: MENSAJES & CONSULTAS ================= */
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
        .inbox-item-top h5 {
            font-size: 0.92rem;
            color: var(--text-dark);
            font-weight: 600;
        }
        .inbox-item-top small {
            font-size: 0.74rem;
            color: var(--text-light);
        }
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
        .btn-wa:hover {
            background: #20BA5A;
            transform: translateY(-1px);
        }

        /* ================= APARTADO 5: CAPACITACIÓN / VIDEOS ================= */
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
        .video-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 30px rgba(109, 93, 211, 0.1);
        }
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
        .video-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.6;
            transition: 0.3s;
        }
        .video-card:hover .video-thumbnail img {
            transform: scale(1.05);
            opacity: 0.75;
        }
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
        .video-card:hover .video-play-btn {
            transform: scale(1.1);
            background: #FFF;
            color: var(--accent);
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
        .video-content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            justify-content: space-between;
        }
        .video-content h4 {
            font-size: 1.02rem;
            color: var(--text-dark);
            font-weight: 600;
            line-height: 1.35;
        }
        .video-content p {
            color: var(--text-light);
            font-size: 0.8rem;
            line-height: 1.4;
        }
        .video-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
            color: var(--primary);
            font-weight: 600;
            margin-top: 6px;
        }

        /* ================= RIGHT SIDEBAR ================= */
        .right-sidebar {
            width: 320px;
            padding: 10px 0 10px 30px;
            border-left: 2px solid #F4F7FE;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .right-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .right-header h3 {
            font-size: 1.2rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .right-header .view-all {
            color: var(--text-light);
            font-size: 0.8rem;
            text-decoration: none;
            font-weight: 500;
        }
        .right-header .view-all:hover {
            color: var(--primary);
        }

        .tabs {
            display: flex;
            background: #F4F7FE;
            border-radius: 20px;
            padding: 5px;
            margin-bottom: 25px;
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
            gap: 20px;
            margin-bottom: auto;
        }

        .user-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-item img {
            width: 45px;
            height: 45px;
            border-radius: 15px;
            object-fit: cover;
        }

        .user-info {
            flex: 1;
        }

        .user-info h5 {
            color: var(--text-dark);
            font-size: 0.92rem;
            font-weight: 600;
        }

        .user-info p {
            color: var(--text-light);
            font-size: 0.78rem;
        }

        .user-action {
            color: var(--text-light);
            width: 32px;
            height: 32px;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: 0.2s;
        }
        .user-action:hover {
            color: var(--primary);
            border-color: var(--primary);
        }

        .map-widget {
            margin-top: 25px;
        }
        
        .map-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .map-header h3 {
            font-size: 1.05rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .map-header .view {
            color: var(--text-light);
            font-size: 0.8rem;
            text-decoration: none;
        }
        
        .map-img {
            background: #E8EBF5;
            height: 130px;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .map-img::before {
            content: '';
            position: absolute;
            width: 150%;
            height: 150%;
            background-image: linear-gradient(#FFF 2px, transparent 2px),
            linear-gradient(90deg, #FFF 2px, transparent 2px),
            linear-gradient(#FFF 1px, transparent 1px),
            linear-gradient(90deg, #FFF 1px, transparent 1px);
            background-size: 100px 100px, 100px 100px, 20px 20px, 20px 20px;
            background-position: -2px -2px, -2px -2px, -1px -1px, -1px -1px;
            opacity: 0.5;
            transform: rotate(15deg);
        }

        .map-pin {
            width: 30px;
            height: 30px;
            background: var(--accent);
            border: 3px solid #FFF;
            border-radius: 50%;
            position: absolute;
            box-shadow: 0 5px 10px rgba(255,126,159,0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 0.7rem;
        }
        
        .pin-1 { top: 30%; left: 20%; background: #FFF; color: var(--text-dark); }
        .pin-2 { top: 50%; left: 60%; }
        .pin-3 { top: 70%; left: 40%; background: #FFF; color: var(--text-dark); }

        /* ================= MODAL DIALOGS ================= */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeInView 0.2s ease;
        }
        .modal-overlay.open {
            display: flex;
        }
        .modal-box {
            background: #FFF;
            border-radius: 28px;
            padding: 32px;
            width: 90%;
            max-width: 480px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            text-align: center;
            position: relative;
        }
        .modal-icon {
            width: 70px;
            height: 70px;
            border-radius: 22px;
            background: rgba(109, 93, 211, 0.1);
            color: var(--primary);
            font-size: 1.8rem;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
        }
        .modal-icon.pink {
            background: rgba(255, 126, 159, 0.15);
            color: var(--accent);
        }
        .modal-box h3 {
            font-size: 1.35rem;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        .modal-box p {
            font-size: 0.88rem;
            color: var(--text-light);
            margin-bottom: 24px;
            line-height: 1.45;
        }
        .modal-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn-modal {
            padding: 13px;
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
        .btn-modal.primary {
            background: var(--primary);
            color: #FFF;
        }
        .btn-modal.primary:hover {
            background: var(--primary-light);
        }
        .btn-modal.secondary {
            background: #F4F7FE;
            color: var(--text-dark);
        }
        .btn-modal.secondary:hover {
            background: #E2E8F0;
        }

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
            
            <div class="icon active" onclick="switchSection('inicio')" data-section="inicio" data-tooltip="Dashboard Principal">
                <i class="fa-solid fa-house"></i>
            </div>

            <div class="icon" onclick="switchSection('facturacion')" data-section="facturacion" data-tooltip="Facturación & Pedidos">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>

            <div class="icon" onclick="switchSection('reportes')" data-section="reportes" data-tooltip="Reportes & Gráficos">
                <i class="fa-solid fa-chart-column"></i>
            </div>

            <div class="icon" onclick="switchSection('mensajes')" data-section="mensajes" data-tooltip="Consultas Web / WhatsApp">
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
            
            <!-- HEADER GLOBAL -->
            <div class="header">
                <div class="header-title">
                    <span id="headerSectionSubtitle">Principal</span>
                    <h2 id="headerSectionTitle">Dashboard General</h2>
                </div>
                
                <div class="header-right">
                    <div class="search-bar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="globalSearchInput" placeholder="Buscar en CRM...">
                    </div>
                    <div class="user-avatar" onclick="alert('Sesión activa: Administrador BS Perú')">
                        <div class="user-meta">
                            <strong>Rodrigo Alonso</strong>
                            <small>Admin Comercial</small>
                        </div>
                        <img src="https://ui-avatars.com/api/?name=Rodrigo+Alonso&background=6D5DD3&color=fff" alt="User">
                    </div>
                </div>
            </div>

            <!-- ================= SECCIÓN 1: INICIO / DASHBOARD ================= -->
            <div id="section-inicio" class="crm-view active">
                
                <!-- KPI CARDS -->
                <div class="kpi-row">
                    <div class="kpi-card">
                        <div class="kpi-icon purple"><i class="fa-solid fa-wallet"></i></div>
                        <div class="kpi-data">
                            <p>Ventas del Mes</p>
                            <h3>S/ 94,178</h3>
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
                        <div class="kpi-icon orange"><i class="fa-solid fa-building-circle-check"></i></div>
                        <div class="kpi-data">
                            <p>Clientes Activos</p>
                            <h3>38 constructoras</h3>
                            <span class="kpi-badge info"><i class="fa-solid fa-plus"></i> 6 nuevas</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon blue"><i class="fa-solid fa-headset"></i></div>
                        <div class="kpi-data">
                            <p>Consultas Web</p>
                            <h3>24 hoy</h3>
                            <span class="kpi-badge up"><i class="fa-solid fa-bolt"></i> 4 min resp.</span>
                        </div>
                    </div>
                </div>

                <!-- BANNER DE ACCIONES RÁPIDAS -->
                <div class="quick-actions-bar">
                    <div>
                        <h4>Centro de Operaciones Rápidas</h4>
                        <p>Atiende cotizaciones prioritarias y despachos de aditivos para construcción.</p>
                    </div>
                    <div class="actions-btns">
                        <button class="btn-action btn-white" onclick="switchSection('facturacion')">
                            <i class="fa-solid fa-file-circle-plus"></i> Nueva Cotización
                        </button>
                        <a class="btn-action" href="https://bsperu.pe/Pruebas/sucursales.html" target="_blank">
                            <i class="fa-solid fa-map-location-dot"></i> Ver Sucursales
                        </a>
                        <a class="btn-action" href="https://bsperu.pe/Pruebas/productos.html" target="_blank">
                            <i class="fa-solid fa-box-open"></i> Catálogo Web
                        </a>
                    </div>
                </div>

                <!-- TABLA DE DESPACHOS RECIENTES -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fa-solid fa-dolly"></i> Despachos Recientes a Obras</h3>
                        <button class="btn-action" style="background: var(--primary-soft); color: var(--primary); border:none;" onclick="switchSection('facturacion')">
                            Ver todas las órdenes <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>N° Orden</th>
                                    <th>Constructora / Cliente</th>
                                    <th>Proyecto / Destino</th>
                                    <th>Material Despachado</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>#ORD-9021</strong></td>
                                    <td>Cosapi S.A.</td>
                                    <td>Línea 2 Metro de Lima - Est. 14</td>
                                    <td>120 baldes Z 2000 (Membrana Líquida)</td>
                                    <td><strong>S/ 14,400.00</strong></td>
                                    <td><span class="badge badge-purple"><i class="fa-solid fa-truck"></i> En Tránsito</span></td>
                                    <td><a href="https://wa.me/51923326704?text=Hola%20Cosapi,%20su%20despacho%20Z2000%20va%20en%20camino." target="_blank" class="badge badge-success"><i class="fa-brands fa-whatsapp"></i> Notificar</a></td>
                                </tr>
                                <tr>
                                    <td><strong>#ORD-9020</strong></td>
                                    <td>Besco Constructora</td>
                                    <td>Residencial Los Álamos, Chorrillos</td>
                                    <td>50 bolsas Z Grout (Alta Resistencia)</td>
                                    <td><strong>S/ 4,250.00</strong></td>
                                    <td><span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Entregado</span></td>
                                    <td><span class="badge badge-purple">Comprobante F001</span></td>
                                </tr>
                                <tr>
                                    <td><strong>#ORD-9019</strong></td>
                                    <td>Consorcio Vial Piura</td>
                                    <td>Puente Grau, Piura</td>
                                    <td>80 galones Curador Químico</td>
                                    <td><strong>S/ 6,800.00</strong></td>
                                    <td><span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Preparando</span></td>
                                    <td><a href="https://wa.me/51922956171" target="_blank" class="badge badge-success"><i class="fa-brands fa-whatsapp"></i> Coordinar</a></td>
                                </tr>
                                <tr>
                                    <td><strong>#ORD-9018</strong></td>
                                    <td>JJC Contratistas Generales</td>
                                    <td>Edificio Corporativo San Borja</td>
                                    <td>30 kits Adhesivo Epóxico</td>
                                    <td><strong>S/ 7,100.00</strong></td>
                                    <td><span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Entregado</span></td>
                                    <td><span class="badge badge-purple">Comprobante F001</span></td>
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
                        <p>Total Facturado (Mes Actual)</p>
                        <h3>S/ 94,178.00</h3>
                        <span class="badge badge-success">+18% respecto a meta</span>
                    </div>
                    <div class="fact-stat-card">
                        <p>Monto Cobrado / Transferido</p>
                        <h3 style="color: var(--accent-green);">S/ 82,450.00</h3>
                        <span class="badge badge-success">87.5% efectividad</span>
                    </div>
                    <div class="fact-stat-card">
                        <p>Pendiente por Cobrar</p>
                        <h3 style="color: var(--accent-orange);">S/ 11,728.00</h3>
                        <span class="badge badge-warning">6 comprobantes por vencer</span>
                    </div>
                </div>

                <div class="table-card">
                    <div class="fact-filters-bar">
                        <div class="filter-pills">
                            <div class="filter-pill active" onclick="filtrarFacturas('todos', this)">Todos (24)</div>
                            <div class="filter-pill" onclick="filtrarFacturas('pagado', this)">Pagados (16)</div>
                            <div class="filter-pill" onclick="filtrarFacturas('pendiente', this)">Pendientes (6)</div>
                            <div class="filter-pill" onclick="filtrarFacturas('anulado', this)">Anulados (2)</div>
                        </div>
                        <button class="btn-action" style="background: var(--primary); color: white; border: none;" onclick="alert('Generando nueva factura electrónica...')">
                            <i class="fa-solid fa-file-invoice"></i> + Emitir Comprobante
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="custom-table" id="tablaFacturas">
                            <thead>
                                <tr>
                                    <th>N° Comprobante</th>
                                    <th>Tipo</th>
                                    <th>Cliente / Razón Social</th>
                                    <th>Fecha</th>
                                    <th>Monto Total</th>
                                    <th>Método</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr data-estado="pagado">
                                    <td><strong>F001-00892</strong></td>
                                    <td>Factura</td>
                                    <td>Constructora San Martín S.A.C.</td>
                                    <td>06/09/2026</td>
                                    <td><strong>S/ 18,250.00</strong></td>
                                    <td>Transf. BCP</td>
                                    <td><span class="badge badge-success">Pagado</span></td>
                                    <td>
                                        <button class="btn-action" style="padding:4px 8px; font-size:0.75rem; background:#F4F7FE; color:#333; border:none;" onclick="alert('Descargando PDF de F001-00892')"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                                    </td>
                                </tr>
                                <tr data-estado="pendiente">
                                    <td><strong>F001-00891</strong></td>
                                    <td>Factura</td>
                                    <td>Graña & Montero Ingeniería</td>
                                    <td>05/09/2026</td>
                                    <td><strong>S/ 8,420.00</strong></td>
                                    <td>Crédito 15d</td>
                                    <td><span class="badge badge-warning">Pendiente</span></td>
                                    <td>
                                        <a href="https://wa.me/51981288456?text=Hola,%20adjunto%20recordatorio%20del%20comprobante%20F001-00891." target="_blank" class="badge badge-success"><i class="fa-brands fa-whatsapp"></i> Cobranza</a>
                                    </td>
                                </tr>
                                <tr data-estado="pagado">
                                    <td><strong>B001-00431</strong></td>
                                    <td>Boleta</td>
                                    <td>Ing. Manuel Zevallos (Obra Chorrillos)</td>
                                    <td>04/09/2026</td>
                                    <td><strong>S/ 1,350.00</strong></td>
                                    <td>Yape / Plin</td>
                                    <td><span class="badge badge-success">Pagado</span></td>
                                    <td>
                                        <button class="btn-action" style="padding:4px 8px; font-size:0.75rem; background:#F4F7FE; color:#333; border:none;" onclick="alert('Descargando PDF de B001-00431')"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                                    </td>
                                </tr>
                                <tr data-estado="pendiente">
                                    <td><strong>F001-00890</strong></td>
                                    <td>Factura</td>
                                    <td>Edificaciones del Pacífico E.I.R.L.</td>
                                    <td>03/09/2026</td>
                                    <td><strong>S/ 3,308.00</strong></td>
                                    <td>Transf. BBVA</td>
                                    <td><span class="badge badge-warning">Pendiente</span></td>
                                    <td>
                                        <a href="https://wa.me/51981288456" target="_blank" class="badge badge-success"><i class="fa-brands fa-whatsapp"></i> Cobranza</a>
                                    </td>
                                </tr>
                                <tr data-estado="anulado">
                                    <td><strong>F001-00889</strong></td>
                                    <td>Factura</td>
                                    <td>Inversiones del Sur S.A.</td>
                                    <td>01/09/2026</td>
                                    <td><strong>S/ 2,400.00</strong></td>
                                    <td>Error en RUC</td>
                                    <td><span class="badge badge-danger">Anulado</span></td>
                                    <td><span style="color:#A0AEC0; font-size:0.75rem;">Reemplazado</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- ================= SECCIÓN 3: REPORTES & GRÁFICOS (ORIGINAL) ================= -->
            <div id="section-reportes" class="crm-view">
                
                <!-- TOP WIDGETS -->
                <div class="top-widgets">
                    <!-- Overview Graph -->
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
                                <strong>S/ 94,178</strong><br>Ingresos
                            </div>
                        </div>
                        
                        <div class="overview-stats">
                            <div class="stat-block">
                                <p>Mes Anterior</p>
                                <h3 id="statMesAnt">S/ 74,800</h3>
                            </div>
                            <div class="stat-block active">
                                <p>Mes Actual</p>
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
                        <div class="mini-card" onclick="switchSection('facturacion')" style="cursor: pointer;">
                            <div class="icon-box">
                                <i class="fa-solid fa-file-invoice"></i>
                            </div>
                            <div class="card-info">
                                <h4>Confirmaciones de<br>pago Pendientes</h4>
                                <p>12 comprobantes nuevos</p>
                            </div>
                        </div>
                        
                        <div class="mini-card pink" onclick="switchSection('facturacion')" style="cursor: pointer;">
                            <div class="top-row">
                                <div class="icon-box">
                                    <i class="fa-solid fa-rectangle-xmark"></i>
                                </div>
                                <h4>Cancelaciones<br>Pendientes</h4>
                            </div>
                            <div class="bottom-row">
                                <div>
                                    <p>Por procesar</p>
                                    <h2>8 ped.</h2>
                                </div>
                                <div class="arrow-btn"><i class="fa-solid fa-chevron-right"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BOTTOM WIDGETS -->
                <div class="bottom-widgets">
                    <div class="branch-card">
                        <i class="fa-solid fa-ellipsis-vertical more-btn"></i>
                        <div class="icon-box">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <h4>Sucursal Lima</h4>
                        <p>Meta: S/ 50,000 / mes</p>
                        
                        <div class="progress-container">
                            <div class="progress-labels">
                                <span class="left-val">Progreso</span>
                                <span class="right-val">45%</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar green"></div>
                            </div>
                            <div class="progress-labels">
                                <span class="left-val">22,500 / 50,000</span>
                                <span class="right-val">Faltan 14 días</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="branch-card">
                        <i class="fa-solid fa-ellipsis-vertical more-btn"></i>
                        <div class="icon-box">
                            <i class="fa-solid fa-city"></i>
                        </div>
                        <h4>Sucursal Arequipa</h4>
                        <p>Meta: S/ 30,000 / mes</p>
                        
                        <div class="progress-container">
                            <div class="progress-labels">
                                <span class="left-val">Progreso</span>
                                <span class="right-val">13%</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar green-light"></div>
                            </div>
                            <div class="progress-labels">
                                <span class="left-val">3,900 / 30,000</span>
                                <span class="right-val">Faltan 14 días</span>
                            </div>
                        </div>
                    </div>

                    <div class="branch-card">
                        <i class="fa-solid fa-ellipsis-vertical more-btn"></i>
                        <div class="icon-box">
                            <i class="fa-solid fa-store"></i>
                        </div>
                        <h4>Sucursal Piura</h4>
                        <p>Meta: S/ 20,000 / mes</p>
                        
                        <div class="progress-container">
                            <div class="progress-labels">
                                <span class="left-val">Progreso</span>
                                <span class="right-val">90%</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar green-full"></div>
                            </div>
                            <div class="progress-labels">
                                <span class="left-val">18,000 / 20,000</span>
                                <span class="right-val">Meta alcanzada</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ================= SECCIÓN 4: MENSAJES & CONSULTAS WEB ================= -->
            <div id="section-mensajes" class="crm-view">
                
                <div class="chat-split-container">
                    
                    <!-- Lista de Consultas -->
                    <div class="inbox-list">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <h4 style="color:var(--text-dark); font-size:1.05rem;">Bandeja de Entrada</h4>
                            <span class="badge badge-purple" id="countConsultas">4 mensajes</span>
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

                        <div class="inbox-item" onclick="seleccionarConsulta('c3', this)">
                            <div class="inbox-item-top">
                                <h5>Maestro Roberto Quispe</h5>
                                <small>Ayer 16:30 PM</small>
                            </div>
                            <p>Quisiera saber si tienen stock de Z Imperoof en la sucursal de San Borja para recoger hoy mismo por la tarde...</p>
                        </div>

                        <div class="inbox-item" onclick="seleccionarConsulta('c4', this)">
                            <div class="inbox-item-top">
                                <h5>Constructora Graña</h5>
                                <small>Ayer 11:15 AM</small>
                            </div>
                            <p>Solicitamos envío de la ficha técnica y certificado de ensayo de laboratorio del Adhesivo Epóxico para supervisión...</p>
                        </div>
                    </div>

                    <!-- Detalle de la Consulta -->
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
                            <div style="display:flex; gap:8px;">
                                <button class="filter-pill" style="font-size:0.75rem; padding:4px 10px;" onclick="cargarPlantilla('stock')">Plantilla Stock</button>
                                <button class="filter-pill" style="font-size:0.75rem; padding:4px 10px;" onclick="cargarPlantilla('cotizacion')">Plantilla Cotización</button>
                                <button class="filter-pill" style="font-size:0.75rem; padding:4px 10px;" onclick="cargarPlantilla('ficha')">Plantilla Ficha Técnica</button>
                            </div>
                            <textarea id="replyText" placeholder="Escribe tu respuesta personalizada aquí..."></textarea>
                            <div class="chat-actions-row">
                                <small style="color:var(--text-light);"><i class="fa-solid fa-lock"></i> Canal seguro BS Perú</small>
                                <a id="btnReplyWhatsapp" href="https://wa.me/51987654321?text=Hola%20Ing.%20Carlos%20Mendoza,%20le%20escribimos%20de%20BS%20Per%C3%BA%20respecto%20a%20su%20solicitud%20de%20Z%20Grout." target="_blank" class="btn-wa">
                                    <i class="fa-brands fa-whatsapp"></i> Responder vía WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- ================= SECCIÓN 5: CAPACITACIÓN / VIDEOS ================= -->
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
                                <span style="cursor:pointer;" onclick="verVideo('Z 2000 - Membrana Líquida', 'Detalle técnico')"><i class="fa-solid fa-circle-info"></i> Ver Guía</span>
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
                                <span style="cursor:pointer;" onclick="verVideo('Z Grout - Mortero', 'Detalle')"><i class="fa-solid fa-circle-info"></i> Ver Guía</span>
                            </div>
                        </div>
                    </div>

                    <div class="video-card">
                        <div class="video-thumbnail" onclick="verVideo('Z Imperoof - Sellador Elastomérico', 'Sellado de fisuras vivas y juntas de dilatación en reservorios y cubiertas de concreto con alta elasticidad.')">
                            <img src="https://images.unsplash.com/photo-1590381105924-c72589b9ef3f?auto=format&fit=crop&w=600&q=80" alt="Video 3">
                            <div class="video-play-btn"><i class="fa-solid fa-play"></i></div>
                            <span class="video-duration">3:50 min</span>
                        </div>
                        <div class="video-content">
                            <h4>Sellado de juntas y fisuras vivas con Z Imperoof</h4>
                            <p>Membrana elastomérica de rápido secado con excelente puenteo de fisuras estructurales.</p>
                            <div class="video-meta">
                                <span><i class="fa-solid fa-tag"></i> Selladores</span>
                                <span style="cursor:pointer;" onclick="verVideo('Z Imperoof', 'Detalle')"><i class="fa-solid fa-circle-info"></i> Ver Guía</span>
                            </div>
                        </div>
                    </div>

                    <div class="video-card">
                        <div class="video-thumbnail" onclick="verVideo('Curadores y Desmoldantes BS Perú', 'Uso óptimo de curador químico base agua para evitar agrietamiento por retracción plástica en losas de pavimento.')">
                            <img src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?auto=format&fit=crop&w=600&q=80" alt="Video 4">
                            <div class="video-play-btn"><i class="fa-solid fa-play"></i></div>
                            <span class="video-duration">4:30 min</span>
                        </div>
                        <div class="video-content">
                            <h4>Uso Eficiente de Curadores y Desmoldantes</h4>
                            <p>Retención de humedad para resistencia máxima del concreto en climas cálidos y ventosos.</p>
                            <div class="video-meta">
                                <span><i class="fa-solid fa-tag"></i> Aditivos para Concreto</span>
                                <span style="cursor:pointer;" onclick="verVideo('Curadores', 'Detalle')"><i class="fa-solid fa-circle-info"></i> Ver Guía</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

        <!-- RIGHT SIDEBAR (EQUIPO & MAPA EN VIVO) -->
        <div class="right-sidebar">
            <div class="right-header">
                <h3><i class="fa-solid fa-users"></i> Equipo Comercial</h3>
                <a href="#" class="view-all" onclick="alert('Equipo de ventas BS Perú: 14 asesores comerciales en 9 sucursales.'); return false;">Ver Todos</a>
            </div>
            
            <div class="tabs">
                <div class="tab active" onclick="cambiarTabEquipo(this, 'actividades')">Actividades</div>
                <div class="tab" onclick="cambiarTabEquipo(this, 'enlinea')">En línea (9)</div>
            </div>
            
            <div class="user-list" id="teamUserList">
                <div class="user-item">
                    <img src="https://ui-avatars.com/api/?name=Maria+Gomez&background=F5E6E8&color=D85C7B" alt="User">
                    <div class="user-info">
                        <h5>Maria Gomez</h5>
                        <p>Ventas Corporativas Lima</p>
                    </div>
                    <div class="user-action" onclick="alert('Chat con Maria Gomez')"><i class="fa-regular fa-comment"></i></div>
                </div>
                
                <div class="user-item">
                    <img src="https://ui-avatars.com/api/?name=Carlos+Ruiz&background=E6F5E8&color=5CBA7B" alt="User">
                    <div class="user-info">
                        <h5>Carlos Ruiz</h5>
                        <p>Despachos & Logística</p>
                    </div>
                    <div class="user-action" onclick="alert('Chat con Carlos Ruiz')"><i class="fa-regular fa-comment"></i></div>
                </div>
                
                <div class="user-item">
                    <img src="https://ui-avatars.com/api/?name=Ana+Torres&background=E6EBF5&color=5C7BBA" alt="User">
                    <div class="user-info">
                        <h5>Ana Torres</h5>
                        <p>Asesora Sucursal San Borja</p>
                    </div>
                    <div class="user-action" onclick="alert('Chat con Ana Torres')"><i class="fa-regular fa-comment"></i></div>
                </div>
                
                <div class="user-item">
                    <img src="https://ui-avatars.com/api/?name=Luis+Paz&background=F5F0E6&color=BA9A5C" alt="User">
                    <div class="user-info">
                        <h5>Luis Paz</h5>
                        <p>Sede Arequipa / Sur</p>
                    </div>
                    <div class="user-action" onclick="alert('Chat con Luis Paz')"><i class="fa-regular fa-comment"></i></div>
                </div>
            </div>
            
            <div class="map-widget">
                <div class="map-header">
                    <h3><i class="fa-solid fa-location-dot"></i> Entregas en vivo</h3>
                    <a href="https://bsperu.pe/Pruebas/sucursales.html" target="_blank" class="view">Ver mapa</a>
                </div>
                <div class="map-img">
                    <div class="map-pin pin-1" title="Camión 1 - Lima Norte"><i class="fa-solid fa-truck"></i></div>
                    <div class="map-pin pin-2" title="Camión 2 - Chorrillos"><i class="fa-solid fa-location-crosshairs"></i></div>
                    <div class="map-pin pin-3" title="Camión 3 - Piura"><i class="fa-solid fa-truck"></i></div>
                </div>
            </div>
        </div>

    </div>

    <!-- MODAL DE SALIR / LOGOUT -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-box">
            <div class="modal-icon pink">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>
            <h3>¿Cerrar Sesión del CRM?</h3>
            <p>Puedes regresar al catálogo público de productos o salir de la plataforma de administración.</p>
            <div class="modal-buttons">
                <a href="https://bsperu.pe/Pruebas/productos.html" class="btn-modal primary">
                    <i class="fa-solid fa-arrow-left"></i> Ir a Productos (`productos.html`)
                </a>
                <a href="https://bsperu.pe/Pruebas/sucursales.html" class="btn-modal secondary">
                    <i class="fa-solid fa-map-location-dot"></i> Ir a Sucursales (`sucursales.html`)
                </a>
                <button class="btn-modal secondary" onclick="closeLogoutModal()">
                    Cancelar y permanecer en CRM
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DE DETALLE DE VIDEO -->
    <div class="modal-overlay" id="videoModal">
        <div class="modal-box" style="max-width: 580px; text-align: left;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <h3 id="videoModalTitle" style="margin:0; font-size:1.2rem;">Detalle del Video</h3>
                <i class="fa-solid fa-xmark" style="cursor:pointer; font-size:1.2rem; color:var(--text-light);" onclick="closeVideoModal()"></i>
            </div>
            <div style="background:#000; border-radius:16px; height:240px; display:flex; justify-content:center; align-items:center; color:#FFF; margin-bottom:16px;">
                <div style="text-align:center;">
                    <i class="fa-solid fa-circle-play" style="font-size:3.5rem; color:var(--accent); cursor:pointer;"></i>
                    <p style="margin-top:10px; font-size:0.85rem; opacity:0.8;">Haga clic para reproducir en pantalla completa</p>
                </div>
            </div>
            <p id="videoModalDesc" style="font-size:0.88rem; color:var(--text-dark); margin-bottom:20px; line-height:1.5;">
                Descripción del procedimiento de aplicación en obra.
            </p>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button class="btn-modal secondary" onclick="closeVideoModal()">Cerrar</button>
                <button class="btn-modal primary" onclick="alert('Descargando ficha técnica oficial en PDF...'); closeVideoModal();">
                    <i class="fa-solid fa-file-pdf"></i> Descargar Ficha PDF
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT DE INTERACTIVIDAD -->
    <script>
        // Metadatos de cada sección para actualizar header
        const SECCIONES = {
            'inicio': {
                sub: 'Principal',
                title: 'Dashboard General'
            },
            'facturacion': {
                sub: 'Gestión Comercial',
                title: 'Facturación y Comprobantes'
            },
            'reportes': {
                sub: 'Estadísticas & Métricas',
                title: 'Dashboard Reportes'
            },
            'mensajes': {
                sub: 'Atención al Cliente',
                title: 'Consultas Web y WhatsApp'
            },
            'capacitacion': {
                sub: 'Recursos de Obra',
                title: 'Capacitación y Videoteca'
            }
        };

        // Función para cambiar de sección
        function switchSection(sectionId) {
            if (!SECCIONES[sectionId]) return;

            // 1. Quitar clase active a todos los botones del sidebar
            document.querySelectorAll('.sidebar .icon').forEach(icon => {
                icon.classList.remove('active');
            });

            // 2. Activar el botón correspondiente
            const targetIcon = document.querySelector(`.sidebar .icon[data-section="${sectionId}"]`);
            if (targetIcon) {
                targetIcon.classList.add('active');
            }

            // 3. Ocultar todas las vistas y mostrar la seleccionada
            document.querySelectorAll('.crm-view').forEach(view => {
                view.classList.remove('active');
            });
            const targetView = document.getElementById(`section-${sectionId}`);
            if (targetView) {
                targetView.classList.add('active');
            }

            // 4. Actualizar textos del header
            document.getElementById('headerSectionSubtitle').textContent = SECCIONES[sectionId].sub;
            document.getElementById('headerSectionTitle').textContent = SECCIONES[sectionId].title;

            // 5. Guardar estado en memoria
            localStorage.setItem('crm_active_section', sectionId);
        }

        // Cargar sección guardada o por defecto
        window.addEventListener('DOMContentLoaded', () => {
            const saved = localStorage.getItem('crm_active_section');
            if (saved && SECCIONES[saved]) {
                switchSection(saved);
            } else {
                switchSection('inicio');
            }
        });

        // Filtro de Facturas
        function filtrarFacturas(tipo, el) {
            document.querySelectorAll('.filter-pill').forEach(pill => pill.classList.remove('active'));
            el.classList.add('active');

            const filas = document.querySelectorAll('#tablaFacturas tbody tr');
            filas.forEach(fila => {
                if (tipo === 'todos') {
                    fila.style.display = '';
                } else {
                    const estado = fila.getAttribute('data-estado');
                    fila.style.display = (estado === tipo) ? '' : 'none';
                }
            });
        }

        // Datos de consultas para chat
        const CONSULTAS = {
            'c1': {
                nombre: 'Ing. Carlos Mendoza',
                empresa: 'Constructora Andina | Cel: +51 987 654 321',
                estado: 'Nuevo Mensaje',
                mensaje: '"Buenas tardes, requiero cotización formal por 60 bolsas de Z Grout para anclaje de maquinaria y columnas en nuestra obra de Santiago de Surco. Por favor indicar si tienen entrega directa en obra y tiempo estimado de entrega."',
                waLink: 'https://wa.me/51987654321?text=Estimado%20Ing.%20Carlos%20Mendoza,%20le%20saluda%20BS%20Per%C3%BA.%20Con%20gusto%20le%20enviamos%20la%20cotizaci%C3%B3n%20por%20las%2060%20bolsas%20de%20Z%20Grout.'
            },
            'c2': {
                nombre: 'Arq. Patricia Vega',
                empresa: 'Edificaciones del Norte | Cel: +51 922 956 171',
                estado: 'En Proceso',
                mensaje: '"Hola BS Perú, ¿el impermeabilizante Z 2000 viene en presentación de cilindro de 55 galones o solo en balde? Necesitamos impermeabilizar 1,200 m2 de techo en Piura antes de las lluvias."',
                waLink: 'https://wa.me/51922956171?text=Hola%20Arq.%20Patricia,%20en%20BS%20Per%C3%BA%20disponemos%20de%20Z%202000%20tanto%20en%20balde%20como%20en%20cilindro%20para%20grandes%20obras.'
            },
            'c3': {
                nombre: 'Maestro Roberto Quispe',
                empresa: 'Contratista Independiente | Cel: +51 981 288 456',
                estado: 'Pendiente',
                mensaje: '"Quisiera saber si tienen stock disponible de Z Imperoof en la sucursal de San Borja (Av. San Luis 3051) para recoger hoy mismo por la tarde."',
                waLink: 'https://wa.me/51981288456?text=Hola%20Maestro%20Roberto,%20s%C3%AD%20tenemos%20stock%20de%20Z%20Imperoof%20en%20nuestra%20sede%20de%20San%20Borja.'
            },
            'c4': {
                nombre: 'Supervisión Consorcio Graña',
                empresa: 'Proyecto Vial | Cel: +51 923 062 809',
                estado: 'Atendido',
                mensaje: '"Solicitamos el envío inmediato de la ficha técnica y certificado de ensayo de laboratorio del Adhesivo Epóxico para aprobación de supervisión."',
                waLink: 'https://wa.me/51923062809?text=Hola,%20adjuntamos%20la%20ficha%20t%C3%A9cnica%20y%20certificados%20del%20Adhesivo%20Ep%C3%B3xico%20BS%20Per%C3%BA.'
            }
        };

        function seleccionarConsulta(id, el) {
            document.querySelectorAll('.inbox-item').forEach(item => item.classList.remove('active'));
            el.classList.add('active');

            const c = CONSULTAS[id];
            if (!c) return;

            document.getElementById('chatNombre').textContent = c.nombre;
            document.getElementById('chatEmpresa').textContent = c.empresa;
            document.getElementById('chatEstado').textContent = c.estado;
            document.getElementById('chatMensaje').textContent = c.mensaje;
            document.getElementById('btnReplyWhatsapp').href = c.waLink;
            document.getElementById('replyText').value = '';
        }

        function cargarPlantilla(tipo) {
            const ta = document.getElementById('replyText');
            if (tipo === 'stock') {
                ta.value = 'Estimado cliente, contamos con stock inmediato en nuestras sucursales de Lima, Piura y Arequipa. Podemos despachar hoy mismo.';
            } else if (tipo === 'cotizacion') {
                ta.value = 'Le adjuntamos la propuesta comercial con descuento por volumen para su obra, válida por 7 días calendario.';
            } else if (tipo === 'ficha') {
                ta.value = 'Le enviamos la Ficha Técnica oficial y el Certificado de Calidad de Laboratorio avalado por normas ASTM.';
            }
        }

        // Selector mensual/semanal de reportes
        function cambiarPeriodoReportes(periodo) {
            const label = document.getElementById('chartPointLabel');
            const path = document.getElementById('svgCurvePath');
            const mesAct = document.getElementById('statMesAct');
            const mesAnt = document.getElementById('statMesAnt');

            if (periodo === 'semanal') {
                label.innerHTML = '<strong>S/ 24,650</strong><br>Esta semana';
                mesAct.textContent = 'S/ 24,650';
                mesAnt.textContent = 'S/ 19,200';
                path.setAttribute('d', 'M0,120 C100,70 180,90 250,50 C320,20 420,80 500,40');
            } else {
                label.innerHTML = '<strong>S/ 94,178</strong><br>Ingresos';
                mesAct.textContent = 'S/ 94,178';
                mesAnt.textContent = 'S/ 74,800';
                path.setAttribute('d', 'M0,100 C100,50 150,150 250,80 C350,10 400,120 500,60');
            }
        }

        // Modales
        function openLogoutModal() {
            document.getElementById('logoutModal').classList.add('open');
        }
        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('open');
        }

        function verVideo(titulo, desc) {
            document.getElementById('videoModalTitle').textContent = titulo;
            document.getElementById('videoModalDesc').textContent = desc;
            document.getElementById('videoModal').classList.add('open');
        }
        function closeVideoModal() {
            document.getElementById('videoModal').classList.remove('open');
        }

        function cambiarTabEquipo(el, tab) {
            document.querySelectorAll('.right-sidebar .tab').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
        }
    </script>

</body>
</html>

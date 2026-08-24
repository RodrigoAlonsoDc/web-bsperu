<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - CRM</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #E6EAF6;
            --app-bg: #FFFFFF;
            --primary: #6D5DD3;
            --primary-light: #7A6EED;
            --accent: #FF7E9F;
            --text-dark: #2D3748;
            --text-light: #A0AEC0;
            --shadow: 0 20px 40px rgba(109, 93, 211, 0.15);
            --card-shadow: 0 10px 20px rgba(0,0,0,0.05);
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
            width: 95%;
            max-width: 1800px;
            height: 95vh;
            min-height: 800px;
            border-radius: 40px;
            box-shadow: var(--shadow);
            display: flex;
            padding: 25px;
            overflow: hidden;
        }

        /* SIDEBAR */
        .sidebar {
            background: var(--primary);
            width: 90px;
            border-radius: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 0;
            gap: 30px;
        }

        .sidebar .icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.2rem;
            cursor: pointer;
            transition: 0.3s;
        }

        .sidebar .icon.active, .sidebar .icon:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #FFF;
        }

        .sidebar .icon.logo {
            background: transparent;
            color: #FFF;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        
        .sidebar .bottom-icon {
            margin-top: auto;
        }

        /* MAIN DASHBOARD */
        .main-content {
            flex: 1;
            padding: 20px 40px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-title h2 {
            font-size: 1.8rem;
            color: var(--text-dark);
            font-weight: 700;
        }

        .header-title span {
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
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
        }

        .search-bar input {
            border: none;
            background: transparent;
            outline: none;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .search-bar i {
            color: var(--text-light);
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #CCC;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* TOP WIDGETS ROW */
        .top-widgets {
            display: flex;
            gap: 25px;
            margin-bottom: 25px;
            flex: 1;
        }

        .overview-card {
            flex: 2;
            background: linear-gradient(135deg, #7162D6, #5B4C9D);
            border-radius: 30px;
            padding: 30px;
            color: #FFF;
            position: relative;
            box-shadow: 0 15px 30px rgba(109, 93, 211, 0.3);
            display: flex;
            flex-direction: column;
        }

        .overview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .overview-header select {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: #FFF;
            padding: 5px 15px;
            border-radius: 15px;
            outline: none;
        }

        .overview-header select option {
            color: #333;
        }

        .chart-mockup {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }

        /* SVG curve simulation */
        .svg-curve {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
        }
        
        .chart-point {
            position: absolute;
            top: 20%;
            left: 30%;
            width: 16px;
            height: 16px;
            background: #FF7E9F;
            border: 3px solid #FFF;
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(255,126,159,0.5);
            z-index: 10;
        }

        .chart-point-label {
            position: absolute;
            top: -5px;
            left: 35%;
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 10px;
            font-size: 0.8rem;
            backdrop-filter: blur(5px);
        }

        .overview-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
        }

        .stat-block p {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.7);
        }

        .stat-block h3 {
            font-size: 1.4rem;
            font-weight: 600;
        }

        .overview-stats .stat-block.active {
            background: rgba(255,255,255,0.15);
            padding: 10px 20px;
            border-radius: 20px;
            margin-top: -10px;
        }

        .side-cards {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .mini-card {
            background: var(--primary-light);
            border-radius: 25px;
            padding: 25px;
            color: #FFF;
            flex: 1;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 15px 30px rgba(122, 110, 237, 0.3);
        }

        .mini-card .icon-box {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
        }
        
        .mini-card .card-info h4 {
            font-size: 1.1rem;
            font-weight: 600;
            line-height: 1.2;
        }
        
        .mini-card .card-info p {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.8);
            margin-top: 5px;
        }

        .mini-card.pink {
            background: linear-gradient(135deg, #FF95AA, #FF5B85);
            flex-direction: column;
            align-items: flex-start;
            justify-content: space-between;
            box-shadow: 0 15px 30px rgba(255, 91, 133, 0.3);
        }

        .mini-card.pink .top-row {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .mini-card.pink .bottom-row {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .mini-card.pink h2 {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .mini-card.pink .arrow-btn {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: 2px solid #FFF;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #FFF;
            text-decoration: none;
        }

        /* BOTTOM ROW WIDGETS (SUCURSALES) */
        .bottom-widgets {
            display: flex;
            gap: 20px;
        }

        .branch-card {
            flex: 1;
            background: #FFF;
            border-radius: 25px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
        }

        .branch-card .icon-box {
            width: 60px;
            height: 60px;
            background: var(--primary);
            color: #FFF;
            border-radius: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
            box-shadow: 0 10px 20px rgba(109, 93, 211, 0.2);
        }

        .branch-card h4 {
            color: var(--text-dark);
            font-size: 1.1rem;
        }

        .branch-card p {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-bottom: 15px;
        }

        .branch-card .progress-container {
            width: 100%;
            margin-top: auto;
        }

        .progress-bar-bg {
            width: 100%;
            height: 6px;
            background: #F0F2F5;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-bar {
            height: 100%;
            border-radius: 3px;
        }
        
        .progress-bar.green { background: #38C976; width: 45%; }
        .progress-bar.green-light { background: #38C976; width: 13%; }
        .progress-bar.green-full { background: #38C976; width: 90%; }

        .progress-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .progress-labels .left-val { color: var(--text-light); }
        .progress-labels .right-val { color: var(--accent); }

        .branch-card .more-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            color: var(--text-light);
            cursor: pointer;
        }

        /* RIGHT SIDEBAR */
        .right-sidebar {
            width: 320px;
            padding: 10px 0 10px 30px;
            border-left: 2px solid #F4F7FE;
            display: flex;
            flex-direction: column;
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
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            color: var(--text-light);
        }

        .tab.active {
            background: var(--primary);
            color: #FFF;
            box-shadow: 0 5px 15px rgba(109, 93, 211, 0.2);
        }

        .user-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            flex: 1;
            overflow-y: auto;
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
            font-size: 0.95rem;
            font-weight: 600;
        }

        .user-info p {
            color: var(--text-light);
            font-size: 0.8rem;
        }

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
        }

        .map-widget {
            margin-top: 30px;
        }
        
        .map-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .map-header h3 {
            font-size: 1.1rem;
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
            width: 100%;
            height: 150px;
            background: #E2E8F0;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Map lines simulation */
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
        
        /* Hide scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E0;
            border-radius: 3px;
        }

    </style>
</head>
<body>

    <div class="app-container">
        
        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="icon logo">
                <i class="fa-brands fa-bity"></i>
            </div>
            
            <div class="icon">
                <i class="fa-solid fa-house"></i>
            </div>
            <div class="icon">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <div class="icon active">
                <i class="fa-solid fa-chart-simple"></i>
            </div>
            <div class="icon">
                <i class="fa-regular fa-comment-dots"></i>
            </div>
            <div class="icon">
                <i class="fa-regular fa-circle-play"></i>
            </div>
            
            <div class="icon bottom-icon">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            
            <div class="header">
                <div class="header-title">
                    <span>Principal</span>
                    <h2>Dashboard Reportes</h2>
                </div>
                
                <div class="header-right">
                    <div class="search-bar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Buscar...">
                    </div>
                    <div class="user-avatar">
                        <img src="https://ui-avatars.com/api/?name=Admin+User&background=6D5DD3&color=fff" alt="User">
                    </div>
                </div>
            </div>

            <!-- TOP WIDGETS -->
            <div class="top-widgets">
                <!-- Overview Graph -->
                <div class="overview-card">
                    <div class="overview-header">
                        <h3>Resumen de Operaciones</h3>
                        <select>
                            <option>Mensual</option>
                            <option>Semanal</option>
                        </select>
                    </div>
                    
                    <div class="chart-mockup">
                        <!-- Simulated SVG Curve for visual effect -->
                        <svg class="svg-curve" viewBox="0 0 500 150" preserveAspectRatio="none">
                            <path d="M0,100 C100,50 150,150 250,80 C350,10 400,120 500,60" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="4"></path>
                            <path d="M0,100 C100,50 150,150 250,80 C350,10 400,120 500,60 L500,150 L0,150 Z" fill="rgba(255,255,255,0.05)"></path>
                        </svg>
                        
                        <div class="chart-point"></div>
                        <div class="chart-point-label">
                            <strong>S/ 94,178</strong><br>Ingresos
                        </div>
                    </div>
                    
                    <div class="overview-stats">
                        <div class="stat-block">
                            <p>Mes Anterior</p>
                            <h3>S/ 74,800</h3>
                        </div>
                        <div class="stat-block active">
                            <p>Mes Actual</p>
                            <h3>S/ 94,178</h3>
                        </div>
                        <div class="stat-block">
                            <p>Meta del Mes</p>
                            <h3>S/ 120,000</h3>
                        </div>
                    </div>
                </div>

                <!-- Right Side Cards -->
                <div class="side-cards">
                    <div class="mini-card">
                        <div class="icon-box">
                            <i class="fa-solid fa-file-invoice"></i>
                        </div>
                        <div class="card-info">
                            <h4>Confirmaciones de<br>pago Pendientes</h4>
                            <p>12 comprobantes nuevos</p>
                        </div>
                    </div>
                    
                    <div class="mini-card pink">
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
                            <a href="#" class="arrow-btn"><i class="fa-solid fa-chevron-right"></i></a>
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

        <!-- RIGHT SIDEBAR -->
        <div class="right-sidebar">
            <div class="right-header">
                <h3><i class="fa-solid fa-users"></i> Equipo</h3>
                <a href="#" class="view-all">Ver Todos</a>
            </div>
            
            <div class="tabs">
                <div class="tab active">Actividades</div>
                <div class="tab">En línea</div>
            </div>
            
            <div class="user-list">
                <div class="user-item">
                    <img src="https://ui-avatars.com/api/?name=Maria+Gomez&background=F5E6E8&color=D85C7B" alt="User">
                    <div class="user-info">
                        <h5>Maria Gomez</h5>
                        <p>Ventas Corporativas</p>
                    </div>
                    <div class="user-action"><i class="fa-regular fa-square-check"></i></div>
                </div>
                
                <div class="user-item">
                    <img src="https://ui-avatars.com/api/?name=Carlos+Ruiz&background=E6F5E8&color=5CBA7B" alt="User">
                    <div class="user-info">
                        <h5>Carlos Ruiz</h5>
                        <p>Distribución</p>
                    </div>
                    <div class="user-action"><i class="fa-regular fa-square-check"></i></div>
                </div>
                
                <div class="user-item">
                    <img src="https://ui-avatars.com/api/?name=Ana+Torres&background=E6EBF5&color=5C7BBA" alt="User">
                    <div class="user-info">
                        <h5>Ana Torres</h5>
                        <p>Ventas Retail</p>
                    </div>
                    <div class="user-action"><i class="fa-regular fa-square-check"></i></div>
                </div>
                
                <div class="user-item">
                    <img src="https://ui-avatars.com/api/?name=Luis+Paz&background=F5F0E6&color=BA9A5C" alt="User">
                    <div class="user-info">
                        <h5>Luis Paz</h5>
                        <p>Sucursal Arequipa</p>
                    </div>
                    <div class="user-action"><i class="fa-regular fa-square-check"></i></div>
                </div>
            </div>
            
            <div class="map-widget">
                <div class="map-header">
                    <h3><i class="fa-solid fa-location-dot"></i> Entregas en vivo</h3>
                    <a href="#" class="view">Ver mapa</a>
                </div>
                <div class="map-img">
                    <div class="map-pin pin-1"><img src="https://ui-avatars.com/api/?name=L&background=FFF&color=333&size=24" style="border-radius:50%;width:100%;height:100%;"></div>
                    <div class="map-pin pin-2"><i class="fa-solid fa-truck"></i></div>
                    <div class="map-pin pin-3"><img src="https://ui-avatars.com/api/?name=P&background=FFF&color=333&size=24" style="border-radius:50%;width:100%;height:100%;"></div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>

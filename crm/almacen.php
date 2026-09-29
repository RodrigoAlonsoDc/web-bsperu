<?php
session_start();
$vendedorCod = $_SESSION['crm_vendedor_cod'] ?? '99';

$fileAlmacenPath = __DIR__ . '/crm_data/almacen_movimientos.json';
if (!file_exists(__DIR__ . '/crm_data')) {
    mkdir(__DIR__ . '/crm_data', 0777, true);
}
if (!file_exists($fileAlmacenPath)) {
    file_put_contents($fileAlmacenPath, json_encode([]));
}

$rawMov = json_decode(file_get_contents($fileAlmacenPath), true) ?: [];
$gs_counter = 18824; 
foreach ($rawMov as $m) {
    if ($m['tipo'] === 'GS') {
        $num = intval(explode('-', $m['numero'])[1]);
        if ($num >= $gs_counter) $gs_counter = $num + 1;
    }
}
$siguienteGS = 'T001 - ' . str_pad($gs_counter, 7, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BS Perú - Panel de Almacén</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --outer-bg: #1B4079;
            --app-frame: #161719;
            --sidebar-bg: #161719;
            --main-bg: #FFFFFF;
            --accent-tan: #1B4079;
            --text-dark: #1E2024;
            --text-muted: #8E9299;
            --border-soft: #ECE7DE;
            --card-radius: 28px;
            --pill-radius: 40px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--sidebar-bg); height: 100vh; width: 100vw; display: flex; overflow: hidden; }

        .sidebar { width: 280px; background: var(--sidebar-bg); display: flex; flex-direction: column; padding: 26px 18px; gap: 16px; flex-shrink: 0; }
        .brand-logo { display: flex; align-items: center; gap: 12px; padding: 6px 12px 18px 12px; color: #FFF; text-decoration: none; }
        .brand-logo-icon { width: 40px; height: 40px; background: linear-gradient(135deg, #1B4079 0%, #4D7C8A 100%); border-radius: 12px; display: flex; justify-content: center; align-items: center; font-weight: 800; font-size: 1.25rem; color: #FFF; }
        .brand-logo-text { font-family: 'Outfit', sans-serif; font-size: 1.4rem; font-weight: 800; color: #FFF; }
        
        .nav-menu { display: flex; flex-direction: column; gap: 6px; flex: 1; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: var(--pill-radius); color: var(--text-muted); font-size: 0.88rem; font-weight: 500; text-decoration: none; cursor: pointer; transition: 0.2s; }
        .nav-item:hover { color: #FFF; background: rgba(255,255,255,0.05); }
        .nav-item.active { background: var(--accent-tan); color: #FFF; font-weight: 600; }
        
        .main-content { flex: 1; background: var(--main-bg); border-radius: var(--card-radius) 0 0 var(--card-radius); padding: 30px 40px; overflow-y: auto; display: flex; flex-direction: column; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-title h1 { font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 700; color: var(--text-dark); }
        
        .sucursal-selector { display: flex; align-items: center; gap: 10px; background: rgba(27,64,121,0.05); padding: 10px 20px; border-radius: 15px; border: 1px solid rgba(27,64,121,0.1); }
        .sucursal-selector select { border: none; background: transparent; font-family: 'Outfit', sans-serif; font-size: 1rem; font-weight: 600; color: var(--accent-tan); outline: none; cursor: pointer; }

        .btn-primary { background: var(--accent-tan); color: #FFF; border: none; padding: 12px 24px; border-radius: 12px; font-family: 'Outfit', sans-serif; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-2px); }

        .view-section { display: none; }
        .view-section.active { display: block; }
        
        .table-container { background: #FFF; border-radius: 20px; border: 1px solid var(--border-soft); overflow: hidden; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #F8F9FA; padding: 16px 20px; font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }
        td { padding: 16px 20px; font-size: 0.95rem; color: var(--text-dark); border-bottom: 1px solid var(--border-soft); font-weight: 500; }
        .badge { padding: 4px 10px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; }
        .badge.gs { background: rgba(27,64,121,0.1); color: var(--accent-tan); }
        .badge.ft { background: rgba(56,161,105,0.1); color: #38A169; }
        .badge.bv { background: rgba(236,201,75,0.2); color: #B7791F; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }
        .form-group input, .form-group select { padding: 12px 16px; border: 1px solid var(--border-soft); border-radius: 12px; font-family: 'Poppins', sans-serif; font-size: 0.95rem; outline: none; }
        .form-group input:focus { border-color: var(--accent-tan); }
        
        .items-table input { width: 100%; border: 1px solid var(--border-soft); padding: 8px; border-radius: 6px; outline: none; }
        
        @media print {
            .sidebar, .main-content, .margin-settings-panel, .live-preview-toolbar { display: none !important; }
            body { 
                background: white !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                display: block !important; 
            }
            
            #pdfTemplate { 
                display: block !important; 
                position: relative !important; 
                width: 100% !important; 
                max-width: var(--pdf-width, 195mm) !important;
                margin: 0 auto !important; 
                padding: var(--pdf-margin-top, 4mm) var(--pdf-margin-lr, 5mm) !important; 
                font-family: Arial, Helvetica, sans-serif !important;
                font-size: 8.5pt !important;
                color: #000 !important;
            }
            
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                box-sizing: border-box !important;
            }

            @page { 
                size: A4 portrait; 
                margin: var(--pdf-page-margin, 0mm); 
            }
        }

        /* Estilos base del PDF */
        #pdfTemplate {
            display: none;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background: #fff;
            max-width: var(--pdf-width, 195mm);
            margin: 0 auto;
            padding: var(--pdf-margin-top, 4mm) var(--pdf-margin-lr, 5mm);
        }

        #pdfTemplate * {
            box-sizing: border-box;
        }

        /* 1. Encabezado */
        .pdf-header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            width: 100%;
        }
        .pdf-logo {
            width: 23%;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }
        .pdf-logo img {
            width: 140px;
            max-width: 100%;
            height: auto;
        }
        .pdf-company-info {
            width: 44%;
            text-align: center;
            white-space: nowrap;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: Arial, Helvetica, sans-serif;
        }
        .pdf-comp-title {
            font-size: 11pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
            letter-spacing: 0.2px;
        }
        .pdf-comp-fiscal {
            font-size: 8pt;
            font-weight: bold;
            line-height: 1.25;
            margin-bottom: 5px;
            color: #000;
        }
        .pdf-comp-branch {
            font-size: 8.5pt;
            font-weight: bold;
            line-height: 1.25;
            color: #000;
        }
        .pdf-ruc-box {
            width: 32%;
            border: 1.5px solid #000;
            border-radius: 6px;
            text-align: center;
            overflow: hidden;
            background: #fff;
        }
        .pdf-ruc-top {
            font-size: 11.5pt;
            font-weight: bold;
            padding: 5px 0;
            letter-spacing: 0.5px;
        }
        .pdf-ruc-mid {
            background-color: #004080 !important;
            color: #ffffff !important;
            padding: 5px 2px;
            line-height: 1.2;
        }
        .pdf-ruc-mid div:first-child {
            font-size: 10pt;
            font-weight: bold;
            white-space: nowrap;
            letter-spacing: 0px;
        }
        .pdf-ruc-mid div:last-child {
            font-size: 10.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-top: 1px;
        }
        .pdf-ruc-bot {
            font-size: 11.5pt;
            font-weight: bold;
            padding: 5px 0;
            letter-spacing: 0.5px;
        }

        /* 2. Caja de Info General */
        .pdf-info-box {
            border: 1px solid #000;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 8px;
            font-size: 8.5pt;
            line-height: 1.35;
        }
        .pdf-info-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 1px;
        }
        .pdf-info-left {
            display: flex;
            align-items: baseline;
            flex: 1;
        }
        .pdf-info-right {
            display: flex;
            align-items: baseline;
            width: 220px;
            white-space: nowrap;
            justify-content: flex-start;
        }
        .pdf-lbl {
            font-weight: bold;
            display: inline-block;
            min-width: 90px;
            white-space: nowrap;
        }
        .pdf-val {
            font-weight: normal;
        }

        /* 3. Puntos de partida y llegada */
        .pdf-locations-box {
            border: 1px solid #000;
            border-radius: 4px;
            display: flex;
            margin-bottom: 8px;
            font-size: 8pt;
            line-height: 1.3;
        }
        .pdf-loc-half {
            flex: 1;
            padding: 5px 8px;
        }
        .pdf-loc-divider {
            width: 1px;
            background-color: #000;
        }
        .pdf-loc-title {
            font-weight: bold;
            margin-bottom: 2px;
        }

        /* 4. Motivo de traslado */
        .pdf-motivo-title {
            font-weight: bold;
            font-size: 8pt;
            margin-bottom: 2px;
        }
        .pdf-motivo-box {
            border: 1px solid #000;
            border-radius: 4px;
            padding: 5px 8px;
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 8pt;
        }
        .pdf-motivo-col1 { width: 44%; display: flex; flex-direction: column; gap: 4px; }
        .pdf-motivo-col2 { width: 28%; display: flex; flex-direction: column; gap: 4px; }
        .pdf-motivo-col3 { width: 26%; display: flex; flex-direction: column; gap: 4px; }
        .pdf-chk-item { display: flex; align-items: center; gap: 6px; }
        .pdf-chk {
            width: 12px;
            height: 12px;
            min-width: 12px;
            border: 1px solid #000;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: bold;
            line-height: 1;
        }

        /* 5. Tabla de Items */
        .pdf-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 8pt;
        }
        .pdf-table th {
            background-color: #004080 !important;
            color: #ffffff !important;
            border: 1px solid #000;
            padding: 4px 2px;
            font-weight: bold;
            text-align: center;
            font-size: 8pt;
        }
        .pdf-table td {
            border: 1px solid #000;
            padding: 4px 3px;
            text-align: center;
            font-size: 8pt;
        }

        /* 6. Conductor y Transporte */
        .pdf-footer-titles {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 8pt;
            margin-bottom: 2px;
        }
        .pdf-footer-boxes {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 8pt;
        }
        .pdf-footer-box {
            width: 49.5%;
            border: 1px solid #000;
            border-radius: 4px;
            overflow: hidden;
            line-height: 1.3;
        }
        .pdf-box-row {
            display: flex;
        }
        .pdf-box-lbl {
            width: 55px;
            padding: 3px 6px;
            border-right: 1px solid #000;
            font-weight: normal;
        }
        .pdf-box-val {
            flex: 1;
            padding: 3px 6px;
        }

        /* 7. Peso Bruto */
        .pdf-peso-row {
            text-align: right;
            font-size: 7.5pt;
            font-weight: bold;
            margin-bottom: 6px;
            padding-right: 2px;
        }

        /* 8. QR y SUNAT */
        .pdf-qr-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 8pt;
        }
        .pdf-qr {
            width: 75px;
            height: 75px;
            min-width: 75px;
        }
        .pdf-qr img {
            width: 100%;
            height: 100%;
            display: block;
        }
        .pdf-hash-text {
            flex: 1;
            line-height: 1.25;
        }

        /* Contenedor de Vista Previa en Pantalla */
        #livePreviewWrapper {
            display: none;
            background: #cbd5e1;
            padding: 25px;
            border-radius: 16px;
            margin-top: 25px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        .live-preview-paper {
            background: #ffffff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            margin: 0 auto;
            border-radius: 2px;
            transition: all 0.2s ease;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <a href="#" class="brand-logo">
            <div class="brand-logo-icon">BS</div>
            <span class="brand-logo-text">Almacén</span>
        </a>
        <nav class="nav-menu">
            <a class="nav-item active" onclick="switchView('historial', this)">
                <i class="fa-solid fa-clock-rotate-left"></i> Historial
            </a>
            <a class="nav-item" onclick="switchView('nueva_guia', this)">
                <i class="fa-solid fa-truck-fast"></i> Nueva Guía (GS)
            </a>
        </nav>
        <div style="margin-top:auto">
            <a href="ventas.php" class="nav-item" style="color:var(--text-muted); font-size:0.8rem;">
                <i class="fa-solid fa-arrow-left"></i> Volver a Ventas
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-title">
                <h1 id="pageTitle">Historial de Movimientos</h1>
            </div>
            <div class="sucursal-selector">
                <i class="fa-solid fa-building" style="color:var(--accent-tan)"></i>
                <select id="sucursalActiva" onchange="actualizarSucursal()">
                    <option value="PRINCIPAL">Sede Principal (Lima)</option>
                    <option value="PIURA">Sucursal Piura</option>
                    <option value="AREQUIPA">Sucursal Arequipa</option>
                </select>
            </div>
        </div>

        <div id="view_historial" class="view-section active">
            <button class="btn-primary" onclick="document.querySelectorAll('.nav-item')[1].click()">
                <i class="fa-solid fa-plus"></i> Generar Guía Remisión
            </button>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Origen/Destino</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="historialBody">
                        <tr>
                            <td><strong>T001-0018823</strong></td>
                            <td><span class="badge gs">GS</span></td>
                            <td>22/09/2026</td>
                            <td>Lima &rarr; Piura</td>
                            <td>Traslado mismo establecimiento</td>
                            <td style="color:#38A169;font-weight:600;"><i class="fa-solid fa-check-circle"></i> Emitido</td>
                        </tr>
                        <tr>
                            <td><strong>B001-004521</strong></td>
                            <td><span class="badge bv">BV</span></td>
                            <td>21/09/2026</td>
                            <td>Almacén Lima &rarr; Cliente</td>
                            <td>Venta</td>
                            <td style="color:#38A169;font-weight:600;"><i class="fa-solid fa-check-circle"></i> Emitido</td>
                        </tr>
                        <tr>
                            <td><strong>F002-009812</strong></td>
                            <td><span class="badge ft">FT</span></td>
                            <td>20/09/2026</td>
                            <td>Almacén Piura &rarr; Consorcio X</td>
                            <td>Venta</td>
                            <td style="color:#38A169;font-weight:600;"><i class="fa-solid fa-check-circle"></i> Emitido</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="view_nueva_guia" class="view-section">
            <div style="background:#fff; border-radius: 20px; border: 1px solid var(--border-soft); padding: 30px;">
                <h3 style="margin-bottom:20px; font-family:'Outfit';">Generar Guía de Remisión Electrónica</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Destinatario (Razón Social)</label>
                        <input type="text" id="g_cliente" value="BUILDING SYSTEMS PERU S.A.C.">
                    </div>
                    <div class="form-group">
                        <label>R.U.C. Destinatario</label>
                        <input type="text" id="g_ruc" value="20609793806">
                    </div>
                    <div class="form-group">
                        <label>Punto de Partida</label>
                        <input type="text" id="g_partida" value="AV. LOS FAISANES 675 URB. LA CAMPIÑA">
                    </div>
                    <div class="form-group">
                        <label>Punto de Llegada</label>
                        <input type="text" id="g_llegada" value="AAHH. MANUEL SEOANE CORRALES MZ. H LOTE 01">
                    </div>
                    <div class="form-group">
                        <label>Motivo de Traslado</label>
                        <select id="g_motivo">
                            <option value="traslado">Traslado entre establecimientos de la misma empresa</option>
                            <option value="venta">Venta</option>
                            <option value="devolucion">Devolución</option>
                            <option value="consignacion">Consignación</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Glosa / Observación</label>
                        <input type="text" id="g_glosa" value="VENTA PUNTUAL SUC PIURA/CINTHYA CESPEDES CASTRO//SERVICIOS TERAN">
                    </div>
                </div>

                <h4 style="margin:20px 0 10px;">Items a trasladar</h4>
                <div class="table-container" style="margin-top:0; margin-bottom:20px; border-radius:10px;">
                    <table class="items-table">
                        <thead style="background:#1B4079; color:#fff;">
                            <tr>
                                <th style="color:#fff">CÓDIGO</th>
                                <th style="color:#fff">DESCRIPCIÓN</th>
                                <th style="color:#fff">LOTE</th>
                                <th style="color:#fff">CANT.</th>
                                <th style="color:#fff">U.M.</th>
                                <th style="color:#fff">PESO (KG)</th>
                            </tr>
                        </thead>
                        <tbody id="g_items_body">
                            <tr>
                                <td><input type="text" value="110014513" class="i_cod"></td>
                                <td><input type="text" value="MICROSILICA Z X 20 KG" class="i_desc"></td>
                                <td><input type="text" value="200426" class="i_lote"></td>
                                <td><input type="number" value="3" class="i_cant" onchange="calcPeso()"></td>
                                <td><input type="text" value="B20" class="i_um"></td>
                                <td><input type="number" value="20.10" class="i_peso" onchange="calcPeso()"></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn-primary" style="margin:10px; padding:6px 12px; font-size:0.8rem;" onclick="addFila()">+ Añadir Fila</button>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Conductor (Nombre)</label>
                        <input type="text" id="g_cond_nombre" value="MIGUEL HUMBERTO CONDEÑA AVALOS">
                    </div>
                    <div class="form-group">
                        <label>Conductor (D.N.I / Licencia)</label>
                        <input type="text" id="g_cond_dni" value="46830741">
                    </div>
                    <div class="form-group">
                        <label>Vehículo (Marca)</label>
                        <input type="text" id="g_veh_marca" value="CANTER">
                    </div>
                    <div class="form-group">
                        <label>Vehículo (Placa)</label>
                        <input type="text" id="g_veh_placa" value="BYF906">
                    </div>
                </div>

                
                <!-- PANEL DE CONTROL DE MÁRGENES DE IMPRESIÓN -->
                <div class="margin-settings-panel" style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 14px; padding: 18px 20px; margin-top: 25px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                        <span style="font-weight: 700; color: #1e293b; font-size: 1rem; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-sliders" style="color: var(--accent-tan);"></i> Ajustes de Margen y Diseño de la Guía
                        </span>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" onclick="resetMargenes()" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 7px 14px; border-radius: 8px; cursor: pointer; font-size: 0.82rem; font-weight: 600;">
                                <i class="fa-solid fa-rotate-left"></i> Restablecer
                            </button>
                            <button type="button" onclick="toggleLivePreview()" style="background: #1B4079; color: white; border: none; padding: 7px 16px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-eye"></i> <span id="btnPreviewText">Vista Previa en Pantalla</span>
                            </button>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                        <div class="form-group" style="margin: 0; background: #fff; padding: 10px 14px; border-radius: 10px; border: 1px solid #e2e8f0;">
                            <div style="display:flex; justify-content:space-between; margin-bottom: 6px;">
                                <label style="font-size: 0.8rem; font-weight: 600; color: #475569;">Margen Superior</label>
                                <span id="val_m_top" style="font-size: 0.85rem; font-weight: 700; color: #1B4079;">4mm</span>
                            </div>
                            <input type="range" id="cfg_m_top" min="0" max="30" value="4" oninput="aplicarMargenes()" style="width: 100%; accent-color: var(--accent-tan); cursor: pointer;">
                        </div>
                        
                        <div class="form-group" style="margin: 0; background: #fff; padding: 10px 14px; border-radius: 10px; border: 1px solid #e2e8f0;">
                            <div style="display:flex; justify-content:space-between; margin-bottom: 6px;">
                                <label style="font-size: 0.8rem; font-weight: 600; color: #475569;">Margen Lateral (Izq/Der)</label>
                                <span id="val_m_lr" style="font-size: 0.85rem; font-weight: 700; color: #1B4079;">5mm</span>
                            </div>
                            <input type="range" id="cfg_m_lr" min="0" max="30" value="5" oninput="aplicarMargenes()" style="width: 100%; accent-color: var(--accent-tan); cursor: pointer;">
                        </div>

                        <div class="form-group" style="margin: 0; background: #fff; padding: 10px 14px; border-radius: 10px; border: 1px solid #e2e8f0;">
                            <label style="font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 6px; display: block;">Ancho de Hoja</label>
                            <select id="cfg_width" onchange="aplicarMargenes()" style="width: 100%; padding: 6px 10px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 0.85rem; background: #fff; outline: none;">
                                <option value="195mm" selected>195 mm (Recomendado A4)</option>
                                <option value="190mm">190 mm (Estrecho)</option>
                                <option value="185mm">185 mm</option>
                                <option value="100%">100% (Ancho Total)</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin: 0; background: #fff; padding: 10px 14px; border-radius: 10px; border: 1px solid #e2e8f0;">
                            <label style="font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 6px; display: block;">Margen Navegador (@page)</label>
                            <select id="cfg_page_m" onchange="aplicarMargenes()" style="width: 100%; padding: 6px 10px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 0.85rem; background: #fff; outline: none;">
                                <option value="0mm" selected>0 mm (Sin marcos blancos extra)</option>
                                <option value="3mm">3 mm</option>
                                <option value="5mm">5 mm</option>
                                <option value="8mm">8 mm</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 12px; font-size: 0.8rem; color: #0369a1; background: #e0f2fe; border-left: 4px solid #0284c7; padding: 8px 12px; border-radius: 6px; line-height: 1.4;">
                        <strong><i class="fa-solid fa-lightbulb"></i> ¿Cómo ajustar el margen en la ventana de impresión (Ctrl + P)?</strong><br>
                        En la ventana que se abre al presionar <em>"Generar Guía"</em>: desglosa <strong>"Más opciones"</strong> &rarr; en <strong>"Márgenes"</strong> elige <strong>"Ninguno"</strong> (para que use estos milímetros exactos) o <strong>"Personalizado"</strong> (para arrastrar las líneas manualmente). Marca también <strong>"Gráficos de fondo"</strong>.
                    </div>
                </div>

                <!-- CONTENEDOR DE VISTA PREVIA EN PANTALLA -->
                <div id="livePreviewWrapper">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px; color: #334155; font-size: 0.9rem; font-weight: 600;">
                        <span><i class="fa-solid fa-file-invoice"></i> Vista Previa en Pantalla (Hoja A4 Simulada)</span>
                        <span style="font-size: 0.8rem; color: #64748b;">Los cambios de márgenes de arriba se reflejan en tiempo real aquí</span>
                    </div>
                    <div id="livePreviewContainer" class="live-preview-paper">
                        <!-- El template clonado se renderiza aquí -->
                    </div>
                </div>

                <div style="margin-top: 30px; display:flex; justify-content:flex-end; gap:10px;">
                    <button class="btn-primary" onclick="generarPDF()">
                        <i class="fa-solid fa-file-pdf"></i> Generar Guía y Ver PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- EL PDF OCULTO QUE SE MOSTRARÁ AL IMPRIMIR -->
    <div id="pdfTemplate">
        <!-- 1. ENCABEZADO -->
        <div class="pdf-header-top">
            <div class="pdf-logo">
                <img src="img/logo_bs.png" alt="Logo">
            </div>
            <div class="pdf-company-info">
                <div class="pdf-comp-title">BUILDING SYSTEMS PERU S.A.C.</div>
                <div class="pdf-comp-fiscal">
                    <div>Domicilio Fiscal: Av. Los Faisanes Nº 675</div>
                    <div>Urb. La Campiña, Chorrillos - Lima - Lima</div>
                </div>
                <div class="pdf-comp-branch">
                    <div id="pdf_suc_dir">
                        <div>Sucursal: Av. Los Faisanes Nº 675</div>
                        <div>Urb. La Campiña, Chorrillos - Lima - Lima</div>
                    </div>
                    <div>E-mail : ventas.04@bsperu.pe</div>
                    <div>Telf.: (01) 329 9307 Cel. 923 062 809</div>
                </div>
            </div>
            <div class="pdf-ruc-box">
                <div class="pdf-ruc-top">RUC Nº 20609793806</div>
                <div class="pdf-ruc-mid">
                    <div>GUÍA DE REMISIÓN REMITENTE</div>
                    <div>ELECTRÓNICA</div>
                </div>
                <div class="pdf-ruc-bot">N° <?php echo $siguienteGS; ?></div>
            </div>
        </div>

        <!-- 2. DATOS DE EMISIÓN -->
        <div class="pdf-info-box">
            <div class="pdf-info-row">
                <div class="pdf-info-left">
                    <span class="pdf-lbl">Fecha Emisión:</span>
                    <span class="pdf-val" id="pdf_fecha">29/09/2026</span>
                </div>
            </div>
            <div class="pdf-info-row">
                <div class="pdf-info-left">
                    <span class="pdf-lbl">Nombre:</span>
                    <span class="pdf-val" id="pdf_nombre">BUILDING SYSTEMS PERU S.A.C.</span>
                </div>
            </div>
            <div class="pdf-info-row">
                <div class="pdf-info-left">
                    <span class="pdf-lbl">R.U.C.:</span>
                    <span class="pdf-val" id="pdf_ruc">20609793806</span>
                </div>
            </div>
            <div class="pdf-info-row">
                <div class="pdf-info-left">
                    <span class="pdf-lbl">Dirección:</span>
                    <span class="pdf-val" id="pdf_dir">AV. LOS FAISANES Nº 675 URB. LA CAMPIÑA CHORRILLOS -<br>LIMA - LIMA</span>
                </div>
                <div class="pdf-info-right">
                    <span class="pdf-lbl">N° Pedido:</span>
                    <span class="pdf-val" id="pdf_pedido"></span>
                </div>
            </div>
            <div class="pdf-info-row">
                <div class="pdf-info-left">
                    <span class="pdf-lbl">Cod. Vendedor:</span>
                    <span class="pdf-val">99 &nbsp;&nbsp; VENTAS OFICINA</span>
                </div>
                <div class="pdf-info-right">
                    <span class="pdf-lbl">N° Ord. Compra:</span>
                    <span class="pdf-val" id="pdf_oc"></span>
                </div>
            </div>
            <div class="pdf-info-row">
                <div class="pdf-info-left">
                    <span class="pdf-lbl">Glosa:</span>
                    <span class="pdf-val" id="pdf_glosa">VENTA PUNTUAL SUC PIURA/CINTHYA CESPEDES CASTRO//SERVICIOS TERAN</span>
                </div>
                <div class="pdf-info-right">
                    <span class="pdf-lbl">Doc. Referencia:</span>
                    <span class="pdf-val">NI &nbsp;&nbsp;&nbsp;&nbsp; 0000 - 0000231</span>
                </div>
            </div>
        </div>

        <!-- 3. PUNTOS DE PARTIDA Y LLEGADA -->
        <div class="pdf-locations-box">
            <div class="pdf-loc-half">
                <div class="pdf-loc-title">Punto de Partida:</div>
                <div class="pdf-loc-val" id="pdf_partida">AV. LOS FAISANES 675 URB. LA CAMPIÑA</div>
            </div>
            <div class="pdf-loc-divider"></div>
            <div class="pdf-loc-half">
                <div class="pdf-loc-title">Punto de Llegada:</div>
                <div class="pdf-loc-val" id="pdf_llegada">AAHH. MANUEL SEOANE CORRALES MZ. H LOTE 01</div>
            </div>
        </div>

        <!-- 4. MOTIVO DE TRASLADO -->
        <div class="pdf-motivo-title">MOTIVO DE TRASLADO</div>
        <div class="pdf-motivo-box">
            <div class="pdf-motivo-col1">
                <div class="pdf-chk-item"><span class="pdf-chk" id="chk_venta"></span> <span>Venta</span></div>
                <div class="pdf-chk-item"><span class="pdf-chk" id="chk_traslado">X</span> <span>Traslado entre establecimientos de la misma empresa</span></div>
            </div>
            <div class="pdf-motivo-col2">
                <div class="pdf-chk-item"><span class="pdf-chk" id="chk_consignacion"></span> <span>Consignación</span></div>
                <div class="pdf-chk-item"><span class="pdf-chk" id="chk_exportacion"></span> <span>Exportación</span></div>
            </div>
            <div class="pdf-motivo-col3">
                <div class="pdf-chk-item"><span class="pdf-chk" id="chk_devolucion"></span> <span>Devolución</span></div>
                <div class="pdf-chk-item"><span class="pdf-chk" id="chk_otros"></span> <span>Otros</span></div>
            </div>
        </div>

        <!-- 5. TABLA DE ITEMS -->
        <table class="pdf-table">
            <thead>
                <tr>
                    <th style="width: 5.5%;">ITEM</th>
                    <th style="width: 14%;">CODIGO</th>
                    <th style="width: 44%; text-align: left; padding-left: 6px;">DESCRIPCION</th>
                    <th style="width: 11%;">LOTE</th>
                    <th style="width: 9.5%;">CANTIDAD</th>
                    <th style="width: 6%;">U.M.</th>
                    <th style="width: 10%;">PESO</th>
                </tr>
            </thead>
            <tbody id="pdf_items_render">
                <!-- Se llena dinámicamente -->
            </tbody>
        </table>

        <!-- 6. CONDUCTOR Y TRANSPORTE -->
        <div class="pdf-footer-titles">
            <div style="width: 49.5%;">Datos del Conductor:</div>
            <div style="width: 49.5%;">Datos de la Unidad de Transporte:</div>
        </div>
        <div class="pdf-footer-boxes">
            <div class="pdf-footer-box">
                <div class="pdf-box-row">
                    <div class="pdf-box-lbl">Nombre:</div>
                    <div class="pdf-box-val" id="pdf_cond_n">MIGUEL HUMBERTO CONDEÑA AVALOS</div>
                </div>
                <div class="pdf-box-row">
                    <div class="pdf-box-lbl">D.N.I.:</div>
                    <div class="pdf-box-val" id="pdf_cond_d">46830741</div>
                </div>
                <div class="pdf-box-row">
                    <div class="pdf-box-lbl">Licencia:</div>
                    <div class="pdf-box-val" id="pdf_cond_l">Q46830741</div>
                </div>
            </div>
            <div class="pdf-footer-box">
                <div class="pdf-box-row">
                    <div class="pdf-box-lbl" style="width: 55px;">Marca:</div>
                    <div class="pdf-box-val" id="pdf_veh_m">CANTER</div>
                </div>
                <div class="pdf-box-row">
                    <div class="pdf-box-lbl" style="width: 55px;">Placa:</div>
                    <div class="pdf-box-val" id="pdf_veh_p">BYF906</div>
                </div>
            </div>
        </div>

        <!-- 7. PESO BRUTO -->
        <div class="pdf-peso-row">
            Peso Bruto Total: <span id="pdf_peso_total">60.30</span> KGM
        </div>

        <!-- 8. QR Y FIRMA FACTRON -->
        <div class="pdf-qr-row">
            <div class="pdf-qr">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=95x95&data=<?php echo urlencode($siguienteGS); ?>" alt="QR">
            </div>
            <div class="pdf-hash-text">
                <div style="font-size: 8.5pt; margin-bottom: 4px;">ERnrvHe8zr7oF3C5BSm9KPv1Zws=</div>
                <div style="font-size: 8pt; line-height: 1.25;">
                    Representación impresa de la GUÍA DE REMISIÓN<br>
                    REMITENTE ELECTRÓNICA.<br>
                    Consulte el documento en<br>
                    starsoftweb.com/FactronWeb/Factron<br>
                    Autorizado mediante resolución 2023 / SUNAT
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchView(viewId, el) {
            document.querySelectorAll('.view-section').forEach(v => v.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            document.getElementById('view_' + viewId).classList.add('active');
            el.classList.add('active');
            
            document.getElementById('pageTitle').innerText = viewId === 'historial' ? 'Historial de Movimientos' : 'Generar Nueva Guía';
        }

        function actualizarSucursal() {
            const suc = document.getElementById('sucursalActiva').value;
            let l1 = "Av. Los Faisanes Nº 675";
            let l2 = "Urb. La Campiña, Chorrillos - Lima - Lima";
            if (suc === 'PIURA') {
                l1 = "Mz. D Lote 17";
                l2 = "Zona Industrial - Piura";
            }
            if (suc === 'AREQUIPA') {
                l1 = "Parque Industrial Rio Seco";
                l2 = "Arequipa - Arequipa";
            }
            const el = document.getElementById('pdf_suc_dir');
            if (el) {
                el.innerHTML = `<div>Sucursal: ${l1}</div><div>${l2}</div>`;
            }
        }

        function addFila() {
            const tbody = document.getElementById('g_items_body');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="text" class="i_cod" value=""></td>
                <td><input type="text" class="i_desc" value=""></td>
                <td><input type="text" class="i_lote" value=""></td>
                <td><input type="number" value="1" class="i_cant" onchange="calcPeso()"></td>
                <td><input type="text" value="B20" class="i_um"></td>
                <td><input type="number" value="0.00" class="i_peso" step="0.01" onchange="calcPeso()"></td>
            `;
            tbody.appendChild(tr);
        }

        function calcPeso() {
            let total = 0;
            document.querySelectorAll('#g_items_body tr').forEach(tr => {
                let cant = parseFloat(tr.querySelector('.i_cant').value) || 1;
                let p = parseFloat(tr.querySelector('.i_peso').value) || 0;
                total += (cant * p);
            });
            document.getElementById('pdf_peso_total').innerText = total.toFixed(2);
        }

        
        function aplicarMargenes() {
            const topVal = document.getElementById('cfg_m_top').value;
            const lrVal = document.getElementById('cfg_m_lr').value;
            const top = topVal + 'mm';
            const lr = lrVal + 'mm';
            const w = document.getElementById('cfg_width').value;
            const pageM = document.getElementById('cfg_page_m').value;

            document.getElementById('val_m_top').innerText = top;
            document.getElementById('val_m_lr').innerText = lr;

            document.documentElement.style.setProperty('--pdf-margin-top', top);
            document.documentElement.style.setProperty('--pdf-margin-lr', lr);
            document.documentElement.style.setProperty('--pdf-width', w);
            document.documentElement.style.setProperty('--pdf-page-margin', pageM);

            const tmpl = document.getElementById('pdfTemplate');
            if (tmpl) {
                tmpl.style.padding = `${top} ${lr}`;
                tmpl.style.maxWidth = w;
            }

            // Si la vista previa en pantalla está abierta, actualizarla
            const previewTarget = document.querySelector('#livePreviewContainer #pdfTemplate_clone');
            if (previewTarget) {
                previewTarget.style.padding = `${top} ${lr}`;
                previewTarget.style.maxWidth = w;
            }
        }

        function resetMargenes() {
            document.getElementById('cfg_m_top').value = 4;
            document.getElementById('cfg_m_lr').value = 5;
            document.getElementById('cfg_width').value = '195mm';
            document.getElementById('cfg_page_m').value = '0mm';
            aplicarMargenes();
        }

        function toggleLivePreview() {
            const wrapper = document.getElementById('livePreviewWrapper');
            const btn = document.getElementById('btnPreviewText');
            if (wrapper.style.display === 'none' || !wrapper.style.display) {
                syncDataToTemplate();
                const container = document.getElementById('livePreviewContainer');
                const orig = document.getElementById('pdfTemplate');
                container.innerHTML = orig.outerHTML;
                const clone = container.querySelector('#pdfTemplate');
                if (clone) {
                    clone.id = 'pdfTemplate_clone';
                    clone.style.display = 'block';
                    clone.style.margin = '0 auto';
                    clone.style.boxShadow = '0 8px 24px rgba(0,0,0,0.12)';
                    clone.style.borderRadius = '4px';
                    clone.style.background = '#fff';
                }
                aplicarMargenes();
                wrapper.style.display = 'block';
                btn.innerText = 'Ocultar Vista Previa';
                wrapper.scrollIntoView({ behavior: 'smooth' });
            } else {
                wrapper.style.display = 'none';
                btn.innerText = 'Vista Previa en Pantalla';
            }
        }

        function syncDataToTemplate() {
            const d = new Date();
            const fechaStr = d.toLocaleDateString('es-PE', {day:'2-digit', month:'2-digit', year:'numeric'});
            document.getElementById('pdf_fecha').innerText = fechaStr;

            document.getElementById('pdf_nombre').innerText = document.getElementById('g_cliente').value;
            document.getElementById('pdf_ruc').innerText = document.getElementById('g_ruc').value;
            document.getElementById('pdf_partida').innerText = document.getElementById('g_partida').value;
            document.getElementById('pdf_llegada').innerText = document.getElementById('g_llegada').value;
            document.getElementById('pdf_glosa').innerText = document.getElementById('g_glosa').value;

            const m = document.getElementById('g_motivo').value;
            document.querySelectorAll('.pdf-chk').forEach(c => c.innerText = '');
            if(m==='venta') document.getElementById('chk_venta').innerText = 'X';
            if(m==='traslado') document.getElementById('chk_traslado').innerText = 'X';
            if(m==='devolucion') document.getElementById('chk_devolucion').innerText = 'X';
            if(m==='consignacion') document.getElementById('chk_consignacion').innerText = 'X';

            document.getElementById('pdf_cond_n').innerText = document.getElementById('g_cond_nombre').value;
            document.getElementById('pdf_cond_d').innerText = document.getElementById('g_cond_dni').value;
            document.getElementById('pdf_cond_l').innerText = 'Q' + document.getElementById('g_cond_dni').value; 
            document.getElementById('pdf_veh_m').innerText = document.getElementById('g_veh_marca').value;
            document.getElementById('pdf_veh_p').innerText = document.getElementById('g_veh_placa').value;

            const renderBody = document.getElementById('pdf_items_render');
            renderBody.innerHTML = '';
            let it = 1;
            document.querySelectorAll('#g_items_body tr').forEach(tr => {
                let html = `<tr>
                    <td>${it++}</td>
                    <td>${tr.querySelector('.i_cod').value}</td>
                    <td style="text-align:left; padding-left: 6px;">${tr.querySelector('.i_desc').value}</td>
                    <td>${tr.querySelector('.i_lote').value}</td>
                    <td>${parseFloat(tr.querySelector('.i_cant').value).toFixed(2)}</td>
                    <td>${tr.querySelector('.i_um').value}</td>
                    <td>${parseFloat(tr.querySelector('.i_peso').value).toFixed(2)}</td>
                </tr>`;
                renderBody.innerHTML += html;
            });
            calcPeso();
        }

        function generarPDF() {
            try {
                aplicarMargenes();
                syncDataToTemplate();
                const d = new Date();
                const fechaStr = d.toLocaleDateString('es-PE', {day:'2-digit', month:'2-digit', year:'numeric'});
                document.getElementById('pdf_fecha').innerText = fechaStr;

                document.getElementById('pdf_nombre').innerText = document.getElementById('g_cliente').value;
                document.getElementById('pdf_ruc').innerText = document.getElementById('g_ruc').value;
                document.getElementById('pdf_partida').innerText = document.getElementById('g_partida').value;
                document.getElementById('pdf_llegada').innerText = document.getElementById('g_llegada').value;
                document.getElementById('pdf_glosa').innerText = document.getElementById('g_glosa').value;

                const m = document.getElementById('g_motivo').value;
                document.querySelectorAll('.pdf-chk').forEach(c => c.innerText = '');
                if(m==='venta') document.getElementById('chk_venta').innerText = 'X';
                if(m==='traslado') document.getElementById('chk_traslado').innerText = 'X';
                if(m==='devolucion') document.getElementById('chk_devolucion').innerText = 'X';
                if(m==='consignacion') document.getElementById('chk_consignacion').innerText = 'X';

                document.getElementById('pdf_cond_n').innerText = document.getElementById('g_cond_nombre').value;
                document.getElementById('pdf_cond_d').innerText = document.getElementById('g_cond_dni').value;
                document.getElementById('pdf_cond_l').innerText = 'Q' + document.getElementById('g_cond_dni').value; 
                document.getElementById('pdf_veh_m').innerText = document.getElementById('g_veh_marca').value;
                document.getElementById('pdf_veh_p').innerText = document.getElementById('g_veh_placa').value;

                const renderBody = document.getElementById('pdf_items_render');
                renderBody.innerHTML = '';
                let it = 1;
                document.querySelectorAll('#g_items_body tr').forEach(tr => {
                    let html = `<tr>
                        <td>${it++}</td>
                        <td>${tr.querySelector('.i_cod').value}</td>
                        <td style="text-align:left; padding-left: 6px;">${tr.querySelector('.i_desc').value}</td>
                        <td>${tr.querySelector('.i_lote').value}</td>
                        <td>${parseFloat(tr.querySelector('.i_cant').value).toFixed(2)}</td>
                        <td>${tr.querySelector('.i_um').value}</td>
                        <td>${parseFloat(tr.querySelector('.i_peso').value).toFixed(2)}</td>
                    </tr>`;
                    renderBody.innerHTML += html;
                });
                calcPeso();

                window.print();
            } catch(e) {
                alert("Error en generarPDF: " + e.message);
            }
        }
    </script>
</body>
</html>

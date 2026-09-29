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
            /* Ocultar UI normal */
            .sidebar, .main-content { display: none !important; }
            body { background: white !important; margin: 0; padding: 0; display: block !important; }
            
            /* Mostrar PDF */
            #pdfTemplate { 
                display: block !important; 
                position: relative; 
                width: 100%; 
                margin: 0; 
                padding: 10mm 20mm 20mm 20mm; 
            }
            
            /* Configuraciones de la pagina */
            @page { size: A4 portrait; margin: 0; }
        }

        #pdfTemplate { width: 210mm; margin: 0 auto; padding: 20mm; font-family: Arial, sans-serif; background: #fff; box-sizing: border-box; display: none; }
        .pdf-header { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .pdf-logo { width: 180px; }
        .pdf-company-info { text-align: center; flex: 1; padding: 0 15px; font-size: 11px; }
        .pdf-company-info h2 { font-size: 16px; margin-bottom: 5px; font-weight: bold; }
        .pdf-ruc-box { border: 2px solid #000; text-align: center; width: 280px; border-radius: 8px; overflow: hidden; }
        .pdf-ruc-box .ruc-top { font-size: 16px; font-weight: bold; padding: 10px 0; }
        .pdf-ruc-box .ruc-mid { background: #0b3c7c; color: #fff; padding: 10px 0; font-size: 14px; font-weight: bold; letter-spacing: 1px; }
        .pdf-ruc-box .ruc-bot { font-size: 16px; font-weight: bold; padding: 10px 0; }

        .pdf-box { border: 1px solid #000; border-radius: 5px; padding: 8px; margin-bottom: 10px; font-size: 11px; }
        .pdf-flex-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        
        .pdf-motivo { display: flex; flex-wrap: wrap; justify-content: space-between; font-size: 10px; }
        .pdf-motivo > div { width: 33%; margin-bottom: 5px; display: flex; align-items: center; gap: 5px; }
        .pdf-checkbox { width: 14px; height: 14px; border: 1px solid #000; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; font-size: 10px; }

        .pdf-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10px; text-align: center; }
        .pdf-table th { background: #0b3c7c; color: #fff; padding: 6px; border: 1px solid #000; }
        .pdf-table td { padding: 6px; border: 1px solid #000; }
        
        .pdf-footer-boxes { display: flex; gap: 10px; font-size: 10px; }
        .pdf-footer-box { border: 1px solid #000; flex: 1; border-radius: 5px; padding: 8px; }
        
        .pdf-signatures { display: flex; gap: 15px; margin-top: 10px; align-items: flex-end; font-size: 10px; }
        .pdf-qr { width: 100px; height: 100px; background: #eee; }
        .pdf-hash { flex: 1; }
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
        <div class="pdf-header">
            <div class="pdf-logo">
                <h1 style="color:#0b3c7c; font-family:'Outfit'; font-size:28px; margin-top:20px;">BSP | BS PERÚ</h1>
            </div>
            <div class="pdf-company-info">
                <h2>BUILDING SYSTEMS PERU S.A.C.</h2>
                <p>Domicilio Fiscal: Av. Los Faisanes N° 675<br>Urb. La Campiña, Chorrillos - Lima - Lima</p>
                <p style="margin-top:8px;">Sucursal: <span id="pdf_suc_dir">Av. Los Faisanes N° 675 Urb. La Campiña, Chorrillos - Lima</span></p>
                <p>E-mail: ventas.04@bsperu.pe</p>
                <p>Telf.: (01) 329 9307 Cel. 923 062 809</p>
            </div>
            <div class="pdf-ruc-box">
                <div class="ruc-top">RUC N° 20609793806</div>
                <div class="ruc-mid">GUÍA DE REMISIÓN REMITENTE<br>ELECTRÓNICA</div>
                <div class="ruc-bot">N° <?php echo $siguienteGS; ?></div>
            </div>
        </div>

        <div class="pdf-box">
            <div class="pdf-flex-row">
                <div style="width:120px;"><strong>Fecha Emisión:</strong></div>
                <div style="flex:1"><?php echo date('d/m/Y'); ?></div>
            </div>
            <div class="pdf-flex-row">
                <div style="width:120px;"><strong>Nombre:</strong></div>
                <div style="flex:1" id="pdf_nombre">BUILDING SYSTEMS PERU S.A.C.</div>
            </div>
            <div class="pdf-flex-row">
                <div style="width:120px;"><strong>R.U.C.:</strong></div>
                <div style="flex:1" id="pdf_ruc">20609793806</div>
            </div>
            <div class="pdf-flex-row">
                <div style="width:120px;"><strong>Dirección:</strong></div>
                <div style="flex:1" id="pdf_dir">AV. LOS FAISANES...</div>
                <div style="width:100px;"><strong>N° Pedido:</strong></div>
                <div style="width:150px;"></div>
            </div>
            <div class="pdf-flex-row">
                <div style="width:120px;"><strong>Cod. Vendedor:</strong></div>
                <div style="flex:1">99 VENTAS OFICINA</div>
                <div style="width:100px;"><strong>Doc. Referencia:</strong></div>
                <div style="width:150px;">NI 0000 - 0000231</div>
            </div>
            <div class="pdf-flex-row">
                <div style="width:120px;"><strong>Glosa:</strong></div>
                <div style="flex:1" id="pdf_glosa">VENTA PUNTUAL SUC PIURA...</div>
            </div>
        </div>

        <div style="display:flex; gap:10px; margin-bottom:10px;">
            <div class="pdf-box" style="flex:1; margin-bottom:0;">
                <strong>Punto de Partida:</strong><br>
                <span id="pdf_partida">AV. LOS FAISANES 675</span>
            </div>
            <div class="pdf-box" style="flex:1; margin-bottom:0;">
                <strong>Punto de Llegada:</strong><br>
                <span id="pdf_llegada">AAHH. MANUEL SEOANE...</span>
            </div>
        </div>

        <strong>MOTIVO DE TRASLADO</strong>
        <div class="pdf-box pdf-motivo">
            <div><span class="pdf-checkbox" id="chk_venta"></span> Venta</div>
            <div><span class="pdf-checkbox" id="chk_consignacion"></span> Consignación</div>
            <div><span class="pdf-checkbox" id="chk_devolucion"></span> Devolución</div>
            <div><span class="pdf-checkbox" id="chk_traslado">X</span> Traslado entre establecimientos de la misma empresa</div>
            <div><span class="pdf-checkbox" id="chk_exportacion"></span> Exportación</div>
            <div><span class="pdf-checkbox" id="chk_otros"></span> Otros</div>
        </div>

        <table class="pdf-table">
            <thead>
                <tr>
                    <th style="width:40px;">ITEM</th>
                    <th>CODIGO</th>
                    <th style="text-align:left;">DESCRIPCION</th>
                    <th>LOTE</th>
                    <th>CANTIDAD</th>
                    <th>U.M.</th>
                    <th>PESO</th>
                </tr>
            </thead>
            <tbody id="pdf_items_render">
            </tbody>
        </table>

        <div class="pdf-footer-boxes">
            <div class="pdf-footer-box">
                <div style="margin-bottom:5px;"><strong>Datos del Conductor:</strong></div>
                <div class="pdf-flex-row">
                    <div style="width:60px;">Nombre:</div><div style="flex:1" id="pdf_cond_n">MIGUEL HUMBERTO</div>
                </div>
                <div class="pdf-flex-row">
                    <div style="width:60px;">D.N.I.:</div><div style="flex:1" id="pdf_cond_d">46830741</div>
                </div>
                <div class="pdf-flex-row">
                    <div style="width:60px;">Licencia:</div><div style="flex:1" id="pdf_cond_l">Q46830741</div>
                </div>
            </div>
            <div class="pdf-footer-box">
                <div style="margin-bottom:5px;"><strong>Datos de la Unidad de Transporte:</strong></div>
                <div class="pdf-flex-row">
                    <div style="width:60px;">Marca:</div><div style="flex:1" id="pdf_veh_m">CANTER</div>
                </div>
                <div class="pdf-flex-row">
                    <div style="width:60px;">Placa:</div><div style="flex:1" id="pdf_veh_p">BYF906</div>
                </div>
            </div>
        </div>
        
        <div style="text-align:right; font-size:10px; font-weight:bold; margin-top:5px;">
            Peso Bruto Total: <span id="pdf_peso_total">60.30</span> KGM
        </div>

        <div class="pdf-signatures">
            <div class="pdf-qr">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo urlencode($siguienteGS); ?>" style="width:100%; height:100%;">
            </div>
            <div class="pdf-hash">
                <p>ERnrvHe8zr7oF3C5BSm9KPv1Zws=</p>
                <p>Representación impresa de la GUÍA DE REMISIÓN<br>REMITENTE ELECTRÓNICA.<br>Consulte el documento en<br>starsoftweb.com/FactronWeb/Factron<br>Autorizado mediante resolución 2023 / SUNAT</p>
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
            let dir = "Av. Los Faisanes N° 675 Urb. La Campiña, Chorrillos - Lima";
            if (suc === 'PIURA') dir = "Mz. D Lote 17 Zona Industrial - Piura";
            if (suc === 'AREQUIPA') dir = "Parque Industrial Rio Seco - Arequipa";
            document.getElementById('pdf_suc_dir').innerText = dir;
        }

        function addFila() {
            const tbody = document.getElementById('g_items_body');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="text" class="i_cod"></td>
                <td><input type="text" class="i_desc"></td>
                <td><input type="text" class="i_lote"></td>
                <td><input type="number" value="1" class="i_cant" onchange="calcPeso()"></td>
                <td><input type="text" value="B20" class="i_um"></td>
                <td><input type="number" value="0.00" class="i_peso" onchange="calcPeso()"></td>
            `;
            tbody.appendChild(tr);
        }

        function calcPeso() {
            let total = 0;
            document.querySelectorAll('#g_items_body tr').forEach(tr => {
                let p = parseFloat(tr.querySelector('.i_peso').value) || 0;
                total += p;
            });
            document.getElementById('pdf_peso_total').innerText = total.toFixed(2);
        }

        function generarPDF() {
            document.getElementById('pdf_nombre').innerText = document.getElementById('g_cliente').value;
            document.getElementById('pdf_ruc').innerText = document.getElementById('g_ruc').value;
            document.getElementById('pdf_dir').innerText = document.getElementById('g_partida').value;
            document.getElementById('pdf_glosa').innerText = document.getElementById('g_glosa').value;
            document.getElementById('pdf_partida').innerText = document.getElementById('g_partida').value;
            document.getElementById('pdf_llegada').innerText = document.getElementById('g_llegada').value;

            const m = document.getElementById('g_motivo').value;
            document.querySelectorAll('.pdf-checkbox').forEach(c => c.innerText = '');
            if(m==='venta') document.getElementById('chk_venta').innerText = 'X';
            if(m==='traslado') document.getElementById('chk_traslado').innerText = 'X';
            if(m==='devolucion') document.getElementById('chk_devolucion').innerText = 'X';
            if(m==='consignacion') document.getElementById('chk_consignacion').innerText = 'X';

            document.getElementById('pdf_cond_n').innerText = document.getElementById('g_cond_nombre').value;
            document.getElementById('pdf_cond_d').innerText = document.getElementById('g_cond_dni').value;
            document.getElementById('pdf_cond_l').innerText = document.getElementById('g_cond_dni').value; 
            document.getElementById('pdf_veh_m').innerText = document.getElementById('g_veh_marca').value;
            document.getElementById('pdf_veh_p').innerText = document.getElementById('g_veh_placa').value;

            const renderBody = document.getElementById('pdf_items_render');
            renderBody.innerHTML = '';
            let it = 1;
            document.querySelectorAll('#g_items_body tr').forEach(tr => {
                let html = `<tr>
                    <td>${it++}</td>
                    <td>${tr.querySelector('.i_cod').value}</td>
                    <td style="text-align:left;">${tr.querySelector('.i_desc').value}</td>
                    <td>${tr.querySelector('.i_lote').value}</td>
                    <td>${parseFloat(tr.querySelector('.i_cant').value).toFixed(2)}</td>
                    <td>${tr.querySelector('.i_um').value}</td>
                    <td>${parseFloat(tr.querySelector('.i_peso').value).toFixed(2)}</td>
                </tr>`;
                renderBody.innerHTML += html;
            });
            calcPeso();

            window.print();
        }
    </script>
</body>
</html>

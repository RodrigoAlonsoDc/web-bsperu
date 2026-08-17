<?php
// crm/inventario.php
session_start();
// Temporalmente deshabilitamos el login para que pruebes.
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: ../admin/login.php");
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario y Logistica - ERP BS Peru</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- Libreria para generar codigos de barras -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <!-- Libreria para escanear codigos de barras con la camara -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        :root {
            --bg: #f4f6f9; --surface: #ffffff; --primary: #17a2b8; --text-primary: #333; --border: #dee2e6; --green: #28a745;
        }
        body { font-family: 'Roboto', sans-serif; background-color: var(--bg); margin: 0; padding: 0; }
        .layout { display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 60px; background: #28a745; display: flex; flex-direction: column; align-items: center; padding-top: 15px; gap: 20px; }
        .sidebar a { color: white; text-decoration: none; font-size: 24px; }
        .sidebar a:hover { background: rgba(0,0,0,0.1); border-radius: 8px; }
        .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: var(--surface); }
        .topbar { background: var(--border); height: 40px; display: flex; justify-content: flex-end; align-items: center; padding: 0 20px; }
        .content { padding: 20px; flex: 1; overflow-y: auto; }
        
        /* Tabs */
        .tabs { display: flex; border-bottom: 2px solid var(--border); margin-bottom: 20px; }
        .tab { padding: 10px 20px; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; font-weight: 500; }
        .tab.active { border-bottom: 2px solid var(--primary); color: var(--primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .btn { background: var(--green); color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: var(--primary); }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid var(--border); }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; box-sizing: border-box; }
        
        .card { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 20px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        #reader { width: 100%; max-width: 400px; margin: 0 auto; }
        .scan-result { margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 8px; text-align: center; display: none; }
        .barcode-print-area { text-align: center; padding: 20px; border: 2px dashed #ccc; margin-top: 20px; display: none; }
        
        @media print {
            body * { visibility: hidden; }
            #printArea, #printArea * { visibility: visible; }
            #printArea { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <div class="sidebar">
            <a href="index.php" title="Dashboard"><i class="ph ph-squares-four"></i></a>
            <a href="clientes.php" title="Clientes"><i class="ph ph-users"></i></a>
            <a href="inventario.php" title="Inventario" style="background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px;"><i class="ph ph-barcode"></i></a>
            <a href="pedidos.php" title="Cotizaciones"><i class="ph ph-file-text"></i></a>
        </div>
        <div class="main">
            <div class="topbar">ERP BS Peru - Logistica Sede Chorrillos</div>
            <div class="content">
                <h2>Modulo de Inventario</h2>
                
                <div class="tabs">
                    <div class="tab active" onclick="switchTab('ingreso')">1. Recepcion de Mercaderia</div>
                    <div class="tab" onclick="switchTab('etiquetas')">2. Historial de Lotes / Etiquetas</div>
                    <div class="tab" onclick="switchTab('escaner')">3. Escaner (Camara)</div>
                </div>

                <!-- TAB 1: INGRESO -->
                <div id="ingreso" class="tab-content active">
                    <div class="card">
                        <h3>Registrar Ingreso de Lote</h3>
                        <form id="ingresoForm" onsubmit="saveIngreso(event)">
                            <div class="form-group">
                                <label>Producto (Catalogo)</label>
                                <div style="display:flex; gap:10px;">
                                    <select id="producto_id" name="producto_id" required style="flex:1;"></select>
                                    <button type="button" class="btn btn-primary" onclick="nuevoProductoPrompt()">+ Nuevo</button>
                                </div>
                            </div>
                            <div style="display:flex; gap:10px;">
                                <div class="form-group" style="flex:1;">
                                    <label>Cantidad (Unidades/Galones)</label>
                                    <input type="number" id="cantidad" name="cantidad" required min="1">
                                </div>
                                <div class="form-group" style="flex:1;">
                                    <label>Estante / Ubicacion</label>
                                    <input type="text" id="estante" name="estante" placeholder="Ej: A-01">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Fecha de Vencimiento del Lote</label>
                                <input type="date" id="fecha_vencimiento" name="fecha_vencimiento">
                            </div>
                            <button type="submit" class="btn" style="width:100%; font-size:16px; padding:10px;"><i class="ph ph-package"></i> Guardar y Generar Codigo</button>
                        </form>
                        
                        <!-- Area de Impresion dinamica -->
                        <div id="barcodePreviewArea" class="barcode-print-area">
                            <h4>Codigo Generado para el Lote</h4>
                            <div id="printArea">
                                <svg id="barcodeVisual"></svg>
                                <p id="printProductInfo" style="margin:0; font-size:12px; font-weight:bold;"></p>
                            </div>
                            <br>
                            <button class="btn btn-primary" onclick="window.print()"><i class="ph ph-printer"></i> Imprimir Etiqueta</button>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: ETIQUETAS Y LOTES -->
                <div id="etiquetas" class="tab-content">
                    <button class="btn" onclick="loadLotes()" style="margin-bottom:10px;"><i class="ph ph-arrows-clockwise"></i> Actualizar</button>
                    <table>
                        <thead>
                            <tr>
                                <th>Codigo Barras</th>
                                <th>Producto</th>
                                <th>Cant.</th>
                                <th>Estante</th>
                                <th>Vencimiento</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody id="lotesTableBody"></tbody>
                    </table>
                </div>

                <!-- TAB 3: ESCANER -->
                <div id="escaner" class="tab-content">
                    <div class="card" style="text-align: center;">
                        <h3>Escanear Etiqueta</h3>
                        <p style="color: #666; font-size: 14px;">Apunta con la camara de tu celular al codigo de barras del producto (o usa la webcam).</p>
                        <div id="reader"></div>
                        
                        <div id="scanResult" class="scan-result">
                            <h2 id="r_nombre" style="color:var(--primary); margin:0 0 10px 0;"></h2>
                            <p style="font-size:18px; margin:5px 0;"><strong>Stock Lote:</strong> <span id="r_stock"></span></p>
                            <p style="font-size:18px; margin:5px 0;"><strong>Estante:</strong> <span id="r_estante"></span></p>
                            <p style="font-size:18px; margin:5px 0;"><strong>Vence:</strong> <span id="r_vence" style="color:red; font-weight:bold;"></span></p>
                            <p style="font-size:14px; margin-top:15px; color:#555;">Codigo interno: <span id="r_code"></span></p>
                            <br>
                            <button class="btn btn-primary" onclick="resumeScanner()"><i class="ph ph-scan"></i> Escanear Otro</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        let html5QrcodeScanner;

        document.addEventListener('DOMContentLoaded', () => {
            loadProductos();
            loadLotes();
        });

        function switchTab(tabId) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tabId).classList.add('active');

            // Init scanner only when tab is active
            if(tabId === 'escaner' && !html5QrcodeScanner) {
                html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: {width: 250, height: 100} }, false);
                html5QrcodeScanner.render(onScanSuccess, onScanFailure);
            }
        }

        async function loadProductos() {
            try {
                const res = await fetch('api/inventario.php?action=list_productos');
                const data = await res.json();
                const sel = document.getElementById('producto_id');
                sel.innerHTML = '<option value="">Seleccione producto...</option>';
                data.forEach(p => {
                    sel.innerHTML += <option value="+p.id+">+p.nombre+</option>;
                });
            } catch (e) { console.error(e); }
        }

        async function loadLotes() {
            try {
                const res = await fetch('api/inventario.php?action=list_lotes');
                const data = await res.json();
                const tbody = document.getElementById('lotesTableBody');
                tbody.innerHTML = '';
                data.forEach(l => {
                    tbody.innerHTML += 
                        <tr>
                            <td><strong>+l.codigo_barras+</strong></td>
                            <td>+l.producto_nombre+</td>
                            <td>+l.cantidad+</td>
                            <td>+(l.estante || '-')+</td>
                            <td>+(l.fecha_vencimiento || '-')+</td>
                            <td>
                                <button onclick="reprintBarcode('+l.codigo_barras+', '+l.producto_nombre+')" class="btn btn-primary" style="padding:4px 8px;"><i class="ph ph-printer"></i></button>
                            </td>
                        </tr>
                    ;
                });
            } catch (e) { console.error(e); }
        }

        async function saveIngreso(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('ingresoForm'));
            formData.append('action', 'save_lote');
            
            try {
                const res = await fetch('api/inventario.php', { method: 'POST', body: formData });
                const result = await res.json();
                if(result.success) {
                    // Limpiar form
                    document.getElementById('ingresoForm').reset();
                    // Mostrar codigo de barras
                    const select = document.getElementById('producto_id');
                    const nombreProd = select.options[select.selectedIndex].text;
                    reprintBarcode(result.codigo_barras, nombreProd);
                    loadLotes();
                    alert('Lote registrado con exito. Por favor imprime la etiqueta.');
                } else {
                    alert(result.error);
                }
            } catch(e) { alert('Error de red'); }
        }

        function reprintBarcode(codigo, nombreProd) {
            document.getElementById('barcodePreviewArea').style.display = 'block';
            document.getElementById('printProductInfo').innerText = nombreProd;
            JsBarcode("#barcodeVisual", codigo, {
                format: "CODE128",
                lineColor: "#000",
                width: 2,
                height: 50,
                displayValue: true
            });
            // Si el usuario hace esto desde la tabla, lo enviamos a la pestaña 1 para que vea el boton de imprimir
            if(!document.getElementById('ingreso').classList.contains('active')) {
                switchTab('ingreso');
                // Hacemos un poco de magia para activar el estilo en el tab
                document.querySelectorAll('.tab').forEach((t, i) => {
                    if(i === 0) t.classList.add('active'); else t.classList.remove('active');
                });
            }
        }

        async function nuevoProductoPrompt() {
            const nombre = prompt('Ingresa el nombre del nuevo producto:');
            if(nombre) {
                const fd = new FormData();
                fd.append('action', 'save_producto');
                fd.append('nombre', nombre);
                fd.append('categoria', 'General');
                
                const res = await fetch('api/inventario.php', { method: 'POST', body: fd });
                const r = await res.json();
                if(r.success) {
                    await loadProductos();
                    document.getElementById('producto_id').value = r.id;
                }
            }
        }

        // ====== LOGICA ESCANER ======
        function onScanSuccess(decodedText, decodedResult) {
            // Pausar el escaneo
            if(html5QrcodeScanner) {
                html5QrcodeScanner.pause();
            }
            fetchProductData(decodedText);
        }

        function onScanFailure(error) {
            // Se ejecuta varias veces por segundo cuando no encuentra nada, lo ignoramos.
        }

        async function fetchProductData(codigo) {
            const fd = new FormData();
            fd.append('action', 'scan');
            fd.append('codigo_barras', codigo);
            
            try {
                const res = await fetch('api/inventario.php', { method: 'POST', body: fd });
                const r = await res.json();
                
                document.getElementById('reader').style.display = 'none';
                const s = document.getElementById('scanResult');
                s.style.display = 'block';

                if(r.success) {
                    document.getElementById('r_nombre').innerText = r.data.producto_nombre;
                    document.getElementById('r_stock').innerText = r.data.cantidad + ' ' + r.data.unidad_medida;
                    document.getElementById('r_estante').innerText = r.data.estante || 'Sin ubicar';
                    document.getElementById('r_vence').innerText = r.data.fecha_vencimiento ? r.data.fecha_vencimiento : 'No expira';
                    document.getElementById('r_code').innerText = codigo;
                } else {
                    document.getElementById('r_nombre').innerText = 'PRODUCTO NO ENCONTRADO';
                    document.getElementById('r_stock').innerText = '-';
                    document.getElementById('r_estante').innerText = '-';
                    document.getElementById('r_vence').innerText = '-';
                    document.getElementById('r_code').innerText = codigo;
                }
            } catch(e) { console.error(e); }
        }

        function resumeScanner() {
            document.getElementById('scanResult').style.display = 'none';
            document.getElementById('reader').style.display = 'block';
            if(html5QrcodeScanner) {
                html5QrcodeScanner.resume();
            }
        }
    </script>
</body>
</html>
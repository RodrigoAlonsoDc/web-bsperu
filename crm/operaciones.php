<?php
// crm/operaciones.php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operaciones y Almacen - ERP BS Peru</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root { --bg: #f4f6f9; --surface: #ffffff; --primary: #17a2b8; --text-primary: #333; --border: #dee2e6; --warning: #ffc107; --success: #28a745; }
        body { font-family: 'Roboto', sans-serif; background-color: var(--bg); margin: 0; padding: 0; }
        .layout { display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 60px; background: #6f42c1; display: flex; flex-direction: column; align-items: center; padding-top: 15px; gap: 20px; z-index: 100; }
        .sidebar a { color: white; text-decoration: none; font-size: 24px; }
        .sidebar a:hover { background: rgba(0,0,0,0.1); border-radius: 8px; }
        .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: var(--surface); position: relative; }
        .topbar { background: var(--border); height: 40px; display: flex; justify-content: flex-end; align-items: center; padding: 0 20px; flex-shrink: 0; }
        .content { padding: 20px; flex: 1; overflow-y: auto; }
        
        .card { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .btn { background: var(--primary); color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-size: 14px; display:inline-flex; align-items:center; gap:5px;}
        .btn-warning { background: var(--warning); color: #000; }
        .btn-success { background: var(--success); }
        table.ui-table { width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom:15px; }
        table.ui-table th, table.ui-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        
        /* Modal General */
        .modal { display: none; position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 20px; border-radius: 8px; width: 600px; max-width: 90%; max-height:90vh; overflow-y:auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; box-sizing: border-box; }

        /* Escaner */
        #reader { width: 100%; border-radius: 8px; overflow: hidden; display:none; margin-bottom:15px;}
        
        .picking-item { display:flex; justify-content:space-between; align-items:center; background:#f8f9fa; padding:10px; border:1px solid #ddd; margin-bottom:10px; border-radius:4px; }
        .picking-item.done { background:#d4edda; border-color:#c3e6cb; }
        .picking-qty { font-size:18px; font-weight:bold; }

        /* Estilo PDF Declaracion Jurada Oculto */
        #dj-template { display: none; width: 800px; padding: 40px; background: white; color: #000; font-family: Arial, sans-serif; font-size: 14px; line-height:1.6;}
        .dj-title { text-align: center; font-size: 20px; font-weight: bold; margin: 40px 0; text-decoration: underline; }
        .dj-logo { font-size: 24px; font-weight: bold; color: #004a99; border-bottom:2px solid #004a99; padding-bottom:10px; margin-bottom:20px;}
    </style>
</head>
<body>
    <div class="layout">
        <div class="sidebar">
            <a href="index.php" title="Dashboard"><i class="ph ph-squares-four"></i></a>
            <a href="clientes.php" title="Clientes"><i class="ph ph-users"></i></a>
            <a href="inventario.php" title="Inventario"><i class="ph ph-barcode"></i></a>
            <a href="pedidos.php" title="Cotizaciones"><i class="ph ph-file-text"></i></a>
            <a href="facturacion.php" title="Facturacion y Pagos"><i class="ph ph-receipt"></i></a>
            <a href="operaciones.php" title="Operaciones y Despacho" style="background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px;"><i class="ph ph-truck"></i></a>
        </div>
        <div class="main">
            <div class="topbar">ERP BS Peru - Modulo de Almacen / Despacho</div>
            <div class="content">
                <h2>Bandeja de Despachos</h2>
                <div class="card">
                    <p style="color:#666;">Pedidos pagados y listos para ser preparados (Picking) y entregados al cliente o agencia.</p>
                    <button class="btn" onclick="loadPedidos()" style="margin-bottom:15px;"><i class="ph ph-arrows-clockwise"></i> Refrescar</button>
                    <table class="ui-table">
                        <thead>
                            <tr>
                                <th>Cod. Cotizacion</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody id="pedidosBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Picking -->
    <div class="modal" id="pickingModal">
        <div class="modal-content">
            <h3>Preparacion de Pedido <span id="lbl_pick_codigo" style="color:var(--primary);"></span></h3>
            
            <div id="reader"></div>
            
            <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
                <button class="btn btn-warning" onclick="startScanner()"><i class="ph ph-camera"></i> Iniciar Escaner</button>
                <button class="btn" onclick="stopScanner()" style="background:#6c757d;"><i class="ph ph-camera-slash"></i> Detener Escaner</button>
            </div>

            <div id="itemsContainer" style="margin-bottom:20px;"></div>

            <hr>
            
            <div id="finalizarForm" style="display:none; background:#f4f6f9; padding:15px; border-radius:4px;">
                <h4>Cierre de Despacho</h4>
                <input type="hidden" id="f_cot_id">
                <input type="hidden" id="f_cliente_nombre">
                <div class="form-group">
                    <label>Modalidad de Entrega *</label>
                    <select id="f_modalidad" required onchange="toggleAgencia()">
                        <option value="Recojo en Local">Recojo en Local</option>
                        <option value="Envio a Provincia">Envio a Provincia</option>
                    </select>
                </div>
                <div class="form-group" id="box_agencia" style="display:none;">
                    <label>Agencia de Transporte</label>
                    <input type="text" id="f_agencia" placeholder="Ej. Shalom, Marvisur...">
                </div>
                <div class="form-group">
                    <label>N° Guia de Remision (Opcional)</label>
                    <input type="text" id="f_guia" placeholder="GR-XXXX">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn" style="background:#6c757d;" onclick="closePickingModal()">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="procesarDespacho()"><i class="ph ph-check-circle"></i> Confirmar Despacho</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DECLARACION JURADA PDF OCULTA -->
    <div id="pdf-container" style="display:none;">
        <div id="dj-template">
            <div class="dj-logo">BS PERÚ<br><span style="font-size:12px; font-weight:normal; color:#666;">Building Systems Peru S.A.C.</span></div>
            <div style="text-align:right;">
                Fecha: <span id="dj_fecha"></span><br>
                Lima, Peru
            </div>
            
            <div class="dj-title">DECLARACION JURADA DE TRANSPORTE</div>
            
            <p>Por medio del presente documento, la empresa <strong>BUILDING SYSTEMS PERU S.A.C.</strong> con RUC 20609793806, declara bajo juramento que los bienes detallados en la Cotizacion/Pedido N° <strong id="dj_codigo"></strong> han sido despachados y entregados a la agencia de transportes para su traslado a provincia.</p>
            
            <p><strong>DATOS DEL ENVIO:</strong></p>
            <ul>
                <li><strong>Cliente Destino:</strong> <span id="dj_cliente"></span></li>
                <li><strong>Agencia de Transporte:</strong> <span id="dj_agencia"></span></li>
                <li><strong>Guia de Remision:</strong> <span id="dj_guia"></span></li>
            </ul>

            <p>Declaramos que los productos enviados son materiales de construccion e insumos industriales que no se encuentran restringidos para su libre transito a nivel nacional.</p>
            
            <p>Se expide la presente Declaracion Jurada a solicitud del transportista para los fines que estime conveniente.</p>

            <br><br><br><br>
            <div style="text-align:center;">
                _______________________________________<br>
                <strong>Area de Operaciones y Logistica</strong><br>
                Building Systems Peru S.A.C.
            </div>
        </div>
    </div>

    <script>
        let html5QrcodeScanner = null;
        let currentItems = [];
        let pickingCotizacionId = 0;

        document.addEventListener('DOMContentLoaded', loadPedidos);

        async function loadPedidos() {
            try {
                const res = await fetch('api/despacho.php?action=list_facturadas');
                const data = await res.json();
                const tbody = document.getElementById('pedidosBody');
                tbody.innerHTML = '';
                if(data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No hay pedidos listos para despacho.</td></tr>';
                }
                data.forEach(c => {
                    tbody.innerHTML += `
                        <tr>
                            <td><strong>${c.codigo}</strong></td>
                            <td>${c.cliente_nombre}</td>
                            <td><span style="background:#28a745; color:#fff; padding:3px 6px; border-radius:4px; font-size:12px;">Facturada / Lista</span></td>
                            <td>
                                <button onclick="openPicking(${c.id}, '${c.codigo}', '${c.cliente_nombre}')" class="btn btn-warning"><i class="ph ph-package"></i> Preparar (Picking)</button>
                            </td>
                        </tr>
                    `;
                });
            } catch(e) {}
        }

        async function openPicking(id, codigo, cliente_nombre) {
            pickingCotizacionId = id;
            document.getElementById('lbl_pick_codigo').innerText = codigo;
            document.getElementById('f_cot_id').value = id;
            document.getElementById('f_cliente_nombre').value = cliente_nombre;
            
            // Cargar items
            try {
                const res = await fetch(`api/despacho.php?action=get_pedido_items&id=${id}`);
                const r = await res.json();
                currentItems = r.data.map(item => {
                    return { ...item, escaneados: 0 };
                });
                renderItems();
            } catch(e) {}

            document.getElementById('finalizarForm').style.display = 'none';
            document.getElementById('pickingModal').classList.add('active');
        }

        function renderItems() {
            const container = document.getElementById('itemsContainer');
            container.innerHTML = '';
            let allDone = true;

            currentItems.forEach(item => {
                let isDone = item.escaneados >= item.cantidad;
                if(!isDone) allDone = false;
                
                container.innerHTML += `
                    <div class="picking-item ${isDone ? 'done' : ''}">
                        <div>
                            <strong>${item.nombre}</strong><br>
                            <span style="font-size:12px; color:#555;">Cod: ${item.producto_id}</span>
                        </div>
                        <div class="picking-qty">
                            ${item.escaneados} / ${item.cantidad}
                        </div>
                    </div>
                `;
            });

            if(allDone && currentItems.length > 0) {
                stopScanner();
                document.getElementById('finalizarForm').style.display = 'block';
            }
        }

        function closePickingModal() {
            stopScanner();
            document.getElementById('pickingModal').classList.remove('active');
        }

        function startScanner() {
            if(html5QrcodeScanner == null) {
                html5QrcodeScanner = new Html5Qrcode("reader");
            }
            document.getElementById("reader").style.display = "block";
            html5QrcodeScanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 100 } },
                onScanSuccess,
                (errorMessage) => {}
            ).catch(err => { alert("Error de camara: " + err); });
        }

        function stopScanner() {
            if(html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    document.getElementById("reader").style.display = "none";
                }).catch(err => {});
            }
        }

        async function onScanSuccess(decodedText, decodedResult) {
            // Se escaneó un código (Ej. BSP-260817-0001)
            // Tenemos que saber a qué producto corresponde. Buscamos el primer item que falte escanear
            let targetItemIndex = currentItems.findIndex(i => i.escaneados < i.cantidad);
            if(targetItemIndex === -1) {
                alert("Ya terminaste de escanear todo.");
                return;
            }

            let targetItem = currentItems[targetItemIndex];
            
            // Pausar para que no escanee doble rápido
            stopScanner();

            const fd = new FormData();
            fd.append('action', 'scan_lote');
            fd.append('codigo_lote', decodedText);
            fd.append('producto_id', targetItem.producto_id);

            try {
                const res = await fetch('api/despacho.php', { method:'POST', body:fd });
                const r = await res.json();
                
                if(r.success) {
                    // Restado con éxito en DB
                    currentItems[targetItemIndex].escaneados++;
                    renderItems();
                    if(currentItems.findIndex(i => i.escaneados < i.cantidad) !== -1) {
                        // Si aún faltan, reiniciar escáner
                        startScanner();
                    }
                } else {
                    alert(r.error);
                    startScanner(); // reiniciar
                }
            } catch(e) { alert("Error de conexion."); startScanner(); }
        }

        function toggleAgencia() {
            const mod = document.getElementById('f_modalidad').value;
            document.getElementById('box_agencia').style.display = (mod === 'Envio a Provincia') ? 'block' : 'none';
        }

        async function procesarDespacho() {
            const id = document.getElementById('f_cot_id').value;
            const modalidad = document.getElementById('f_modalidad').value;
            const agencia = document.getElementById('f_agencia').value;
            const guia = document.getElementById('f_guia').value;

            const fd = new FormData();
            fd.append('action', 'finalizar_despacho');
            fd.append('cotizacion_id', id);
            fd.append('modalidad', modalidad);
            fd.append('agencia', agencia);
            fd.append('guia', guia);

            try {
                const res = await fetch('api/despacho.php', { method: 'POST', body: fd });
                const r = await res.json();
                if(r.success) {
                    closePickingModal();
                    alert('Despacho finalizado con exito.');
                    
                    if(modalidad === 'Envio a Provincia') {
                        if(confirm('¿Desea descargar la Declaracion Jurada en PDF?')) {
                            await generateDJ(document.getElementById('lbl_pick_codigo').innerText, document.getElementById('f_cliente_nombre').value, agencia, guia);
                        }
                    }
                    loadPedidos();
                } else {
                    alert(r.error);
                }
            } catch(error) {
                alert('Error al procesar despacho');
            }
        }

        async function generateDJ(codigo, cliente, agencia, guia) {
            document.getElementById('pdf-container').style.display = 'block';

            document.getElementById('dj_fecha').innerText = new Date().toISOString().substring(0, 10);
            document.getElementById('dj_codigo').innerText = codigo;
            document.getElementById('dj_cliente').innerText = cliente;
            document.getElementById('dj_agencia').innerText = agencia || 'No especificada';
            document.getElementById('dj_guia').innerText = guia || 'S/N';

            const element = document.getElementById('dj-template');
            element.style.display = 'block';
            
            const opt = {
                margin:       20,
                filename:     'DJ_' + codigo + '.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            await html2pdf().set(opt).from(element).save();
            
            element.style.display = 'none';
            document.getElementById('pdf-container').style.display = 'none';
        }
    </script>
</body>
</html>
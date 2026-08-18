<?php
// crm/facturacion.php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanzas / Facturacion - ERP BS Peru</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root { --bg: #f4f6f9; --surface: #ffffff; --primary: #17a2b8; --text-primary: #333; --border: #dee2e6; --success: #28a745; }
        body { font-family: 'Roboto', sans-serif; background-color: var(--bg); margin: 0; padding: 0; }
        .layout { display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 60px; background: var(--success); display: flex; flex-direction: column; align-items: center; padding-top: 15px; gap: 20px; z-index: 100; }
        .sidebar a { color: white; text-decoration: none; font-size: 24px; }
        .sidebar a:hover { background: rgba(0,0,0,0.1); border-radius: 8px; }
        .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: var(--surface); position: relative; }
        .topbar { background: var(--border); height: 40px; display: flex; justify-content: flex-end; align-items: center; padding: 0 20px; flex-shrink: 0; }
        .content { padding: 20px; flex: 1; overflow-y: auto; }
        
        .card { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .btn { background: var(--success); color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-size: 14px; display:inline-flex; align-items:center; gap:5px;}
        .btn-primary { background: var(--primary); }
        table.ui-table { width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom:15px; }
        table.ui-table th, table.ui-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        
        .modal { display: none; position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 20px; border-radius: 8px; width: 400px; max-width: 90%; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; box-sizing: border-box; }

        /* Estilo PDF Oculto (Similar a la Cotizacion pero es Factura/Comprobante) */
        #pdf-template {
            display: none; width: 800px; padding: 40px; background: white; color: #000;
            font-family: Arial, sans-serif; font-size: 11px;
        }
        .pdf-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .pdf-logo { font-size: 24px; font-weight: bold; color: #004a99; }
        .pdf-title { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; color:#28a745;}
        .pdf-client-box { border: 1px solid #000; padding: 5px; margin-bottom: 15px; }
        .pdf-client-box table { width: 100%; border-collapse: collapse; }
        .pdf-client-box td { padding: 3px; vertical-align: top; }
        .pdf-client-box td:first-child { font-weight: bold; width: 100px; }
        
        .pdf-items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .pdf-items-table th { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 5px; text-align: left; }
        .pdf-items-table td { padding: 5px; }
        
        .pdf-totals { width: 250px; float: right; border: 1px solid #000; border-collapse: collapse; margin-bottom: 15px; }
        .pdf-totals td { padding: 5px; border: 1px solid #000; }
        .pdf-totals td:first-child { font-weight: bold; }
        
        .pdf-footer-box { border: 1px solid #000; padding: 10px; margin-top: 60px; clear: both; font-size: 11px; text-align:center; font-weight:bold; color:#d32f2f;}
    </style>
</head>
<body>
    <div class="layout">
        <div class="sidebar">
            <a href="index.php" title="Dashboard"><i class="ph ph-squares-four"></i></a>
            <a href="clientes.php" title="Clientes"><i class="ph ph-users"></i></a>
            <a href="inventario.php" title="Inventario"><i class="ph ph-barcode"></i></a>
            <a href="pedidos.php" title="Cotizaciones"><i class="ph ph-file-text"></i></a>
            <a href="facturacion.php" title="Facturacion y Pagos" style="background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px;"><i class="ph ph-receipt"></i></a>
        </div>
        <div class="main">
            <div class="topbar">ERP BS Peru - Modulo de Finanzas</div>
            <div class="content">
                <h2>Validacion de Pagos y Facturacion</h2>
                <div class="card">
                    <p style="color:#666;">Cotizaciones aprobadas por el cliente que estan a la espera del deposito/pago para ser facturadas y enviadas a almacen.</p>
                    <table class="ui-table">
                        <thead>
                            <tr>
                                <th>Cod. Cotizacion</th>
                                <th>Fecha Aprob.</th>
                                <th>Cliente</th>
                                <th>Monto a Cobrar</th>
                                <th>Estado</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody id="pendientesBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pago -->
    <div class="modal" id="pagoModal">
        <div class="modal-content">
            <h3>Registrar Pago</h3>
            <p>Cotizacion: <strong id="lbl_cot_codigo"></strong></p>
            <p>Monto: <strong id="lbl_cot_monto" style="font-size:18px; color:var(--success);"></strong></p>
            <form id="pagoForm" onsubmit="procesarPago(event)">
                <input type="hidden" id="p_cot_id">
                <input type="hidden" id="p_cot_monto">
                <div class="form-group">
                    <label>Metodo de Pago *</label>
                    <select id="p_metodo" required>
                        <option value="">Seleccionar...</option>
                        <option value="Transferencia BCP">Transferencia BCP</option>
                        <option value="Transferencia BBVA">Transferencia BBVA</option>
                        <option value="Transferencia Interbank">Transferencia Interbank</option>
                        <option value="Yape">Yape</option>
                        <option value="Plin">Plin</option>
                        <option value="Efectivo">Efectivo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>N° Operacion / Referencia</label>
                    <input type="text" id="p_operacion" placeholder="Ej. 123456789">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn" style="background:#6c757d;" onclick="closePagoModal()">Cancelar</button>
                    <button type="submit" class="btn"><i class="ph ph-check-circle"></i> Validar Pago y Facturar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- PDF ESTRUCTURA OCULTA -->
    <div id="pdf-container" style="display:none;">
        <div id="pdf-template">
            <div class="pdf-header">
                <div class="pdf-logo">BS PERÚ<br><span style="font-size:10px; font-weight:normal; color:#666;">Building Systems Peru</span></div>
                <div style="text-align:right; font-size:12px;">
                    Fecha Impresion: <span id="pdf_fecha"></span>
                </div>
            </div>
            
            <div class="pdf-title">COMPROBANTE CANCELADO / FACTURA<br><span style="font-size:12px; color:#000;">Ref. Cotizacion: <span id="pdf_codigo"></span></span></div>
            
            <div class="pdf-client-box">
                <table>
                    <tr><td>Razon Social:</td><td id="pdf_rsocial"></td></tr>
                    <tr><td>RUC:</td><td id="pdf_ruc"></td></tr>
                    <tr><td>Metodo Pago:</td><td id="pdf_metodo" style="font-weight:bold;"></td></tr>
                    <tr><td>Operacion:</td><td id="pdf_op"></td></tr>
                </table>
            </div>

            <table class="pdf-items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Codigo</th>
                        <th>Descripcion</th>
                        <th>Cant.</th>
                        <th>UMed</th>
                        <th>P.Unit</th>
                        <th>SubTotal</th>
                    </tr>
                </thead>
                <tbody id="pdf_items"></tbody>
            </table>

            <table class="pdf-totals">
                <tr><td>Subtotal</td><td id="pdf_subtotal">S/.0.00</td></tr>
                <tr><td>IGV (18%)</td><td id="pdf_igv">S/.0.00</td></tr>
                <tr><td>TOTAL PAGADO</td><td id="pdf_total">S/.0.00</td></tr>
            </table>

            <div class="pdf-footer-box">
                ESTE DOCUMENTO ACREDITA QUE EL PAGO HA SIDO VERIFICADO Y CANCELADO.<br>
                VALIDO PARA INICIAR DESPACHO Y OPERACIONES.
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', loadPendientes);

        async function loadPendientes() {
            try {
                const res = await fetch('api/pagos.php?action=list_aceptadas');
                const data = await res.json();
                const tbody = document.getElementById('pendientesBody');
                tbody.innerHTML = '';
                if(data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No hay pagos pendientes de validacion.</td></tr>';
                }
                data.forEach(c => {
                    tbody.innerHTML += `
                        <tr>
                            <td><strong>${c.codigo}</strong></td>
                            <td>${c.created_at.substring(0, 10)}</td>
                            <td>${c.cliente_nombre}</td>
                            <td><strong>S/ ${c.total}</strong></td>
                            <td><span style="background:#ffc107; color:#000; padding:3px 6px; border-radius:4px; font-size:12px;">En Espera de Pago</span></td>
                            <td>
                                <button onclick="openPagoModal(${c.id}, '${c.codigo}', ${c.total})" class="btn"><i class="ph ph-currency-circle-dollar"></i> Registrar Pago</button>
                            </td>
                        </tr>
                    `;
                });
            } catch(e) {}
        }

        function openPagoModal(id, codigo, total) {
            document.getElementById('pagoForm').reset();
            document.getElementById('p_cot_id').value = id;
            document.getElementById('p_cot_monto').value = total;
            document.getElementById('lbl_cot_codigo').innerText = codigo;
            document.getElementById('lbl_cot_monto').innerText = 'S/ ' + total;
            document.getElementById('pagoModal').classList.add('active');
        }

        function closePagoModal() {
            document.getElementById('pagoModal').classList.remove('active');
        }

        async function procesarPago(e) {
            e.preventDefault();
            const id = document.getElementById('p_cot_id').value;
            const monto = document.getElementById('p_cot_monto').value;
            const metodo = document.getElementById('p_metodo').value;
            const op = document.getElementById('p_operacion').value;

            const fd = new FormData();
            fd.append('action', 'registrar_pago');
            fd.append('cotizacion_id', id);
            fd.append('monto_pagado', monto);
            fd.append('metodo_pago', metodo);
            fd.append('numero_operacion', op);

            try {
                const res = await fetch('api/pagos.php', { method: 'POST', body: fd });
                const r = await res.json();
                if(r.success) {
                    closePagoModal();
                    alert('Pago validado correctamente. Se generara el comprobante para Operaciones.');
                    await generateFacturaPDF(id, metodo, op);
                    loadPendientes();
                } else {
                    alert(r.error);
                }
            } catch(error) {
                alert('Error al registrar pago');
            }
        }

        async function generateFacturaPDF(cotizacion_id, metodo, op) {
            const res = await fetch('api/cotizaciones.php?action=get_details&id=' + cotizacion_id);
            const r = await res.json();
            if(!r.success) return;

            const c = r.data;
            document.getElementById('pdf-container').style.display = 'block';

            document.getElementById('pdf_fecha').innerText = new Date().toISOString().substring(0, 10);
            document.getElementById('pdf_codigo').innerText = c.codigo;
            document.getElementById('pdf_rsocial').innerText = c.razon_social;
            document.getElementById('pdf_ruc').innerText = c.ruc_dni;
            document.getElementById('pdf_metodo').innerText = metodo;
            document.getElementById('pdf_op').innerText = op || 'Sin codigo';

            const tbody = document.getElementById('pdf_items');
            tbody.innerHTML = '';
            let itemNum = 1;
            c.detalles.forEach(d => {
                tbody.innerHTML += `
                    <tr>
                        <td>${itemNum++}</td>
                        <td>${d.producto_id}</td>
                        <td>${d.producto_nombre}</td>
                        <td>${d.cantidad}</td>
                        <td>${d.unidad_medida || 'UNI'}</td>
                        <td>${d.precio_unitario}</td>
                        <td>${d.subtotal}</td>
                    </tr>
                `;
            });

            document.getElementById('pdf_subtotal').innerText = 'S/.' + c.subtotal;
            document.getElementById('pdf_igv').innerText = 'S/.' + c.igv;
            document.getElementById('pdf_total').innerText = 'S/.' + c.total;

            const element = document.getElementById('pdf-template');
            element.style.display = 'block';
            
            const opt = {
                margin:       10,
                filename:     'FACTURA_' + c.codigo + '.pdf',
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
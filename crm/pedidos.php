<?php
// crm/pedidos.php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotizaciones - ERP BS Peru</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        :root { --bg: #f4f6f9; --surface: #ffffff; --primary: #17a2b8; --text-primary: #333; --border: #dee2e6; --green: #28a745; }
        body { font-family: 'Roboto', sans-serif; background-color: var(--bg); margin: 0; padding: 0; }
        .layout { display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 60px; background: #28a745; display: flex; flex-direction: column; align-items: center; padding-top: 15px; gap: 20px; z-index: 100; }
        .sidebar a { color: white; text-decoration: none; font-size: 24px; }
        .sidebar a:hover { background: rgba(0,0,0,0.1); border-radius: 8px; }
        .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: var(--surface); position: relative; }
        .topbar { background: var(--border); height: 40px; display: flex; justify-content: flex-end; align-items: center; padding: 0 20px; flex-shrink: 0; }
        .content { padding: 20px; flex: 1; overflow-y: auto; }
        
        .tabs { display: flex; border-bottom: 2px solid var(--border); margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .tab { padding: 10px 20px; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; font-weight: 500; }
        .tab.active { border-bottom: 2px solid var(--primary); color: var(--primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .btn { background: var(--green); color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: var(--primary); }
        .btn-danger { background: #dc3545; }
        
        table.ui-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom:15px; }
        table.ui-table th, table.ui-table td { padding: 10px; text-align: left; border-bottom: 1px solid var(--border); }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; box-sizing: border-box; font-size: 14px;}
        
        .flex-row { display: flex; gap: 10px; align-items: flex-end; margin-bottom: 15px; }
        
        .card { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .totals-box { text-align: right; margin-top: 15px; font-size: 16px; line-height: 1.6; }

        /* Estilos especificos para el PDF Oculto */
        #pdf-template {
            display: none; width: 800px; padding: 40px; background: white; color: #000;
            font-family: Arial, sans-serif; font-size: 11px;
        }
        .pdf-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .pdf-logo { font-size: 24px; font-weight: bold; color: #004a99; }
        .pdf-title { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 20px; }
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
        
        .pdf-footer-box { border: 1px solid #000; padding: 5px; margin-top: 60px; clear: both; font-size: 10px;}
        .pdf-signature { margin-top: 40px; text-align: center; font-size: 11px; }
    </style>
</head>
<body>
    <div class="layout">
        <div class="sidebar">
            <a href="index.php" title="Dashboard"><i class="ph ph-squares-four"></i></a>
            <a href="clientes.php" title="Clientes"><i class="ph ph-users"></i></a>
            <a href="inventario.php" title="Inventario"><i class="ph ph-barcode"></i></a>
            <a href="pedidos.php" title="Cotizaciones" style="background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px;"><i class="ph ph-file-text"></i></a>
            <a href="facturacion.php" title="Facturacion y Pagos"><i class="ph ph-receipt"></i></a>
            <a href="operaciones.php" title="Operaciones y Despacho"><i class="ph ph-truck"></i></a>
        </div>
        <div class="main">
            <div class="topbar">ERP BS Peru - Modulo de Ventas</div>
            <div class="content">
                <h2 style="margin-top:0;">Cotizaciones</h2>
                
                <div class="tabs">
                    <div class="tab active" onclick="switchTab('nueva')" id="tabNuevaTitle">1. Nueva Cotizacion</div>
                    <div class="tab" onclick="switchTab('historial')">2. Historial de Cotizaciones</div>
                </div>

                <!-- TAB 1: NUEVA COTIZACION -->
                <div id="nueva" class="tab-content active">
                    <div class="card">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <h3 id="formTitle">Nueva Cotizacion</h3>
                            <button type="button" class="btn" style="background:#6c757d;" onclick="resetForm()">Nueva Cotizacion (Limpiar)</button>
                        </div>
                        <input type="hidden" id="c_edit_id" value="0">
                        <div class="flex-row">
                            <div class="form-group" style="flex:1;">
                                <label>Seleccionar Cliente *</label>
                                <select id="c_cliente" required onchange="loadClientData()"></select>
                            </div>
                        </div>
                        <div id="clientInfo" style="font-size:13px; color:#555; margin-bottom:20px;"></div>

                        <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">

                        <h3>Productos</h3>
                        <div class="flex-row">
                            <div class="form-group" style="flex:2;">
                                <label>Producto</label>
                                <select id="c_producto"></select>
                            </div>
                            <div class="form-group" style="flex:1;">
                                <label>Cantidad</label>
                                <input type="number" id="c_cantidad" min="1" value="1">
                            </div>
                            <div class="form-group" style="flex:1;">
                                <label>Precio Unit. (S/)</label>
                                <input type="number" id="c_precio" step="0.01" min="0">
                            </div>
                            <button type="button" class="btn btn-primary" style="margin-bottom:15px;" onclick="addProduct()"><i class="ph ph-plus"></i> Agregar</button>
                        </div>

                        <table class="ui-table" id="cartTable">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cant.</th>
                                    <th>P. Unit</th>
                                    <th>Subtotal</th>
                                    <th>Accion</th>
                                </tr>
                            </thead>
                            <tbody id="cartBody"></tbody>
                        </table>
                        
                        <div class="totals-box">
                            <p>Subtotal: S/ <span id="cartSubtotal">0.00</span></p>
                            <p>IGV (18%): S/ <span id="cartIgv">0.00</span></p>
                            <h3>Total: S/ <span id="cartTotal">0.00</span></h3>
                        </div>

                        <div style="text-align:right; margin-top:20px;">
                            <button class="btn btn-primary" onclick="saveQuotation()" style="font-size:16px; padding:12px 20px;"><i class="ph ph-floppy-disk"></i> Guardar y Generar PDF</button>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: HISTORIAL -->
                <div id="historial" class="tab-content">
                    <button class="btn" onclick="loadCotizaciones()" style="margin-bottom:15px;"><i class="ph ph-arrows-clockwise"></i> Actualizar</button>
                    <div style="overflow-x:auto;">
                        <table class="ui-table">
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Total (S/)</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="cotizacionesBody"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ESTRUCTURA OCULTA PARA EL PDF -->
    <div id="pdf-container" style="display:none;">
        <div id="pdf-template">
            <div class="pdf-header">
                <div class="pdf-logo">BS PERÚ<br><span style="font-size:10px; font-weight:normal; color:#666;">Building Systems Peru</span></div>
                <div style="text-align:right; font-size:12px;">
                    <span id="pdf_fecha"></span>
                </div>
            </div>
            
            <div class="pdf-title">COTIZACIONES: <span id="pdf_codigo"></span></div>
            
            <div class="pdf-client-box">
                <table>
                    <tr><td>Razon Social:</td><td id="pdf_rsocial"></td></tr>
                    <tr><td>Dirección:</td><td id="pdf_dir"></td></tr>
                    <tr><td>RUC:</td><td id="pdf_ruc"></td></tr>
                    <tr><td>E mail:</td><td id="pdf_email"></td></tr>
                    <tr><td>Telefono:</td><td id="pdf_tel"></td></tr>
                    <tr><td>Contacto:</td><td id="pdf_contacto"></td></tr>
                </table>
            </div>

            <p>De acuerdo con su amable solicitud, tenemos el agrado de cotizarle lo siguiente:</p>

            <table class="pdf-items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Codigo</th>
                        <th>Descripcion</th>
                        <th>Cantidad</th>
                        <th>UMed</th>
                        <th>Pre.Orig</th>
                        <th>Descto %</th>
                        <th>Prec.Total</th>
                        <th>SubTotal</th>
                    </tr>
                </thead>
                <tbody id="pdf_items"></tbody>
            </table>

            <table class="pdf-totals">
                <tr><td>Subtotal</td><td id="pdf_subtotal">S/.0.00</td></tr>
                <tr><td>IGV</td><td id="pdf_igv">S/.0.00</td></tr>
                <tr><td>Total Neto</td><td id="pdf_total">S/.0.00</td></tr>
            </table>

            <div class="pdf-footer-box">
                <p style="border-bottom:1px solid #000; margin:0 0 5px 0;">Observaciones -</p>
                <p style="margin:0;">CONDICIONES COMERCIALES<br>
                Forma de Pago: CONTADO CONTRA ENTREGA<br>
                Vigencia: 7 dias<br>
                El Horario de atención de las oficinas es de Lunes a Viernes 8:00 a 17:30 y Sábados 8:00 a 12:00 Horas<br>
                Entregamos certificados de calidad, hojas de seguridad (MSDS) y especificaciones técnicas de todos nuestros productos a solicitud del cliente</p>
                
                <p style="margin:5px 0 0 0;">CONSIDERACIONES PARA LA FABRICACIÓN DE PRODUCTOS HECHOS A PEDIDO:<br>
                Los productos que se elaboran bajo pedido, garantizan disponibilidad y calidad idónea de un producto de complejidad técnica.<br>
                Debido a este proceso, el tiempo de entrega puede variar entre 8 a 20 días útiles, dependiendo de la disponibilidad de la materia prima, stock y cantidad solicitada.<br>
                El plazo exacto será confirmado por el vendedor al momento de contar con la OC y abono respectivo.<br>
                Toda cancelación de dicho pedido puede ocasionar la perdida parcial o completa del abono realizado, dado que son productos que no pueden ser almacenados.</p>
                
                <p style="border-top:1px solid #000; margin:5px 0 0 0; padding-top:5px;">BUILDING SYSTEMS PERU S.A.C.<br>RUC: 20609793806</p>
                <p style="margin:0;">Deposito en cuenta Corriente<br>
                Cta. Cte. BCP Soles: 193-9902956-0-56 (CCI: 00219300990295605614)<br>
                Cta. Cte. BBVA Soles: 0011-0152-0100100654 (CCI: 011-152-000100100654-61)<br>
                Cta. Cte. Interbank Soles: 200-3005486597 (CCI: 003-200-003005486597-34)</p>
                <p style="margin:0;">SUCURSAL CHORRILLOS<br>AV. LOS FAISANES N° 675 URB. LA CAMPIÑA CHORRILLOS</p>
            </div>

            <div class="pdf-signature">
                <p style="margin:0; font-weight:bold;">ASESOR DE VENTAS</p>
                <p style="margin:0;">Asesor de ventas - CHORRILLOS</p>
            </div>
        </div>
    </div>

    <script>
        let clientesData = [];
        let productosData = [];
        let carrito = [];

        document.addEventListener('DOMContentLoaded', () => {
            loadInitialData();
            loadCotizaciones();
        });

        function switchTab(tabId) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            if(event) {
                event.target.classList.add('active');
            } else {
                if(tabId === 'nueva') document.getElementById('tabNuevaTitle').classList.add('active');
            }
            document.getElementById(tabId).classList.add('active');
        }

        function resetForm() {
            document.getElementById('c_edit_id').value = '0';
            document.getElementById('formTitle').innerText = 'Nueva Cotizacion';
            document.getElementById('tabNuevaTitle').innerText = '1. Nueva Cotizacion';
            document.getElementById('c_cliente').value = '';
            document.getElementById('clientInfo').innerHTML = '';
            carrito = [];
            renderCart();
        }

        async function loadInitialData() {
            try {
                let resC = await fetch('api/clientes.php?action=list');
                clientesData = await resC.json();
                let selC = document.getElementById('c_cliente');
                selC.innerHTML = '<option value="">-- Seleccionar Cliente --</option>';
                clientesData.forEach(c => {
                    selC.innerHTML += `<option value="${c.id}">${c.ruc_dni} - ${c.razon_social}</option>`;
                });

                let resP = await fetch('api/inventario.php?action=list_productos');
                productosData = await resP.json();
                let selP = document.getElementById('c_producto');
                selP.innerHTML = '<option value="">-- Seleccionar Producto --</option>';
                productosData.forEach(p => {
                    selP.innerHTML += `<option value="${p.id}">${p.nombre}</option>`;
                });
            } catch(e) { console.error("Error loading data", e); }
        }

        function loadClientData() {
            const id = document.getElementById('c_cliente').value;
            const c = clientesData.find(x => x.id == id);
            if(c) {
                document.getElementById('clientInfo').innerHTML = 
                    `<strong>RUC/DNI:</strong> ${c.ruc_dni} | <strong>Direccion:</strong> ${c.direccion || '-'} | <strong>Tel:</strong> ${c.telefono || '-'}`;
            } else {
                document.getElementById('clientInfo').innerHTML = '';
            }
        }

        function addProduct() {
            const pid = document.getElementById('c_producto').value;
            const cant = parseFloat(document.getElementById('c_cantidad').value);
            const pUnit = parseFloat(document.getElementById('c_precio').value);

            if(!pid || cant <= 0 || isNaN(pUnit)) {
                alert("Completa correctamente el producto, cantidad y precio.");
                return;
            }

            const prod = productosData.find(x => x.id == pid);
            carrito.push({
                producto_id: prod.id,
                nombre: prod.nombre,
                unidad: prod.unidad_medida,
                cantidad: cant,
                precio: pUnit
            });

            document.getElementById('c_producto').value = '';
            document.getElementById('c_cantidad').value = '1';
            document.getElementById('c_precio').value = '';
            renderCart();
        }

        function renderCart() {
            const tbody = document.getElementById('cartBody');
            tbody.innerHTML = '';
            let subtotal = 0;

            carrito.forEach((item, index) => {
                let st = item.cantidad * item.precio;
                subtotal += st;
                tbody.innerHTML += `
                    <tr>
                        <td>${item.nombre}</td>
                        <td>${item.cantidad}</td>
                        <td>S/ ${item.precio.toFixed(2)}</td>
                        <td>S/ ${st.toFixed(2)}</td>
                        <td><button onclick="removeProduct(${index})" class="btn btn-danger" style="padding:2px 6px;">X</button></td>
                    </tr>
                `;
            });

            let igv = subtotal * 0.18;
            let total = subtotal + igv;

            document.getElementById('cartSubtotal').innerText = subtotal.toFixed(2);
            document.getElementById('cartIgv').innerText = igv.toFixed(2);
            document.getElementById('cartTotal').innerText = total.toFixed(2);
        }

        function removeProduct(index) {
            carrito.splice(index, 1);
            renderCart();
        }

        async function saveQuotation() {
            const cliente_id = document.getElementById('c_cliente').value;
            const edit_id = document.getElementById('c_edit_id').value;
            if(!cliente_id) { alert('Selecciona un cliente'); return; }
            if(carrito.length === 0) { alert('Agrega al menos un producto'); return; }

            const payload = {
                edit_id: edit_id,
                cliente_id: cliente_id,
                detalles: carrito
            };

            try {
                const res = await fetch('api/cotizaciones.php?action=save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const r = await res.json();
                if(r.success) {
                    alert('Cotizacion Guardada: ' + r.codigo);
                    await generatePDF(r.cotizacion_id);
                    resetForm();
                    loadCotizaciones();
                } else {
                    alert(r.error);
                }
            } catch(e) { alert("Error guardando cotizacion"); }
        }

        async function editCotizacion(id) {
            try {
                const res = await fetch(`api/cotizaciones.php?action=get_details&id=${id}`);
                const r = await res.json();
                if(r.success) {
                    const c = r.data;
                    document.getElementById('c_edit_id').value = c.id;
                    document.getElementById('formTitle').innerText = 'Editar Cotizacion: ' + c.codigo;
                    document.getElementById('tabNuevaTitle').innerText = '1. Editar Cotizacion';
                    document.getElementById('c_cliente').value = c.cliente_id;
                    loadClientData();
                    
                    carrito = c.detalles.map(d => ({
                        producto_id: d.producto_id,
                        nombre: d.producto_nombre,
                        unidad: d.unidad_medida,
                        cantidad: parseInt(d.cantidad),
                        precio: parseFloat(d.precio)
                    }));
                    renderCart();
                    switchTab('nueva');
                }
            } catch(e) {}
        }

        async function generatePDF(cotizacion_id) {
            const res = await fetch('api/cotizaciones.php?action=get_details&id=' + cotizacion_id);
            const r = await res.json();
            if(!r.success) return;

            const c = r.data;
            document.getElementById('pdf-container').style.display = 'block';

            document.getElementById('pdf_fecha').innerText = c.created_at.substring(0, 10);
            document.getElementById('pdf_codigo').innerText = c.codigo;
            
            document.getElementById('pdf_rsocial').innerText = c.razon_social;
            document.getElementById('pdf_dir').innerText = c.direccion || '';
            document.getElementById('pdf_ruc').innerText = c.ruc_dni;
            document.getElementById('pdf_email').innerText = c.correo || '';
            document.getElementById('pdf_tel').innerText = c.telefono || '';
            document.getElementById('pdf_contacto').innerText = ''; 

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
                        <td>${d.precio}</td>
                        <td>0.00</td>
                        <td>${d.precio}</td>
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
                margin:       5,
                filename:     c.codigo + '.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            await html2pdf().set(opt).from(element).save();
            
            element.style.display = 'none';
            document.getElementById('pdf-container').style.display = 'none';
        }

        async function loadCotizaciones() {
            try {
                const res = await fetch('api/cotizaciones.php?action=list');
                const data = await res.json();
                const tbody = document.getElementById('cotizacionesBody');
                tbody.innerHTML = '';
                data.forEach(c => {
                    let btnAceptar = '';
                    let btnEditar = `<button onclick="editCotizacion(${c.id})" class="btn" style="background:#ffc107; color:#000; padding:4px 8px;" title="Editar"><i class="ph ph-pencil"></i></button>`;
                    
                    if(c.estado === 'Evaluacion') {
                        btnAceptar = `<button onclick="cambiarEstado(${c.id}, 'Aceptada')" class="btn btn-primary" style="padding:4px 8px;">Aceptar</button>`;
                    } else {
                        // Si ya no es Evaluacion, tal vez no queramos que se edite, o tal vez sí. Le dejaremos el boton por si acaso pero idealmente no se debe.
                    }

                    tbody.innerHTML += `
                        <tr>
                            <td><strong>${c.codigo}</strong></td>
                            <td>${c.created_at.substring(0, 10)}</td>
                            <td>${c.cliente_nombre}</td>
                            <td>S/ ${c.total}</td>
                            <td>
                                <span style="background:#eee; padding:3px 6px; border-radius:4px; font-size:12px;">${c.estado}</span>
                            </td>
                            <td>
                                <button onclick="generatePDF(${c.id})" class="btn" style="background:#6c757d; padding:4px 8px;" title="Descargar PDF"><i class="ph ph-download-simple"></i></button>
                                ${btnEditar}
                                ${btnAceptar}
                            </td>
                        </tr>
                    `;
                });
            } catch(e) {}
        }

        async function cambiarEstado(id, nuevoEstado) {
            if(!confirm(`¿Marcar cotizacion como ${nuevoEstado}?`)) return;
            const fd = new FormData();
            fd.append('action', 'update_status');
            fd.append('id', id);
            fd.append('estado', nuevoEstado);

            try {
                const res = await fetch('api/cotizaciones.php', { method: 'POST', body: fd });
                const r = await res.json();
                if(r.success) {
                    loadCotizaciones();
                }
            } catch(e) {}
        }
    </script>
</body>
</html>
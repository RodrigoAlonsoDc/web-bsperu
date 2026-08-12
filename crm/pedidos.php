<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../admin/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos de Venta - ERP BS Peru</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --bg-color: #f4f6f9;
            --primary: #17a2b8;
            --pink: #d81b60;
            --green: #28a745;
            --text-dark: #333;
            --border: #dee2e6;
            
            --btn-teal: #17a2b8;
            --btn-red: #dc3545;
            --btn-orange: #fd7e14;
            --btn-purple: #6f42c1;
            
            --row-blue: #cce5ff;
            --row-gray: #e2e3e5;
        }
        body { font-family: 'Roboto', sans-serif; background-color: var(--bg-color); margin: 0; padding: 0; color: var(--text-dark); }
        
        /* Navbar Sidebar estilo ERP */
        .layout { display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 60px; background: #28a745; display: flex; flex-direction: column; align-items: center; padding-top: 15px; gap: 20px; }
        .sidebar a { color: white; text-decoration: none; font-size: 24px; opacity: 0.8; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { opacity: 1; background: rgba(0,0,0,0.1); padding: 10px; border-radius: 8px; }
        
        .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: white; }
        
        .topbar { background: #343a40; height: 40px; display: flex; justify-content: flex-end; align-items: center; padding: 0 20px; color: white; }
        
        /* Contenido de la Tabla */
        .content { padding: 20px; flex: 1; overflow-y: auto; }
        .toolbar { display: flex; justify-content: space-between; margin-bottom: 15px; align-items: center; }
        .toolbar select { padding: 5px; border: 1px solid var(--border); }
        .toolbar input { padding: 5px 10px; border: 1px solid var(--border); border-radius: 4px; }
        
        .btn-green { background: var(--green); color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        
        /* Tabla ERP */
        table.erp-table { width: 100%; border-collapse: collapse; font-size: 11px; color: #555; }
        table.erp-table th { padding: 10px 5px; text-align: left; border-bottom: 2px solid var(--border); color: #777; font-weight: 500; }
        table.erp-table td { padding: 8px 5px; vertical-align: middle; }
        table.erp-table tbody tr:nth-child(odd) { background-color: var(--row-blue); }
        table.erp-table tbody tr:nth-child(even) { background-color: var(--row-gray); }
        
        .actions-col { display: flex; gap: 3px; }
        .btn-action { width: 24px; height: 24px; border: none; border-radius: 3px; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; }
        .btn-action.teal { background: var(--btn-teal); }
        .btn-action.red { background: var(--btn-red); }
        .btn-action.orange { background: var(--btn-orange); }
        .btn-action.purple { background: var(--btn-purple); }
        
        /* Modal ERP Formulario */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; align-items: flex-start; justify-content: center; opacity: 0; pointer-events: none; transition: 0.2s; z-index: 1000; padding: 20px; box-sizing: border-box; overflow-y: auto; }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal-card { background: var(--bg-color); width: 100%; max-width: 1200px; border-radius: 4px; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.3); margin-bottom: 40px; }
        .modal-header { padding: 15px 20px; background: white; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 20px; flex: 1; }
        .modal-footer { padding: 15px 20px; background: white; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; }
        
        /* Grid del Formulario */
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; align-items: flex-start; }
        .form-col { flex: 1; background: white; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 20px; position: relative; }
        
        .col-header { position: absolute; top: -15px; left: 20px; background: var(--pink); color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .col-title { font-size: 16px; color: #555; margin-left: 60px; margin-bottom: 20px; margin-top: -5px; font-weight: 300; }
        
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 11px; color: #888; margin-bottom: 4px; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 2px; font-size: 12px; outline: none; box-sizing: border-box; }
        .form-group input[readonly] { background: #e9ecef; }
        .form-group.inline { display: flex; gap: 10px; }
        .form-group.inline > div { flex: 1; }
        
        .client-section { background: white; padding: 20px; border-left: 5px solid var(--green); margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .client-section h3 { margin: 0 0 15px 0; font-weight: 300; font-size: 18px; color: #555; }
        .client-grid { display: grid; grid-template-columns: 100px 1fr; gap: 10px; align-items: center; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px; }
        .client-grid label { color: #17a2b8; font-weight: 500; }
        .client-grid span { color: #dc3545; font-weight: 500; }
        .client-grid span.blue { color: blue; font-weight: bold; }
        
        .cart-section { background: white; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .cart-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 12px; }
        .cart-table th, .cart-table td { padding: 8px; border: 1px solid #ddd; }
        .cart-table th { background: #f8f9fa; }
        
    </style>
</head>
<body>

    <div class="layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <a href="index.php" title="Volver al Menú"><i class="ph ph-squares-four"></i></a>
            <a href="#" class="active" title="Pedidos"><i class="ph ph-browser"></i></a>
            <a href="#" title="Despachos"><i class="ph ph-forklift"></i></a>
            <a href="#" title="Reportes"><i class="ph ph-clipboard-text"></i></a>
        </div>

        <div class="main">
            <!-- Topbar -->
            <div class="topbar">
                <i class="ph ph-user"></i>&nbsp; Administrador
            </div>

            <!-- Content -->
            <div class="content">
                <div class="toolbar">
                    <div>
                        Show <select><option>10</option><option>50</option><option>100</option></select> entries
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" placeholder="Buscar Registros">
                        <button class="btn-green" onclick="abrirModal()"><i class="ph ph-plus"></i> Nuevo Pedido</button>
                    </div>
                </div>

                <table class="erp-table" id="pedidosTable">
                    <thead>
                        <tr>
                            <th>ID ↑↓</th>
                            <th>Fecha Doc ↑↓</th>
                            <th>Cliente ↑↓</th>
                            <th>Importe ↑↓</th>
                            <th>Usuario ↑↓</th>
                            <th>Doc STS ↑↓</th>
                            <th>Despacho ↑↓</th>
                            <th>Detalle ↑↓</th>
                            <th>Estado ↑↓</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Llenado por JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal ERP: Nuevo Pedido -->
    <div class="modal-overlay" id="pedidoModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 style="margin:0; font-weight:400;">Registro de Pedido Venta</h3>
                <button style="background:none;border:none;font-size:20px;cursor:pointer;" onclick="cerrarModal()">&times;</button>
            </div>
            
            <form id="pedidoForm">
                <input type="hidden" name="action" value="save">
                <input type="hidden" id="h_subtotal" name="subtotal" value="0">
                <input type="hidden" id="h_igv" name="igv" value="0">
                <input type="hidden" id="h_total" name="total" value="0">
                <input type="hidden" id="h_productos" name="productos" value="[]">

                <div class="modal-body">
                    
                    <!-- 3 Columnas Cabecera -->
                    <div class="form-row">
                        <!-- Col 1 -->
                        <div class="form-col">
                            <div class="col-header"><i class="ph ph-file-text"></i></div>
                            <div class="col-title">Cabecera</div>
                            
                            <div class="form-group inline">
                                <div>
                                    <label>Fecha</label>
                                    <input type="date" name="fecha_doc" id="f_fecha_doc" required>
                                </div>
                                <div>
                                    <label>&nbsp;</label>
                                    <select name="sucursal">
                                        <option value="SUCURSAL CHORRILLOS">SUCURSAL CHORRILLOS</option>
                                        <option value="SUCURSAL LIMA">SUCURSAL LIMA</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group inline">
                                <div>
                                    <select name="forma_pago">
                                        <option value="CONTADO CONTRAENTREGA">CONTADO CONTRAENTREGA</option>
                                        <option value="CREDITO 30 DIAS">CREDITO 30 DIAS</option>
                                    </select>
                                </div>
                                <div>
                                    <select name="gestor_campo">
                                        <option value="02-PATRICIA GAMBOA">02-PATRICIA GAMBOA</option>
                                        <option value="04-KAREN RIVERA">04-KAREN RIVERA</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="margin-top:-15px;">gestor de tienda</label>
                                    <input type="text" name="gestor_tienda" value="HELEN REYES" readonly>
                                </div>
                            </div>

                            <div class="form-group inline">
                                <div style="flex: 2;">
                                    <label>Cliente (RUC)</label>
                                    <div style="display:flex;">
                                        <input type="text" name="documento" id="input_documento" placeholder="RUC">
                                        <button type="button" onclick="buscarDocumento()" style="background:#17a2b8; color:white; border:none; padding:0 10px; cursor:pointer;"><i class="ph ph-magnifying-glass"></i></button>
                                    </div>
                                </div>
                                <div style="flex: 1;">
                                    <label>% Descuento Global</label>
                                    <input type="number" name="descuento" value="0">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Razon Social</label>
                                <input type="text" name="cliente" id="input_cliente" readonly required>
                            </div>
                            
                            <div class="form-group">
                                <label>Dirección</label>
                                <input type="text" name="direccion" id="input_direccion" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label>Lugar de Entrega</label>
                                <input type="text" name="lugar_entrega">
                            </div>
                            
                            <div class="form-group">
                                <label>Departamento</label>
                                <input type="text" name="departamento">
                            </div>
                        </div>
                        
                        <!-- Col 2 -->
                        <div class="form-col">
                            <div class="col-header"><i class="ph ph-file-text"></i></div>
                            <div class="col-title">Cabecera</div>
                            
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <select name="lugar_compra">
                                    <option value="Lugar Compra">Lugar Compra</option>
                                    <option value="TIENDA FISICA">TIENDA FISICA</option>
                                    <option value="ONLINE">ONLINE</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Tiempo Entrega (Días)</label>
                                <input type="number" name="tiempo_entrega" value="3" style="background:#e8f0fe;">
                            </div>
                            
                            <div class="form-group">
                                <select name="tipo_despacho">
                                    <option value="RECOJO EN PLANTA">RECOJO EN PLANTA</option>
                                    <option value="RECOJO EN TIENDA">RECOJO EN TIENDA</option>
                                    <option value="DELIVERY">DELIVERY</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Glosa</label>
                                <input type="text" name="glosa">
                            </div>
                            
                            <div class="form-group">
                                <label>Comentario</label>
                                <input type="text" name="comentario">
                            </div>
                        </div>
                        </div>
                    </div>
                    
                    <!-- Datos de Cliente -->
                    <div class="client-section">
                        <h3>Datos de Cliente:</h3>
                        <div class="form-group">
                            <select id="clienteSelectVisual">
                                <option>Seleccione o busque arriba...</option>
                            </select>
                        </div>
                        
                        <div class="client-grid">
                            <label>RUC:</label>
                            <span class="blue" id="lbl_ruc">-</span>
                            
                            <label>Cliente:</label>
                            <span id="lbl_razon_social">-</span>
                            
                            <label>Telefono (Verificar):</label>
                            <input type="text" name="telefono" style="border:none; border-bottom:1px solid #ccc; outline:none; color:red; font-weight:bold; padding:2px;">
                            
                            <label>Contacto:</label>
                            <input type="text" name="contacto" style="border:none; border-bottom:1px solid #ccc; outline:none; color:red; font-weight:bold; padding:2px;">
                            
                            <label>Tipo Control:</label>
                            <div style="border:1px solid #ccc; padding:5px; display:inline-flex; gap:10px;">
                                <label style="color:#555; font-weight:normal;"><input type="radio" name="tipo_control" value="Visita"> Visita</label>
                                <label style="color:#555; font-weight:normal;"><input type="radio" name="tipo_control" value="Llamada"> Llamada</label>
                                <label style="color:#555; font-weight:normal;"><input type="radio" name="tipo_control" value="whatsapp" checked> whatsapp</label>
                            </div>
                        </div>
                        
                        <h3 style="margin-top:20px;">¿Va a registrar un pedido?</h3>
                        <div style="display:flex; gap:10px;">
                            <button type="button" class="btn-green" style="width:60px; justify-content:center;">SI</button>
                            <button type="button" style="background:#dc3545; color:white; border:none; border-radius:4px; width:60px; cursor:pointer;">NO</button>
                        </div>
                    </div>
                    
                    <!-- Carrito de Compras Original (Para añadir productos) -->
                    <div class="cart-section">
                        <h3>Productos del Pedido</h3>
                        <div style="display:flex; gap:10px;">
                            <select id="productoSelect" style="padding:8px; flex:1; border:1px solid #ccc;">
                                <option value="">-- Buscar Producto --</option>
                            </select>
                            <button type="button" class="btn-green" onclick="agregarAlCarrito()"><i class="ph ph-plus"></i> Añadir</button>
                        </div>
                        
                        <table class="cart-table" id="cartTable">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Cant.</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        
                        <div style="text-align:right; margin-top:15px; font-size:14px;">
                            <div>Subtotal: <span id="lbl_subtotal">S/ 0.00</span></div>
                            <div>IGV (18%): <span id="lbl_igv">S/ 0.00</span></div>
                            <div style="font-size:18px; font-weight:bold; color:var(--primary); margin-top:5px;">
                                Total: <span id="lbl_total">S/ 0.00</span>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" style="padding:8px 20px; background:#e2e3e5; border:none; cursor:pointer;" onclick="cerrarModal()">Cerrar</button>
                    <button type="submit" class="btn-green">Guardar Pedido</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('f_fecha_doc').valueAsDate = new Date();
        
        let catalogoProductos = [];
        let carrito = [];

        // BUSCADOR RUC/DNI (API)
        async function buscarDocumento() {
            const numero = document.getElementById('input_documento').value.trim();
            if(!numero) { alert("Ingresa un DNI/RUC"); return; }
            
            try {
                const res = await fetch('api_reniec.php?numero=' + numero);
                const data = await res.json();
                if(data.success) {
                    document.getElementById('input_cliente').value = data.nombre;
                    
                    // Llenar abajo también
                    document.getElementById('lbl_ruc').innerText = numero;
                    document.getElementById('lbl_razon_social').innerText = data.nombre;
                    document.getElementById('clienteSelectVisual').innerHTML = `<option>${data.nombre}</option>`;
                    
                } else {
                    alert("Documento no encontrado");
                }
            } catch(e) { alert("Error de conexión"); }
        }

        // CARGAR CATÁLOGO
        async function cargarCatalogo() {
            try {
                const res = await fetch('../assets/Data/productos.json');
                catalogoProductos = await res.json();
                const select = document.getElementById('productoSelect');
                catalogoProductos.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.sku;
                    opt.textContent = `${p.sku} - ${p.nombre} (S/ ${p.precio || '0.00'})`;
                    select.appendChild(opt);
                });
            } catch(e) { console.log(e); }
        }

        // CARGAR TABLA ERP
        async function cargarPedidos() {
            try {
                const res = await fetch('api_pedidos.php?action=list');
                const pedidos = await res.json();
                
                const tbody = document.querySelector('#pedidosTable tbody');
                tbody.innerHTML = '';
                
                pedidos.forEach(p => {
                    // Limpiar strings
                    const clienteSplit = p.cliente.substring(0, 35) + "...";
                    const gestorSplit = (p.gestor_campo || '').split('-')[1] || p.gestor_campo;
                    
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${p.id}</td>
                        <td>${p.fecha_doc} <br><span style="color:#999">00:00:00</span></td>
                        <td>${clienteSplit}</td>
                        <td>${parseFloat(p.total).toFixed(6)}</td>
                        <td style="color:#17a2b8;">${gestorSplit}</td>
                        <td>${p.doc_sts}</td>
                        <td>${p.tipo_despacho}</td>
                        <td>${p.detalle}</td>
                        <td>${p.estado}</td>
                        <td class="actions-col">
                            <button class="btn-action teal"><i class="ph ph-magnifying-glass"></i></button>
                            <button class="btn-action red" onclick="eliminarPedido('${p.id}')"><i class="ph ph-x"></i></button>
                            <button class="btn-action orange"><i class="ph ph-user"></i></button>
                            <button class="btn-action purple"><i class="ph ph-printer"></i></button>
                            <button class="btn-action purple"><i class="ph ph-printer"></i></button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch(e) { console.log(e); }
        }

        async function eliminarPedido(id) {
            if(!confirm("¿Eliminar pedido " + id + "?")) return;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            await fetch('api_pedidos.php', { method: 'POST', body: formData });
            cargarPedidos();
        }

        // CARRITO LOGIC
        function agregarAlCarrito() {
            const sku = document.getElementById('productoSelect').value;
            if(!sku) return;
            const producto = catalogoProductos.find(p => p.sku === sku);
            if(!producto) return;
            
            const existe = carrito.find(item => item.sku === sku);
            if(existe) { existe.cantidad += 1; } 
            else {
                carrito.push({ sku: producto.sku, nombre: producto.nombre, precio: parseFloat(producto.precio || 0), cantidad: 1 });
            }
            actualizarTablaCarrito();
        }

        function actualizarTablaCarrito() {
            const tbody = document.querySelector('#cartTable tbody');
            tbody.innerHTML = '';
            let subtotal = 0;
            carrito.forEach((item, i) => {
                const subt = item.precio * item.cantidad;
                subtotal += subt;
                tbody.innerHTML += `
                    <tr>
                        <td>${item.nombre}</td>
                        <td>S/ ${item.precio.toFixed(2)}</td>
                        <td><input type="number" value="${item.cantidad}" min="1" onchange="carrito[${i}].cantidad=this.value; actualizarTablaCarrito()" style="width:50px;"></td>
                        <td>S/ ${subt.toFixed(2)}</td>
                        <td><button type="button" style="color:red;border:none;background:none;cursor:pointer;" onclick="carrito.splice(${i},1); actualizarTablaCarrito()">X</button></td>
                    </tr>
                `;
            });
            const igv = subtotal * 0.18;
            const total = subtotal + igv;
            document.getElementById('lbl_subtotal').innerText = 'S/ ' + subtotal.toFixed(2);
            document.getElementById('lbl_igv').innerText = 'S/ ' + igv.toFixed(2);
            document.getElementById('lbl_total').innerText = 'S/ ' + total.toFixed(2);
            
            document.getElementById('h_subtotal').value = subtotal;
            document.getElementById('h_igv').value = igv;
            document.getElementById('h_total').value = total;
            document.getElementById('h_productos').value = JSON.stringify(carrito);
        }

        // MODAL
        function abrirModal() {
            document.getElementById('pedidoForm').reset();
            document.getElementById('f_fecha_doc').valueAsDate = new Date();
            document.getElementById('lbl_ruc').innerText = '-';
            document.getElementById('lbl_razon_social').innerText = '-';
            carrito = []; actualizarTablaCarrito();
            document.getElementById('pedidoModal').classList.add('active');
        }
        function cerrarModal() { document.getElementById('pedidoModal').classList.remove('active'); }

        // SUBMIT
        document.getElementById('pedidoForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            if(carrito.length === 0) { alert("Agrega al menos un producto"); return; }
            const formData = new FormData(e.target);
            try {
                const res = await fetch('api_pedidos.php', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.success) {
                    cerrarModal(); cargarPedidos();
                } else { alert("Error: " + data.error); }
            } catch(e) { alert("Error de conexión"); }
        });

        // INIT
        cargarCatalogo();
        cargarPedidos();
    </script>
</body>
</html>

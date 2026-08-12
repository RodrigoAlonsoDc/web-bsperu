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
    <title>Gestión de Pedidos - CRM BS Peru</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --bg-color: #f0f2f5;
            --primary: #3f51b5;
            --pink: #e91e63;
            --text-dark: #333;
            --text-gray: #777;
            --border: #e0e0e0;
        }
        body { font-family: 'Roboto', sans-serif; background-color: var(--bg-color); margin: 0; padding: 0; color: var(--text-dark); }
        
        /* Navbar */
        .navbar { background: white; padding: 15px 30px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .nav-left { display: flex; align-items: center; gap: 20px; }
        .back-btn { color: var(--text-gray); text-decoration: none; display: flex; align-items: center; gap: 5px; font-weight: 500; }
        .back-btn:hover { color: var(--primary); }
        .navbar h1 { font-size: 20px; margin: 0; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .navbar h1 i { color: var(--pink); font-size: 24px; }
        
        /* Contenido */
        .container { padding: 30px; max-width: 1200px; margin: 0 auto; }
        .toolbar { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .btn-primary:hover { background: #303f9f; }
        
        /* Tabla */
        .card { background: white; border-radius: 4px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid var(--border); font-size: 14px; }
        th { background: #fafafa; color: var(--text-gray); font-weight: 500; text-transform: uppercase; font-size: 12px; }
        
        /* Etiquetas de Estado */
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; cursor: pointer; }
        .status-pendiente { background: #fff3e0; color: #e65100; border: 1px solid #ffe0b2; }
        .status-proceso { background: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; }
        .status-despachado { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .status-cancelado { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        
        /* Modal Nuevo Pedido */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: 0.2s; z-index: 1000; }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal-card { background: white; width: 100%; max-width: 800px; border-radius: 4px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-header { padding: 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { margin: 0; font-size: 18px; font-weight: 500; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-gray); }
        .modal-body { padding: 20px; overflow-y: auto; flex: 1; }
        .modal-footer { padding: 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; background: #fafafa; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; color: var(--text-gray); margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px; outline: none; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus { border-color: var(--primary); }
        
        /* Tabla Carrito */
        .cart-section { border: 1px solid var(--border); border-radius: 4px; overflow: hidden; margin-bottom: 20px; }
        .cart-header { background: #f5f5f5; padding: 10px 15px; display: flex; gap: 10px; border-bottom: 1px solid var(--border); }
        .cart-header select { flex: 1; padding: 8px; border: 1px solid var(--border); border-radius: 4px; }
        .btn-add { background: var(--primary); color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        
        .cart-table th, .cart-table td { padding: 10px 15px; }
        .btn-remove { color: #e91e63; background: none; border: none; cursor: pointer; font-size: 18px; }
        
        .totals-section { text-align: right; padding: 15px; background: #fafafa; border-top: 1px solid var(--border); }
        .totals-row { display: flex; justify-content: flex-end; gap: 20px; margin-bottom: 5px; font-size: 14px; }
        .totals-row.grand-total { font-size: 18px; font-weight: 500; margin-top: 10px; color: var(--primary); }
        
        .btn-cancel { background: transparent; color: var(--text-gray); border: 1px solid var(--border); padding: 10px 20px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

    <div class="navbar">
        <div class="nav-left">
            <a href="index.php" class="back-btn"><i class="ph ph-arrow-left"></i> Volver al Menú</a>
            <h1><i class="ph ph-squares-four"></i> Módulo de Pedidos (Ventas)</h1>
        </div>
    </div>

    <div class="container">
        <div class="toolbar">
            <div></div> <!-- Espaciador -->
            <button class="btn-primary" onclick="abrirModal()"><i class="ph ph-plus"></i> Nuevo Pedido</button>
        </div>

        <div class="card">
            <table id="pedidosTable">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Subtotal</th>
                        <th>IGV (18%)</th>
                        <th>Total Final</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Se llena con JS -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Nuevo Pedido -->
    <div class="modal-overlay" id="pedidoModal">
        <div class="modal-card">
            <div class="modal-header">
                <h2>Crear Nuevo Pedido</h2>
                <button class="modal-close" onclick="cerrarModal()">&times;</button>
            </div>
            <form id="pedidoForm">
                <input type="hidden" name="action" value="save">
                <input type="hidden" id="h_subtotal" name="subtotal" value="0">
                <input type="hidden" id="h_igv" name="igv" value="0">
                <input type="hidden" id="h_total" name="total" value="0">
                <input type="hidden" id="h_productos" name="productos" value="[]">

                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre del Cliente / Razón Social</label>
                            <input type="text" name="cliente" required>
                        </div>
                        <div class="form-group">
                            <label>DNI / RUC</label>
                            <input type="text" name="documento">
                        </div>
                    </div>

                    <div class="cart-section">
                        <div class="cart-header">
                            <select id="productoSelect">
                                <option value="">-- Seleccionar Producto del Catálogo --</option>
                                <!-- Se llena con JS -->
                            </select>
                            <button type="button" class="btn-add" onclick="agregarAlCarrito()"><i class="ph ph-plus"></i> Agregar</button>
                        </div>
                        <table class="cart-table" id="cartTable">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th width="100">Precio Unit.</th>
                                    <th width="100">Cant.</th>
                                    <th width="100">Subtotal</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Carrito dinámico -->
                            </tbody>
                        </table>
                        <div class="totals-section">
                            <div class="totals-row">
                                <span>Subtotal:</span>
                                <span id="lbl_subtotal">S/ 0.00</span>
                            </div>
                            <div class="totals-row">
                                <span>IGV (18%):</span>
                                <span id="lbl_igv">S/ 0.00</span>
                            </div>
                            <div class="totals-row grand-total">
                                <span>Total a Cobrar:</span>
                                <span id="lbl_total">S/ 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Registrar Pedido</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let catalogoProductos = [];
        let carrito = [];

        // 1. Cargar el catálogo real para el select
        async function cargarCatalogo() {
            try {
                // Leemos directo del json original como lo hace la web
                const res = await fetch('../assets/Data/productos.json');
                catalogoProductos = await res.json();
                
                const select = document.getElementById('productoSelect');
                catalogoProductos.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.sku;
                    opt.textContent = `${p.sku} - ${p.nombre} (S/ ${p.precio || '0.00'})`;
                    select.appendChild(opt);
                });
            } catch(e) {
                console.error("No se pudo cargar el catálogo", e);
            }
        }

        // 2. Cargar lista de Pedidos
        async function cargarPedidos() {
            try {
                const res = await fetch('api_pedidos.php?action=list');
                const pedidos = await res.json();
                
                const tbody = document.querySelector('#pedidosTable tbody');
                tbody.innerHTML = '';
                
                pedidos.forEach(p => {
                    let badgeClass = 'status-pendiente';
                    if(p.estado === 'En Proceso') badgeClass = 'status-proceso';
                    if(p.estado === 'Despachado') badgeClass = 'status-despachado';
                    if(p.estado === 'Cancelado') badgeClass = 'status-cancelado';
                    
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${p.id}</strong></td>
                        <td>${p.fecha}</td>
                        <td>${p.cliente} <br><small style="color:#777">${p.documento}</small></td>
                        <td>S/ ${p.subtotal.toFixed(2)}</td>
                        <td>S/ ${p.igv.toFixed(2)}</td>
                        <td><strong>S/ ${p.total.toFixed(2)}</strong></td>
                        <td>
                            <span class="status-badge ${badgeClass}" onclick="cambiarEstado('${p.id}', '${p.estado}')">
                                ${p.estado}
                            </span>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch(e) {
                console.error("Error al cargar pedidos");
            }
        }

        // Cambiar Estado rápido
        async function cambiarEstado(id, estadoActual) {
            const estados = ['Pendiente', 'En Proceso', 'Despachado', 'Cancelado'];
            let nextIndex = (estados.indexOf(estadoActual) + 1) % estados.length;
            let nuevoEstado = estados[nextIndex];
            
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('id', id);
            formData.append('estado', nuevoEstado);
            
            await fetch('api_pedidos.php', { method: 'POST', body: formData });
            cargarPedidos();
        }

        // Lógica del Carrito
        function agregarAlCarrito() {
            const sku = document.getElementById('productoSelect').value;
            if(!sku) return;
            
            const producto = catalogoProductos.find(p => p.sku === sku);
            if(!producto) return;
            
            // Revisar si ya existe en carrito
            const existe = carrito.find(item => item.sku === sku);
            if(existe) {
                existe.cantidad += 1;
            } else {
                carrito.push({
                    sku: producto.sku,
                    nombre: producto.nombre,
                    precio: parseFloat(producto.precio || 0),
                    cantidad: 1
                });
            }
            actualizarTablaCarrito();
        }

        function cambiarCantidad(index, cant) {
            carrito[index].cantidad = parseInt(cant) || 1;
            actualizarTablaCarrito();
        }

        function quitarDelCarrito(index) {
            carrito.splice(index, 1);
            actualizarTablaCarrito();
        }

        function actualizarTablaCarrito() {
            const tbody = document.querySelector('#cartTable tbody');
            tbody.innerHTML = '';
            
            let subtotal = 0;
            
            carrito.forEach((item, i) => {
                const subtotalItem = item.precio * item.cantidad;
                subtotal += subtotalItem;
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.nombre} <br><small>${item.sku}</small></td>
                    <td>S/ ${item.precio.toFixed(2)}</td>
                    <td><input type="number" value="${item.cantidad}" min="1" style="width:60px; padding:5px" onchange="cambiarCantidad(${i}, this.value)"></td>
                    <td>S/ ${subtotalItem.toFixed(2)}</td>
                    <td><button type="button" class="btn-remove" onclick="quitarDelCarrito(${i})"><i class="ph ph-x-circle"></i></button></td>
                `;
                tbody.appendChild(tr);
            });
            
            // Cálculos con IGV
            const igv = subtotal * 0.18;
            const total = subtotal + igv;
            
            document.getElementById('lbl_subtotal').innerText = 'S/ ' + subtotal.toFixed(2);
            document.getElementById('lbl_igv').innerText = 'S/ ' + igv.toFixed(2);
            document.getElementById('lbl_total').innerText = 'S/ ' + total.toFixed(2);
            
            // Guardar en inputs hidden para mandar al PHP
            document.getElementById('h_subtotal').value = subtotal;
            document.getElementById('h_igv').value = igv;
            document.getElementById('h_total').value = total;
            document.getElementById('h_productos').value = JSON.stringify(carrito);
        }

        // Modal
        function abrirModal() {
            document.getElementById('pedidoForm').reset();
            carrito = [];
            actualizarTablaCarrito();
            document.getElementById('pedidoModal').classList.add('active');
        }

        function cerrarModal() {
            document.getElementById('pedidoModal').classList.remove('active');
        }

        // Guardar Pedido
        document.getElementById('pedidoForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            if(carrito.length === 0) {
                alert("Debes agregar al menos un producto al pedido.");
                return;
            }
            
            const formData = new FormData(e.target);
            try {
                const res = await fetch('api_pedidos.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if(data.success) {
                    cerrarModal();
                    cargarPedidos();
                } else {
                    alert("Error: " + data.error);
                }
            } catch(e) {
                alert("Error de conexión");
            }
        });

        // Init
        cargarCatalogo();
        cargarPedidos();
    </script>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador - BS Peru</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text: #0f172a;
            --text-light: #64748b;
            --border: #e2e8f0;
            --danger: #ef4444;
            --success: #10b981;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); color: var(--text); display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: var(--surface); border-right: 1px solid var(--border); display: flex; flex-direction: column; }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 0.75rem; }
        .sidebar-header i { font-size: 2rem; color: var(--primary); }
        .sidebar-header h2 { font-size: 1.25rem; font-weight: 600; }
        .nav-menu { padding: 1rem 0; flex: 1; }
        .nav-item { padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.75rem; color: var(--text-light); text-decoration: none; transition: 0.2s; }
        .nav-item:hover, .nav-item.active { background: #eff6ff; color: var(--primary); border-right: 3px solid var(--primary); }
        .nav-item i { font-size: 1.25rem; }
        .logout-btn { padding: 1rem 1.5rem; border-top: 1px solid var(--border); color: var(--danger); text-decoration: none; display: flex; align-items: center; gap: 0.75rem; }
        
        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { background: var(--surface); padding: 1rem 2rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .content-body { padding: 2rem; flex: 1; overflow-y: auto; }
        
        /* Cards & Tables */
        .card { background: var(--surface); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid var(--border); overflow: hidden; }
        .card-header { padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-weight: 500; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-danger { background: var(--danger); color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; }
        .btn-edit { background: #f1f5f9; color: var(--text); border: none; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; margin-right: 0.5rem; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #f8fafc; font-weight: 600; color: var(--text-light); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
        td img { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; }
        
        /* Modal */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: 0.2s; z-index: 100; }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal-card { background: var(--surface); width: 100%; max-width: 600px; border-radius: 16px; overflow: hidden; transform: translateY(20px); transition: 0.3s; max-height: 90vh; display: flex; flex-direction: column; }
        .modal-overlay.active .modal-card { transform: translateY(0); }
        .modal-header { padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-light); }
        .modal-body { padding: 1.5rem; overflow-y: auto; }
        .modal-footer { padding: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 1rem; }
        
        /* Forms */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; }
        .form-group input, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; outline: none; }
        .form-group input:focus { border-color: var(--primary); }
        .form-group input[type="file"] { padding: 0.5rem; background: #f8fafc; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="ph ph-buildings"></i>
            <h2>BS Peru</h2>
        </div>
        <nav class="nav-menu">
            <a href="#" class="nav-item active"><i class="ph ph-package"></i> Catálogo</a>
            <!-- Aquí puedes añadir más opciones en el futuro -->
        </nav>
        <a href="logout.php" class="logout-btn"><i class="ph ph-sign-out"></i> Cerrar Sesión</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h2>Gestión de Catálogo</h2>
            <div>
                <span style="color: var(--text-light); font-size: 0.9rem;">Bienvenido, Administrador</span>
            </div>
        </header>

        <div class="content-body">
            <div class="card">
                <div class="card-header">
                    <h3>Lista de Productos</h3>
                    <button class="btn-primary" onclick="abrirModal()"><i class="ph ph-plus"></i> Nuevo Producto</button>
                </div>
                <table id="productsTable">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>SKU</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Marca</th>
                            <th>Precio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Llenado por JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Formulario -->
    <div class="modal-overlay" id="productModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modalTitle">Nuevo Producto</h3>
                <button class="modal-close" onclick="cerrarModal()"><i class="ph ph-x"></i></button>
            </div>
            <form id="productForm">
                <input type="hidden" name="action" value="save">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>SKU (Código)</label>
                            <input type="text" name="sku" id="f_sku" required>
                        </div>
                        <div class="form-group">
                            <label>Categoría</label>
                            <input type="text" name="categoria" id="f_categoria">
                        </div>
                        <div class="form-group full">
                            <label>Nombre del Producto</label>
                            <input type="text" name="nombre" id="f_nombre" required>
                        </div>
                        <div class="form-group">
                            <label>Marca</label>
                            <input type="text" name="marca" id="f_marca">
                        </div>
                        <div class="form-group">
                            <label>Precio (Ej. 50.00)</label>
                            <input type="text" name="precio" id="f_precio">
                        </div>
                        <div class="form-group">
                            <label>Peso (Kg)</label>
                            <input type="number" name="peso" id="f_peso" step="0.01">
                        </div>
                        <div class="form-group full">
                            <label>Subir Imagen (Opcional si editas)</label>
                            <input type="file" name="imagen" accept="image/*">
                        </div>
                        <div class="form-group full">
                            <label>Subir Ficha Técnica PDF (Opcional)</label>
                            <input type="file" name="ficha_pdf" accept="application/pdf">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-edit" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let globalProducts = [];

        async function cargarProductos() {
            try {
                const res = await fetch('api.php?action=list');
                const data = await res.json();
                globalProducts = data;
                renderTabla();
            } catch (error) {
                console.error("Error al cargar productos", error);
            }
        }

        function renderTabla() {
            const tbody = document.querySelector('#productsTable tbody');
            tbody.innerHTML = '';
            
            // Mostrar últimos 50 para no colapsar la vista, luego puedes agregar paginación
            const mostrar = globalProducts.slice().reverse().slice(0, 100); 

            mostrar.forEach(p => {
                const tr = document.createElement('tr');
                const imgUrl = p.imagen ? p.imagen : 'https://via.placeholder.com/40';
                
                tr.innerHTML = `
                    <td><img src="${imgUrl}" alt="img"></td>
                    <td>${p.sku || '-'}</td>
                    <td><strong>${p.nombre}</strong></td>
                    <td>${p.categoria || '-'}</td>
                    <td>${p.marca || '-'}</td>
                    <td>${p.precio || '-'}</td>
                    <td>
                        <button class="btn-edit" onclick="editarProducto('${p.sku}')"><i class="ph ph-pencil-simple"></i></button>
                        <button class="btn-danger" onclick="eliminarProducto('${p.sku}')"><i class="ph ph-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function abrirModal(sku = null) {
            document.getElementById('productForm').reset();
            document.getElementById('modalTitle').innerText = sku ? 'Editar Producto' : 'Nuevo Producto';
            
            if (sku) {
                const p = globalProducts.find(x => x.sku === sku);
                if (p) {
                    document.getElementById('f_sku').value = p.sku;
                    document.getElementById('f_sku').readOnly = true; // No permitir cambiar SKU en edición
                    document.getElementById('f_nombre').value = p.nombre;
                    document.getElementById('f_categoria').value = p.categoria;
                    document.getElementById('f_marca').value = p.marca;
                    document.getElementById('f_precio').value = p.precio || '';
                    document.getElementById('f_peso').value = p.peso2 || '';
                }
            } else {
                document.getElementById('f_sku').readOnly = false;
            }
            
            document.getElementById('productModal').classList.add('active');
        }

        function cerrarModal() {
            document.getElementById('productModal').classList.remove('active');
        }

        document.getElementById('productForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            try {
                const res = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    cerrarModal();
                    cargarProductos();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Ocurrió un error al guardar.');
            }
        });

        async function eliminarProducto(sku) {
            if (!confirm('¿Estás seguro de eliminar el producto con SKU: ' + sku + '?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('sku', sku);
            
            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    cargarProductos();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Ocurrió un error.');
            }
        }

        // Iniciar
        cargarProductos();
    </script>
</body>
</html>

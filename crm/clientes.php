<?php
// crm/clientes.php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - ERP BS Peru</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root { --bg: #f4f6f9; --surface: #ffffff; --primary: #17a2b8; --text-primary: #333; --border: #dee2e6; }
        body { font-family: 'Roboto', sans-serif; background-color: var(--bg); margin: 0; padding: 0; }
        .layout { display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 60px; background: #28a745; display: flex; flex-direction: column; align-items: center; padding-top: 15px; gap: 20px; z-index: 100; }
        .sidebar a { color: white; text-decoration: none; font-size: 24px; }
        .sidebar a:hover { background: rgba(0,0,0,0.1); border-radius: 8px; }
        .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: var(--surface); position: relative; }
        .topbar { background: var(--border); height: 40px; display: flex; justify-content: flex-end; align-items: center; padding: 0 20px; flex-shrink: 0; }
        .content { padding: 20px; flex: 1; overflow-y: auto; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { background: var(--primary); color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #f8f9fa; font-weight: 500; }
        
        .modal { display: none; position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 20px; border-radius: 8px; width: 500px; max-width: 90%; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; box-sizing: border-box; }
        .flex-row { display: flex; gap: 15px; }
        .flex-row .form-group { flex: 1; }
    </style>
</head>
<body>
    <div class="layout">
        <div class="sidebar">
            <a href="index.php" title="Dashboard"><i class="ph ph-squares-four"></i></a>
            <a href="clientes.php" title="Clientes" style="background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px;"><i class="ph ph-users"></i></a>
            <a href="inventario.php" title="Inventario"><i class="ph ph-barcode"></i></a>
            <a href="pedidos.php" title="Cotizaciones"><i class="ph ph-file-text"></i></a>
        </div>
        <div class="main">
            <div class="topbar">ERP BS Peru - Clientes</div>
            <div class="content">
                <div class="toolbar">
                    <h2 style="margin:0;">Directorio de Clientes</h2>
                    <button class="btn" onclick="openModal()"><i class="ph ph-plus"></i> Nuevo Cliente</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>RUC/DNI</th>
                            <th>Razón Social / Nombre</th>
                            <th>Categoría</th>
                            <th>Teléfono</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="clientTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo/Editar Cliente -->
    <div class="modal" id="clientModal">
        <div class="modal-content">
            <h3 id="modalTitle">Nuevo Cliente</h3>
            <form id="clientForm" onsubmit="saveClient(event)">
                <input type="hidden" id="c_id" name="id">
                <div class="flex-row">
                    <div class="form-group" style="flex: 0.6;">
                        <label>RUC/DNI *</label>
                        <div style="display: flex; gap: 5px;">
                            <input type="text" id="c_ruc_dni" name="ruc_dni" required>
                            <button type="button" class="btn" onclick="searchReniec()" style="padding: 8px;"><i class="ph ph-magnifying-glass"></i></button>
                        </div>
                    </div>
                    <div class="form-group" style="flex: 1.4;">
                        <label>Razón Social / Nombre *</label>
                        <input type="text" id="c_razon_social" name="razon_social" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" id="c_direccion" name="direccion">
                </div>
                <div class="flex-row">
                    <div class="form-group">
                        <label>Categoría *</label>
                        <select id="c_categoria" name="categoria" required>
                            <option value="aplicador">Aplicador</option>
                            <option value="revendedor_minorista">Revendedor Minorista</option>
                            <option value="contratista">Contratista</option>
                            <option value="constructora">Constructora</option>
                        </select>
                    </div>
                    <div class="form-group" style="display:flex; align-items:center; margin-top:25px;">
                        <input type="checkbox" id="c_agente" name="agente_retencion" value="1" style="width:auto; margin-right:5px;">
                        <label for="c_agente" style="margin:0;">¿Es Agente de Retención?</label>
                    </div>
                </div>
                <div class="flex-row">
                    <div class="form-group">
                        <label>Contacto (Nombre)</label>
                        <input type="text" id="c_contacto" name="contacto">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" id="c_telefono" name="telefono">
                    </div>
                </div>
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" id="c_correo" name="correo">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn" style="background:#6c757d;" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn" style="background:#17a2b8;">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', loadClients);

        async function loadClients() {
            try {
                const res = await fetch('api/clientes.php?action=list');
                const data = await res.json();
                const tbody = document.getElementById('clientTableBody');
                tbody.innerHTML = '';
                data.forEach(c => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${c.ruc_dni}</td>
                            <td>${c.razon_social}</td>
                            <td>${c.categoria.replace('_', ' ')}</td>
                            <td>${c.telefono || '-'}</td>
                            <td>
                                <button onclick='editClient(${JSON.stringify(c)})' class="btn" style="background:#ffc107; color:#000; padding:4px 8px; display:inline-block;"><i class="ph ph-pencil"></i></button>
                            </td>
                        </tr>
                    `;
                });
            } catch (error) {
                console.error("Error", error);
            }
        }

        async function searchReniec() {
            const numero = document.getElementById('c_ruc_dni').value.trim();
            if(!numero) return;
            try {
                const res = await fetch(`api_reniec.php?numero=${numero}`);
                const data = await res.json();
                if(data.success && data.nombre) {
                    document.getElementById('c_razon_social').value = data.nombre;
                } else {
                    alert('No se encontro el documento');
                }
            } catch(e) {
                alert('Error al consultar');
            }
        }

        function openModal() {
            document.getElementById('clientForm').reset();
            document.getElementById('c_id').value = '';
            document.getElementById('modalTitle').innerText = 'Nuevo Cliente';
            document.getElementById('clientModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('clientModal').classList.remove('active');
        }

        function editClient(client) {
            document.getElementById('c_id').value = client.id;
            document.getElementById('c_ruc_dni').value = client.ruc_dni;
            document.getElementById('c_razon_social').value = client.razon_social;
            document.getElementById('c_direccion').value = client.direccion;
            document.getElementById('c_categoria').value = client.categoria;
            document.getElementById('c_agente').checked = client.agente_retencion == 1;
            document.getElementById('c_contacto').value = client.contacto;
            document.getElementById('c_telefono').value = client.telefono;
            document.getElementById('c_correo').value = client.correo;
            
            document.getElementById('modalTitle').innerText = 'Editar Cliente';
            document.getElementById('clientModal').classList.add('active');
        }

        async function saveClient(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('clientForm'));
            formData.append('action', 'save');
            
            try {
                const res = await fetch('api/clientes.php', { method: 'POST', body: formData });
                const result = await res.json();
                if(result.success) {
                    closeModal();
                    loadClients();
                } else {
                    alert(result.error || 'Error');
                }
            } catch(error) {
                alert('Error');
            }
        }
    </script>
</body>
</html>
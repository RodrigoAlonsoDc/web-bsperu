<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Aplicaciones - CRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --bg-color: #f0f2f5;
            --orange: #ff9800;
            --pink: #e91e63;
            --green-light: #4caf50;
            --green-dark: #388e3c;
            --text-dark: #333;
            --text-gray: #999;
        }
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 40px;
        }
        
        /* Encabezado */
        .header {
            margin-bottom: 50px;
        }
        .header h1 {
            background-color: #3f51b5;
            color: white;
            display: inline-block;
            padding: 5px 12px;
            font-size: 20px;
            font-weight: 400;
            margin: 0;
        }
        
        /* Contenedor de Tarjetas */
        .cards-container {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        /* Estilo de cada Tarjeta (Material Design) */
        .card {
            background: white;
            border-radius: 4px;
            box-shadow: 0 1px 4px 0 rgba(0,0,0,0.14);
            width: 260px;
            position: relative;
            margin-top: 30px; /* Espacio para el icono flotante */
            display: flex;
            flex-direction: column;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            padding-top: 0;
        }
        
        /* Icono flotante */
        .card-icon {
            width: 85px;
            height: 85px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            border-radius: 3px;
            box-shadow: 0 4px 20px 0 rgba(0,0,0,.14), 0 7px 10px -5px rgba(0,0,0,.4);
            margin-top: -20px; /* Hace que flote hacia arriba */
        }
        
        .card-title {
            color: var(--text-gray);
            font-size: 16px;
            margin-top: 20px;
            text-align: right;
            flex: 1;
            font-weight: 300;
        }
        
        .card-divider {
            height: 1px;
            background: #eee;
            margin: 0 15px;
        }
        
        /* Pie de la tarjeta (Link) */
        .card-footer {
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--text-gray);
        }
        
        .card-footer a {
            color: var(--text-gray);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .card-footer a:hover {
            color: #333;
        }
        
        .card-footer i {
            font-size: 14px;
        }
        
        /* Degradados y Colores Específicos */
        .bg-orange { background: linear-gradient(60deg, #ffa726, #fb8c00); }
        .bg-pink { background: linear-gradient(60deg, #ec407a, #d81b60); }
        .bg-green-light { background: linear-gradient(60deg, #66bb6a, #43a047); }
        .bg-green-dark { background: linear-gradient(60deg, #4caf50, #2e7d32); }
        
        .text-orange { color: #fb8c00; }
        .text-pink { color: #d81b60; }
        .text-green-light { color: #43a047; }
        .text-green-dark { color: #2e7d32; }
        
        /* Footer Global */
        .global-footer {
            margin-top: 50px;
            text-align: right;
            color: var(--text-gray);
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Lista de Aplicaciones</h1>
    </div>

    <div class="cards-container">
        <!-- Tarjeta: Comercial -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon bg-orange">
                    <i class="ph ph-chart-bar"></i>
                </div>
                <div class="card-title">Comercial</div>
            </div>
            <div class="card-divider"></div>
            <div class="card-footer">
                <i class="ph ph-chart-bar text-orange"></i>
                <a href="#">Ventas de Productos</a>
            </div>
        </div>

        <!-- Tarjeta: Pedidos -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon bg-pink">
                    <i class="ph ph-squares-four"></i>
                </div>
                <div class="card-title">Pedidos</div>
            </div>
            <div class="card-divider"></div>
            <div class="card-footer">
                <i class="ph ph-browser text-pink"></i>
                <a href="#">Ventas</a>
            </div>
        </div>

        <!-- Tarjeta: Despachos -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon bg-green-light">
                    <i class="ph ph-forklift"></i>
                </div>
                <div class="card-title">Despachos</div>
            </div>
            <div class="card-divider"></div>
            <div class="card-footer">
                <i class="ph ph-package text-green-light"></i>
                <a href="#">Despachos</a>
            </div>
        </div>

        <!-- Tarjeta: Reportes -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon bg-green-dark">
                    <i class="ph ph-clipboard-text"></i>
                </div>
                <div class="card-title">Reportes</div>
            </div>
            <div class="card-divider"></div>
            <div class="card-footer">
                <i class="ph ph-file-text text-green-dark"></i>
                <a href="#">Reportes</a>
            </div>
        </div>
    </div>

    <div class="global-footer">
        &copy; 2026 , Hecho para BS Peru
    </div>

</body>
</html>

<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "No autorizado";
    exit;
}

$id = $_GET['id'] ?? '';
if (!$id) {
    echo "ID no proporcionado";
    exit;
}

$pedidos = json_decode(file_get_contents('../assets/Data/pedidos.json'), true) ?? [];
$pedido = null;
foreach ($pedidos as $p) {
    if ($p['id'] == $id) {
        $pedido = $p;
        break;
    }
}

if (!$pedido) {
    echo "Pedido no encontrado";
    exit;
}

// Limpiar nombre del gestor
$gestorFull = $pedido['gestor_campo'] ?? '';
$partesGestor = explode('-', $gestorFull);
$nombreGestor = isset($partesGestor[1]) ? trim($partesGestor[1]) : trim($gestorFull);
if(empty($nombreGestor)) $nombreGestor = "ASESOR COMERCIAL";

// Extraer SUCURSAL para la firma
$sucursalFirma = str_replace("SUCURSAL ", "", $pedido['sucursal'] ?? 'CHORRILLOS');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización <?php echo $id; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background: #525659; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        .toolbar { width: 210mm; background: #323639; padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; justify-content: space-between; color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.5); box-sizing: border-box; }
        .btn-download { background: #d81b60; color: white; border: none; padding: 10px 20px; font-size: 16px; cursor: pointer; border-radius: 4px; font-weight: bold; }
        
        /* A4 Page */
        .page { width: 210mm; min-height: 297mm; background: white; padding: 15mm; box-sizing: border-box; font-size: 11px; color: #000; box-shadow: 0 0 10px rgba(0,0,0,0.5); position: relative; }
        
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .logo { color: #0056b3; font-size: 24px; font-weight: bold; margin: 0; display: flex; align-items: center; gap: 10px; }
        .logo span { font-size: 10px; font-weight: normal; color: #555; }
        .date { font-size: 14px; margin-top: 20px; }
        .title { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 20px; letter-spacing: 1px; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-table td { border: 1px solid #000; padding: 4px 8px; font-size: 10px; }
        .info-table td.label { font-weight: bold; width: 100px; }
        
        .message { margin-bottom: 10px; font-size: 11px; font-weight: bold; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .items-table th { border-bottom: 1px solid #000; padding: 5px; text-align: left; font-size: 10px; }
        .items-table td { padding: 5px; font-size: 10px; vertical-align: top; }
        
        .totals-table { width: 200px; border-collapse: collapse; margin-left: auto; margin-bottom: 20px; }
        .totals-table td { border: 1px solid #000; padding: 4px 8px; font-size: 10px; }
        .totals-table td.label { font-weight: bold; }
        
        .box { border: 1px solid #000; margin-bottom: 10px; font-size: 10px; }
        .box-title { border-bottom: 1px solid #000; padding: 4px 8px; font-weight: bold; }
        .box-content { padding: 4px 8px; line-height: 1.4; }
        
        .signature { margin-top: 50px; text-align: center; font-size: 11px; }
        .signature .name { font-weight: bold; font-size: 12px; margin-bottom: 5px; }
        
        @media print {
            body { background: white; padding: 0; }
            .toolbar { display: none; }
            .page { box-shadow: none; padding: 0; width: auto; min-height: auto; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <div>
            <h2>Cotización: <?php echo $id; ?></h2>
        </div>
        <button class="btn-download" onclick="descargarPDF()">📥 Descargar PDF</button>
    </div>

    <div class="page" id="pdf-content">
        <div class="header">
            <div class="logo">
                <div style="width:30px;height:30px;border-radius:50%;border:2px solid #0056b3;display:flex;align-items:center;justify-content:center;font-size:12px;">BSP</div>
                <div>
                    BS PERÚ<br>
                    <span>Building Systems Peru</span>
                </div>
            </div>
            <div class="date"><?php echo $pedido['fecha_doc']; ?></div>
        </div>
        
        <div class="title">COTIZACIONES: <?php echo $id; ?></div>
        
        <table class="info-table">
            <tr>
                <td class="label">Razon Social:</td>
                <td><?php echo strtoupper($pedido['cliente']); ?></td>
            </tr>
            <tr>
                <td class="label">Dirección:</td>
                <td><?php echo strtoupper($pedido['direccion'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td class="label">RUC:</td>
                <td><?php echo $pedido['documento']; ?></td>
            </tr>
            <tr>
                <td class="label">E mail :</td>
                <td>-</td>
            </tr>
            <tr>
                <td class="label">Telefono:</td>
                <td><?php echo $pedido['telefono'] ?? '-'; ?></td>
            </tr>
            <tr>
                <td class="label">Contacto:</td>
                <td><?php echo strtoupper($pedido['contacto'] ?? '-'); ?></td>
            </tr>
        </table>
        
        <div class="message">De acuerdo con su amable solicitud, tenemos el agrado de cotizarle lo siguiente:</div>
        
        <table class="items-table">
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
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                foreach ($pedido['productos'] as $prod): 
                    $precio_orig = number_format($prod['precio'], 4);
                    $subtotal_prod = number_format($prod['precio'] * $prod['cantidad'], 4);
                ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $prod['sku']; ?></td>
                    <td style="max-width:150px;"><?php echo strtoupper($prod['nombre']); ?></td>
                    <td><?php echo number_format($prod['cantidad'], 2); ?></td>
                    <td>UNI</td>
                    <td><?php echo $precio_orig; ?></td>
                    <td>0.00</td>
                    <td><?php echo $precio_orig; ?></td>
                    <td><?php echo $subtotal_prod; ?></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal</td>
                <td>S/.<?php echo number_format($pedido['subtotal'], 2); ?></td>
            </tr>
            <tr>
                <td class="label">IGV</td>
                <td>S/.<?php echo number_format($pedido['igv'], 2); ?></td>
            </tr>
            <tr>
                <td class="label">Total Neto</td>
                <td>S/.<?php echo number_format($pedido['total'], 2); ?></td>
            </tr>
        </table>
        
        <div class="box">
            <div class="box-title" style="font-weight:normal;">Observaciones -</div>
            <div class="box-content">
                CONDICIONES COMERCIALES<br>
                Forma de Pago: <?php echo $pedido['forma_pago'] ?? 'CONTADO CONTRA ENTREGA'; ?><br>
                Vigencia: 7 dias<br>
                El Horario de atención de las oficinas es de Lunes a Viernes 8:00 a 17:30 y Sábados 8:00 a 12:00 Horas<br>
                Entregamos certificados de calidad, hojas de seguridad (MSDS) y especificaciones técnicas de todos nuestros productos a solicitud del cliente
            </div>
        </div>
        
        <div class="box">
            <div class="box-content">
                CONSIDERACIONES PARA LA FABRICACIÓN DE PRODUCTOS HECHOS A PEDIDO:<br>
                Los productos que se elaboran bajo pedido, garantizan disponibilidad y calidad idónea de un producto de complejidad técnica.<br>
                Debido a este proceso, el tiempo de entrega puede variar entre 8 a 20 días útiles, dependiendo de la disponibilidad de la materia prima, stock y cantidad solicitada.<br>
                El plazo exacto será confirmado por el vendedor al momento de contar con la OC y abono respectivo.<br>
                Toda cancelación de dicho pedido puede ocasionar la perdida parcial o completa del abono realizado, dado que son productos que no pueden ser almacenados.
            </div>
        </div>
        
        <div class="box">
            <div class="box-content">
                BUILDING SYSTEMS PERU S.A.C.<br>
                RUC: 20609793806<br>
                Deposito en cuenta Corriente<br>
                Cta. Cte. BCP Soles: 193-9902956-0-56<br>
                Código Interbancario: 00219300990295605614<br>
                Cta. Cte. BBVA Soles: 0011-0152-0100100654<br>
                Código Interbancario: 011-152-000100100654-61<br>
                Cta. Cte. Interbank Soles: 200-3005486597<br>
                Código Interbancario: 003-200-003005486597-34<br>
                <?php echo $pedido['sucursal'] ?? 'SUCURSAL CHORRILLOS'; ?><br>
                AV. LOS FAISANES N° 675 URB. LA CAMPIÑA CHORRILLOS
            </div>
        </div>
        
        <div class="signature">
            <div class="name"><?php echo $nombreGestor; ?></div>
            <div>Asesor de ventas - <?php echo $sucursalFirma; ?></div>
            <div>-</div>
            <div>ventas@bsperu.pe</div>
        </div>
    </div>

    <script>
        function descargarPDF() {
            const element = document.getElementById('pdf-content');
            const opt = {
                margin:       [10, 0, 10, 0], // top, left, bottom, right
                filename:     'Cotizacion_<?php echo $id; ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            // New Promise-based usage:
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>

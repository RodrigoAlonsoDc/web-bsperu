<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$dataFile = '../assets/Data/productos.json';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// LISTAR PRODUCTOS
if ($action === 'list') {
    header('Content-Type: application/json');
    if (file_exists($dataFile)) {
        echo file_get_contents($dataFile);
    } else {
        echo json_encode([]);
    }
    exit;
}

// ELIMINAR PRODUCTO
if ($action === 'delete') {
    $sku = $_POST['sku'] ?? '';
    if (!$sku) {
        echo json_encode(["success" => false, "error" => "SKU requerido"]);
        exit;
    }
    
    $products = json_decode(file_get_contents($dataFile), true) ?? [];
    $products = array_filter($products, function($p) use ($sku) {
        return $p['sku'] !== $sku;
    });
    
    file_put_contents($dataFile, json_encode(array_values($products), JSON_PRETTY_PRINT));
    echo json_encode(["success" => true]);
    exit;
}

// GUARDAR / EDITAR PRODUCTO
if ($action === 'save') {
    $sku = trim($_POST['sku'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $precio = trim($_POST['precio'] ?? '');
    $peso = $_POST['peso'] ?? 0;
    
    if (empty($sku) || empty($nombre)) {
        echo json_encode(["success" => false, "error" => "SKU y Nombre son obligatorios"]);
        exit;
    }
    
    $products = json_decode(file_get_contents($dataFile), true) ?? [];
    
    $isUpdate = false;
    $targetProduct = null;
    $targetIndex = -1;
    
    foreach ($products as $i => $p) {
        if ($p['sku'] === $sku) {
            $isUpdate = true;
            $targetProduct = $p;
            $targetIndex = $i;
            break;
        }
    }
    
    if (!$isUpdate) {
        // Estructura por defecto para nuevo producto
        $targetProduct = [
            "sku" => $sku,
            "marca" => $marca,
            "categoria" => $categoria,
            "nombre" => $nombre,
            "descripcion" => null,
            "descripcion_larga" => null,
            "precio" => $precio,
            "peso2" => $peso,
            "imagen" => "",
            "miniaturas" => ["miniatura1"=>"","miniatura2"=>"","miniatura3"=>""],
            "ficha_pdf" => null,
            "hoja_seguridad_pdf" => null,
            "oferta_disponible" => false,
            "producto_disponible" => true
        ];
    } else {
        // Actualizar datos existentes
        $targetProduct['nombre'] = $nombre;
        $targetProduct['marca'] = $marca;
        $targetProduct['categoria'] = $categoria;
        $targetProduct['precio'] = $precio;
        $targetProduct['peso2'] = $peso;
    }
    
    // Subida de Archivos
    // Se crea una carpeta con el nombre del producto (como lo tienes organizado)
    $folderName = str_replace(['/', '\\'], '_', $nombre);
    $uploadDir = '../assets/img catalogo/' . $folderName . '/';
    
    if (!empty($_FILES['imagen']['name'])) {
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = basename($_FILES['imagen']['name']);
        $targetFilePath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $targetFilePath)) {
            $publicPath = '/assets/img catalogo/' . rawurlencode($folderName) . '/' . rawurlencode($fileName);
            $targetProduct['imagen'] = $publicPath;
            $targetProduct['miniaturas']['miniatura1'] = $publicPath;
        }
    }
    
    if (!empty($_FILES['ficha_pdf']['name'])) {
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = basename($_FILES['ficha_pdf']['name']);
        $targetFilePath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['ficha_pdf']['tmp_name'], $targetFilePath)) {
            $publicPath = '/assets/img catalogo/' . rawurlencode($folderName) . '/' . rawurlencode($fileName);
            $targetProduct['ficha_pdf'] = $publicPath;
        }
    }
    
    if ($isUpdate) {
        $products[$targetIndex] = $targetProduct;
    } else {
        $products[] = $targetProduct; // Añadir al final
    }
    
    // Guardar cambios
    file_put_contents($dataFile, json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo json_encode(["success" => true]);
    exit;
}
?>

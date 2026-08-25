<?php
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consulta = isset($_POST['consulta']) ? trim($_POST['consulta']) : '';
    
    if (!empty($consulta)) {
        $fecha = date('Y-m-d H:i:s');
        
        // Formatear el registro
        $registro = "========================================================\n";
        $registro .= "Fecha: " . $fecha . "\n";
        $registro .= "Consulta: " . $consulta . "\n";
        $registro .= "========================================================\n\n";
        
        // Guardar en un archivo de texto (puede ser reemplazado por base de datos luego)
        $archivo = 'consultas_registradas.txt';
        
        // file_put_contents crea el archivo si no existe, FILE_APPEND lo agrega al final
        if (file_put_contents($archivo, $registro, FILE_APPEND | LOCK_EX)) {
            echo "Consulta guardada con éxito.";
        } else {
            http_response_code(500);
            echo "Error al guardar la consulta.";
        }
    } else {
        http_response_code(400);
        echo "La consulta está vacía.";
    }
} else {
    http_response_code(405);
    echo "Método no permitido.";
}
?>

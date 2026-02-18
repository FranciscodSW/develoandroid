<?php
// listar_recetas_api.php - Versión JSON para Android

// Configurar headers primero
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // IMPORTANTE para Android
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Configuración de conexión
$hostname = 'localhost';
$database = 'gourmeet';
$username = 'root';
$password = '';

// Conexión con manejo de errores
try {
    $conexion = new mysqli($hostname, $username, $password, $database);

    if ($conexion->connect_error) {
        throw new Exception('Error de conexión a la base de datos: ' . $conexion->connect_error);
    }

    // Forzar UTF-8 en la conexión
    $conexion->set_charset('utf8');

    // Consulta SQL - TODAS las recetas
    $sql = "SELECT 
                REC_ID,
                REC_NOMBRE,
                REC_DESCRIPCION,
                REC_TIEMPO_PREPARACION,
                REC_PORCIONES,
                REC_FECHACREACION,
                Dificultad,
                Calorias,
                REC_ENLACEYOUTUBE
            FROM recetas 
            WHERE REC_ESTATUS = 1 
            ORDER BY REC_ID DESC";

    $resultado = $conexion->query($sql);

    if (!$resultado) {
        throw new Exception('Error en consulta SQL: ' . $conexion->error);
    }

    $recetas = [];
    while ($fila = $resultado->fetch_assoc()) {
        // Asegurar que todos los campos sean strings para JSON
        $receta_limpia = array_map(function ($valor) {
            return $valor === null ? '' : $valor;
        }, $fila);

        $recetas[] = $receta_limpia;
    }

    // Cerrar resultados y conexión
    $resultado->free();
    $conexion->close();

    // Respuesta JSON exitosa
    echo json_encode([
        'success' => true,
        'count' => count($recetas),
        'recetas' => $recetas
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    // Respuesta de error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

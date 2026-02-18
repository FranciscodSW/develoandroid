<?php
// api_categorias.php - Versión JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // IMPORTANTE para Android

$hostname = 'localhost';
$database = 'gourmeet';
$username = 'root';
$password = '';

// Conexión
$conexion = new mysqli($hostname, $username, $password, $database);

if ($conexion->connect_error) {
    echo json_encode(['error' => 'Error de conexión BD']);
    exit;
}

// Consulta para obtener solo RC_DESCRIPCION y RC_COLOR
$sql = "SELECT 
            RC_ID,
            RC_DESCRIPCION,
            RC_COLOR
        FROM `recetas_categoria` 
        WHERE RC_ESTATUS = 1 
        ORDER BY RC_ID ASC";

$resultado = $conexion->query($sql);

if (!$resultado) {
    echo json_encode(['error' => 'Error en consulta SQL']);
    exit;
}

$categorias = [];
while ($fila = $resultado->fetch_assoc()) {
    $categorias[] = $fila;
}

// Respuesta JSON simple
echo json_encode([
    'success' => true,
    'count' => count($categorias),
    'categorias' => $categorias
], JSON_UNESCAPED_UNICODE);

$conexion->close();

<?php
// listar_recetas_api.php - Versión para Android
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // IMPORTANTE para Android
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$hostname = 'localhost';
$database = 'gourmeet';
$username = 'root';
$password = '';

// Conexión
$conexion = new mysqli($hostname, $username, $password, $database);

if ($conexion->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión: ' . $conexion->connect_error
    ]);
    exit;
}

// Parámetros de paginación desde Android
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
$inicio = ($pagina - 1) * $limite;

// Obtener total
$total_query = $conexion->query("SELECT COUNT(*) as total FROM recetas WHERE REC_ESTATUS = 1");
$total_fila = $total_query->fetch_assoc();
$total_recetas = $total_fila['total'];
$total_paginas = ceil($total_recetas / $limite);

// Consulta principal
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
        ORDER BY REC_FECHACREACION DESC 
        LIMIT $inicio, $limite";

$resultado = $conexion->query($sql);

if (!$resultado) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en consulta: ' . $conexion->error
    ]);
    exit;
}

$recetas = [];
while ($fila = $resultado->fetch_assoc()) {
    $recetas[] = $fila;
}

// Respuesta JSON
echo json_encode([
    'success' => true,
    'pagina_actual' => $pagina,
    'total_paginas' => $total_paginas,
    'total_recetas' => $total_recetas,
    'limite_por_pagina' => $limite,
    'recetas' => $recetas
], JSON_UNESCAPED_UNICODE);

$conexion->close();

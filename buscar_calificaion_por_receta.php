<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$hostname = 'localhost';
$database = 'gourmeet';
$username = 'root';
$password = '';

$conexion = new mysqli($hostname, $username, $password, $database);

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión BD']);
    exit;
}

$CAL_REC_ID = isset($_GET['CAL_REC_ID']) ? intval($_GET['CAL_REC_ID']) : 0;

if ($CAL_REC_ID <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'CAL_REC_ID inválido.',
        'Calificaciones' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "SELECT 
            AVG(CAL_CALIFICACION) AS promedio,
            COUNT(*) AS votos
        FROM calificaciones
        WHERE CAL_REC_ID = $CAL_REC_ID
          AND CAL_ESTATUS = 1";


$resultado = $conexion->query($sql);

$calificaciones = [];

if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $calificaciones[] = $fila;
    }
}

echo json_encode([
    'success' => true,
    'Calificaciones' => $calificaciones
], JSON_UNESCAPED_UNICODE);

$conexion->close();

<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$hostname = 'localhost';
$database = 'gourmeet';
$username = 'root';
$password = '';

$conexion = new mysqli($hostname, $username, $password, $database);

if ($conexion->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión BD',
        'imagenes' => []
    ]);
    exit;
}

$RI_REC_ID = isset($_GET['RI_REC_ID']) ? intval($_GET['RI_REC_ID']) : 0;

if ($RI_REC_ID <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'RI_REC_ID inválido',
        'imagenes' => []
    ]);
    exit;
}

$sql = "SELECT i.Foto_Ingrediente
        FROM recetas_ingredientes ri
        JOIN ingredientes i
        ON ri.RI_ING_ID = i.ING_ID
        WHERE ri.RI_REC_ID = ?";

$stmt = $conexion->prepare($sql);

$stmt->bind_param("i", $RI_REC_ID);

$stmt->execute();

$resultado = $stmt->get_result();

$imagenes = [];

while ($fila = $resultado->fetch_assoc()) {
    $imagenes[] = $fila['Foto_Ingrediente'];
}

echo json_encode([
    'success' => true,
    'count' => count($imagenes),
    'imagenes' => $imagenes
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conexion->close();

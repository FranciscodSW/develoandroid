<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$hostname = 'localhost';
$database = 'gourmeet';
$username = 'root';
$password = '';

// Conexión
$conexion = new mysqli($hostname, $username, $password, $database);

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión BD', 'recetas' => []]);
    exit;
}

// Obtener término de búsqueda
$q = isset($_GET['REC_NOMBRE']) ? trim($_GET['REC_NOMBRE']) : '';
$rc_id = isset($_GET['REC_RC_ID']) ? intval($_GET['REC_RC_ID']) : 0;


if ($rc_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'REC_RC_ID inválido', 'recetas' => []]);
    exit;
}


// Búsqueda con LIKE (versión simple)
$busqueda = '%' . $q . '%';

$sql = "SELECT 
            REC_ID,
            REC_NOMBRE,
            REC_DESCRIPCION,
            REC_TIEMPO_PREPARACION,
            REC_PORCIONES,
            REC_FECHACREACION,
            Dificultad,
            Calorias,
            REC_ENLACEYOUTUBE,
            REC_RC_ID
        FROM recetas 
        WHERE REC_ESTATUS = 1 
        AND REC_NOMBRE LIKE ?
        AND REC_RC_ID = ?
        ORDER BY REC_ID DESC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("si", $busqueda, $rc_id);
$stmt->execute();
$resultado = $stmt->get_result();

$recetas = [];
while ($fila = $resultado->fetch_assoc()) {
    $recetas[] = $fila;
}

echo json_encode([
    'success' => true,
    'q' => $q,
    'count' => count($recetas),
    'recetas' => $recetas
], JSON_UNESCAPED_UNICODE);

$conexion->close();

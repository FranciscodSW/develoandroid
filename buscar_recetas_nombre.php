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
            r.REC_ID,
            r.REC_NOMBRE,
            r.REC_DESCRIPCION,
            r.REC_TIEMPO_PREPARACION,
            r.REC_PORCIONES,
            r.REC_FECHACREACION,
            r.Dificultad,
            r.Calorias,
            r.REC_ENLACEYOUTUBE,
            r.REC_RC_ID,
            r.FotoReceta,

            ROUND(AVG(c.CAL_CALIFICACION), 1) AS promedio,
            COUNT(c.CAL_ID) AS votos

        FROM recetas r

        LEFT JOIN calificaciones c 
            ON r.REC_ID = c.CAL_REC_ID
            AND c.CAL_ESTATUS = 1

        WHERE r.REC_ESTATUS = 1 
        AND r.REC_NOMBRE LIKE ?
        AND r.REC_RC_ID = ?

        GROUP BY 
            r.REC_ID,
            r.REC_NOMBRE,
            r.REC_DESCRIPCION,
            r.REC_TIEMPO_PREPARACION,
            r.REC_PORCIONES,
            r.REC_FECHACREACION,
            r.Dificultad,
            r.Calorias,
            r.REC_ENLACEYOUTUBE,
            r.REC_RC_ID,
            r.FotoReceta

        ORDER BY r.REC_ID DESC";


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

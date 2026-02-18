<?php
// listar_recetas_rec_rc_id_api.php - Versión JSON
header('Content-Type: application/json; charset=utf-8');
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
$rec_rc_id = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : 1;


// CONSULTA PREPARADA - IMPORTANTE usar prepare() y bind_param()
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
        AND REC_RC_ID = ?
        ORDER BY REC_ID DESC";

// Preparar la consulta
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Error al preparar consulta: ' . $conexion->error]);
    exit;
}

// Vincular parámetro
$stmt->bind_param("i", $rec_rc_id); // "i" = integer

// Ejecutar
if (!$stmt->execute()) {
    echo json_encode(['error' => 'Error al ejecutar consulta: ' . $stmt->error]);
    exit;
}

// Obtener resultados
$resultado = $stmt->get_result();

$recetas = [];
while ($fila = $resultado->fetch_assoc()) {
    // Limpiar valores NULL
    $fila = array_map(function ($valor) {
        return $valor === null ? '' : $valor;
    }, $fila);
    $recetas[] = $fila;
}

// Cerrar statement
$stmt->close();

// Respuesta JSON
echo json_encode([
    'success' => true,
    'categoria_id' => $rec_rc_id,
    'count' => count($recetas),
    'recetas' => $recetas
], JSON_UNESCAPED_UNICODE);

$conexion->close();

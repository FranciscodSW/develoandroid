<?php
header('Content-Type: application/json; charset=utf-8');
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

$rec_rc_id = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : 1;

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
        AND r.REC_RC_ID = ?

        GROUP BY r.REC_ID

        ORDER BY r.REC_ID ";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Error al preparar consulta']);
    exit;
}

$stmt->bind_param("i", $rec_rc_id);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Error al ejecutar consulta']);
    exit;
}

$resultado = $stmt->get_result();

$recetas = [];

while ($fila = $resultado->fetch_assoc()) {

    $fila = array_map(function ($valor) {
        return $valor === null ? '' : $valor;
    }, $fila);

    // Si no hay votos → promedio = 0
    if ($fila['votos'] == 0) {
        $fila['promedio'] = 0;
    }

    $recetas[] = $fila;
}

$stmt->close();
echo json_encode([
    'success' => true,
    'categoria_id' => $rec_rc_id,
    'count' => count($recetas),
    'recetas' => $recetas
], JSON_UNESCAPED_UNICODE);

$conexion->close();

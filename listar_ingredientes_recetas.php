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

// Obtener ID de la receta
$rec_id = isset($_GET['REC_ID']) ? intval($_GET['REC_ID']) : 0;

if ($rec_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'REC_ID inválido', 'recetas' => []]);
    exit;
}

// Consulta para obtener detalles de la receta con sus ingredientes
$sql = "SELECT 
            r.REC_ID,
            r.REC_NOMBRE,
            r.REC_DESCRIPCION,
            r.REC_PORCIONES,
            r.REC_TIEMPO_PREPARACION,
            r.REC_FECHACREACION,
            r.Dificultad,
            r.Calorias,
            r.REC_ENLACEYOUTUBE,
            r.REC_RC_ID,
            ri.RI_ID,
            ri.RI_DESC_CANTIDAD,
            i.Ing_DESCRIPCION AS Nombre_Ingrediente,
            i.Calorias AS Calorias_Ingrediente,
            i.Precio_Estimado
        FROM recetas r
        INNER JOIN recetas_ingredientes ri ON r.REC_ID = ri.RI_REC_ID
        INNER JOIN ingredientes i ON ri.RI_ING_ID = i.Ing_ID
        WHERE r.REC_ID = ?
          AND r.REC_ESTATUS = 1
          AND ri.RI_ESTATUS = 1
        ORDER BY ri.RI_ID";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $rec_id);
$stmt->execute();
$resultado = $stmt->get_result();

$receta = null;
$ingredientes = [];

while ($fila = $resultado->fetch_assoc()) {
    if ($receta === null) {
        // Solo tomamos los datos de la receta una vez
        $receta = [
            'REC_ID' => $fila['REC_ID'],
            'REC_NOMBRE' => $fila['REC_NOMBRE'],
            'REC_DESCRIPCION' => $fila['REC_DESCRIPCION'],
            'REC_PORCIONES' => $fila['REC_PORCIONES'],
            'REC_TIEMPO_PREPARACION' => $fila['REC_TIEMPO_PREPARACION'],
            'REC_FECHACREACION' => $fila['REC_FECHACREACION'],
            'Dificultad' => $fila['Dificultad'],
            'Calorias' => $fila['Calorias'],
            'REC_ENLACEYOUTUBE' => $fila['REC_ENLACEYOUTUBE'],
            'REC_RC_ID' => $fila['REC_RC_ID'],
            'ingredientes' => []
        ];
    }

    // Agregar cada ingrediente al array
    $ingredientes[] = [
        'RI_ID' => $fila['RI_ID'],
        'RI_DESC_CANTIDAD' => $fila['RI_DESC_CANTIDAD'],
        'Nombre_Ingrediente' => $fila['Nombre_Ingrediente'],
        'Calorias_Ingrediente' => $fila['Calorias_Ingrediente'],
        'Precio_Estimado' => $fila['Precio_Estimado']
    ];
}

if ($receta === null) {
    echo json_encode(['success' => false, 'error' => 'Receta no encontrada', 'recetas' => []]);
    exit;
}

// Agregar los ingredientes a la receta
$receta['ingredientes'] = $ingredientes;

echo json_encode([
    'success' => true,
    'receta' => $receta
], JSON_UNESCAPED_UNICODE);

$conexion->close();

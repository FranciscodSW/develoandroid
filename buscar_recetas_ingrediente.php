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
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión BD',
        'recetas' => []
    ]);
    exit;
}

// Parámetros
$ingredientes = isset($_GET['ingredientes']) ? trim($_GET['ingredientes']) : '';
$categoria_id = isset($_GET['categoria']) ? intval($_GET['categoria']) : 1;

if (empty($ingredientes)) {
    echo json_encode([
        'success' => false,
        'error' => 'Debe especificar ingredientes',
        'recetas' => []
    ]);
    exit;
}

// Convertir ingredientes → lista limpia
$ingredientes_lista = explode(',', $ingredientes);
$ingredientes_lista = array_map('trim', $ingredientes_lista);
$ingredientes_lista = array_filter($ingredientes_lista);

if (empty($ingredientes_lista)) {
    echo json_encode([
        'success' => false,
        'error' => 'Ingredientes inválidos',
        'recetas' => []
    ]);
    exit;
}

// =============================
// CONSULTA DINÁMICA SEGURA 🔥
// =============================
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
            COUNT(c.CAL_ID) AS votos,

            COUNT(DISTINCT i.ING_ID) AS coincidencias

        FROM recetas r

        JOIN recetas_ingredientes ri ON r.REC_ID = ri.RI_REC_ID
        JOIN ingredientes i ON ri.RI_ING_ID = i.ING_ID

        LEFT JOIN calificaciones c 
            ON r.REC_ID = c.CAL_REC_ID
            AND c.CAL_ESTATUS = 1

        WHERE r.REC_ESTATUS = 1
        AND r.REC_RC_ID = ?";


// Parámetros dinámicos
$conditions = [];
$params = [$categoria_id];
$param_types = 'i';

// Generar filtros LIKE
foreach ($ingredientes_lista as $ingrediente) {
    $conditions[] = "i.ING_DESCRIPCION LIKE ?";
    $params[] = '%' . $ingrediente . '%';
    $param_types .= 's';
}

// ✅ SOLO agregar AND si hay condiciones
if (!empty($conditions)) {
    $sql .= " AND (" . implode(' OR ', $conditions) . ")";
}

// Final de consulta
$sql .= " GROUP BY r.REC_ID
          HAVING coincidencias >= 1
          ORDER BY coincidencias DESC";



// Preparar statement
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en prepare',
        'recetas' => []
    ]);
    exit;
}

// Bind dinámico
$bind_params = [$param_types];
foreach ($params as &$param) {
    $bind_params[] = &$param;
}

call_user_func_array([$stmt, 'bind_param'], $bind_params);

// Ejecutar
$stmt->execute();
$resultado = $stmt->get_result();

// Resultados
$recetas = [];
while ($fila = $resultado->fetch_assoc()) {
    $recetas[] = $fila;
}

// Respuesta JSON
echo json_encode([
    'success' => true,
    'categoria' => $categoria_id,
    'ingredientes' => array_values($ingredientes_lista),
    'count' => count($recetas),
    'recetas' => $recetas
], JSON_UNESCAPED_UNICODE);

// Cerrar
$stmt->close();
$conexion->close();

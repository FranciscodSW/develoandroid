<?php
// api_buscar_ingredientes.php - Versión JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // IMPORTANTE para Android

$hostname = 'localhost';
$database = 'gourmeet'; // Ajusta el nombre de tu BD
$username = 'root';
$password = '';

// Conexión
$conexion = new mysqli($hostname, $username, $password, $database);

if ($conexion->connect_error) {
    echo json_encode(['error' => 'Error de conexión BD']);
    exit;
}

// Obtener parámetro de búsqueda
$q = isset($_GET['q']) ? $_GET['q'] : '';

if (empty($q)) {
    echo json_encode([
        'success' => true,
        'count' => 0,
        'message' => 'Ingresa un término de búsqueda',
        'ingredientes' => []
    ]);
    exit;
}

// Limpiar y preparar término de búsqueda
$busqueda = $conexion->real_escape_string($q);
$termino = "%{$busqueda}%";

// Consulta para buscar ingredientes por descripción
$sql = "SELECT 
            ING_ID,
            ING_DESCRIPCION,
            Foto_Ingrediente
            
        FROM `ingredientes` 
        WHERE ING_DESCRIPCION LIKE ?
          AND ING_ESTATUS = 1 
        ORDER BY ING_DESCRIPCION ASC";

// Usar prepared statement para seguridad
$stmt = $conexion->prepare($sql);

$stmt->bind_param("s", $termino); // CORRECCIÓN: Solo una "s"

$stmt->execute();
$resultado = $stmt->get_result();

if (!$resultado) {
    echo json_encode(['error' => 'Error en consulta SQL']);
    exit;
}

$ingredientes = [];
while ($fila = $resultado->fetch_assoc()) {
    $ingredientes[] = $fila;
}

// Respuesta JSON
echo json_encode([
    'success' => true,
    'count' => count($ingredientes),
    'search_term' => $q,
    'ingredientes' => $ingredientes
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conexion->close();

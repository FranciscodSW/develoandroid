<?php
// listar_pasos_recetas.php - VERSIÓN CORREGIDA CON SOLUCIÓN
ob_start(); // Iniciar buffer de salida

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Activar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

$hostname = 'localhost';
$database = 'gourmeet';
$username = 'root';
$password = '';

// Conexión
$conexion = new mysqli($hostname, $username, $password, $database);

if ($conexion->connect_error) {
    $response = [
        'success' => false,
        'error' => 'Error de conexión BD: ' . $conexion->connect_error,
        'pasos' => []
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Obtener ID de la receta
$rec_id = isset($_GET['REC_ID']) ? intval($_GET['REC_ID']) : 0;

if ($rec_id <= 0) {
    $response = [
        'success' => false,
        'error' => 'REC_ID inválido. Use: ?REC_ID=1',
        'pasos' => []
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Preparar la consulta
$sql = "SELECT PRE_PASO, PRE_DESCRIPCION,PRE_Tiempo
        FROM preparacion
        WHERE PRE_ESTATUS = 1
        AND PRE_REC_ID = ?
        ORDER BY PRE_PASO ASC";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    $response = [
        'success' => false,
        'error' => 'Error al preparar consulta: ' . $conexion->error,
        'pasos' => []
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Vincular parámetro
$stmt->bind_param("i", $rec_id);

// Ejecutar
if (!$stmt->execute()) {
    $response = [
        'success' => false,
        'error' => 'Error al ejecutar consulta: ' . $stmt->error,
        'pasos' => []
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Obtener resultado
$resultado = $stmt->get_result();
if (!$resultado) {
    $response = [
        'success' => false,
        'error' => 'Error en resultado: ' . $stmt->error,
        'pasos' => []
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$pasos = [];
while ($fila = $resultado->fetch_assoc()) {
    $pasos[] = [
        'PRE_PASO' => (int)$fila['PRE_PASO'],
        'PRE_DESCRIPCION' => $fila['PRE_DESCRIPCION'],
        'PRE_Tiempo' => $fila['PRE_Tiempo']
    ];
}

// Respuesta JSON
$response = [
    'success' => true,
    'count' => count($pasos),
    'pasos' => $pasos
];

// Limpiar buffer y enviar JSON
ob_end_clean(); // Limpiar cualquier salida previa
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$stmt->close();
$conexion->close();

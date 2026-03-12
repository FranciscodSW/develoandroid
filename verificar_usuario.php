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
        'error' => 'Error de conexión'
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    $data = $_POST;
}

$correo = trim($data['correo'] ?? '');
$nombre = trim($data['nombre'] ?? '');

if (empty($correo) && empty($nombre)) {
    echo json_encode([
        'success' => false,
        'error' => 'Datos vacíos'
    ]);
    exit;
}

$sql = "SELECT CLI_CORREO, CLI_NOMBRE 
        FROM clientes 
        WHERE CLI_CORREO = ? OR CLI_NOMBRE = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ss", $correo, $nombre);
$stmt->execute();

$result = $stmt->get_result();

$correoExiste = false;
$nombreExiste = false;

while ($row = $result->fetch_assoc()) {

    if ($row['CLI_CORREO'] === $correo) {
        $correoExiste = true;
    }

    if ($row['CLI_NOMBRE'] === $nombre) {
        $nombreExiste = true;
    }
}

echo json_encode([
    'success' => true,
    'correo_existe' => $correoExiste,
    'nombre_existe' => $nombreExiste
]);

$stmt->close();
$conexion->close();

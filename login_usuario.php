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
        'error' => 'Error de conexión BD'
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    $data = $_POST;
}

$usuario = trim($data['usuario'] ?? '');
$passwordUser = trim($data['password'] ?? '');

if (empty($usuario) || empty($passwordUser)) {
    echo json_encode([
        'success' => false,
        'error' => 'Datos incompletos'
    ]);
    exit;
}

$sql = "SELECT CLI_ID, CLI_CORREO, CLI_CONTRASENIA, CLI_NOMBRE, CLI_ESTATUS
        FROM clientes
        WHERE CLI_CORREO = ? OR CLI_NOMBRE = ?
        LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ss", $usuario, $usuario);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {

    echo json_encode([
        'success' => false,
        'error' => 'Usuario no encontrado'
    ]);
    exit;
}

$usuarioDB = $result->fetch_assoc();

if ($usuarioDB['CLI_ESTATUS'] == 0) {

    echo json_encode([
        'success' => false,
        'error' => 'Cuenta suspendida'
    ]);
    exit;
}

if (!password_verify($passwordUser, $usuarioDB['CLI_CONTRASENIA'])) {

    echo json_encode([
        'success' => false,
        'error' => 'Contraseña incorrecta'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'usuario_id' => $usuarioDB['CLI_ID'],
    'nombre' => $usuarioDB['CLI_NOMBRE'],
    'correo' => $usuarioDB['CLI_CORREO']
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conexion->close();

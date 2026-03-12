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

$correo = trim($data['correo'] ?? '');
$passwordUser = trim($data['password'] ?? '');
$nombre = trim($data['nombre'] ?? '');
$edad = intval($data['edad'] ?? 0);
$nivel = intval($data['nivel'] ?? 1);
$avatar = trim($data['avatar'] ?? '');
$latitud = $data['latitud'] ?? null;
$longitud = $data['longitud'] ?? null;
$origen = trim($data['origen'] ?? '');
$restricciones = $data['restricciones'] ?? [];

$ip = $_SERVER['REMOTE_ADDR'];

if (empty($correo) || empty($passwordUser) || empty($nombre)) {
    echo json_encode([
        'success' => false,
        'error' => 'Datos incompletos'
    ]);
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'error' => 'Correo inválido'
    ]);
    exit;
}

if (strlen($passwordUser) < 8) {
    echo json_encode([
        'success' => false,
        'error' => 'Password muy corta'
    ]);
    exit;
}

$sqlCheck = "SELECT CLI_ID FROM clientes WHERE CLI_CORREO = ?";
$stmtCheck = $conexion->prepare($sqlCheck);
$stmtCheck->bind_param("s", $correo);
$stmtCheck->execute();
$stmtCheck->store_result();

if ($stmtCheck->num_rows > 0) {

    echo json_encode([
        'success' => false,
        'error' => 'El correo ya está registrado'
    ]);
    exit;
}

$stmtCheck->close();

$passwordHash = password_hash($passwordUser, PASSWORD_DEFAULT);

$conexion->begin_transaction();

try {

    $sqlInsert = "INSERT INTO clientes 
    (CLI_CORREO, CLI_CONTRASENIA, CLI_NOMBRE, CLI_PRIMER_IP, CLI_ESTATUS, FotoUsuario, NIVEL, edad, latitud_cliente, longitud_cliente, Origen)
    VALUES (?,?,?,?,1,?,?,?,?,?,?)";

    $stmtInsert = $conexion->prepare($sqlInsert);

    $stmtInsert->bind_param(
        "sssssiidds",
        $correo,
        $passwordHash,
        $nombre,
        $ip,
        $avatar,
        $nivel,
        $edad,
        $latitud,
        $longitud,
        $origen
    );

    $stmtInsert->execute();

    $idUsuario = $conexion->insert_id;

    if (!empty($restricciones)) {

        $sqlRestriccion = "INSERT INTO usuario_restricciones 
        (id_usuario, id_restriccion) VALUES (?,?)";

        $stmtRestriccion = $conexion->prepare($sqlRestriccion);

        foreach ($restricciones as $idRestriccion) {

            $stmtRestriccion->bind_param("ii", $idUsuario, $idRestriccion);
            $stmtRestriccion->execute();
        }

        $stmtRestriccion->close();
    }

    $conexion->commit();

    echo json_encode([
        'success' => true,
        'usuario_id' => $idUsuario,
        'message' => 'Registro completo'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {

    $conexion->rollback();

    echo json_encode([
        'success' => false,
        'error' => 'Error al registrar usuario'
    ]);
}
$conexion->close();

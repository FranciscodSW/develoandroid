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

// =========================
// ✅ Leer JSON o POST (API híbrida 🔥)
// =========================
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    $data = $_POST;   // fallback elegante 😎
}

// =========================
// ✅ Obtener datos
// =========================
$correo = isset($data['correo']) ? trim($data['correo']) : '';
$passwordUser = isset($data['password']) ? trim($data['password']) : '';
$nombre = isset($data['nombre']) ? trim($data['nombre']) : '';
$ip = isset($data['cli_primer_ip']) ? trim($data['cli_primer_ip']) : $_SERVER['REMOTE_ADDR'];

// =========================
// ✅ Validar campos
// =========================
if (empty($correo) || empty($passwordUser) || empty($nombre)) {
    echo json_encode([
        'success' => false,
        'error' => 'Datos incompletos'
    ]);
    exit;
}

// =========================
// ✅ Validar correo
// =========================
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'error' => 'Correo inválido'
    ]);
    exit;
}

// =========================
// ✅ Validar password
// =========================
if (strlen($passwordUser) < 8) {
    echo json_encode([
        'success' => false,
        'error' => 'Password muy corta'
    ]);
    exit;
}

// =========================
// ✅ Validar nombre 🔥
// =========================
if (!preg_match("/^[A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ ]*$/u", $nombre)) {
    echo json_encode([
        'success' => false,
        'error' => 'Nombre inválido'
    ]);
    exit;
}

// =========================
// ✅ Verificar duplicado
// =========================
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

// =========================
// ✅ Encriptar password 🔥
// =========================
$passwordHash = password_hash($passwordUser, PASSWORD_DEFAULT);

// =========================
// ✅ Insertar usuario
// =========================
$sqlInsert = "INSERT INTO clientes 
              (CLI_CORREO, CLI_CONTRASENIA, CLI_NOMBRE, CLI_PRIMER_IP, CLI_ESTATUS)
              VALUES (?, ?, ?, ?, 1)";

$stmtInsert = $conexion->prepare($sqlInsert);
$stmtInsert->bind_param("ssss", $correo, $passwordHash, $nombre, $ip);

if ($stmtInsert->execute()) {

    echo json_encode([
        'success' => true,
        'message' => 'Usuario registrado correctamente'
    ], JSON_UNESCAPED_UNICODE);
} else {

    echo json_encode([
        'success' => false,
        'error' => 'Error al registrar usuario'
    ]);
}

$stmtInsert->close();
$conexion->close();

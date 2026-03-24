<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 🔥 activar errores (solo desarrollo)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// conexión
$conexion = new mysqli('localhost', 'root', '', 'gourmeet');

if ($conexion->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión BD'
    ]);
    exit;
}

// recibir datos
$data = json_decode(file_get_contents("php://input"), true) ?? $_POST;

$correo = trim($data['correo'] ?? '');
$nombre = trim($data['nombre'] ?? 'Usuario');
$facebook_id = trim($data['facebook_id'] ?? '');
$avatar = trim($data['avatar'] ?? '');
$edad = intval($data['edad'] ?? 0);
$nivel = intval($data['nivel'] ?? 1);
$latitud = is_numeric($data['latitud'] ?? null) ? $data['latitud'] : 0;
$longitud = is_numeric($data['longitud'] ?? null) ? $data['longitud'] : 0;
$restricciones = $data['restricciones'] ?? [];
$origen = 'facebook';
$ip = $_SERVER['REMOTE_ADDR'];

// ================= VALIDACIONES =================

// 🔥 solo facebook_id obligatorio
if (empty($facebook_id)) {
    echo json_encode([
        'success' => false,
        'error' => 'Facebook ID requerido'
    ]);
    exit;
}

// validar correo SOLO si existe
if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'error' => 'Correo inválido'
    ]);
    exit;
}

// ================= REGISTRO =================

$conexion->begin_transaction();

try {

    // password dummy
    $passwordDummy = password_hash(uniqid(), PASSWORD_DEFAULT);

    $stmt = $conexion->prepare("
        INSERT INTO clientes 
        (CLI_CORREO, CLI_CONTRASENIA, CLI_NOMBRE, CLI_PRIMER_IP, CLI_ESTATUS, FotoUsuario, NIVEL, edad, latitud_cliente, longitud_cliente, Origen, CLI_FACEBOOK_ID)
        VALUES (?,?,?,?,1,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "sssssiiddss",
        $correo,
        $passwordDummy,
        $nombre,
        $ip,
        $avatar,
        $nivel,
        $edad,
        $latitud,
        $longitud,
        $origen,
        $facebook_id
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $idUsuario = $conexion->insert_id;

    // guardar restricciones
    if (!empty($restricciones)) {

        $stmtR = $conexion->prepare("
            INSERT INTO usuario_restricciones (id_usuario,id_restriccion) 
            VALUES (?,?)
        ");

        foreach ($restricciones as $r) {
            $stmtR->bind_param("ii", $idUsuario, $r);
            $stmtR->execute();
        }

        $stmtR->close();
    }

    $conexion->commit();

    echo json_encode([
        'success' => true,
        'usuario_id' => $idUsuario,
        'registro' => true,
        'nombre' => $nombre
    ]);
} catch (Throwable $e) {

    $conexion->rollback();

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage() // 🔥 ahora sí verás el error real
    ]);
}

$conexion->close();

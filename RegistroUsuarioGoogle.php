<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$conexion = new mysqli('localhost', 'root', '', 'gourmeet');

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error BD']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true) ?? $_POST;

$correo = trim($data['correo'] ?? '');
$nombre = trim($data['nombre'] ?? 'Usuario');
$google_id = trim($data['google_id'] ?? '');
$avatar = trim($data['avatar'] ?? '');
$edad = intval($data['edad'] ?? 0);
$nivel = intval($data['nivel'] ?? 1);
$latitud = is_numeric($data['latitud'] ?? null) ? $data['latitud'] : 0;
$longitud = is_numeric($data['longitud'] ?? null) ? $data['longitud'] : 0;
$origen = 'google';
$restricciones = $data['restricciones'] ?? [];

$ip = $_SERVER['REMOTE_ADDR'];

if (empty($correo) || empty($google_id)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Correo inválido']);
    exit;
}

/* ================= LOGIN ================= */

$stmt = $conexion->prepare("SELECT CLI_ID, CLI_NOMBRE, CLI_ESTATUS FROM clientes WHERE CLI_CORREO=? OR CLI_GOOGLE_ID=?");
$stmt->bind_param("ss", $correo, $google_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {

    $user = $res->fetch_assoc();
    $idUsuario = $user['CLI_ID'];

    if ($user['CLI_ESTATUS'] == 0) {
        echo json_encode(['success' => false, 'error' => 'Cuenta suspendida']);
        exit;
    }

    // actualizar restricciones
    if (!empty($restricciones)) {

        $conexion->query("DELETE FROM usuario_restricciones WHERE id_usuario=$idUsuario");

        $stmtR = $conexion->prepare("INSERT INTO usuario_restricciones (id_usuario,id_restriccion) VALUES (?,?)");

        foreach ($restricciones as $r) {
            $stmtR->bind_param("ii", $idUsuario, $r);
            $stmtR->execute();
        }

        $stmtR->close();
    }

    echo json_encode([
        'success' => true,
        'usuario_id' => $idUsuario,
        'nombre' => $user['CLI_NOMBRE'],
        'login' => true
    ]);
    exit;
}

/* ================= REGISTRO ================= */

$conexion->begin_transaction();

try {

    // contraseña dummy para cumplir la BD
    $passwordDummy = password_hash(uniqid(), PASSWORD_DEFAULT);

    $stmt = $conexion->prepare("
        INSERT INTO clientes 
        (CLI_CORREO, CLI_CONTRASENIA, CLI_NOMBRE, CLI_PRIMER_IP, CLI_ESTATUS, FotoUsuario, NIVEL, edad, latitud_cliente, longitud_cliente, Origen, CLI_GOOGLE_ID)
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
        $google_id
    );

    $stmt->execute();
    $idUsuario = $conexion->insert_id;

    // guardar restricciones
    if (!empty($restricciones)) {

        $stmtR = $conexion->prepare("INSERT INTO usuario_restricciones (id_usuario,id_restriccion) VALUES (?,?)");

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
        'registro' => true
    ]);
} catch (Exception $e) {

    $conexion->rollback();

    echo json_encode([
        'success' => false,
        'error' => 'Error registro'
    ]);
}

$conexion->close();

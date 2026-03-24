<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$conexion = new mysqli('localhost', 'root', '', 'gourmeet');

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error BD']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true) ?? $_POST;

$correo = trim($data['correo'] ?? '');
$facebook_id = trim($data['facebook_id'] ?? '');

// 🔥 SOLO facebook_id es obligatorio
if (empty($facebook_id)) {
    echo json_encode(['success' => false, 'error' => 'Facebook ID requerido']);
    exit;
}

// 🔍 BUSCAR USUARIO POR FACEBOOK_ID (más confiable)
$sql = "SELECT * FROM clientes WHERE CLI_FACEBOOK_ID = ? LIMIT 1";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Error en prepare']);
    exit;
}

$stmt->bind_param("s", $facebook_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    // validar origen
    if (!isset($row['Origen'])) {
        echo json_encode(['success' => false, 'error' => 'Campo Origen no existe']);
        exit;
    }

    if ($row['Origen'] !== 'facebook') {
        echo json_encode([
            'success' => false,
            'error' => 'Este usuario no está registrado con Facebook'
        ]);
        exit;
    }

    // validar estatus
    if ($row['CLI_ESTATUS'] == 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Cuenta suspendida'
        ]);
        exit;
    }

    // ✅ LOGIN OK
    echo json_encode([
        'success' => true,
        'usuario_id' => $row['CLI_ID'],
        'nombre' => $row['CLI_NOMBRE'],
        'correo' => $row['CLI_CORREO'],
        'login' => true
    ]);
} else {

    // 🔎 OPCIONAL: intentar por correo (si existe)
    if (!empty($correo)) {

        $sql2 = "SELECT * FROM clientes WHERE CLI_CORREO = ? LIMIT 1";
        $stmt2 = $conexion->prepare($sql2);

        if ($stmt2) {
            $stmt2->bind_param("s", $correo);
            $stmt2->execute();
            $res2 = $stmt2->get_result();

            if ($row2 = $res2->fetch_assoc()) {

                echo json_encode([
                    'success' => false,
                    'error' => 'Este correo ya está registrado con otro método'
                ]);
                exit;
            }
        }
    }

    echo json_encode([
        'success' => false,
        'error' => 'Usuario no registrado con Facebook'
    ]);
}

$stmt->close();
$conexion->close();

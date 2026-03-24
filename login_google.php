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
$google_id = trim($data['google_id'] ?? '');

if (empty($correo) || empty($google_id)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// 🔍 VALIDAR PREPARE
$sql = "SELECT * FROM clientes WHERE CLI_CORREO = ? LIMIT 1";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Error en prepare']);
    exit;
}

$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    // ⚠️ VALIDAR QUE EXISTA LA COLUMNA
    if (!isset($row['Origen'])) {
        echo json_encode(['success' => false, 'error' => 'Campo Origen no existe']);
        exit;
    }

    if ($row['Origen'] !== 'google') {
        echo json_encode([
            'success' => false,
            'error' => 'Este correo ya está registrado'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'usuario_id' => $row['CLI_ID'],
        'nombre' => $row['CLI_NOMBRE'],
        'correo' => $row['CLI_CORREO']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Usuario no registrado con Google'
    ]);
}

$stmt->close();
$conexion->close();

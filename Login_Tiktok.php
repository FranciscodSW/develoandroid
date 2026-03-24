<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 🔑 CONFIG
$client_key = "sbawcifei5hccyogut";
$client_secret = "4Qaqc66m1Gr2wjf6eVszkWsZe8WHqGMM";
$redirect_uri = "https://webhook.site/4e3282fd-1395-497c-ad7d-f79402426aed";

// 📡 BD
$conexion = new mysqli('localhost', 'root', '', 'gourmeet');

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error BD']);
    exit;
}

// 📥 recibir code
$data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
$code = $data['code'] ?? '';

if (empty($code)) {
    echo json_encode(['success' => false, 'error' => 'Code requerido']);
    exit;
}

try {

    // =========================
    // 🔥 1. TOKEN
    // =========================
    $url = "https://open.tiktokapis.com/v2/oauth/token/";

    $postData = http_build_query([
        "client_key" => $client_key,
        "client_secret" => $client_secret,
        "code" => $code,
        "grant_type" => "authorization_code",
        "redirect_uri" => $redirect_uri
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($response, true);

    if (!isset($tokenData['access_token'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Error token',
            'detalle' => $tokenData
        ]);
        exit;
    }

    $access_token = $tokenData['access_token'];

    // =========================
    // 🔥 2. USUARIO
    // =========================
    $urlUser = "https://open.tiktokapis.com/v2/user/info/?fields=open_id,display_name,avatar_url";

    $headers = [
        "Authorization: Bearer $access_token"
    ];

    $ch = curl_init($urlUser);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $responseUser = curl_exec($ch);
    curl_close($ch);

    $userData = json_decode($responseUser, true);

    if (!isset($userData['data']['user']['open_id'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Error usuario',
            'detalle' => $userData
        ]);
        exit;
    }

    $tiktok_id = $userData['data']['user']['open_id'];
    $nombre = $userData['data']['user']['display_name'];
    $avatar = $userData['data']['user']['avatar_url'];

    // =========================
    // 🔴 3. VALIDAR SI YA EXISTE
    // =========================
    $stmt = $conexion->prepare("SELECT CLI_ID FROM clientes WHERE CLI_TIKTOK_ID=? LIMIT 1");
    $stmt->bind_param("s", $tiktok_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Usuario ya registrado con TikTok'
        ]);
        exit;
    }

    // =========================
    // 🟢 4. REGISTRO
    // =========================
    $passwordDummy = password_hash(uniqid(), PASSWORD_DEFAULT);
    $ip = $_SERVER['REMOTE_ADDR'];
    $origen = "tiktok";

    $stmt = $conexion->prepare("
        INSERT INTO clientes 
        (CLI_CONTRASENIA, CLI_NOMBRE, CLI_PRIMER_IP, CLI_ESTATUS, FotoUsuario, Origen, CLI_TIKTOK_ID)
        VALUES (?,?,?,1,?,?,?)
    ");

    $stmt->bind_param(
        "ssssss",
        $passwordDummy,
        $nombre,
        $ip,
        $avatar,
        $origen,
        $tiktok_id
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $idUsuario = $conexion->insert_id;

    echo json_encode([
        'success' => true,
        'usuario_id' => $idUsuario,
        'registro' => true,
        'nombre' => $nombre
    ]);
} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conexion->close();

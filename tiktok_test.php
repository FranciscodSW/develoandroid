<?php

header('Content-Type: application/json; charset=utf-8');

// 🔐 CREDENCIALES
$client_key = "sbawcifei5hccyogut";
$client_secret = "4Qaqc66m1Gr2wjf6eVszkWsZe8WHqGMM";
$redirect_uri = "https://webhook.site/4e3282fd-1395-497c-ad7d-f79402426aed";

// 📥 RECIBIR CODE
$data = json_decode(file_get_contents("php://input"), true);
$code = $data['code'] ?? '';

if (empty($code)) {
    echo json_encode([
        'success' => false,
        'error' => 'Code vacío'
    ]);
    exit;
}

/* ================= TOKEN ================= */

$postData = http_build_query([
    "client_key" => $client_key,
    "client_secret" => $client_secret,
    "code" => $code,
    "grant_type" => "authorization_code",
    "redirect_uri" => $redirect_uri
]);

$ch = curl_init("https://open.tiktokapis.com/v2/oauth/token/");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$json = json_decode($response, true);

if (!isset($json['access_token'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Error obteniendo token',
        'tiktok_response' => $json
    ]);
    exit;
}

$access_token = $json['access_token'];
$open_id = $json['open_id'];

/* ================= USER INFO ================= */

$ch = curl_init("https://open.tiktokapis.com/v2/user/info/?fields=open_id,display_name,avatar_url");

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token"
]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$responseUser = curl_exec($ch);
curl_close($ch);

$userData = json_decode($responseUser, true);

$nombre = $userData['data']['user']['display_name'] ?? null;
$avatar = $userData['data']['user']['avatar_url'] ?? null;

/* ================= RESPUESTA FINAL ================= */

echo json_encode([
    'success' => true,
    'open_id' => $open_id,
    'nombre' => $nombre,
    'avatar' => $avatar,
    'raw_user_data' => $userData
]);

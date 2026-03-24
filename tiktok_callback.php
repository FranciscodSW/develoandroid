<?php

// 🔥 Mostrar errores (solo para pruebas)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 🔥 Obtener datos de TikTok
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';
$scope = $_GET['scope'] ?? '';

// 🔥 Log en archivo (para ver qué llega)
file_put_contents(
    "tiktok_log.txt",
    "====================\n" .
        "CODE: $code\n" .
        "STATE: $state\n" .
        "SCOPE: $scope\n" .
        "FECHA: " . date("Y-m-d H:i:s") . "\n\n",
    FILE_APPEND
);

// 🔥 Validar code
if (!empty($code)) {

    // 🔁 Redirigir a tu app
    header("Location: gourmeet://callback?code=$code&state=$state");
    exit;
} else {

    echo "❌ No se recibió code";
}

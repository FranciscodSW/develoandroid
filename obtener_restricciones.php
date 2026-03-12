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
        'error' => 'Error de conexión'
    ]);
    exit;
}

$sql = "SELECT 
        Res_Tipo,
        Id_Restricciones,
        Res_Nombre,
        Res_Descripcion
        FROM restricciones
        ORDER BY Res_Tipo, Res_Nombre";

$resultado = $conexion->query($sql);

$restricciones = [];

while ($fila = $resultado->fetch_assoc()) {

    $tipo = $fila['Res_Tipo'];

    // Crear contenedor por tipo si no existe
    if (!isset($restricciones[$tipo])) {
        $restricciones[$tipo] = [];
    }

    $restricciones[$tipo][] = [
        'Id_Restricciones' => $fila['Id_Restricciones'],
        'Res_Nombre' => $fila['Res_Nombre'],
        'Res_Descripcion' => $fila['Res_Descripcion']
    ];
}

echo json_encode([
    'success' => true,
    'restricciones' => $restricciones
], JSON_UNESCAPED_UNICODE);

$conexion->close();

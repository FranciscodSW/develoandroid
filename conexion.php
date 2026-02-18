<?php
$hostname = 'localhost';
$database = 'gourmeet';
$username = 'root';
$password = '';  // ← CONTRASEÑA VACÍA (predeterminado en XAMPP)

$conexion = new mysqli($hostname, $username, $password, $database);

if ($conexion->connect_error) {
    echo "Error con contraseña vacía: " . $conexion->connect_error . "<br>";
} else {
    echo "¡Conexión exitosa con contraseña vacía!<br>";
    echo "Base de datos: " . $database;
}

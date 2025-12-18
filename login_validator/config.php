<?php
$host = "localhost";
$user = "superaplicacion"; // Using the user found in the repo exploration
$pass = "Superaplicacion123$"; // Using the password found in the repo exploration
$db = "proyecto_login";

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");
?>
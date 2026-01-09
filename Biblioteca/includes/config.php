<?php
$host = "172.24.103.203";
$user = "ruthydomi"; // Using the user found in the repo exploration
$pass = "BibliotecaPersonal123$"; // Using the password found in the repo exploration
$db = "biblioteca_personal";

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");

// --- AUTO-MIGRACIÓN (Asegura que la BD tenga las columnas nuevas) ---
try {
    // 1. Columnas en Libros
    $check = $conexion->query("SHOW COLUMNS FROM Libros LIKE 'portada'");
    if ($check->num_rows == 0) {
        $conexion->query("ALTER TABLE Libros ADD COLUMN portada VARCHAR(255) DEFAULT NULL");
    }
    
    $check = $conexion->query("SHOW COLUMNS FROM Libros LIKE 'precio'");
    if ($check->num_rows == 0) {
        $conexion->query("ALTER TABLE Libros ADD COLUMN precio DECIMAL(6, 2) DEFAULT NULL");
    }

    // 2. Columnas en Coleccion
    $check = $conexion->query("SHOW COLUMNS FROM Coleccion LIKE 'capitulo_actual'");
    if ($check->num_rows == 0) {
        $conexion->query("ALTER TABLE Coleccion ADD COLUMN capitulo_actual INT DEFAULT 0");
    }

    $check = $conexion->query("SHOW COLUMNS FROM Coleccion LIKE 'valoracion'");
    if ($check->num_rows == 0) {
        $conexion->query("ALTER TABLE Coleccion ADD COLUMN valoracion INT DEFAULT 0");
    }

} catch (Exception $e) {
    // Si falla la migración, no detenemos, pero podría causar error más adelante.
    // Error_log($e->getMessage());
}
?>
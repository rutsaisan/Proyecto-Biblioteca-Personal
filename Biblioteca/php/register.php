<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_completo = filter_input(INPUT_POST, 'nombre_completo', FILTER_SANITIZE_SPECIAL_CHARS);
    $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $contrasena = $_POST['contrasena'];

    if (empty($nombre_completo) || empty($usuario) || empty($email) || empty($contrasena)) {
        echo "Por favor, complete todos los campos. <a href='../register.html'>Volver</a>";
        exit;
    }

    // Corregido: Uso de 'Usuarios' con Mayúscula
    $sql_check = "SELECT id_usuario FROM Usuarios WHERE usuario = ? OR email = ?";
    if ($stmt = $conexion->prepare($sql_check)) {
        $stmt->bind_param("ss", $usuario, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo "El usuario o correo electrónico ya está registrado. <a href='../register.html'>Intentar de nuevo</a>";
            $stmt->close();
            exit;
        }
        $stmt->close();
    }

    $password_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Corregido: 'Usuarios' con Mayúscula
    $sql_insert = "INSERT INTO Usuarios (usuario, email, contrasena, nombre_completo) VALUES (?, ?, ?, ?)";

    if ($stmt = $conexion->prepare($sql_insert)) {
        $stmt->bind_param("ssss", $usuario, $email, $password_hash, $nombre_completo);

        if ($stmt->execute()) {
            echo "<h1>¡Registro exitoso!</h1>";
            echo "<p>Ahora puedes <a href='../index.php'>iniciar sesión</a>.</p>";
        } else {
            echo "Error al registrar el usuario: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error en la preparación de la consulta: " . $conexion->error;
    }

    $conexion->close();
}
?>
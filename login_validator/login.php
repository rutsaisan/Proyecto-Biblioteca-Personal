<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $contrasena = $_POST['contrasena'];

    if (empty($email) || empty($contrasena)) {
        echo "Por favor, complete todos los campos.";
        exit;
    }

    // Corregido: id_usuario en lugar de id, y Usuarios con Mayúscula
    $sql = "SELECT id_usuario, contrasena, nombre_completo FROM Usuarios WHERE email = ?";

    if ($stmt = $conexion->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            // Corregido: nombre de variable id_usuario
            $stmt->bind_result($id_usuario, $hashed_password, $nombre_completo);
            $stmt->fetch();

            if (password_verify($contrasena, $hashed_password)) {
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $id_usuario;
                $_SESSION['username'] = $email;
                $_SESSION['nombre'] = $nombre_completo;

                echo "<h1>¡Bienvenido, " . htmlspecialchars($nombre_completo) . "!</h1>";
                echo "<p>Login exitoso.</p>";
                // header("Location: dashboard.php"); // Descomenta esto cuando tengas tu página principal
            } else {
                echo "La contraseña introducida no es válida.";
            }
        } else {
            echo "No se encontró ninguna cuenta con ese correo electrónico.";
        }
        $stmt->close();
    } else {
        echo "Error en la consulta de base de datos: " . $conexion->error;
    }
    $conexion->close();
}
?>
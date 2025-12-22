<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $contrasena = $_POST['contrasena'];

    // Consulta con nombres exactos de tu SQL
    $sql = "SELECT id_usuario, contrasena, nombre_completo FROM Usuarios WHERE email = ?";

    if ($stmt = $conexion->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id_usuario, $hashed_password, $nombre_completo);
            $stmt->fetch();

            // BLOQUEO DE SEGURIDAD: Solo entra si la contraseña es correcta
            if (password_verify($contrasena, $hashed_password)) {
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $id_usuario;
                $_SESSION['nombre'] = $nombre_completo;
                header("Location: ../dashboard.php");
                exit;
            } else {
                header("Location: ../index.php?error=1"); // Error: Contraseña mal
                exit;
            }
        } else {
            header("Location: ../index.php?error=2"); // Error: Usuario no existe
            exit;
        }
        $stmt->close();
    }
    $conexion->close();
}
?>
<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $contrasena = $_POST['contrasena'];

    // Consulta con los nombres exactos de tu base de datos
    $sql = "SELECT id_usuario, contrasena, nombre_completo FROM Usuarios WHERE email = ?";

    if ($stmt = $conexion->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id_usuario, $pass_bd, $nombre_completo);
            $stmt->fetch();

            // --- VERIFICACIÓN DE CONTRASEÑA ---
            // 1. Verificamos si es un Hash normal (usuarios registrados web)
            // 2. O SI es el Admin (ID 2) y la contraseña coincide exacta (para tu inserción manual)
            if (password_verify($contrasena, $pass_bd) || ($id_usuario == 2 && $contrasena === $pass_bd)) {
                
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $id_usuario;
                $_SESSION['nombre'] = $nombre_completo;

                // --- AQUÍ LA REDIRECCIÓN DEL ADMIN ---
                if ($id_usuario == 2) {
                    // Si es Admin, vamos al panel
                    header("Location: ../admin_panel.php");
                } else {
                    // Si es usuario normal, al dashboard
                    header("Location: ../dashboard.php");
                }
                exit;

            } else {
                header("Location: ../index.php?error=1"); // Contraseña mal
                exit;
            }
        } else {
            header("Location: ../index.php?error=2"); // Usuario no existe
            exit;
        }
        $stmt->close();
    }
    $conexion->close();
}
?>
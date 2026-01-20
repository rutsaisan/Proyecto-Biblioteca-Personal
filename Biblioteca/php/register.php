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
<?php
session_start();
require_once '../includes/config.php';

// Función para mostrar alerta y volver atrás sin dejar una página en blanco
function mostrarError($mensaje) {
    echo "<script>
            alert('$mensaje');
            window.history.back();
          </script>";
    exit; // Detiene el script aquí
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_completo = filter_input(INPUT_POST, 'nombre_completo', FILTER_SANITIZE_SPECIAL_CHARS);
    $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $contrasena = $_POST['contrasena'];

    // 1. Validar campos vacíos
    if (empty($nombre_completo) || empty($usuario) || empty($email) || empty($contrasena)) {
        mostrarError("Por favor, complete todos los campos.");
    }

    // ---------------------------------------------------------
    // 2. VALIDACIÓN DE VALORES LÍMITE (Tus restricciones)
    // ---------------------------------------------------------

    // a. Validación para el nombre de usuario (Mínimo 5, Máximo 20)
    // Esto cubre los casos: 4 (error), 5 (ok), 20 (ok), 21 (error)
    $len_usuario = strlen($usuario);
    if ($len_usuario < 5 || $len_usuario > 20) {
        mostrarError("Error: El usuario debe tener entre 5 y 20 caracteres.");
    }

    // b. Validación para la contraseña (Mínimo 8, Máximo 16)
    // Esto cubre los casos: 7 (error), 8 (ok), 16 (ok), 17 (error)
    $len_pass = strlen($contrasena);
    if ($len_pass < 8 || $len_pass > 16) {
        mostrarError("Error: La contraseña debe tener entre 8 y 16 caracteres.");
    }
    // ---------------------------------------------------------

    // Verificar duplicados
    $sql_check = "SELECT id_usuario FROM Usuarios WHERE usuario = ? OR email = ?";
    if ($stmt = $conexion->prepare($sql_check)) {
        $stmt->bind_param("ss", $usuario, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            mostrarError("El usuario o correo electrónico ya está registrado.");
        }
        $stmt->close();
    }

    $password_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Insertar usuario
    $sql_insert = "INSERT INTO Usuarios (usuario, email, contrasena, nombre_completo) VALUES (?, ?, ?, ?)";

    if ($stmt = $conexion->prepare($sql_insert)) {
        $stmt->bind_param("ssss", $usuario, $email, $password_hash, $nombre_completo);

        if ($stmt->execute()) {
            // Éxito: Aquí sí podemos redirigir o mostrar mensaje de éxito
            echo "<h1>¡Registro exitoso!</h1>";
            echo "<p>Ahora puedes <a href='../index.php'>iniciar sesión</a>.</p>";
            // Opcional: Redirección automática tras éxito
            // header("Location: ../index.php"); 
        } else {
            mostrarError("Error al registrar el usuario en la base de datos.");
        }
        $stmt->close();
    } else {
        mostrarError("Error en la preparación de la consulta: " . $conexion->error);
    }

    $conexion->close();
}
?>
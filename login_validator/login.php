<?php
// Iniciar una nueva sesión o reanudar la existente. Esto permite guardar variables (como si el usuario está logueado) a través de distintas páginas.
session_start();

// Incluir el archivo de configuración 'config.php' una sola vez. Este archivo contiene la conexión a la base de datos '$conexion'.
require_once 'config.php';

// Verificar si el método de solicitud es POST. Esto ocurre cuando el usuario envía el formulario.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recoger el campo 'email' enviado por el formulario y limpiarlo para eliminar caracteres ilegales (saneamiento).
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    // Recoger el campo 'contrasena' enviado por el formulario. No se sanea aquí porque las contraseñas pueden contener caracteres especiales, pero se debe manejar con cuidado.
    $contrasena = $_POST['contrasena'];

    // Validar si el campo 'email' o 'contrasena' están vacíos.
    if (empty($email) || empty($contrasena)) {
        // Si alguno está vacío, mostrar un mensaje de error al usuario y detener la ejecución del script.
        echo "Por favor, complete todos los campos.";
        exit;
    }

    // Definir la consulta SQL para buscar el usuario. Buscamos el ID, la contraseña encriptada y el nombre completo donde el email coincida.
    // Usamos '?' como marcador de posición para evitar inyecciones SQL.
    $sql = "SELECT id, contrasena, nombre_completo FROM usuarios WHERE email = ?";

    // Preparar la consulta SQL en la base de datos para su ejecución segura.
    if ($stmt = $conexion->prepare($sql)) {

        // Vincular el parámetro '$email' al marcador de posición '?' de la consulta. La "s" indica que el parámetro es un String.
        $stmt->bind_param("s", $email);

        // Ejecutar la consulta preparada.
        $stmt->execute();

        // Almacenar el resultado de la consulta en el buffer interno para poder contar las filas encontradas.
        $stmt->store_result();

        // Verificar si la consulta devolvió exactamente 1 fila (significa que el usuario existe).
        if ($stmt->num_rows == 1) {

            // Vincular las columnas del resultado (ID, contraseña, nombre) a variables de PHP.
            $stmt->bind_result($id, $hashed_password, $nombre_completo);

            // Obtener los valores de la fila actual y asignarlos a las variables vinculadas anteriormente.
            $stmt->fetch();

            // Verificar si la contraseña introducida por el usuario coincide con la contraseña hasheada (encriptada) de la base de datos.
            if (password_verify($contrasena, $hashed_password)) {

                // Si la contraseña es correcta, establecer una variable de sesión 'loggedin' como true.
                $_SESSION['loggedin'] = true;

                // Guardar el ID del usuario en la sesión para identificarlo posteriormente.
                $_SESSION['id'] = $id;

                // Guardar el email (que usamos como username) en la sesión.
                $_SESSION['username'] = $email;

                // Guardar el nombre completo del usuario en la sesión para mostrarlo (por ejemplo, en el saludo).
                $_SESSION['nombre'] = $nombre_completo;

                // Mostrar un mensaje de bienvenida personalizado utilizando el nombre completo. htmlspecialchars evita ataques XSS (Cross-Site Scripting).
                echo "<h1>¡Bienvenido, " . htmlspecialchars($nombre_completo) . "!</h1>";
                echo "<p>Login exitoso.</p>";

                // Aquí normalmente redirigirías al usuario a otra página, ej: header("Location: dashboard.php");
            } else {
                // Si la contraseña no coincide, mostrar un mensaje de error.
                echo "La contraseña introducida no es válida.";
            }
        } else {
            // Si no se encontró ninguna fila (num_rows != 1), significa que el correo electrónico no existe en la base de datos.
            echo "No se encontró ninguna cuenta con ese correo electrónico.";
        }

        // Cerrar la sentencia preparada para liberar recursos.
        $stmt->close();
    } else {
        // mensaje de error si la base de datos falla al preparar la consulta
        echo "Error en la consulta de base de datos.";
    }

    // Cerrar la conexión a la base de datos.
    $conexion->close();
}
?>
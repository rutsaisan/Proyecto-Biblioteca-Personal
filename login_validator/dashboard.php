<?php
session_start();

// Seguridad: Expulsar si no hay sesión activa
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

$nombre_usuario = htmlspecialchars($_SESSION['nombre']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mi Biblioteca Personal</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        .welcome-container {
            font-family: 'Quicksand', sans-serif; /* Aplicamos la letra del estilo */
            text-align: center;
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: 50px auto;
        }
        .welcome-container h1 { color: #333; font-weight: 700; }
        .welcome-container p { color: #666; font-weight: 400; }
        .logout-link {
            color: #ff4d4d;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="logo-container">
            <img src="img/logo.png" alt="Logo" class="logo">
        </div>

        <div class="welcome-container">
            <h1>¡Hola, <?php echo $nombre_usuario; ?>!</h1>
            <p>Tu contraseña es correcta. Bienvenido a tu biblioteca.</p>
            <a href="logout.php" class="logout-link">Cerrar Sesión</a>
        </div>
    </div>
</body>
</html>
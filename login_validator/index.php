<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Biblioteca Personal</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        /* Adaptamos la letra del error al estilo Quicksand */
        .error-message {
            color: #ff4d4d;
            font-family: 'Quicksand', sans-serif;
            font-weight: 600;
            font-size: 0.9em;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="logo-container">
            <img src="img/logo.png" alt="Logo" class="logo">
        </div>
        <div class="title-container">
            <h1>Mi Biblioteca Personal</h1>
        </div>

        <form action="login.php" method="POST" class="login-form">
            <input type="email" name="email" placeholder="Correo..." required>
            <input type="password" name="contrasena" placeholder="Contraseña..." required>

            <?php if (isset($_GET['error']) && $_GET['error'] == '1'): ?>
                <p class="error-message">Contraseña incorrecta, vuelve a intentarlo</p>
            <?php elseif (isset($_GET['error']) && $_GET['error'] == '2'): ?>
                <p class="error-message">El correo no existe o faltan datos</p>
            <?php endif; ?>

            <button type="submit">Iniciar sesión</button>
        </form>

        <div class="footer-link">
            <a href="register.html">Crear cuenta</a>
        </div>
    </div>
</body>
</html>
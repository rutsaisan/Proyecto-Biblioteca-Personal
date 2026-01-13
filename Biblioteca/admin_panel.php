<?php
session_start();

// 1. SEGURIDAD: Solo Admin (ID 2)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['id'] != 2) {
    header("Location: index.php");
    exit;
}

require_once 'includes/config.php';

// 2. OBTENER LISTA DE TABLAS
$lista_tablas = [];
$sql_tablas = "SHOW TABLES";
$result_tablas = $conexion->query($sql_tablas);

if ($result_tablas) {
    while ($row = $result_tablas->fetch_array()) {
        $lista_tablas[] = $row[0];
    }
}

// 3. DETERMINAR QUÉ TABLA MOSTRAR
// Si hay ?tabla=X en la URL, usamos esa. Si no, la primera de la lista.
$tabla_actual = isset($_GET['tabla']) ? $_GET['tabla'] : (count($lista_tablas) > 0 ? $lista_tablas[0] : null);

// 4. SEGURIDAD SQL (Verificar que la tabla existe)
if ($tabla_actual && !in_array($tabla_actual, $lista_tablas)) {
    die("Error: La tabla solicitada no existe.");
}

// 5. CONSULTA DE DATOS
$columnas = [];
$res_datos = null;

if ($tabla_actual) {
    // Obtener columnas
    $sql_cols = "SHOW COLUMNS FROM $tabla_actual";
    $res_cols = $conexion->query($sql_cols);
    while ($col = $res_cols->fetch_assoc()) {
        $columnas[] = $col['Field'];
    }

    // Obtener datos
    $sql_datos = "SELECT * FROM $tabla_actual";
    $res_datos = $conexion->query($sql_datos);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - <?php echo ucfirst($tabla_actual); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilo.css">
    
    <style>
        body { background-color: #cc79cfff; margin: 0; padding-bottom: 50px; }
        
        /* --- NUEVO MENÚ HORIZONTAL --- */
        .navbar {
            background-color: #343a40;
            padding: 0 20px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between; /* Separa el menú del botón Salir */
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .nav-links {
            display: flex;
            gap: 10px;
            overflow-x: auto; /* Permite scroll horizontal si hay muchas tablas */
        }

        .nav-item {
            color: #ccc;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-family: 'Quicksand', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            white-space: nowrap; /* Evita que el texto se rompa */
            transition: 0.3s;
        }

        .nav-item:hover { background-color: #495057; color: white; }
        
        /* Estilo para la pestaña activa */
        .nav-item.active { background-color: #007bff; color: white; }

        .btn-logout {
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-family: 'Quicksand', sans-serif;
            font-weight: bold;
            margin-left: 20px;
        }

        /* --- CONTENIDO --- */
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        h1 { font-family: 'Quicksand', sans-serif; color: #333; margin-bottom: 10px; }
        .info-count { color: #666; margin-bottom: 20px; display: block; }

        /* TABLA */
        .table-wrapper {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow-x: auto;
            display: inline-grid;
        }
        
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #333; color: white; font-family: 'Quicksand', sans-serif; white-space: nowrap; }
        tr:hover { background-color: #f1f1f1; }
    </style>
</head>
<body>

    <div class="navbar">
        <div class="nav-links">
            <span style="color:white; font-weight:bold; margin-right:15px; align-self:center;">ADMIN PANEL</span>
            
            <?php foreach ($lista_tablas as $tabla): ?>
                <a href="?tabla=<?php echo $tabla; ?>" 
                   class="nav-item <?php echo ($tabla == $tabla_actual) ? 'active' : ''; ?>">
                   <?php echo ucfirst($tabla); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <a href="php/logout.php" class="btn-logout">Salir</a>
    </div>

    <div class="container">
        <?php if ($tabla_actual): ?>
            
            <h1>Tabla: <?php echo ucfirst($tabla_actual); ?></h1>
            <span class="info-count">Total de registros: <strong><?php echo $res_datos->num_rows; ?></strong></span>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($columnas as $columna): ?>
                                <th><?php echo $columna; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_datos->num_rows > 0): ?>
                            <?php while ($fila = $res_datos->fetch_assoc()): ?>
                                <tr>
                                    <?php foreach ($columnas as $columna): ?>
                                        <td>
                                            <?php 
                                            $dato = $fila[$columna];
                                            // Cortar textos muy largos para que no rompan la tabla
                                            echo (strlen($dato) > 60) ? substr($dato, 0, 60) . '...' : $dato; 
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo count($columnas); ?>" style="text-align:center; padding: 20px; color:#777;">
                                    La tabla está vacía.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <h3>No se encontraron tablas.</h3>
        <?php endif; ?>
    </div>

</body>
</html>
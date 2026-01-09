<?php
include "includes/config.php";

$usuario_id = 1; // Usuario temporal, igual que en nuevo_libro.php

// --- LOGICA DE ELIMINAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id_libro_eliminar = intval($_POST['delete_id']);
    
    // Solo eliminar de la colección del usuario para no borrar el libro de la BD global si otros lo tienen
    // (Aunque en este esquema simple, Coleccion es el vinculo)
    $stmt = $conexion->prepare("DELETE FROM Coleccion WHERE id_libro = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_libro_eliminar, $usuario_id);
    
    if ($stmt->execute()) {
        // Redirigir para evitar reenvío de formulario
        header("Location: feed.php");
        exit;
    } else {
        $error = "Error al eliminar el libro.";
    }
    $stmt->close();
}

// --- CONSULTA DE LIBROS ---
$sql = "SELECT L.id_libro, L.titulo, L.portada, L.descripcion, 
               A.nombre AS autor, 
               C.estado, C.capitulo_actual, C.valoracion
        FROM Coleccion C
        JOIN Libros L ON C.id_libro = L.id_libro
        LEFT JOIN Autores A ON L.id_autor = A.id_autor
        WHERE C.id_usuario = ?
        ORDER BY C.fecha_agregado DESC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$libros = [];
while ($row = $result->fetch_assoc()) {
    $libros[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Biblioteca Personal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Caveat:wght@600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fffbef; /* Fondo crema suave */
            color: #1a1a1a;
        }
        
        /* Header estilo 'Barra de búsqueda' con borde redondeado completo */
        .custom-header {
            background: #fff;
            border: 2px solid #a8a29e;
            border-radius: 50px;
            padding: 12px 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1000px;
            margin: 20px auto;
        }

        /* Botón Añadir Libro */
        .btn-add {
            background-color: #9bd676; /* Verde clarito */
            color: #000;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 12px 30px;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            transition: transform 0.2s;
            display: inline-block;
            margin-bottom: 30px;
            text-decoration: none;
        }
        .btn-add:hover { transform: scale(1.03); }

        /* Icono de libros en el header */
        .logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
        }

        /* TARJETAS */
        .book-card {
            border-radius: 24px;
            padding: 24px;
            display: flex;
            gap: 20px;
            position: relative;
            min-height: 280px;
            transition: transform 0.2s;
        }
        .book-card:hover { transform: translateY(-3px); }

        /* Colores de Tarjetas según estado */
        .card-deseado { background-color: #fff59d; } /* Amarillo */
        .card-leyendo { background-color: #ffbbf1; } /* Rosa */
        .card-leido   { background-color: #e1bee7; } /* Violeta */

        .book-cover {
            width: 120px;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 4px 4px 8px rgba(0,0,0,0.2);
            flex-shrink: 0;
            background-color: #ddd;
        }

        .book-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding-bottom: 40px; /* Space for buttons */
        }

        .book-title {
            font-size: 1.4rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 4px;
        }
        
        .book-author {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 12px;
            color: #333;
        }

        .book-desc {
            font-size: 0.85rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 5;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: auto; /* Empuja el contenido inferior hacia abajo */
        }

        .status-badge {
            margin-top: 15px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .progress-text {
            font-family: 'Inter', cursive; /* O usar Caveat si se quiere más hand-written */
            font-size: 0.9rem;
            margin-top: 2px;
        }

        .stars { color: #f59e0b; font-size: 1.2rem; }

        /* Botones de acción (Editar / Borrar) */
        .action-buttons {
            position: absolute;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }
        .icon-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .icon-btn:hover { opacity: 1; }
        .icon-btn svg { width: 24px; height: 24px; stroke-width: 2; stroke: #000; fill: none; }

    </style>
</head>
<body class="bg-[#fffbef]">

    <!-- Header -->
    <div class="px-4">
        <div class="custom-header">
            <div class="flex items-center">
                <!-- Icono representativo (puede ser imagen o svg) -->
                <div class="logo-container">
            <img src="assets/img/logo.png" alt="Logo" class="logo">
        </div>
                <h1 class="text-2xl font-bold tracking-tight">Mi Biblioteca Personal</h1>
            </div>
            <a href="php/logout.php" class="text-red-400 font-semibold hover:text-red-600">Cerrar sesión</a>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="container mx-auto px-4 pb-12 max-w-6xl">
        
        
        <a href="nuevo_libro.php" class="btn-add shadow-md hover:shadow-lg">
            + Añadir libro
        </a>

        <!-- Grid de Libros -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <?php if (empty($libros)): ?>
                <div class="col-span-full text-center py-10 text-gray-500">
                    <p class="text-xl">Aún no tienes libros en tu colección.</p>
                </div>
            <?php else: ?>
                <?php foreach ($libros as $libro): 
                    // Determinar clase según estado
                    $bgClass = 'card-deseado';
                    if (strtolower($libro['estado']) === 'leyendo') $bgClass = 'card-leyendo';
                    if (strtolower($libro['estado']) === 'leido')   $bgClass = 'card-leido';
                    
                    // Ruta de portada (default si no hay)
                    $portada = !empty($libro['portada']) ? $libro['portada'] : 'https://via.placeholder.com/120x180?text=No+Cover';
                ?>
                <div class="book-card <?php echo $bgClass; ?>">
                    <img src="<?php echo htmlspecialchars($portada); ?>" alt="Portada" class="book-cover border-2 border-black/10">
                    
                    <div class="book-info">
                        <div class="book-title"><?php echo htmlspecialchars($libro['titulo']); ?></div>
                        <div class="book-author"><?php echo htmlspecialchars($libro['autor']); ?></div>
                        
                        <div class="book-desc">
                            <?php echo htmlspecialchars($libro['descripcion']); ?>
                        </div>

                        <div class="status-badge">
                            Estado: <?php echo htmlspecialchars($libro['estado']); ?>
                            
                            <?php if (strtolower($libro['estado']) === 'leyendo' && $libro['capitulo_actual']): ?>
                                <div class="progress-text">Cap: <?php echo $libro['capitulo_actual']; ?></div>
                            <?php endif; ?>

                            <?php if (strtolower($libro['estado']) === 'leido'): ?>
                                <div class="stars">
                                    <?php 
                                    $val = $libro['valoracion'] ? $libro['valoracion'] : 0;
                                    for($i=0; $i<5; $i++) {
                                        echo ($i < $val) ? '★' : '☆';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="action-buttons">
                        <!-- Editar (Por ahora link hueco) -->
                        <a href="nuevo_libro.php?edit=<?php echo $libro['id_libro']; ?>" class="icon-btn" title="Editar">
                            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </a>
                        
                        <!-- Eliminar -->
                        <form method="POST" onsubmit="return confirm('¿Estás seguro de querer eliminar este libro?');" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $libro['id_libro']; ?>">
                            <button type="submit" class="icon-btn" title="Eliminar">
                                <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </button>
                        </form>
                    </div>

                </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>

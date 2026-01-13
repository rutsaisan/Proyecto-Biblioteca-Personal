<?php
// 1. INICIAR SESIÓN
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. VERIFICAR LOGIN (Seguridad)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

include "includes/config.php";

// 3. OBTENER ID DEL USUARIO ACTUAL
// Ya no hay redirección. Seas quien seas (ID 1, 2 o 50), verás tus libros.
if (isset($_SESSION['id'])) {
    $usuario_id = $_SESSION['id'];
} else {
    // Fallback por seguridad si la sesión 'id' no está definida
    $usuario_id = 1; 
}

// --- LOGICA DE ELIMINAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id_libro_eliminar = intval($_POST['delete_id']);
    
    // PASO A: Eliminar imagen física (Opcional)
    $stmt_img = $conexion->prepare("SELECT portada FROM Libros WHERE id_libro = ?");
    $stmt_img->bind_param("i", $id_libro_eliminar);
    $stmt_img->execute();
    $res_img = $stmt_img->get_result();
    if ($fila = $res_img->fetch_assoc()) {
        $ruta_imagen = $fila['portada'];
        if (!empty($ruta_imagen) && file_exists($ruta_imagen)) {
            unlink($ruta_imagen);
        }
    }
    $stmt_img->close();

    // PASO B: Eliminar de Coleccion
    $stmt_col = $conexion->prepare("DELETE FROM Coleccion WHERE id_libro = ?");
    $stmt_col->bind_param("i", $id_libro_eliminar);
    $stmt_col->execute();
    $stmt_col->close();

    // PASO C: Eliminar de Libros
    $stmt_libro = $conexion->prepare("DELETE FROM Libros WHERE id_libro = ?");
    $stmt_libro->bind_param("i", $id_libro_eliminar);
    
    if ($stmt_libro->execute()) {
        header("Location: feed.php");
        exit;
    } else {
        $error = "Error al eliminar el libro.";
    }
    $stmt_libro->close();
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fffbef;
            color: #1a1a1a;
            margin: 0;
            padding: 20px;
        }

        /* HEADER */
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
            margin: 0 auto 30px auto; 
        }

        .logo-area { display: flex; align-items: center; gap: 15px; }
        .logo { width: 45px; height: 45px; object-fit: cover; border-radius: 50%; }

        .btn-add {
            background-color: #9bd676;
            color: #000;
            font-weight: 700;
            font-size: 1rem;
            padding: 10px 25px;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            transition: transform 0.2s;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-add:hover { transform: scale(1.05); }

        /* GRID PRINCIPAL */
        .library-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding-bottom: 50px;
        }

        /* CONTENEDOR FANTASMA */
        .card-wrapper {
            position: relative;
            height: 280px;
            width: 100%;
            z-index: 1;
            transition: z-index 0s;
        }
        .card-wrapper:hover { z-index: 1000; }

        /* TARJETA */
        .book-card {
            background-color: #fff; 
            border-radius: 24px;
            padding: 24px;
            display: flex;
            gap: 20px;
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%; 
            box-sizing: border-box;
            overflow: hidden; 
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform-origin: top center;
        }

        .book-card:hover {
            height: auto; 
            min-height: 280px; 
            max-height: 600px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: scale(1.02);
            z-index: 50; 
        }

        /* COLORES Y DETALLES */
        .card-deseado { background-color: #fff59d !important; }
        .card-leyendo { background-color: #ffbbf1 !important; }
        .card-leido   { background-color: #e1bee7 !important; }

        .book-cover {
            width: 100px; height: 150px;
            object-fit: cover; border-radius: 8px;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.15);
            flex-shrink: 0; background-color: #eee;
        }

        .book-info { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .book-title { font-size: 1.1rem; font-weight: bold; line-height: 1.2; margin-bottom: 4px; }
        .book-author { font-size: 0.9rem; color: #666; font-style: italic; margin-bottom: 10px; }
        
        .book-desc {
            font-size: 0.85rem; line-height: 1.5; color: #444;
            display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
        }
        .book-card:hover .book-desc {
            display: block; overflow-y: auto; margin-bottom: 10px; padding-right: 5px; scrollbar-width: thin;
        }

        .status-badge {
            margin-top: 10px; font-size: 0.8rem; font-weight: 600;
            display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        }
        .stars { color: #f59e0b; letter-spacing: 2px; }

        /* BOTONES */
        .action-buttons {
            margin-top: auto; display: flex; justify-content: flex-end; gap: 8px;
            opacity: 0; transform: translateY(10px); transition: all 0.3s ease; padding-top: 10px;
        }
        .book-card:hover .action-buttons { opacity: 1; transform: translateY(0); }

        .icon-btn {
            background: rgba(255,255,255,0.5); border: 1px solid rgba(0,0,0,0.1);
            border-radius: 8px; padding: 8px; cursor: pointer;
            transition: background 0.2s; display: flex; align-items: center; justify-content: center; color: #333;
        }
        .icon-btn:hover { background: #fff; color: #000; }
        .icon-btn svg { width: 18px; height: 18px; }
    </style>
</head>
<body>

    <div class="custom-header">
        <div class="logo-area">
            <img src="assets/img/logo.png" alt="Logo" class="logo">
            <h1 class="text-xl md:text-2xl font-bold tracking-tight m-0">Mi Biblioteca</h1>
        </div>
        
        <div class="flex items-center gap-4">
            <a href="php/logout.php" class="text-red-500 font-semibold hover:text-red-700 text-sm md:text-base no-underline">
                Cerrar sesión
            </a>
        </div>
    </div>

    <div style="max-width: 1000px; margin: 0 auto;">
        
        <div class="flex justify-end pr-4">
            <a href="nuevo_libro.php" class="btn-add shadow-md">
                + Añadir libro
            </a>
        </div>

        <div class="library-grid">
            
            <?php if (empty($libros)): ?>
                <div class="col-span-full text-center py-10 text-gray-500 w-full" style="grid-column: 1 / -1;">
                    <p class="text-xl">Aún no tienes libros en tu colección.</p>
                </div>
            <?php else: ?>
                <?php foreach ($libros as $libro): 
                    $bgClass = 'card-deseado'; 
                    if (strtolower($libro['estado']) === 'leyendo') $bgClass = 'card-leyendo';
                    if (strtolower($libro['estado']) === 'leido')   $bgClass = 'card-leido';
                    
                    $portada = !empty($libro['portada']) ? $libro['portada'] : 'https://via.placeholder.com/120x180?text=No+Cover';
                ?>

                <div class="card-wrapper">
                    
                    <div class="book-card <?php echo $bgClass; ?>">
                        
                        <img src="<?php echo htmlspecialchars($portada); ?>" alt="Portada" class="book-cover">
                        
                        <div class="book-info">
                            <div class="book-title"><?php echo htmlspecialchars($libro['titulo']); ?></div>
                            <div class="book-author"><?php echo htmlspecialchars($libro['autor']); ?></div>
                            
                            <div class="book-desc">
                                <?php echo htmlspecialchars($libro['descripcion']); ?>
                            </div>

                            <div class="status-badge">
                                <span><?php echo ucfirst(htmlspecialchars($libro['estado'])); ?></span>
                                
                                <?php if (strtolower($libro['estado']) === 'leyendo' && $libro['capitulo_actual']): ?>
                                    <span class="text-xs bg-black/10 px-2 py-1 rounded">Cap: <?php echo $libro['capitulo_actual']; ?></span>
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

                            <div class="action-buttons">
                                <a href="nuevo_libro.php?edit=<?php echo $libro['id_libro']; ?>" class="icon-btn" title="Editar">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                    </svg>
                                </a>
                                
                                <form method="POST" onsubmit="return confirm('¿Estás seguro de querer eliminar este libro?');" style="margin:0;">
                                    <input type="hidden" name="delete_id" value="<?php echo $libro['id_libro']; ?>">
                                    <button type="submit" class="icon-btn" title="Eliminar" style="border:none;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                    </button>
                                </form>
                            </div> 
                        </div> 
                    </div> 
                </div>

                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
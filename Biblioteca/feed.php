<?php
session_start();
include "includes/config.php";

// 1. VALIDACIÓN DE SESIÓN
if (!isset($_SESSION['user_id'])) {
    // Si no hay login, mandar al inicio
    header("Location: index.php"); 
    exit;
}
$usuario_id = $_SESSION['user_id'];

// 2. LÓGICA DE ELIMINAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id_libro_eliminar = intval($_POST['delete_id']);
    
    $stmt = $conexion->prepare("DELETE FROM Coleccion WHERE id_libro = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_libro_eliminar, $usuario_id);
    
    if ($stmt->execute()) {
        header("Location: feed.php?msg=eliminado");
        exit;
    } else {
        $error = "Error al eliminar el libro.";
    }
    $stmt->close();
}

// 3. CONSULTA DE LIBROS
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
            background-color: #fffbef;
            color: #1a1a1a;
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
            margin: 20px auto;
        }

        .btn-add {
            background-color: #9bd676;
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

        .logo {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 15px;
        }

        /* --- LOGICA DE TARJETAS EXPANDIBLES --- */

        /* 1. El Wrapper mantiene el espacio en la rejilla */
        .card-wrapper {
            position: relative;
            min-height: 280px; /* Altura base de la tarjeta cerrada */
            width: 100%;
            z-index: 1;
        }

        /* 2. La Tarjeta flota dentro del wrapper */
        .book-card {
            border-radius: 24px;
            padding: 24px;
            display: flex;
            gap: 20px;
            
            position: absolute; /* Clave para superponerse */
            top: 0; 
            left: 0;
            width: 100%;
            height: 100%; /* Inicialmente igual al wrapper */
            overflow: hidden; /* Corta contenido extra */
            
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            background-color: #fff; /* Fondo base */
        }

        /* 3. Efecto Hover en el Wrapper afecta a la Tarjeta */
        .card-wrapper:hover .book-card {
            height: auto; /* Se expande según el contenido */
            min-height: 280px;
            transform: scale(1.03); /* Crece un poco */
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
            z-index: 50; /* Se pone MUY por encima del resto */
            cursor: default;
        }

        /* Colores */
        .card-deseado { background-color: #fff59d; } 
        .card-leyendo { background-color: #ffbbf1; } 
        .card-leido   { background-color: #e1bee7; } 

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

        /* Descripción: Recortada normalmente, visible al hover */
        .book-desc {
            font-size: 0.85rem;
            line-height: 1.4;
            color: #333;
            margin-bottom: 15px;
            
            display: -webkit-box;
            -webkit-line-clamp: 4; /* Max lineas cerrado */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-wrapper:hover .book-desc {
            display: block; /* Muestra todo */
            overflow: visible;
        }

        .status-badge {
            margin-top: 5px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .stars { color: #f59e0b; font-size: 1.2rem; }

        /* Botones */
        .action-buttons {
            margin-top: auto; /* Empuja al fondo */
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 10px;
            opacity: 0.5;
            transition: opacity 0.2s;
        }
        
        .card-wrapper:hover .action-buttons {
            opacity: 1;
        }

        .icon-btn {
            background: rgba(255,255,255,0.4);
            border-radius: 50%;
            padding: 5px;
            border: none;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon-btn:hover { background: rgba(255,255,255,0.8); }
        .icon-btn svg { width: 20px; height: 20px; stroke-width: 2; stroke: #000; fill: none; }

    </style>
</head>
<body class="bg-[#fffbef]">

    <div class="px-4">
        <div class="custom-header">
            <div class="flex items-center">
                <img src="assets/img/logo.png" alt="Logo" class="logo">
                <h1 class="text-2xl font-bold tracking-tight">Mi Biblioteca Personal</h1>
            </div>
            <a href="php/logout.php" class="text-red-400 font-semibold hover:text-red-600">Cerrar sesión</a>
        </div>
    </div>

    <div class="container mx-auto px-4 pb-12 max-w-6xl">
        
        <?php if (isset($_GET['msg'])): ?>
            <div class="mb-6 px-4 py-3 rounded-xl border shadow-sm text-center font-bold
                <?php 
                    if($_GET['msg']=='eliminado') echo 'bg-red-100 text-red-700 border-red-300';
                    else echo 'bg-green-100 text-green-700 border-green-300';
                ?>">
                <?php 
                    if($_GET['msg']=='eliminado') echo "Libro eliminado correctamente.";
                    if($_GET['msg']=='creado') echo "¡Libro añadido con éxito!";
                    if($_GET['msg']=='actualizado') echo "Información actualizada.";
                ?>
            </div>
        <?php endif; ?>

        <a href="nuevo_libro.php" class="btn-add shadow-md hover:shadow-lg">
            + Añadir libro
        </a>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 row-auto">
            
            <?php if (empty($libros)): ?>
                <div class="col-span-full text-center py-10 text-gray-500">
                    <p class="text-xl">Aún no tienes libros en tu colección.</p>
                </div>
            <?php else: ?>
                <?php foreach ($libros as $libro): 
                    $bgClass = 'card-deseado';
                    if (strtolower($libro['estado']) === 'leyendo') $bgClass = 'card-leyendo';
                    if (strtolower($libro['estado']) === 'leido')   $bgClass = 'card-leido';
                    
                    $portada = !empty($libro['portada']) ? $libro['portada'] : 'https://via.placeholder.com/120x180?text=No+Img';
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
                                Estado: <?php echo ucfirst($libro['estado']); ?>
                                
                                <?php if (strtolower($libro['estado']) === 'leyendo' && $libro['capitulo_actual']): ?>
                                    <span class="ml-2 text-sm bg-white/40 px-2 py-1 rounded">Cap: <?php echo $libro['capitulo_actual']; ?></span>
                                <?php endif; ?>

                                <?php if (strtolower($libro['estado']) === 'leido'): ?>
                                    <div class="stars">
                                        <?php 
                                        $val = $libro['valoracion'] ? $libro['valoracion'] : 0;
                                        for($i=0; $i<5; $i++) echo ($i < $val) ? '★' : '☆';
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="action-buttons">
                                <a href="nuevo_libro.php?edit=<?php echo $libro['id_libro']; ?>" class="icon-btn" title="Editar">
                                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                </a>
                                
                                <form method="POST" onsubmit="return confirm('¿Eliminar este libro de tu colección?');" style="display:inline;">
                                    <input type="hidden" name="delete_id" value="<?php echo $libro['id_libro']; ?>">
                                    <button type="submit" class="icon-btn" title="Eliminar">
                                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div> <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>
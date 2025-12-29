<?php
include "includes/config.php";

$usuario_actual_id = 1; // ID temporal

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- RECOGIDA DE DATOS ---
    $titulo = $_POST['titulo'];
    $autor_nombre = trim($_POST['autor']);
    $isbn = $_POST['isbn'];
    $estado = $_POST['estado'];
    $descripcion = $_POST['descripcion'];

    // Datos Paso 2
    $editorial_nombre = trim($_POST['editorial']);
    $categoria_nombre = trim($_POST['categoria']);
    // Convertir a null si están vacíos para evitar errores en bind_param con enteros
    $num_paginas = !empty($_POST['num_paginas']) ? $_POST['num_paginas'] : null;
    $idioma = $_POST['idioma'];
    $edicion = $_POST['edicion'];
    $anio = !empty($_POST['anio']) ? $_POST['anio'] : null;

    // Manejo de Imagen
    $ruta_portada = null;
    if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
        $nombre_archivo = time() . "_" . basename($_FILES['portada']['name']);
        $directorio_destino = "uploads/";
        if (!is_dir($directorio_destino)) { mkdir($directorio_destino, 0777, true); }
        if (move_uploaded_file($_FILES['portada']['tmp_name'], $directorio_destino . $nombre_archivo)) {
            $ruta_portada = $directorio_destino . $nombre_archivo;
        }
    }

    try {
        // Iniciar transacción (MySQLi)
        $conexion->begin_transaction();

        // 1. Autor
        $stmt = $conexion->prepare("SELECT id_autor FROM Autores WHERE nombre = ? LIMIT 1");
        $stmt->bind_param("s", $autor_nombre); // "s" indica string
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $id_autor = $row ? $row['id_autor'] : null;
        $stmt->close();

        if (!$id_autor) {
            $stmt = $conexion->prepare("INSERT INTO Autores (nombre) VALUES (?)");
            $stmt->bind_param("s", $autor_nombre);
            $stmt->execute();
            $id_autor = $conexion->insert_id; // ID del último insert
            $stmt->close();
        }

        // 2. Editorial
        $id_editorial = null;
        if (!empty($editorial_nombre)) {
            $stmt = $conexion->prepare("SELECT id_editorial FROM Editoriales WHERE nombre_editorial = ? LIMIT 1");
            $stmt->bind_param("s", $editorial_nombre);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $id_editorial = $row ? $row['id_editorial'] : null;
            $stmt->close();

            if (!$id_editorial) {
                $stmt = $conexion->prepare("INSERT INTO Editoriales (nombre_editorial) VALUES (?)");
                $stmt->bind_param("s", $editorial_nombre);
                $stmt->execute();
                $id_editorial = $conexion->insert_id;
                $stmt->close();
            }
        }

        // 3. Categoría
        $id_categoria = null;
        if (!empty($categoria_nombre)) {
            $stmt = $conexion->prepare("SELECT id_categoria FROM Categorias WHERE nombre_categoria = ? LIMIT 1");
            $stmt->bind_param("s", $categoria_nombre);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $id_categoria = $row ? $row['id_categoria'] : null;
            $stmt->close();

            if (!$id_categoria) {
                $stmt = $conexion->prepare("INSERT INTO Categorias (nombre_categoria) VALUES (?)");
                $stmt->bind_param("s", $categoria_nombre);
                $stmt->execute();
                $id_categoria = $conexion->insert_id;
                $stmt->close();
            }
        }

        // 4. Libro
        $stmt = $conexion->prepare("SELECT id_libro FROM Libros WHERE isbn = ? LIMIT 1");
        $stmt->bind_param("s", $isbn);
        $stmt->execute();
        $result = $stmt->get_result();
        $libro_row = $result->fetch_assoc();
        $stmt->close();
        
        if ($libro_row) {
            $id_libro = $libro_row['id_libro'];
        } else {
            $sql_libro = "INSERT INTO Libros (titulo, isbn, id_autor, id_editorial, id_categoria, num_paginas, idioma, edicion, año_publicacion, descripcion, portada) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql_libro);
            // Tipos: s=string, i=integer. Orden: titulo(s), isbn(s), autor(i), editorial(i), cat(i), paginas(i), idioma(s), edicion(s), anio(i), desc(s), portada(s)
            $stmt->bind_param("ssiiisiisss", 
                $titulo, 
                $isbn, 
                $id_autor, 
                $id_editorial, 
                $id_categoria, 
                $num_paginas, 
                $idioma, 
                $edicion, 
                $anio, 
                $descripcion, 
                $ruta_portada
            );
            $stmt->execute();
            $id_libro = $conexion->insert_id;
            $stmt->close();
        }

        // 5. Colección
        $stmt = $conexion->prepare("SELECT id_coleccion FROM Coleccion WHERE id_usuario = ? AND id_libro = ?");
        $stmt->bind_param("ii", $usuario_actual_id, $id_libro);
        $stmt->execute();
        $result = $stmt->get_result();
        $existe_coleccion = $result->num_rows > 0;
        $stmt->close();

        if (!$existe_coleccion) {
            $estado_cap = ucfirst($estado);
            $sql_col = "INSERT INTO Coleccion (id_usuario, id_libro, estado, fecha_agregado) VALUES (?, ?, ?, NOW())";
            $stmt = $conexion->prepare($sql_col);
            $stmt->bind_param("iis", $usuario_actual_id, $id_libro, $estado_cap);
            $stmt->execute();
            $stmt->close();

            $conexion->commit(); // Confirmar cambios
            echo "<script>alert('¡Libro guardado con éxito!'); window.location.href='nuevo_libro.php';</script>";
        } else {
            $conexion->rollback(); // Deshacer cambios
            echo "<script>alert('El libro ya está en tu colección');</script>";
        }

    } catch (Exception $e) {
        $conexion->rollback();
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Libro - Identica</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f0f0;
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; margin: 0; padding: 20px;
        }

        .book-card {
            background-color: #d7ccc8; border: 2px solid #000; border-radius: 24px;
            padding: 32px; display: flex; flex-direction: row; gap: 32px;
            width: 100%; max-width: 850px; box-shadow: 8px 8px 0px rgba(0, 0, 0, 0.15);
            position: relative; overflow: hidden;
        }

        /* Sección Imagen (Siempre visible) */
        .book-image-section { flex: 0 0 240px; display: flex; flex-direction: column; gap: 12px; }
        .image-preview-container {
            width: 100%; aspect-ratio: 3/4.2; background-color: #e3f2fd; border: 2px solid #000;
            border-radius: 12px; overflow: hidden; display: flex; align-items: center; justify-content: center;
            position: relative; cursor: pointer; transition: background-color 0.2s;
        }
        .image-preview-container:hover { background-color: #bbdefb; }
        .plus-icon { font-size: 60px; color: #000; font-weight: 300; pointer-events: none; }
        .book-image-label { font-size: 0.8rem; font-weight: 700; color: #000; text-transform: uppercase; }

        /* Contenedor de Pasos */
        .book-details-section { flex: 1; display: flex; flex-direction: column; position: relative; }
        
        /* Efecto de transición simple */
        .step-container {
            display: flex; flex-direction: column; gap: 10px;
            width: 100%; transition: opacity 0.3s ease;
        }
        .hidden-step { display: none !important; }

        /* --- Estilos Paso 1 --- */
        .custom-dropdown { position: relative; width: 180px; align-self: flex-end; margin-bottom: 5px; }
        .dropdown-trigger {
            width: 100%; padding: 8px 16px; border-radius: 25px; border: 2px solid #000;
            background-color: #fff; font-size: 0.9rem; font-weight: 700; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center;
        }
        .dropdown-menu {
            position: absolute; top: 110%; right: 0; width: 100%; background: #fff;
            border: 2px solid #000; border-radius: 18px; padding: 8px; display: none;
            flex-direction: column; gap: 6px; z-index: 50; box-shadow: 4px 4px 0px rgba(0,0,0,0.1);
        }
        .dropdown-menu.show { display: flex; }
        .dropdown-item {
            padding: 8px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700;
            cursor: pointer; border: 1.5px solid transparent; text-align: center; transition: transform 0.1s;
        }
        .dropdown-item:hover { transform: scale(1.03); border-color: #000; }
        .opt-deseado { background-color: #fff176; color: #000; }
        .opt-leyendo { background-color: #f8bbd0; color: #000; }
        .opt-terminado { background-color: #e1bee7; color: #000; }

        .input-title { background: transparent; border: none; font-size: 2rem; font-weight: 800; color: #000; outline: none; width: 100%; }
        .input-author { background: transparent; border: none; font-size: 1.3rem; font-weight: 600; color: #444; outline: none; width: 100%; margin-bottom: 5px;}
        .input-isbn { background: transparent; border: none; font-size: 0.95rem; font-weight: 500; color: #666; outline: none; width: 100%; margin-bottom: 10px; }
        .description-area {
            width: 100%; min-height: 120px; background: transparent; border: none;
            background-image: repeating-linear-gradient(transparent, transparent 29px, #9e9e9e 29px, #9e9e9e 30px, transparent 30px);
            line-height: 30px; font-size: 1.1rem; resize: none; outline: none; padding: 0;
        }

        /* --- Estilos Paso 2 --- */
        .step-title { font-size: 0.9rem; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 5px; }
        
        /* Botones de Categoría */
        .category-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 15px;
        }
        .cat-btn {
            padding: 8px; border: 1px solid #8d6e63; border-radius: 12px;
            background: rgba(255,255,255,0.5); text-align: center; font-size: 0.8rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s; user-select: none;
        }
        .cat-btn:hover { background: #fff; border-color: #000; }
        .cat-btn.selected { background: #000; color: #fff; border-color: #000; transform: scale(1.05); }

        /* Inputs Estilizados */
        .input-group { margin-bottom: 10px; }
        .label-mini { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: #555; display: block; margin-bottom: 2px;}
        .input-styled {
            width: 100%; background: #fff; border: 2px solid #000; border-radius: 12px;
            padding: 8px 12px; font-size: 0.9rem; font-weight: 600; outline: none;
        }
        .input-styled:focus { box-shadow: 2px 2px 0px rgba(0,0,0,0.2); }

        /* Botones Acción */
        .btn-container { display: flex; justify-content: flex-end; gap: 12px; margin-top: auto; padding-top: 20px;}
        .btn {
            padding: 10px 24px; border-radius: 30px; font-weight: 800; text-transform: uppercase;
            border: 2px dashed #000; box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.1); transition: all 0.2s; cursor: pointer;
        }
        .btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px rgba(0, 0, 0, 0.1); }
        .btn-cancel { background-color: #ef9a9a; }
        .btn-next { background-color: #90caf9; border-style: solid; } /* Azul para siguiente */
        .btn-save { background-color: #a5d6a7; border-style: solid; }
        .btn-back { background-color: #e0e0e0; font-size: 0.8rem; padding: 10px 18px; }

        @media (max-width: 768px) {
            .book-card { flex-direction: column; }
            .book-image-section { margin: 0 auto; }
            .category-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

    <form action="nuevo_libro.php" method="POST" enctype="multipart/form-data" id="mainForm">
        <div class="book-card">
            
            <div class="book-image-section">
                <label class="book-image-label">Portada libro:</label>
                <div class="image-preview-container" onclick="document.getElementById('portada').click()">
                    <span class="plus-icon" id="plus-icon">+</span>
                </div>
                <input type="file" name="portada" id="portada" accept="image/*" style="display: none;">
                
                <div class="flex gap-2 justify-center mt-4">
                    <div id="dot1" class="w-3 h-3 rounded-full bg-black"></div>
                    <div id="dot2" class="w-3 h-3 rounded-full bg-gray-400 border border-black"></div>
                </div>
            </div>

            <div class="book-details-section">
                
                <div id="step1" class="step-container">
                    <div class="flex justify-end">
                        <div class="custom-dropdown" id="statusDropdown">
                            <div class="dropdown-trigger" id="dropdownTrigger">
                                <span id="selectedLabel">Seleccione estado</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                            <div class="dropdown-menu" id="dropdownMenu">
                                <div class="dropdown-item opt-deseado" data-value="deseado">DESEADO</div>
                                <div class="dropdown-item opt-leyendo" data-value="leyendo">LEYENDO</div>
                                <div class="dropdown-item opt-terminado" data-value="terminado">TERMINADO</div>
                            </div>
                            <input type="hidden" name="estado" id="estadoInput" required>
                        </div>
                    </div>

                    <input type="text" name="titulo" id="titulo" class="input-title" placeholder="Título del libro" required>
                    <input type="text" name="autor" id="autor" class="input-author" placeholder="Autor/a" required>
                    <input type="text" name="isbn" class="input-isbn" placeholder="ISBN (ej. 978-84-376-0494-7)" required>
                    
                    <label class="text-[0.8rem] font-bold uppercase mt-2">Descripción / Notas:</label>
                    <textarea name="descripcion" class="description-area"></textarea>

                    <div class="btn-container">
                        <button type="button" class="btn btn-cancel" onclick="window.history.back()">CANCELAR</button>
                        <button type="button" class="btn btn-next" onclick="goToStep2()">SIGUIENTE &rarr;</button>
                    </div>
                </div>

                <div id="step2" class="step-container hidden-step">
                    
                    <div class="step-title">Categoría</div>
                    <div class="category-grid" id="catGrid">
                        <div class="cat-btn" onclick="selectCat(this, 'Ficción')">Ficción</div>
                        <div class="cat-btn" onclick="selectCat(this, 'No Ficción')">No Ficción</div>
                        <div class="cat-btn" onclick="selectCat(this, 'Fantasía')">Fantasía</div>
                        <div class="cat-btn" onclick="selectCat(this, 'Ciencia Ficción')">Sci-Fi</div>
                        <div class="cat-btn" onclick="selectCat(this, 'Romance')">Romance</div>
                        <div class="cat-btn" onclick="selectCat(this, 'Misterio')">Misterio</div>
                    </div>
                    <input type="hidden" name="categoria" id="inputCategoria">

                    <div class="step-title">Ficha Técnica</div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="input-group">
                            <label class="label-mini">Editorial</label>
                            <input type="text" name="editorial" class="input-styled" placeholder="Ej. Planeta">
                        </div>
                        <div class="input-group">
                            <label class="label-mini">Idioma</label>
                            <select name="idioma" class="input-styled bg-white h-[42px]">
                                <option value="Español">Español</option>
                                <option value="Inglés">Inglés</option>
                                <option value="Francés">Francés</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label class="label-mini">Nº Páginas</label>
                            <input type="number" name="num_paginas" class="input-styled" placeholder="0">
                        </div>
                        <div class="input-group">
                            <label class="label-mini">Año Pub.</label>
                            <input type="number" name="anio" class="input-styled" placeholder="2024">
                        </div>
                         <div class="input-group col-span-2">
                            <label class="label-mini">Edición</label>
                            <input type="text" name="edicion" class="input-styled" placeholder="Ej. Edición Coleccionista">
                        </div>
                    </div>

                    <div class="btn-container">
                        <button type="button" class="btn btn-back" onclick="goToStep1()">&larr; ATRÁS</button>
                        <button type="submit" class="btn btn-save">GUARDAR TODO</button>
                    </div>
                </div>

            </div>
        </div>
    </form>

    <script>
        // --- LÓGICA DE PASOS ---
        function goToStep2() {
            // Validación simple antes de pasar
            const titulo = document.getElementById('titulo').value;
            const estado = document.getElementById('estadoInput').value;
            
            if(!titulo || !estado) {
                alert("Por favor, rellena el título y selecciona un estado.");
                return;
            }

            document.getElementById('step1').classList.add('hidden-step');
            document.getElementById('step2').classList.remove('hidden-step');
            
            // Actualizar bolitas de progreso
            document.getElementById('dot1').classList.remove('bg-black');
            document.getElementById('dot1').classList.add('bg-gray-400', 'border', 'border-black');
            document.getElementById('dot2').classList.remove('bg-gray-400', 'border', 'border-black');
            document.getElementById('dot2').classList.add('bg-black');
        }

        function goToStep1() {
            document.getElementById('step2').classList.add('hidden-step');
            document.getElementById('step1').classList.remove('hidden-step');
            
            // Restaurar bolitas
            document.getElementById('dot2').classList.remove('bg-black');
            document.getElementById('dot2').classList.add('bg-gray-400', 'border', 'border-black');
            document.getElementById('dot1').classList.remove('bg-gray-400', 'border', 'border-black');
            document.getElementById('dot1').classList.add('bg-black');
        }

        // --- LÓGICA CATEGORÍAS (BOTONES) ---
        function selectCat(btn, valor) {
            // Quitar clase 'selected' a todos
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('selected'));
            // Poner al actual
            btn.classList.add('selected');
            // Guardar valor en input oculto
            document.getElementById('inputCategoria').value = valor;
        }

        // --- LÓGICA DROPDOWN (ESTADO) ---
        const trigger = document.getElementById('dropdownTrigger');
        const menu = document.getElementById('dropdownMenu');
        const items = document.querySelectorAll('.dropdown-item');
        const selectedLabel = document.getElementById('selectedLabel');
        const estadoInput = document.getElementById('estadoInput');

        trigger.addEventListener('click', () => menu.classList.toggle('show'));

        items.forEach(item => {
            item.addEventListener('click', () => {
                const val = item.getAttribute('data-value');
                const text = item.innerText;
                const bgColor = window.getComputedStyle(item).backgroundColor;
                selectedLabel.innerText = text;
                trigger.style.backgroundColor = bgColor;
                estadoInput.value = val;
                menu.classList.remove('show');
            });
        });
        window.addEventListener('click', (e) => {
            if (!document.getElementById('statusDropdown').contains(e.target)) menu.classList.remove('show');
        });

        // --- PREVISUALIZACIÓN IMAGEN ---
        document.getElementById('portada').addEventListener('change', function(e) {
            const container = document.querySelector('.image-preview-container');
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    container.innerHTML = `<img src="${event.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
<?php
// ==========================================
// PARTE 1: LÓGICA PHP (BACKEND)
// ==========================================
include "includes/config.php";

// Reportar errores para depuración
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$usuario_actual_id = 1; 
$libro_editar = null;
$es_edicion = false;

// --- A. MODO EDICIÓN: CARGAR DATOS SI HAY ?edit=ID ---
if (isset($_GET['edit'])) {
    $id_edit = intval($_GET['edit']);
    
    // Consulta para rellenar el formulario
    $sql_edit = "SELECT L.*, 
                        A.nombre AS autor_nombre, 
                        E.nombre_editorial, 
                        Cat.nombre_categoria, 
                        Col.estado, Col.capitulo_actual, Col.valoracion
                 FROM Libros L
                 JOIN Coleccion Col ON L.id_libro = Col.id_libro
                 LEFT JOIN Autores A ON L.id_autor = A.id_autor
                 LEFT JOIN Editoriales E ON L.id_editorial = E.id_editorial
                 LEFT JOIN Categorias Cat ON L.id_categoria = Cat.id_categoria
                 WHERE L.id_libro = ? AND Col.id_usuario = ?";
    
    $stmt = $conexion->prepare($sql_edit);
    $stmt->bind_param("ii", $id_edit, $usuario_actual_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($fila = $res->fetch_assoc()) {
        $libro_editar = $fila;
        $es_edicion = true;
    }
    $stmt->close();
}

// --- B. GUARDAR DATOS (CUANDO PULSAS EL BOTÓN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conexion->begin_transaction();

        // 1. Recoger datos
        $titulo = $_POST['titulo'];
        $autor_nombre = trim($_POST['autor']);
        $isbn = $_POST['isbn'];
        $descripcion = $_POST['descripcion'];
        $editorial_nombre = trim($_POST['editorial']);
        $categoria_nombre = trim($_POST['categoria']);
        $idioma = $_POST['idioma'];
        
        $num_paginas = !empty($_POST['num_paginas']) ? intval($_POST['num_paginas']) : 0;
        $anio = !empty($_POST['anio']) ? intval($_POST['anio']) : 0;
        $precio = !empty($_POST['precio']) ? floatval($_POST['precio']) : 0.00;
        $capitulo_actual = !empty($_POST['capitulo_actual']) ? intval($_POST['capitulo_actual']) : 0;
        $valoracion = !empty($_POST['valoracion']) ? intval($_POST['valoracion']) : 0;
        $edicion = $_POST['edicion'];

        // CORRECCIÓN DE ESTADO (Mapeo exacto para tu BD)
        $estado_raw = $_POST['estado']; 
        $estado_db = 'Deseado'; // Default
        if ($estado_raw === 'leyendo') $estado_db = 'Leyendo';
        if ($estado_raw === 'leido')   $estado_db = 'Leido'; // Exacto como en tu BD

        // ¿Es edición o nuevo?
        $id_libro_editar = isset($_POST['id_libro_editar']) ? intval($_POST['id_libro_editar']) : null;
        $modo_edicion = ($id_libro_editar && $id_libro_editar > 0);

        // 2. Imagen
        $ruta_portada = ($modo_edicion && isset($_POST['portada_actual'])) ? $_POST['portada_actual'] : null;
        if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
            $nombre_archivo = time() . "_" . basename($_FILES['portada']['name']);
            $directorio_destino = "uploads/";
            if (!is_dir($directorio_destino)) { mkdir($directorio_destino, 0777, true); }
            if (move_uploaded_file($_FILES['portada']['tmp_name'], $directorio_destino . $nombre_archivo)) {
                $ruta_portada = $directorio_destino . $nombre_archivo;
            }
        }

        // 3. Relaciones (Autor/Editorial/Categoria)
        // (Lógica simplificada para ahorrar espacio, pero funcional)
        function getIdByName($con, $table, $col_id, $col_name, $val) {
            if(empty($val)) return null;
            $stmt = $con->prepare("SELECT $col_id FROM $table WHERE $col_name = ? LIMIT 1");
            $stmt->bind_param("s", $val); $stmt->execute();
            if($row = $stmt->get_result()->fetch_assoc()) return $row[$col_id];
            
            $stmt = $con->prepare("INSERT INTO $table ($col_name) VALUES (?)");
            $stmt->bind_param("s", $val); $stmt->execute();
            return $con->insert_id;
        }

        $id_autor = getIdByName($conexion, 'Autores', 'id_autor', 'nombre', $autor_nombre);
        $id_editorial = getIdByName($conexion, 'Editoriales', 'id_editorial', 'nombre_editorial', $editorial_nombre);
        $id_categoria = getIdByName($conexion, 'Categorias', 'id_categoria', 'nombre_categoria', $categoria_nombre);

        // 4. Guardar (Update o Insert)
        if ($modo_edicion) {
            // UPDATE
            $sql = "UPDATE Libros SET titulo=?, isbn=?, id_autor=?, id_editorial=?, id_categoria=?, num_paginas=?, idioma=?, edicion=?, año_publicacion=?, descripcion=?, portada=?, precio=? WHERE id_libro=?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssiiiississdi", $titulo, $isbn, $id_autor, $id_editorial, $id_categoria, $num_paginas, $idioma, $edicion, $anio, $descripcion, $ruta_portada, $precio, $id_libro_editar);
            $stmt->execute(); $stmt->close();

            $sql = "UPDATE Coleccion SET estado=?, capitulo_actual=?, valoracion=? WHERE id_libro=? AND id_usuario=?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("siiii", $estado_db, $capitulo_actual, $valoracion, $id_libro_editar, $usuario_actual_id);
            $stmt->execute(); $stmt->close();
            
            $msg = "¡Libro actualizado correctamente!";
        } else {
            // INSERT
            // Verificar duplicado ISBN
            $stmt = $conexion->prepare("SELECT id_libro FROM Libros WHERE isbn = ? LIMIT 1");
            $stmt->bind_param("s", $isbn); $stmt->execute();
            $exist = $stmt->get_result()->fetch_assoc();
            
            if($exist) {
                $id_libro = $exist['id_libro'];
            } else {
                $sql = "INSERT INTO Libros (titulo, isbn, id_autor, id_editorial, id_categoria, num_paginas, idioma, edicion, año_publicacion, descripcion, portada, precio) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ssiiiississd", $titulo, $isbn, $id_autor, $id_editorial, $id_categoria, $num_paginas, $idioma, $edicion, $anio, $descripcion, $ruta_portada, $precio);
                $stmt->execute(); 
                $id_libro = $conexion->insert_id;
            }

            // Verificar Coleccion
            $stmt = $conexion->prepare("SELECT id_coleccion FROM Coleccion WHERE id_usuario=? AND id_libro=?");
            $stmt->bind_param("ii", $usuario_actual_id, $id_libro); $stmt->execute();
            if($stmt->get_result()->num_rows > 0) throw new Exception("Ya tienes este libro.");

            $sql = "INSERT INTO Coleccion (id_usuario, id_libro, estado, capitulo_actual, valoracion, fecha_agregado) VALUES (?,?,?,?,?,NOW())";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("iisii", $usuario_actual_id, $id_libro, $estado_db, $capitulo_actual, $valoracion);
            $stmt->execute();
            
            $msg = "¡Libro guardado correctamente!";
        }

        $conexion->commit();
        // Redirigir al feed o mostrar éxito
        echo "<script>alert('$msg'); window.location.href='feed.php';</script>";

    } catch (Exception $e) {
        $conexion->rollback();
        $error_msg = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $es_edicion ? 'Editar Libro' : 'Nuevo Libro'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <style>
        /* Estilos CSS originales */
        body { font-family: 'Inter', sans-serif; background-color: #f0f0f0; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .book-card { background-color: #d7ccc8; border: 2px solid #000; border-radius: 24px; padding: 32px; display: flex; flex-direction: row; gap: 32px; width: 100%; max-width: 850px; box-shadow: 8px 8px 0px rgba(0, 0, 0, 0.15); position: relative; overflow: hidden; }
        .book-image-section { flex: 0 0 240px; display: flex; flex-direction: column; gap: 12px; }
        .image-preview-container { width: 100%; aspect-ratio: 3/4.2; background-color: #e3f2fd; border: 2px solid #000; border-radius: 12px; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative; cursor: pointer; transition: background-color 0.2s; }
        .image-preview-container:hover { background-color: #bbdefb; }
        .plus-icon { font-size: 60px; color: #000; font-weight: 300; pointer-events: none; }
        .book-image-label { font-size: 0.8rem; font-weight: 700; color: #000; text-transform: uppercase; }
        .book-details-section { flex: 1; display: flex; flex-direction: column; position: relative; }
        .step-container { display: flex; flex-direction: column; gap: 10px; width: 100%; transition: opacity 0.3s ease; }
        .hidden-step { display: none !important; }
        .custom-dropdown { position: relative; width: 180px; align-self: flex-end; margin-bottom: 5px; }
        .dropdown-trigger { width: 100%; padding: 8px 16px; border-radius: 25px; border: 2px solid #000; background-color: #fff; font-size: 0.9rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .dropdown-menu { position: absolute; top: 110%; right: 0; width: 100%; background: #fff; border: 2px solid #000; border-radius: 18px; padding: 8px; display: none; flex-direction: column; gap: 6px; z-index: 50; box-shadow: 4px 4px 0px rgba(0,0,0,0.1); }
        .dropdown-menu.show { display: flex; }
        .dropdown-item { padding: 8px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; cursor: pointer; border: 1.5px solid transparent; text-align: center; transition: transform 0.1s; }
        .dropdown-item:hover { transform: scale(1.03); border-color: #000; }
        .opt-deseado { background-color: #fff176; color: #000; }
        .opt-leyendo { background-color: #f8bbd0; color: #000; }
        .opt-terminado { background-color: #e1bee7; color: #000; }
        .hidden { display: none !important; }
        .dynamic-group { background: #fff; padding: 10px 15px; border-radius: 15px; border: 2px solid #000; margin-top: 10px; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
        .star-rating-container { display: flex; gap: 5px; cursor: pointer; }
        .star-icon { color: #ccc; transition: color 0.2s, fill 0.2s; }
        .star-icon.filled { fill: #FFD700; color: #e6c200; }
        .input-title { background: transparent; border: none; font-size: 2rem; font-weight: 800; color: #000; outline: none; width: 100%; }
        .input-author { background: transparent; border: none; font-size: 1.3rem; font-weight: 600; color: #444; outline: none; width: 100%; margin-bottom: 5px;}
        .input-isbn { background: transparent; border: none; font-size: 0.95rem; font-weight: 500; color: #666; outline: none; width: 100%; margin-bottom: 10px; }
        .description-area { width: 100%; min-height: 120px; background: transparent; border: none; background-image: repeating-linear-gradient(transparent, transparent 29px, #9e9e9e 29px, #9e9e9e 30px, transparent 30px); line-height: 30px; font-size: 1.1rem; resize: none; outline: none; padding: 0; }
        .step-title { font-size: 0.9rem; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 5px; }
        .category-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 15px; }
        .cat-btn { padding: 8px; border: 1px solid #8d6e63; border-radius: 12px; background: rgba(255,255,255,0.5); text-align: center; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.2s; user-select: none; }
        .cat-btn:hover { background: #fff; border-color: #000; }
        .cat-btn.selected { background: #000; color: #fff; border-color: #000; transform: scale(1.05); }
        .input-group { margin-bottom: 10px; }
        .label-mini { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: #555; display: block; margin-bottom: 2px;}
        .input-styled { width: 100%; background: #fff; border: 2px solid #000; border-radius: 12px; padding: 8px 12px; font-size: 0.9rem; font-weight: 600; outline: none; }
        .input-styled:focus { box-shadow: 2px 2px 0px rgba(0,0,0,0.2); }
        .btn-container { display: flex; justify-content: flex-end; gap: 12px; margin-top: auto; padding-top: 20px;}
        .btn { padding: 10px 24px; border-radius: 30px; font-weight: 800; text-transform: uppercase; border: 2px dashed #000; box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.1); transition: all 0.2s; cursor: pointer; }
        .btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px rgba(0, 0, 0, 0.1); }
        .btn-cancel { background-color: #ef9a9a; }
        .btn-next { background-color: #90caf9; border-style: solid; }
        .btn-save { background-color: #a5d6a7; border-style: solid; }
        .btn-back { background-color: #e0e0e0; font-size: 0.8rem; padding: 10px 18px; }
        @media (max-width: 768px) { .book-card { flex-direction: column; } .book-image-section { margin: 0 auto; } .category-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>

    <form action="nuevo_libro.php" method="POST" enctype="multipart/form-data" id="mainForm">
        
        <?php if ($es_edicion): ?>
            <input type="hidden" name="id_libro_editar" value="<?php echo $libro_editar['id_libro']; ?>">
            <input type="hidden" name="portada_actual" value="<?php echo htmlspecialchars($libro_editar['portada'] ?? ''); ?>">
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div style="background:red; color:white; padding:10px; border-radius:10px; margin-bottom:10px;">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <div class="book-card">
            
            <div class="book-image-section">
                <label class="book-image-label">Portada libro:</label>
                <div class="image-preview-container" onclick="document.getElementById('portada').click()">
                    <?php if($es_edicion && !empty($libro_editar['portada'])): ?>
                        <img src="<?php echo htmlspecialchars($libro_editar['portada']); ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <span class="plus-icon" id="plus-icon">+</span>
                    <?php endif; ?>
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
                                <div class="dropdown-item opt-terminado" data-value="leido">LEÍDO</div>
                            </div>
                            <input type="hidden" name="estado" id="estadoInput" required 
                                   value="<?php echo $es_edicion ? strtolower($libro_editar['estado']) : ''; ?>">
                        </div>
                    </div>

                    <div id="dynamic-fields-container" class="mb-4 w-full">
                        <div id="field-deseado" class="dynamic-group hidden">
                            <label class="label-mini">Precio estimado (€)</label>
                            <input type="number" step="0.01" name="precio" id="inputPrecio" class="input-styled" placeholder="0.00" 
                                   value="<?php echo $es_edicion && isset($libro_editar['precio']) ? $libro_editar['precio'] : ''; ?>">
                        </div>
                        <div id="field-leyendo" class="dynamic-group hidden">
                            <label class="label-mini">Capítulo actual</label>
                            <input type="number" name="capitulo_actual" id="inputCapitulo" class="input-styled" placeholder="Ej. 4" 
                                   value="<?php echo $es_edicion ? $libro_editar['capitulo_actual'] : ''; ?>">
                        </div>
                        <div id="field-leido" class="dynamic-group hidden">
                            <label class="label-mini">Valoración</label>
                            <div class="star-rating-container">
                                <input type="hidden" name="valoracion" id="inputValoracion" 
                                       value="<?php echo $es_edicion ? $libro_editar['valoracion'] : '0'; ?>">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <svg onclick="setRating(<?php echo $i; ?>)" onmouseover="hoverRating(<?php echo $i; ?>)" onmouseout="resetRating()" class="star-icon" id="star-<?php echo $i; ?>" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>

                    <input type="text" name="titulo" id="titulo" class="input-title" placeholder="Título del libro" required
                           value="<?php echo $es_edicion ? htmlspecialchars($libro_editar['titulo']) : ''; ?>">
                    
                    <input type="text" name="autor" id="autor" class="input-author" placeholder="Autor/a" required
                           value="<?php echo $es_edicion ? htmlspecialchars($libro_editar['autor_nombre']) : ''; ?>">
                    
                    <input type="text" name="isbn" class="input-isbn" placeholder="ISBN (ej. 978-84-376-0494-7)" required
                           value="<?php echo $es_edicion ? htmlspecialchars($libro_editar['isbn']) : ''; ?>">
                    
                    <label class="text-[0.8rem] font-bold uppercase mt-2">Descripción / Notas:</label>
                    <textarea name="descripcion" class="description-area"><?php echo $es_edicion ? htmlspecialchars($libro_editar['descripcion']) : ''; ?></textarea>

                    <div class="btn-container">
                        <button type="button" class="btn btn-cancel" onclick="window.location.href='feed.php'">CANCELAR</button>
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
                    <input type="hidden" name="categoria" id="inputCategoria"
                           value="<?php echo $es_edicion ? htmlspecialchars($libro_editar['nombre_categoria']) : ''; ?>">

                    <div class="step-title">Ficha Técnica</div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="input-group">
                            <label class="label-mini">Editorial</label>
                            <input type="text" name="editorial" class="input-styled" placeholder="Ej. Planeta"
                                   value="<?php echo $es_edicion ? htmlspecialchars($libro_editar['nombre_editorial']) : ''; ?>">
                        </div>
                        <div class="input-group">
                            <label class="label-mini">Idioma</label>
                            <select name="idioma" class="input-styled bg-white h-[42px]">
                                <?php 
                                    $idioma_sel = $es_edicion ? $libro_editar['idioma'] : 'Español';
                                    $opciones = ['Español', 'Inglés', 'Francés', 'Otro'];
                                    foreach($opciones as $op) {
                                        $selected = ($idioma_sel === $op) ? 'selected' : '';
                                        echo "<option value='$op' $selected>$op</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label class="label-mini">Nº Páginas</label>
                            <input type="number" name="num_paginas" class="input-styled" placeholder="0"
                                   value="<?php echo $es_edicion ? htmlspecialchars($libro_editar['num_paginas']) : ''; ?>">
                        </div>
                        <div class="input-group">
                            <label class="label-mini">Año Pub.</label>
                            <input type="number" name="anio" class="input-styled" placeholder="2024"
                                   value="<?php echo $es_edicion ? htmlspecialchars($libro_editar['año_publicacion']) : ''; ?>">
                        </div>
                         <div class="input-group col-span-2">
                            <label class="label-mini">Edición</label>
                            <input type="text" name="edicion" class="input-styled" placeholder="Ej. Edición Coleccionista"
                                   value="<?php echo $es_edicion ? htmlspecialchars($libro_editar['edicion']) : ''; ?>">
                        </div>
                    </div>

                    <div class="btn-container">
                        <button type="button" class="btn btn-back" onclick="goToStep1()">&larr; ATRÁS</button>
                        <button type="submit" class="btn btn-save"><?php echo $es_edicion ? 'ACTUALIZAR' : 'GUARDAR TODO'; ?></button>
                    </div>
                </div>

            </div>
        </div>
    </form>

    <script>
        // --- LÓGICA DE PASOS ---
        function goToStep2() {
            const titulo = document.getElementById('titulo').value;
            const estado = document.getElementById('estadoInput').value;
            if(!titulo || !estado) {
                alert("Por favor, rellena el título y selecciona un estado.");
                return;
            }
            document.getElementById('step1').classList.add('hidden-step');
            document.getElementById('step2').classList.remove('hidden-step');
            document.getElementById('dot1').classList.remove('bg-black');
            document.getElementById('dot1').classList.add('bg-gray-400', 'border', 'border-black');
            document.getElementById('dot2').classList.remove('bg-gray-400', 'border', 'border-black');
            document.getElementById('dot2').classList.add('bg-black');
        }

        function goToStep1() {
            document.getElementById('step2').classList.add('hidden-step');
            document.getElementById('step1').classList.remove('hidden-step');
            document.getElementById('dot2').classList.remove('bg-black');
            document.getElementById('dot2').classList.add('bg-gray-400', 'border', 'border-black');
            document.getElementById('dot1').classList.remove('bg-gray-400', 'border', 'border-black');
            document.getElementById('dot1').classList.add('bg-black');
        }

        // --- LÓGICA CATEGORÍAS ---
        function selectCat(btn, valor) {
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            document.getElementById('inputCategoria').value = valor;
        }

        // --- LÓGICA DROPDOWN Y ESTADOS ---
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
                updateDynamicFields(val);
            });
        });
        window.addEventListener('click', (e) => {
            if (!document.getElementById('statusDropdown').contains(e.target)) menu.classList.remove('show');
        });

        function updateDynamicFields(estado) {
            document.getElementById('field-deseado').classList.add('hidden');
            document.getElementById('field-leyendo').classList.add('hidden');
            document.getElementById('field-leido').classList.add('hidden');
            if (estado === 'deseado') {
                document.getElementById('field-deseado').classList.remove('hidden');
            } else if (estado === 'leyendo') {
                document.getElementById('field-leyendo').classList.remove('hidden');
            } else if (estado === 'leido') { 
                document.getElementById('field-leido').classList.remove('hidden');
                resetRating(); 
            }
        }

        // --- LÓGICA ESTRELLAS ---
        const ratingInput = document.getElementById('inputValoracion');
        function setRating(val) {
            ratingInput.value = val;
            resetRating();
        }
        function hoverRating(val) {
            for (let i = 1; i <= 5; i++) {
                const star = document.getElementById(`star-${i}`);
                if (i <= val) star.classList.add('filled');
                else star.classList.remove('filled');
            }
        }
        function resetRating() {
            const currentVal = parseInt(ratingInput.value) || 0;
            for (let i = 1; i <= 5; i++) {
                const star = document.getElementById(`star-${i}`);
                if (i <= currentVal) star.classList.add('filled');
                else star.classList.remove('filled');
            }
        }

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

        // --- INICIALIZACIÓN (SI ES EDICIÓN) ---
        window.addEventListener('DOMContentLoaded', () => {
            <?php if ($es_edicion): ?>
                // Recuperar estado
                const savedState = "<?php echo strtolower($libro_editar['estado']); ?>";
                // Mapear el estado guardado a las opciones del dropdown
                let mappedState = savedState;
                // Si la BD tiene 'Leido' (mayúscula) o 'Terminado', lo convertimos a lo que espera el HTML ('leido')
                if(savedState === 'terminado') mappedState = 'leido'; 
                if(savedState === 'leido') mappedState = 'leido'; 

                const stateItem = document.querySelector(`.dropdown-item[data-value="${mappedState}"]`);
                if (stateItem) {
                    const text = stateItem.innerText;
                    const bgColor = window.getComputedStyle(stateItem).backgroundColor;
                    selectedLabel.innerText = text;
                    trigger.style.backgroundColor = bgColor;
                    estadoInput.value = mappedState;
                    updateDynamicFields(mappedState);
                }

                // Recuperar categoría
                const savedCat = "<?php echo $libro_editar['nombre_categoria']; ?>";
                if(savedCat) {
                    const catBtn = Array.from(document.querySelectorAll('.cat-btn')).find(b => b.innerText.trim() === savedCat);
                    if(catBtn) selectCat(catBtn, savedCat);
                }

                // Estrellas (si aplica)
                resetRating();
            <?php endif; ?>
        });
    </script>
</body>
</html>
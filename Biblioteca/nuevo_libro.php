<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Libro - Identica</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .book-card {
            background-color: #d7ccc8; 
            border: 2px solid #000;
            border-radius: 24px;
            padding: 32px;
            display: flex;
            flex-direction: row;
            gap: 32px;
            width: 100%;
            max-width: 850px;
            box-shadow: 8px 8px 0px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        /* Sección de Imagen */
        .book-image-section {
            flex: 0 0 260px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .image-preview-container {
            width: 100%;
            aspect-ratio: 3/4.2;
            background-color: #e3f2fd;
            border: 2px solid #000;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .image-preview-container:hover { background-color: #bbdefb; }

        .plus-icon {
            font-size: 60px;
            color: #000;
            font-weight: 300;
            pointer-events: none;
        }

        .book-image-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
        }

        /* Sección de Detalles */
        .book-details-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* Custom Dropdown Estilizado */
        .custom-dropdown {
            position: relative;
            width: 180px;
        }

        .dropdown-trigger {
            width: 100%;
            padding: 8px 16px;
            border-radius: 25px;
            border: 2px solid #000;
            background-color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
            text-align: left;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dropdown-menu {
            position: absolute;
            top: 110%;
            right: 0;
            width: 100%;
            background: #fff;
            border: 2px solid #000;
            border-radius: 18px;
            padding: 8px;
            display: none;
            flex-direction: column;
            gap: 6px;
            z-index: 50;
            box-shadow: 4px 4px 0px rgba(0,0,0,0.1);
        }

        .dropdown-menu.show { display: flex; }

        .dropdown-item {
            padding: 8px 12px;
            border-radius: 20px; /* Redonditas */
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            border: 1.5px solid transparent;
            text-align: center;
            transition: transform 0.1s;
        }

        .dropdown-item:hover {
            transform: scale(1.03);
            border-color: #000;
        }

        .opt-deseado { background-color: #fff176; color: #000; }
        .opt-leyendo { background-color: #f8bbd0; color: #000; }
        .opt-terminado { background-color: #e1bee7; color: #000; }

        /* Inputs Estilo Libre */
        .input-title {
            background: transparent;
            border: none;
            font-size: 2.2rem;
            font-weight: 800;
            color: #000;
            outline: none;
            width: 100%;
        }

        .input-author {
            background: transparent;
            border: none;
            font-size: 1.4rem;
            font-weight: 600;
            color: #444;
            outline: none;
            width: 100%;
            margin-bottom: 10px;
        }

        .description-area {
            width: 100%;
            min-height: 150px;
            background: transparent;
            border: none;
            background-image: repeating-linear-gradient(transparent, transparent 29px, #9e9e9e 29px, #9e9e9e 30px, transparent 30px);
            line-height: 30px;
            font-size: 1.1rem;
            resize: none;
            outline: none;
            padding: 0;
        }

        /* Botones */
        .btn {
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 800;
            text-transform: uppercase;
            border: 2px dashed #000;
            box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
        }

        .btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px rgba(0, 0, 0, 0.1); }
        .btn-cancel { background-color: #ef9a9a; }
        .btn-save { background-color: #a5d6a7; }

        @media (max-width: 768px) {
            .book-card { flex-direction: column; }
            .book-image-section { margin: 0 auto; }
        }
    </style>
</head>
<body>

    <form action="nuevo_libro.php" method="POST" enctype="multipart/form-data">
        <div class="book-card">
            <!-- Portada -->
            <div class="book-image-section">
                <label class="book-image-label">Portada libro (Opcional):</label>
                <div class="image-preview-container" onclick="document.getElementById('portada').click()">
                    <span class="plus-icon" id="plus-icon">+</span>
                </div>
                <input type="file" name="portada" id="portada" accept="image/*" style="display: none;">
            </div>

            <!-- Detalles -->
            <div class="book-details-section">
                <div class="flex justify-end mb-4">
                    <div class="flex flex-col items-end gap-1">
                        <span class="text-[0.7rem] font-extrabold uppercase tracking-wider">Estado del Libro</span>
                        
                        <!-- Dropdown Personalizado -->
                        <div class="custom-dropdown" id="statusDropdown">
                            <div class="dropdown-trigger" id="dropdownTrigger">
                                <span id="selectedLabel">Seleccione uno</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                            <div class="dropdown-menu" id="dropdownMenu">
                                <div class="dropdown-item opt-deseado" data-value="deseado">DESEADO</div>
                                <div class="dropdown-item opt-leyendo" data-value="leyendo">LEYENDO</div>
                                <div class="dropdown-item opt-terminado" data-value="terminado">TERMINADO</div>
                            </div>
                            <!-- Input oculto para que PHP reciba el valor -->
                            <input type="hidden" name="estado" id="estadoInput" value="">
                        </div>
                    </div>
                </div>

                <input type="text" name="titulo" class="input-title" placeholder="Título libro" required>
                <input type="text" name="autor" class="input-author" placeholder="Autor/ Autora" required>

                <label class="text-[0.8rem] font-bold uppercase mb-1">Descripción / Notas:</label>
                <textarea name="descripcion" class="description-area"></textarea>

                <div class="flex justify-end gap-4 mt-4">
                    <button type="button" class="btn btn-cancel" onclick="window.history.back()">CANCELAR</button>
                    <button type="submit" class="btn btn-save">GUARDAR LIBRO</button>
                </div>
            </div>
        </div>
    </form>

    <script>
        // Lógica del Dropdown Personalizado
        const trigger = document.getElementById('dropdownTrigger');
        const menu = document.getElementById('dropdownMenu');
        const items = document.querySelectorAll('.dropdown-item');
        const selectedLabel = document.getElementById('selectedLabel');
        const estadoInput = document.getElementById('estadoInput');

        trigger.addEventListener('click', () => {
            menu.classList.toggle('show');
        });

        items.forEach(item => {
            item.addEventListener('click', () => {
                const val = item.getAttribute('data-value');
                const text = item.innerText;
                const bgColor = window.getComputedStyle(item).backgroundColor;

                // Actualizar visualmente el botón
                selectedLabel.innerText = text;
                trigger.style.backgroundColor = bgColor;
                estadoInput.value = val;

                menu.classList.remove('show');
            });
        });

        // Cerrar al hacer clic fuera
        window.addEventListener('click', (e) => {
            if (!document.getElementById('statusDropdown').contains(e.target)) {
                menu.classList.remove('show');
            }
        });

        // Previsualización de imagen
        document.getElementById('portada').addEventListener('change', function(e) {
            const container = document.querySelector('.image-preview-container');
            const plus = document.getElementById('plus-icon');
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
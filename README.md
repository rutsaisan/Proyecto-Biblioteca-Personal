# 📚 Proyecto Biblioteca Personal

Este es un sistema de gestión de biblioteca personal desarrollado en PHP y MySQL. Permite a los usuarios llevar un registro detallado de sus libros, organizándolos por estado de lectura y almacenando información técnica y personal sobre cada obra.

## ✨ Características Principales

*   **Gestión de Libros**: Añadir, editar y eliminar libros de tu colección.
*   **Seguimiento de Estado**: Clasifica tus libros en tres estados:
    *   🟡 **Deseado**: Libros que quieres comprar (guarda el precio).
    *   🔵 **Leyendo**: Libros en proceso (guarda el capítulo actual).
    *   🟣 **Leído**: Libros terminados (guarda valoración de 1 a 5 estrellas).
*   **Ficha Técnica Completa**: Almacena título, autor, ISBN, editorial, idioma, número de páginas, año de publicación, edición y categoría.
*   **Portadas de Libros**: Subida de imágenes para las portadas de los libros.
*   **Sistema de Autenticación**: Registro y Login seguro de usuarios.
*   **Vista de Feed**: Visualización de la colección completa con filtros visuales por estado.
*   **Diseño Responsivo**: Interfaz moderna adaptable a móviles y escritorio, estilizada con CSS personalizado y Tailwind CSS (vía CDN).

## 🚀 Tecnologías Utilizadas

*   **Backend**: PHP (con MySQLi para base de datos).
*   **Base de Datos**: MySQL.
*   **Frontend**: HTML5, CSS3, Javascript.
*   **Estilos**: Tailwind CSS (CDN) + hoja de estilos personalizada (`estilo.css` / Google Fonts 'Inter' y 'Quicksand').

## 📋 Requisitos de Instalación

1.  **Servidor Web**: Apache o Nginx (XAMPP, WAMP, o similar recomendado para local).
2.  **PHP**: Versión 7.4 o superior.
3.  **MySQL**: Base de datos activa.

## 🛠️ Configuración

1.  **Base de Datos**:
    *   Importa el archivo script SQL ubicado en `Biblioteca/database/db.sql` en tu gestor de base de datos (phpMyAdmin, Workbench, etc.).
    *   Este script creará la base de datos `biblioteca_personal` y las tablas necesarias (`Usuarios`, `Libros`, `Coleccion`, `Autores`, etc.).

2.  **Conexión**:
    *   Abre el archivo `Biblioteca/includes/config.php`.
    *   Configura las variables con tus credenciales locales:
    ```php
    $host = "localhost"; // o la IP de tu servidor
    $user = "tu_usuario";
    $pass = "tu_contraseña";
    $db = "biblioteca_personal";
    ```
    *   *Nota*: El sistema incluye un mecanismo de "auto-migración" en `config.php` que verifica si faltan columnas nuevas en la base de datos y las añade automáticamente.

3.  **Permisos**:
    *   Asegúrate de que la carpeta `Biblioteca/uploads/` tenga permisos de escritura para que se puedan guardar las imágenes de las portadas.

## 📖 Uso

1.  **Registro/Login**:
    *   Al entrar, verás la pantalla de inicio de sesión. Si no tienes cuenta, ve a "Crear cuenta".
    *   Ingresa tus credenciales para acceder a tu panel principal.

2.  **Añadir Libro**:
    *   Haz clic en el botón **"+ Añadir libro"**.
    *   Sube una portada, rellena el título y selecciona el estado (`Deseado`, `Leyendo` o `Leído`).
    *   Dependiendo del estado, se habilitarán campos específicos (Precio, Capítulo, Estrellas).
    *   Completa la ficha técnica (Categoría, Editorial, etc.) y guarda.

3.  **Gestionar Colección**:
    *   En el **Feed**, verás tus libros.
    *   Pasa el cursor sobre un libro para ver más detalles, editarlo o eliminarlo.
    *   Las tarjetas cambian de color según el estado del libro.

## 📂 Estructura del Proyecto

*   `admin_panel.php`: Panel de administración (si aplica).
*   `feed.php`: Página principal con la colección del usuario.
*   `nuevo_libro.php`: Formulario para crear y editar libros.
*   `index.php`: Página de inicio de sesión.
*   `database/`: Scripts SQL.
*   `includes/`: Conexión a BD y componentes compartidos.
*   `php/`: Lógica de autenticación (login, register, logout).
*   `uploads/`: Directorio donde se almacenan las portadas.

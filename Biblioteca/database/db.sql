CREATE DATABASE IF NOT EXISTS biblioteca_personal;
USE biblioteca_personal;

CREATE TABLE IF NOT EXISTS Usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Usuario de prueba (contraseña: Admin123$)
-- Nota: En producción, la contraseña debe estar hasheada. 
INSERT INTO Usuarios (usuario, email, contrasena, nombre_completo) VALUES 
('admin', 'admin@example.com', '$2y$10$YourHashedPasswordHere...', 'Administrador');

CREATE TABLE Autores (
    id_autor INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    nacionalidad VARCHAR(50)
);

CREATE TABLE Categorias (
    id_categoria INT PRIMARY KEY AUTO_INCREMENT,
    nombre_categoria VARCHAR(50) NOT NULL
);

CREATE TABLE Editoriales (
    id_editorial INT PRIMARY KEY AUTO_INCREMENT,
    nombre_editorial VARCHAR(100) NOT NULL,
    pais VARCHAR(50)
);

CREATE TABLE Libros (
    id_libro INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(150) NOT NULL,
    isbn VARCHAR(20) UNIQUE NOT NULL,
    num_paginas INT,
    idioma VARCHAR(30),
    edicion VARCHAR(50),
    año_publicacion INT,
    descripcion TEXT,
    id_autor INT,
    id_editorial INT,
    id_categoria INT,
    FOREIGN KEY (id_autor) REFERENCES Autores(id_autor),
    FOREIGN KEY (id_editorial) REFERENCES Editoriales(id_editorial),
    FOREIGN KEY (id_categoria) REFERENCES Categorias(id_categoria)
);

CREATE TABLE Coleccion (
    id_coleccion INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_libro INT NOT NULL,
    estado ENUM('Deseado', 'Leyendo', 'Leido') NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Campos específicos por estado
    precio DECIMAL(10, 2),       -- Para 'Deseado'
    capitulo_actual INT,         -- Para 'Leyendo'
    resena TEXT,                 -- Para 'Leido' (Opcional)
    valoracion INT CHECK (valoracion BETWEEN 1 AND 5), -- Para 'Leido'
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_libro) REFERENCES Libros(id_libro) ON DELETE CASCADE,
    UNIQUE KEY (id_usuario, id_libro) -- Evitar duplicados del mismo libro para un usuario
);

-- crea usuario nuevo con contraseña
CREATE USER 
'ruthydomi'@'%' 
IDENTIFIED  BY 'BibliotecaPersonal123$';
-- permite acceso a ese usuario
GRANT USAGE ON *.* TO 'ruthydomi'@'%';
-- quitale todos los limites que tenga
ALTER USER 'ruthydomi'@'%' 
REQUIRE NONE 
WITH MAX_QUERIES_PER_HOUR 0 
MAX_CONNECTIONS_PER_HOUR 0 
MAX_UPDATES_PER_HOUR 0 
MAX_USER_CONNECTIONS 0;
-- dale acceso a la base de datos empresadam
GRANT ALL PRIVILEGES ON `biblioteca_personal`.* 
TO 'ruthydomi'@'%';
-- recarga la tabla de privilegios
FLUSH PRIVILEGES;
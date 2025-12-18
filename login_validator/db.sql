CREATE DATABASE IF NOT EXISTS proyecto_login;
USE proyecto_login;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Usuario de prueba (contraseña: Admin123$)
-- Nota: En producción, la contraseña debe estar hasheada. 
-- Aquí insertamos un ejemplo, pero el registro real debe hacerse desde la app o con hash manual.
INSERT INTO usuarios (usuario, email, contrasena, nombre_completo) VALUES 
('admin', 'admin@example.com', '$2y$10$YourHashedPasswordHere...', 'Administrador');

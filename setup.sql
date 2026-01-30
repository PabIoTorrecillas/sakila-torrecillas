-- Script de configuración para agregar tabla de usuarios
-- Ejecutar después de importar sakila-schema.sql y sakila-data.sql

USE sakila;

-- Crear tabla de usuarios para el sistema de login
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insertar usuario por defecto (password: admin123)
INSERT INTO usuarios (usuario, password, nombre) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador');
-- Usuario: admin
-- Password: admin123

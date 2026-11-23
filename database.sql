-- Agenda personal de contactos (versión 2)
-- Script completo para instalar desde cero (MySQL / MariaDB)
--
-- Si ya tienes la versión 1 instalada y no quieres perder tus datos,
-- usa en su lugar el archivo migracion_v2.sql
--
-- En hosting gratuito (InfinityFree, etc.) la base de datos ya viene creada:
-- borra las dos primeras instrucciones (CREATE DATABASE y USE) antes de importar.

CREATE DATABASE IF NOT EXISTS agenda_contactos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE agenda_contactos;

-- ------------------------------------------------------------
-- Tabla: usuarios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre         VARCHAR(100) NOT NULL,
    correo         VARCHAR(150) NOT NULL,
    password       VARCHAR(255) NOT NULL,  -- hash generado con password_hash()
    fecha_registro DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_correo (correo)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla: categorias (NUEVA)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB;

INSERT IGNORE INTO categorias (id, nombre) VALUES
    (1, 'Familia'),
    (2, 'Amigos'),
    (3, 'Trabajo'),
    (4, 'Universidad'),
    (5, 'Otro');

-- ------------------------------------------------------------
-- Tabla: contactos
-- Nuevos campos: id_categoria, favorito, fecha_creacion
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contactos (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario     INT UNSIGNED NOT NULL,
    id_categoria   INT UNSIGNED NOT NULL DEFAULT 5,       -- 5 = Otro
    nombre         VARCHAR(100) NOT NULL,
    telefono       VARCHAR(20)  NOT NULL,
    correo         VARCHAR(150) NOT NULL,
    ciudad         VARCHAR(80)  NOT NULL,
    favorito       TINYINT(1)   NOT NULL DEFAULT 0,       -- 0 = No, 1 = Sí
    fecha_creacion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_contactos_usuario (id_usuario),
    KEY idx_contactos_categoria (id_categoria),
    CONSTRAINT fk_contactos_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_contactos_categoria
        FOREIGN KEY (id_categoria) REFERENCES categorias (id)
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Script SQL - Base de datos PostgreSQL
-- Tablas: videojuegos y resenas
-- Autor: Estudiante - Proyecto Final
-- =====================================================

-- Eliminar tablas si existen (para reinsercion limpia)
DROP TABLE IF EXISTS resenas;
DROP TABLE IF EXISTS videojuegos;

-- -----------------------------------------------------
-- Tabla: videojuegos
-- Almacena la informacion principal de cada videojuego
-- -----------------------------------------------------
CREATE TABLE videojuegos (
    id SERIAL PRIMARY KEY,                          -- Identificador unico autoincremental
    nombre VARCHAR(200) NOT NULL,                    -- Nombre del videojuego
    genero VARCHAR(100) NOT NULL,                    -- Genero (ej: Accion, RPG, Deportes)
    plataforma VARCHAR(100) NOT NULL,                -- Plataforma (ej: PS5, Xbox, PC, Switch)
    descripcion TEXT,                                -- Descripcion breve del juego
    fecha_lanzamiento DATE,                          -- Fecha en que se lanzo el juego
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Fecha en que se registro en el sistema
);

-- -----------------------------------------------------
-- Tabla: resenas
-- Almacena las resenas que los usuarios escriben
-- sobre los videojuegos
-- -----------------------------------------------------
CREATE TABLE resenas (
    id SERIAL PRIMARY KEY,                          -- Identificador unico autoincremental
    videojuego_id INTEGER NOT NULL,                  -- Relacion con la tabla videojuegos
    nombre_usuario VARCHAR(150) NOT NULL,            -- Nombre de quien escribe la resena
    calificacion INTEGER NOT NULL CHECK (calificacion >= 1 AND calificacion <= 5), -- Calificacion de 1 a 5 estrellas
    comentario TEXT,                                 -- Comentario u opinion del usuario
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Fecha en que se escribio la resena
    
    -- Llave foranea: se vincula con la tabla videojuegos
    CONSTRAINT fk_resena_videojuego
        FOREIGN KEY (videojuego_id)
        REFERENCES videojuegos(id)
        ON DELETE CASCADE   -- Si se borra un juego, se borran sus resenas
);

-- -----------------------------------------------------
-- Datos de ejemplo para pruebas
-- -----------------------------------------------------
INSERT INTO videojuegos (nombre, genero, plataforma, descripcion, fecha_lanzamiento) VALUES
('The Legend of Zelda: Tears of the Kingdom', 'Aventura', 'Nintendo Switch', 'Explora Hyrule en esta epica secuela llena de misterios y construccion.', '2023-05-12'),
('Elden Ring', 'RPG de Accion', 'PC', 'Un vasto mundo abierto creado por FromSoftware y George R.R. Martin.', '2022-02-25'),
('God of War Ragnarok', 'Accion', 'PS5', 'Kratos y Atreus se enfrentan al Ragnarok en la mitologia nordica.', '2022-11-09');

INSERT INTO resenas (videojuego_id, nombre_usuario, calificacion, comentario) VALUES
(1, 'GamerFan123', 5, 'Una obra maestra. La construccion y exploracion son increibles.'),
(1, 'CriticoGamer', 4, 'Excelente juego aunque algunas mecanicas pueden mejorar.'),
(2, 'SoulsPlayer', 5, 'El mejor souls-like jamas creado. Mundo inmenso y desafiante.'),
(3, 'PlayStationFan', 5, 'Graficos impresionantes y una historia conmovedora.');

-- Verificar inserciones
SELECT * FROM videojuegos;
SELECT * FROM resenas;

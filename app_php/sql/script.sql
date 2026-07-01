-- =====================================================
-- Script SQL - Base de datos PostgreSQL
-- Tablas: videojuegos y resenas
-- Autor: Estudiante - Proyecto Final
-- =====================================================
-- 
-- INTRODUCCION GENERAL:
-- Este script crea una base de datos para almacenar informacion
-- de videojuegos y las resenas (reviews) que los usuarios
-- escriben sobre ellos. La relacion es: un videojuego puede
-- tener MUCHAS resenas, pero cada resena pertenece a UN solo
-- videojuego. Esto se llama relacion "uno a muchos" (1:N).
-- 
-- El script esta disenado para ser RE-EJECUTABLE, es decir,
-- se puede correr una y otra vez sin errores. Para lograrlo,
-- usamos DROP TABLE IF EXISTS al inicio (que veremos mas abajo).

-- ---------------------------------------------------------
-- ELIMINACION DE TABLAS EXISTENTES
-- ---------------------------------------------------------
-- 
-- DROP TABLE IF EXISTS:
--   - DROP TABLE: instruccion SQL que ELIMINA (borra) una tabla
--     de la base de datos de forma permanente. Se pierden tanto
--     la estructura de la tabla (columnas, tipos, restricciones)
--     como TODOS los datos que contenga.
--   - IF EXISTS: clausula que evita UN ERROR si la tabla no
--     existe aun. Sin IF EXISTS, intentar borrar una tabla que
--     no existe lanzaria un error y detendria el script.
--     Con IF EXISTS, si la tabla no existe simplemente no hace
--     nada y continua. Esto es util cuando:
--       * Ejecutas el script en una base de datos nueva (vacia).
--       * Quieres re-ejecutar el script para empezar desde cero.
-- 
-- ORDEN DE BORRADO (IMPORTANTE):
--   Primero borramos "resenas" y luego "videojuegos". Esto es
--   obligatorio porque "resenas" tiene una FOREIGN KEY (llave
--   foranea) que apunta a "videojuegos". Si intentaramos borrar
--   "videojuegos" primero, PostgreSQL nos lo impediria porque
--   otra tabla depende de ella. Siempre se borra primero la
--   tabla HIJA (la que tiene la FK) y luego la PADRE.
-- 
DROP TABLE IF EXISTS resenas;
DROP TABLE IF EXISTS videojuegos;

-- ---------------------------------------------------------
-- TABLA: videojuegos
-- ---------------------------------------------------------
-- 
-- PROPOSITO DE LA TABLA:
--   Almacena la informacion principal de cada videojuego:
--   su nombre, genero, plataforma, descripcion, fecha de
--   lanzamiento y la fecha en que fue registrado en el sistema.
--   Cada fila representa UN videojuego distinto.
--   Esta tabla actua como la tabla PADRE en la relacion 1:N
--   (un videojuego -> muchas resenas).

CREATE TABLE videojuegos (

    -- COLUMNA: id
    -- TIPO: SERIAL
    --   SERIAL no es un tipo de dato real, es un "pseudo-tipo"
    --   de PostgreSQL que crea automaticamente:
    --     1. Una columna de tipo INTEGER (entero de 4 bytes,
    --        rango: -2,147,483,648 hasta 2,147,483,647).
    --     2. Una SEQUENCE (secuencia) asociada que genera
    --        numeros consecutivos (1, 2, 3, 4, ...).
    --     3. El valor por defecto de la columna se configura
    --        como nextval('secuencia'), asi cada vez que se
    --        inserta una fila SIN especificar el id, PostgreSQL
    --        automaticamente le asigna el siguiente numero.
    --   En otras bases de datos esto se llama AUTO_INCREMENT
    --   o IDENTITY. En PostgreSQL, SERIAL = auto-increment.
    --   Se usa para tener un identificador UNICO para cada
    --   videojuego sin tener que inventar IDs manualmente.
    -- 
    -- RESTRICCION: PRIMARY KEY (llave primaria)
    --   Una PRIMARY KEY es una restriccion (constraint) que
    --   garantiza DOS cosas sobre la(s) columna(s) marcada(s):
    --     1. UNICIDAD (UNIQUE): No puede haber dos filas con
    --        el mismo valor en la columna id. Cada juego tiene
    --        un ID diferente.
    --     2. NO NULO (NOT NULL): La columna id nunca puede
    --        quedar vacia (NULL). Siempre debe tener un valor.
    --   Ademas, PostgreSQL crea automaticamente un INDICE
    --   (index) sobre la llave primaria, lo que acelera
    --   muchisimo las busquedas por id (ej: WHERE id = 5).
    --   La PRIMARY KEY es la forma estandar de identificar
    --   de manera unica cada fila de una tabla.
    -- 
    id SERIAL PRIMARY KEY,

    -- COLUMNA: nombre
    -- TIPO: VARCHAR(200)
    --   VARCHAR significa "VARiable CHARacter" = texto de
    --   longitud VARIABLE. El numero entre parentesis (200)
    --   es el LIMITE MAXIMO de caracteres permitidos.
    --   ¿Por que VARCHAR(200) y no TEXT?
    --     * VARCHAR(200) impone un limite: no se pueden guardar
    --       nombres mas largos que 200 caracteres. Esto ayuda
    --       a mantener la integridad de los datos (no tiene
    --       sentido un nombre de videojuego de 5000 caracteres).
    --     * TEXT no tiene limite de longitud. Se usa para
    --       textos que pueden ser muy largos (descripciones,
    --       comentarios, articulos completos).
    --     * En PostgreSQL, VARCHAR y TEXT tienen practicamente
    --       el mismo rendimiento. La diferencia es solo la
    --       restriccion de longitud que VARCHAR impone.
    --   En resumen: usamos VARCHAR cuando sabemos el tamano
    --   maximo esperado; usamos TEXT para contenido libre.
    -- 
    -- RESTRICCION: NOT NULL
    --   NULL en SQL significa "ausencia de valor" o "valor
    --   desconocido". Una columna sin NOT NULL puede contener
    --   NULL (vacio, no definido). NOT NULL OBLIGA a que
    --   siempre se proporcione un valor para esa columna.
    --   Si intentas insertar una fila sin nombre, PostgreSQL
    --   rechazara la operacion con un error. Esto garantiza
    --   que NUNCA haya un videojuego sin nombre en la base,
    --   lo cual no tendria sentido para el negocio.
    -- 
    nombre VARCHAR(200) NOT NULL,

    -- COLUMNA: genero
    -- TIPO: VARCHAR(100) NOT NULL
    --   Misma logica que "nombre". VARCHAR(100) porque un
    --   genero (ej: "Accion", "RPG de Accion", "Deportes")
    --   nunca deberia exceder los 100 caracteres. El limite
    --   previene datos anomalos. NOT NULL porque todo juego
    --   debe tener un genero asignado.
    -- 
    genero VARCHAR(100) NOT NULL,

    -- COLUMNA: plataforma
    -- TIPO: VARCHAR(100) NOT NULL
    --   Plataforma donde se ejecuta el juego (ej: "PS5",
    --   "Xbox Series X", "Nintendo Switch", "PC").
    --   VARCHAR(100) es suficiente. NOT NULL porque todo
    --   juego se lanza en al menos una plataforma.
    -- 
    plataforma VARCHAR(100) NOT NULL,

    -- COLUMNA: descripcion
    -- TIPO: TEXT
    --   TEXT es un tipo de dato para texto de longitud
    --   ILIMITADA (o practicamente ilimitada, hasta ~1 GB).
    --   Se usa aqui porque una descripcion de un videojuego
    --   podria ser larga (varios parrafos). A diferencia de
    --   VARCHAR, TEXT no impone un limite fijo.
    --   NOTA: Esta columna NO tiene NOT NULL. Eso significa
    --   que es OPCIONAL (nullable). Un juego puede registrarse
    --   sin descripcion si el usuario no la proporciona.
    -- 
    descripcion TEXT,

    -- COLUMNA: fecha_lanzamiento
    -- TIPO: DATE
    --   DATE almacena SOLO la fecha (anio, mes, dia). NO
    --   incluye hora, minutos ni segundos. Internamente usa
    --   4 bytes. Es el tipo ideal para fechas de nacimiento,
    --   fechas de lanzamiento, fechas de vencimiento, etc.
    --   donde la hora no es relevante.
    --   Formato de entrada estandar: 'YYYY-MM-DD' (ej: '2023-05-12').
    --   Esta columna es nullable (acepta NULL) porque podria
    --   haber juegos cuya fecha de lanzamiento aun no se conoce.
    -- 
    fecha_lanzamiento DATE,

    -- COLUMNA: fecha_registro
    -- TIPO: TIMESTAMP
    --   TIMESTAMP almacena fecha Y hora juntas (anio, mes, dia,
    --   hora, minuto, segundo, y fracciones de segundo). Usa
    --   8 bytes internamente.
    --   Se diferencia de DATE en que incluye la componente
    --   horaria. Es util para registrar CUANDO ocurrio algo
    --   exactamente en el sistema (logs, auditoria, registros).
    --   Ejemplo de valor: '2025-06-30 14:25:33.123456'.
    -- 
    -- CLAUSULA: DEFAULT CURRENT_TIMESTAMP
    --   DEFAULT establece un valor por defecto para la columna.
    --   Si al hacer un INSERT no se especifica un valor para
    --   esta columna, PostgreSQL automaticamente le asigna
    --   el valor por defecto.
    --   CURRENT_TIMESTAMP es una funcion de PostgreSQL que
    --   devuelve la fecha y hora ACTUAL del servidor en el
    --   momento exacto de la insercion.
    --   Combinados: DEFAULT CURRENT_TIMESTAMP significa "si
    --   no me dices que fecha poner, usa la fecha/hora de
    --   AHORA MISMO". Esto es muy comun en columnas de auditoria
    --   para saber cuando se creo cada registro automaticamente
    --   sin que el programador tenga que pasar la fecha.
    -- 
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- TABLA: resenas
-- ---------------------------------------------------------
-- 
-- PROPOSITO DE LA TABLA:
--   Almacena las resenas (reviews / opiniones) que los
--   usuarios escriben sobre los videojuegos. Cada fila
--   representa UNA resena escrita por UN usuario sobre
--   UN videojuego en particular.
--   Esta tabla actua como la tabla HIJA en la relacion 1:N
--   (muchas resenas -> un videojuego).

CREATE TABLE resenas (

    -- COLUMNA: id
    -- TIPO: SERIAL PRIMARY KEY
    --   Identico a lo explicado en videojuegos.id: entero
    --   auto-incremental que identifica de forma unica cada
    --   resena. Ninguna resena comparte el mismo id.
    -- 
    id SERIAL PRIMARY KEY,

    -- COLUMNA: videojuego_id
    -- TIPO: INTEGER NOT NULL
    --   INTEGER: numero entero de 4 bytes (igual que el tipo
    --   base de SERIAL). Se usa INTEGER (no SERIAL) porque
    --   esta columna NO se auto-incrementa; su valor lo
    --   asigna el usuario/programador para indicar a que
    --   videojuego pertenece la resena.
    --   El tipo debe coincidir con el tipo de la columna
    --   a la que referencia (videojuegos.id, que es INTEGER
    --   porque SERIAL crea un INTEGER con secuencia).
    --   NOT NULL: toda resena DEBE estar asociada a un
    --   videojuego existente. No puede haber resenas "huerfanas".
    -- 
    videojuego_id INTEGER NOT NULL,

    -- COLUMNA: nombre_usuario
    -- TIPO: VARCHAR(150) NOT NULL
    --   Nombre o alias del usuario que escribe la resena.
    --   VARCHAR(150) permite nombres de usuario de hasta
    --   150 caracteres, suficiente para la mayoria de alias.
    --   NOT NULL: no se permiten resenas anonimas sin nombre.
    -- 
    nombre_usuario VARCHAR(150) NOT NULL,

    -- COLUMNA: calificacion
    -- TIPO: INTEGER NOT NULL
    --   Almacena la puntuacion como un numero entero (1 a 5).
    --   Se usa INTEGER en vez de SMALLINT (2 bytes) porque
    --   la diferencia de espacio es minima y INTEGER es mas
    --   portable entre bases de datos.
    -- 
    -- RESTRICCION: CHECK (calificacion >= 1 AND calificacion <= 5)
    --   CHECK es una restriccion que VALIDA los datos antes
    --   de que se inserten o actualicen en la tabla.
    --   La condicion dentro del parentesis se evalua para
    --   cada fila: si es VERDADERA (TRUE), la operacion se
    --   permite; si es FALSA (FALSE), PostgreSQL RECHAZA la
    --   operacion con un error.
    --   En este caso, la condicion "calificacion >= 1 AND
    --   calificacion <= 5" garantiza que solo se acepten
    --   calificaciones entre 1 y 5 estrellas inclusive.
    --   Si alguien intenta insertar un 0, un 6, o un -3,
    --   la base de datos lo rechazara automaticamente.
    --   Esto se llama "integridad de datos a nivel de base"
    --   y es mucho mas seguro que validarlo solo en el codigo
    --   de la aplicacion, porque la restriccion vive en la
    --   base de datos y NADIE puede saltarsela.
    -- 
    calificacion INTEGER NOT NULL CHECK (calificacion >= 1 AND calificacion <= 5),

    -- COLUMNA: comentario
    -- TIPO: TEXT
    --   Misma logica que videojuegos.descripcion: texto de
    --   longitud libre (ilimitada) para que el usuario pueda
    --   escribir opiniones tan largas como quiera.
    --   Es nullable: un usuario puede dejar solo la
    --   calificacion sin escribir un comentario.
    -- 
    comentario TEXT,

    -- COLUMNA: fecha_registro
    -- TIPO: TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    --   Igual que en videojuegos.fecha_registro: registra
    --   automaticamente la fecha y hora exacta en que se
    --   creo la resena, usando la hora del servidor en el
    --   momento de la insercion.
    -- 
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- =============================================
    -- RESTRICCION DE LLAVE FORANEA (FOREIGN KEY)
    -- =============================================
    -- 
    -- CONSTRAINT fk_resena_videojuego:
    --   CONSTRAINT es la palabra clave para definir una
    --   restriccion con NOMBRE. Darle un nombre a la
    --   restriccion (fk_resena_videojuego) es una buena
    --   practica porque:
    --     * Hace que los mensajes de error sean mas claros
    --       (ej: "viola la restriccion fk_resena_videojuego"
    --       en vez de un nombre generico como "resenas_id_fkey").
    --     * Permite modificar o eliminar la restriccion
    --       por nombre con ALTER TABLE en el futuro.
    -- 
    -- FOREIGN KEY (videojuego_id):
    --   Declara que la columna videojuego_id de ESTA tabla
    --   (resenas) es una LLAVE FORANEA. Una FOREIGN KEY es
    --   una o varias columnas que hacen REFERENCIA a la
    --   PRIMARY KEY de OTRA tabla (o a una columna UNIQUE).
    --   Esto establece una RELACION entre las dos tablas.
    -- 
    -- REFERENCES videojuegos(id):
    --   Indica a QUE tabla y columna apunta la FK:
    --   "videojuego_id en resenas referencia a id en videojuegos".
    --   Esto garantiza INTEGRIDAD REFERENCIAL: no se puede
    --   insertar una resena con un videojuego_id que NO EXISTE
    --   en la tabla videojuegos. Por ejemplo, si solo hay
    --   3 videojuegos (ids 1, 2, 3), no puedes crear una
    --   resena con videojuego_id = 99 porque ese juego no existe.
    --   PostgreSQL rechazara la operacion.
    -- 
    --   Tambien protege al reves: no puedes borrar un videojuego
    --   que tenga resenas asociadas... A MENOS que tengas la
    --   clausula ON DELETE que vemos a continuacion.
    -- 
    -- ON DELETE CASCADE:
    --   Define que ACCION tomar cuando se ELIMINA la fila
    --   referenciada en la tabla PADRE (videojuegos).
    --   Las opciones comunes de ON DELETE son:
    --     * CASCADE: elimina automaticamente las filas hijas.
    --       Si borras un videojuego, TODAS sus resenas se
    --       borran tambien. Evita filas huerfanas.
    --     * SET NULL: pone la FK a NULL en las filas hijas
    --       (requiere que la columna acepte NULL).
    --     * SET DEFAULT: pone la FK al valor por defecto.
    --     * RESTRICT / NO ACTION: impide borrar el padre si
    --       tiene hijos (es el comportamiento por defecto).
    -- 
    --   En este caso, CASCADE es logico: si un juego se
    --   elimina del sistema, sus resenas ya no tienen sentido
    --   y deben desaparecer tambien. Esto mantiene la base
    --   de datos limpia sin necesidad de borrar manualmente
    --   las resenas antes de borrar el juego.
    -- 
    CONSTRAINT fk_resena_videojuego
        FOREIGN KEY (videojuego_id)
        REFERENCES videojuegos(id)
        ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- INSERCION DE DATOS DE EJEMPLO (PARA PRUEBAS)
-- ---------------------------------------------------------
-- 
-- INSERT INTO:
--   Comando SQL que agrega NUEVAS FILAS a una tabla existente.
--   SINTAXIS:
--     INSERT INTO nombre_tabla (columna1, columna2, ...)
--     VALUES (valor1, valor2, ...),
--            (valor3, valor4, ...),
--            ...;
-- 
--   - Primero se nombra la tabla destino (videojuegos).
--   - Luego, entre parentesis, se LISTAN las columnas que
--     vamos a llenar, en el ORDEN en que apareceran los
--     valores. Las columnas no listadas toman su valor por
--     defecto (DEFAULT) o NULL si no tienen default.
--   - VALUES seguido de una o mas TUPLAS (filas de valores)
--     separadas por comas. Cada tupla es un conjunto de
--     valores entre parentesis. Cada tupla = una fila nueva.
-- 
-- NOTA SOBRE LAS COLUMNAS OMITIDAS:
--   - id: no la incluimos porque es SERIAL. PostgreSQL
--     automaticamente genera el siguiente numero (1, 2, 3...).
--   - fecha_registro: no la incluimos porque tiene DEFAULT
--     CURRENT_TIMESTAMP. PostgreSQL asigna la fecha/hora actual.
-- 
-- NOTA SOBRE LAS COMILLAS:
--   En SQL, las cadenas de texto (strings) van entre COMILLAS
--   SIMPLES ('...'), NO dobles ("..."). Las comillas dobles
--   en PostgreSQL se usan para identificadores (nombres de
--   tablas/columnas), no para valores.
-- 
INSERT INTO videojuegos (nombre, genero, plataforma, descripcion, fecha_lanzamiento) VALUES
('The Legend of Zelda: Tears of the Kingdom', 'Aventura', 'Nintendo Switch', 'Explora Hyrule en esta epica secuela llena de misterios y construccion.', '2023-05-12'),
('Elden Ring', 'RPG de Accion', 'PC', 'Un vasto mundo abierto creado por FromSoftware y George R.R. Martin.', '2022-02-25'),
('God of War Ragnarok', 'Accion', 'PS5', 'Kratos y Atreus se enfrentan al Ragnarok en la mitologia nordica.', '2022-11-09');

-- INSERCION EN LA TABLA resenas
-- 
-- NOTAS SOBRE LOS VALORES INSERTADOS:
--   - videojuego_id: 1, 1, 2, 3. Como el SERIAL de videojuegos
--     empezo en 1 y la tabla esta vacia, los IDs asignados
--     a los juegos de arriba son 1 (Zelda), 2 (Elden Ring),
--     y 3 (God of War). Por eso las resenas usan esos numeros.
--     La primera y segunda resena son del juego 1 (Zelda),
--     la tercera del juego 2, y la cuarta del juego 3.
--   - calificacion: 5 y 4. Son valores validos porque cumplen
--     la restriccion CHECK (estan entre 1 y 5). Si intentaras
--     insertar un 0 o un 6, la base de datos te daria error.
--   - fecha_registro: omitida. Se usara CURRENT_TIMESTAMP.
-- 
INSERT INTO resenas (videojuego_id, nombre_usuario, calificacion, comentario) VALUES
(1, 'GamerFan123', 5, 'Una obra maestra. La construccion y exploracion son increibles.'),
(1, 'CriticoGamer', 4, 'Excelente juego aunque algunas mecanicas pueden mejorar.'),
(2, 'SoulsPlayer', 5, 'El mejor souls-like jamas creado. Mundo inmenso y desafiante.'),
(3, 'PlayStationFan', 5, 'Graficos impresionantes y una historia conmovedora.');

-- ---------------------------------------------------------
-- VERIFICACION DE INSERCIONES
-- ---------------------------------------------------------
-- 
-- SELECT * FROM nombre_tabla;
--   SELECT: comando SQL para CONSULTAR (leer) datos de la base.
--     No modifica nada, solo LEE y muestra resultados.
--   * (asterisco): significa "TODAS las columnas". Es un
--     comodin que selecciona cada columna de la tabla.
--   FROM: indica de que tabla se leeran los datos.
--   Traduccion literal: "Selecciona todas las columnas de
--   todas las filas de la tabla videojuegos".
-- 
-- Estos SELECT al final del script sirven para comprobar
-- visualmente que las inserciones se hicieron correctamente
-- y que los datos se ven como esperamos. En un entorno de
-- produccion estos SELECT se quitarian, pero para desarrollo
-- y aprendizaje son utiles.
-- 
SELECT * FROM videojuegos;
SELECT * FROM resenas;

<?php
/**
 * catalogo.php
 * Vista publica que muestra todos los videojuegos registrados
 * junto con sus respectivas resenas.
 *
 * ARCHIVO COMENTADO CON FINES ACADEMICOS:
 * Cada línea de código PHP, cada consulta SQL, cada estructura HTML,
 * cada variable, cada bucle y cada función está explicada paso a paso
 * para que cualquier estudiante pueda entender el funcionamiento completo.
 */

/**
 * ---------------------------------------------
 * require_once 'conexion.php'
 * ---------------------------------------------
 * Incluye (importa) el archivo "conexion.php" UNA SOLA VEZ.
 * "require_once" diferencia de "require": si el archivo ya fue incluido
 * antes, no lo vuelve a cargar, evitando errores de redeclaración.
 *
 * Dentro de "conexion.php" se encuentra la variable $conexion_bd,
 * que es un objeto PDO (PHP Data Objects) configurado con los datos
 * de conexión a la base de datos PostgreSQL (host, puerto, nombre BD,
 * usuario y contraseña).
 *
 * PDO es una capa de abstracción que permite conectarse a distintos
 * motores de bases de datos (MySQL, PostgreSQL, SQLite, etc.) usando
 * una misma interfaz de métodos.
 */
require_once 'conexion.php';

/**
 * ---------------------------------------------
 * CONSULTA SQL PRINCIPAL: Obtener todos los videojuegos
 * ---------------------------------------------
 *
 * Explicación detallada de la consulta SQL:
 *
 * SELECT v.*
 *   -> Selecciona TODAS las columnas de la tabla "videojuegos" (alias "v").
 *      El "*" es un comodín que significa "todas las columnas".
 *
 * COALESCE(ROUND(AVG(r.calificacion)::numeric, 1), 0) AS promedio_calificacion
 *   -> AVG(r.calificacion): Función de agregación SQL que calcula el PROMEDIO
 *      aritmético de todas las calificaciones de las reseñas asociadas a cada juego.
 *   -> ::numeric: Conversión de tipo (type casting) en PostgreSQL. Asegura que
 *      el promedio se calcule como número decimal y no como entero.
 *   -> ROUND(..., 1): Redondea el promedio a 1 decimal (ej: 4.7 en vez de 4.666...).
 *   -> COALESCE(..., 0): Si el promedio es NULL (porque no hay reseñas), devuelve 0.
 *      COALESCE toma el primer valor NO nulo de la lista que se le pasa.
 *   -> AS promedio_calificacion: Asigna un ALIAS (nombre temporal) a esta columna calculada
 *      para poder referirnos a ella en PHP como $videojuego['promedio_calificacion'].
 *
 * COUNT(r.id) AS total_resenas
 *   -> COUNT(): Función de agregación que cuenta el número de filas.
 *   -> Cuenta cuántas reseñas (r.id) tiene cada videojuego.
 *   -> AS total_resenas: Alias para usar en PHP como $videojuego['total_resenas'].
 *
 * FROM videojuegos v
 *   -> Indica que la tabla principal de la consulta es "videojuegos"
 *      y le asigna el alias "v" para acortar referencias.
 *
 * LEFT JOIN resenas r ON v.id = r.videojuego_id
 *   -> LEFT JOIN: Une dos tablas. "LEFT" significa que TODOS los registros
 *      de la tabla izquierda (videojuegos) aparecerán en el resultado,
 *      incluso si no tienen coincidencias en la tabla derecha (resenas).
 *      Si un juego no tiene reseñas, las columnas de "resenas" serán NULL.
 *   -> ON v.id = r.videojuego_id: Condición de unión. Relaciona cada videojuego
 *      con sus reseñas mediante la clave foránea "videojuego_id".
 *
 * GROUP BY v.id
 *   -> Agrupa los resultados por el ID del videojuego. Es necesario cuando
 *      se usan funciones de agregación (AVG, COUNT) para que operen sobre
 *      cada grupo de reseñas por separado (un grupo por cada videojuego).
 *
 * ORDER BY promedio_calificacion DESC, v.nombre ASC
 *   -> Ordena los resultados:
 *      - Primero por promedio de calificación de forma DESCENDENTE (mayor a menor).
 *      - En caso de empate, por nombre de forma ASCENDENTE (A a Z).
 */
$sql = "SELECT v.*,
               COALESCE(ROUND(AVG(r.calificacion)::numeric, 1), 0) AS promedio_calificacion,
               COUNT(r.id) AS total_resenas
        FROM videojuegos v
        LEFT JOIN resenas r ON v.id = r.videojuego_id
        GROUP BY v.id
        ORDER BY promedio_calificacion DESC, v.nombre ASC";

/**
 * ---------------------------------------------
 * $conexion_bd->query($sql)
 * ---------------------------------------------
 * El método "query()" del objeto PDO ejecuta una consulta SQL
 * y devuelve un objeto PDOStatement.
 *
 * Diferencia entre query() y prepare():
 *   - query(): Se usa para consultas SIN parámetros variables (sin datos del usuario).
 *     Ejecuta la consulta directamente.
 *   - prepare(): Se usa para consultas CON parámetros (marcadores ":parametro").
 *     Previene inyección SQL al separar la estructura SQL de los valores.
 *
 * Aquí usamos query() porque la consulta no recibe datos externos.
 */
$consulta_videojuegos = $conexion_bd->query($sql);

/**
 * ---------------------------------------------
 * $consulta_videojuegos->fetchAll()
 * ---------------------------------------------
 * El método fetchAll() del objeto PDOStatement recupera TODAS las filas
 * resultantes de la consulta y las devuelve como un ARRAY ASOCIATIVO.
 *
 * Un array asociativo en PHP es una estructura de datos donde cada elemento
 * se accede mediante una clave (string) en lugar de un índice numérico.
 *
 * Ejemplo de estructura resultante:
 * $lista_videojuegos = [
 *     [0] => ['id' => 1, 'nombre' => 'Zelda', 'genero' => 'Aventura', ...],
 *     [1] => ['id' => 2, 'nombre' => 'Mario', 'genero' => 'Plataformas', ...],
 *     ...
 * ]
 *
 * Cada elemento del array es a su vez un array asociativo donde las claves
 * son los nombres de las columnas de la tabla.
 */
$lista_videojuegos = $consulta_videojuegos->fetchAll();
?>
<!--
==============================================
    INICIO DEL DOCUMENTO HTML
==============================================
  


-->

<!--
    <!DOCTYPE html>
    Declaración que indica al navegador que este documento
    sigue el estándar HTML5 (la versión más reciente de HTML).
    Sin esta declaración, el navegador entraría en "modo quirks"
    (compatibilidad con páginas antiguas), lo que puede causar
    inconsistencias en el renderizado.
-->
<!DOCTYPE html>

<!--
    <html lang="es">
    Etiqueta raíz del documento HTML.
    El atributo lang="es" indica que el contenido está en español.
    Esto ayuda a los motores de búsqueda (SEO) y a los lectores
    de pantalla (accesibilidad) a interpretar correctamente el idioma.
-->
<html lang="es">

<!--
    ==========================================
    SECCIÓN <head>: Metadatos y recursos externos
    ==========================================
    El <head> contiene información SOBRE la página (no visible directamente).
    Incluye metadatos, título, enlaces a hojas de estilo, scripts, etc.
-->
<head>
    <!--
        <meta charset="UTF-8">
        Define la codificación de caracteres del documento como UTF-8.
        UTF-8 permite representar prácticamente todos los caracteres
        de todos los idiomas (tildes, eñes, caracteres asiáticos, etc.).
        Sin esto, caracteres especiales como "á", "ñ" o "★" podrían
        mostrarse incorrectamente.
    -->
    <meta charset="UTF-8">

    <!--
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        Configura la vista en dispositivos móviles.
        - width=device-width: El ancho de la página se ajusta al ancho
          de la pantalla del dispositivo.
        - initial-scale=1.0: El nivel de zoom inicial es 1 (sin zoom).
        Sin esta etiqueta, los sitios con Bootstrap se verían diminutos
        en celulares porque el navegador asumiría un ancho de escritorio.
    -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--
        <title>
        Define el título que aparece en la PESTAÑA del navegador.
        También es usado por motores de búsqueda como título del resultado.
    -->
    <title>Catálogo de Videojuegos</title>

    <!--
        <link href="..." rel="stylesheet">
        Enlace a la hoja de estilos CSS de Bootstrap 5.3.0 desde un CDN.
        CDN (Content Delivery Network): Servidores distribuidos globalmente
        que entregan archivos estáticos rápidamente.
        Bootstrap es un framework CSS que proporciona componentes pre-diseñados
        (botones, tarjetas, navegación, grid system, etc.) listos para usar
        mediante clases CSS predefinidas.
    -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<!--
    ==========================================
    SECCIÓN <body>: Contenido visible de la página
    ==========================================
    El atributo class="bg-light" aplica un color de fondo gris claro
    a toda la página (clase de utilidad de Bootstrap).
-->
<body class="bg-light">

    <!--
        ==========================================
        BARRA DE NAVEGACIÓN (Navbar)
        ==========================================
        <nav>: Elemento semántico HTML5 que representa una sección
        de navegación (menú de enlaces).

        Clases Bootstrap aplicadas:
        - navbar: Convierte el <nav> en una barra de navegación de Bootstrap.
        - navbar-expand-lg: En pantallas grandes (>=992px), los elementos
          del menú se expanden horizontalmente. En pantallas más pequeñas
          se colapsan en un botón hamburguesa.
        - navbar-dark: Tema oscuro (letras claras sobre fondo oscuro).
        - bg-dark: Color de fondo oscuro (negro/gris muy oscuro).
    -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">

        <!--
            <div class="container">
            Contenedor de Bootstrap que centra el contenido y le da
            un ancho máximo responsivo (se ajusta según el tamaño de pantalla).
        -->
        <div class="container">

            <!--
                <a class="navbar-brand" href="index.php">
                Enlace que actúa como "marca/logotipo" de la barra de navegación.
                - navbar-brand: Clase Bootstrap que estiliza el nombre de la marca.
                - href="index.php": Al hacer clic, redirige a la página de inicio.
            -->
            <a class="navbar-brand" href="index.php">Reseñas Videojuegos</a>

            <!--
                <div class="collapse navbar-collapse">
                Contenedor que agrupa los elementos que se colapsan
                en pantallas pequeñas (el menú de enlaces).
            -->
            <div class="collapse navbar-collapse">

                <!--
                    <ul class="navbar-nav">
                    Lista no ordenada (unordered list) que contiene los
                    elementos del menú de navegación.
                    - navbar-nav: Clase Bootstrap que estiliza la lista
                      como un menú de navegación horizontal.
                -->
                <ul class="navbar-nav">
                    <!-- Cada <li> es un elemento de la lista (list item) -->
                    <!-- nav-item: Clase Bootstrap para cada elemento del menú -->

                    <!-- Enlace a la página de Inicio -->
                    <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>

                    <!-- Enlace a la página para registrar un nuevo videojuego -->
                    <li class="nav-item"><a class="nav-link" href="registrar_videojuego.php">Registrar Videojuego</a></li>

                    <!-- Enlace a la página para registrar una nueva reseña -->
                    <li class="nav-item"><a class="nav-link" href="registrar_resena.php">Registrar Reseña</a></li>

                    <!--
                        Enlace a ESTA misma página (catálogo).
                        - nav-link active: Clase Bootstrap que resalta el enlace
                          de la página actual (color diferente) para indicar
                          al usuario dónde se encuentra.
                    -->
                    <li class="nav-item"><a class="nav-link active" href="catalogo.php">Ver Catálogo</a></li>

                    <!-- Enlace a la página de estadísticas -->
                    <li class="nav-item"><a class="nav-link" href="estadisticas.php">Estadísticas</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!--
        ==========================================
        CONTENEDOR PRINCIPAL
        ==========================================
        <div class="container mt-4">
        - container: Contenedor responsivo de Bootstrap.
        - mt-4: Margin-top de nivel 4 (espacio superior). Bootstrap tiene
          una escala de 0 a 5 para márgenes y paddings.
    -->
    <div class="container mt-4">

        <!--
            <h2 class="mb-4">
            Encabezado de nivel 2 (título de la sección).
            - mb-4: Margin-bottom de nivel 4 (espacio inferior).
        -->
        <h2 class="mb-4">Catálogo de Videojuegos</h2>

        <!--
            ==========================================
            BLOQUE PHP CONDICIONAL: ¿Hay videojuegos?
            ==========================================

            <?php if (count($lista_videojuegos) > 0): ?>
            - count(): Función PHP que devuelve el número de elementos de un array.
            - Si el array tiene al menos 1 elemento, se ejecuta el bloque de código
              que muestra las tarjetas de videojuegos.
            - Sintaxis alternativa de control de flujo en PHP:
              "if (cond): ... endif;" en lugar de "if (cond) { ... }".
              Esta sintaxis es más legible cuando se mezcla PHP con HTML.
        -->
        <?php if (count($lista_videojuegos) > 0): ?>

            <!--
                ==========================================
                BUCLE foreach: Recorrer cada videojuego
                ==========================================

                foreach ($lista_videojuegos as $videojuego):
                - foreach: Estructura de control que itera (recorre) cada elemento
                  de un array. En cada iteración, el elemento actual se asigna
                  a la variable $videojuego.
                - $lista_videojuegos: Array de videojuegos obtenido de la BD.
                - $videojuego: Variable temporal que contiene un array asociativo
                  con los datos de UN videojuego en cada vuelta del bucle.
                - as: Palabra clave que significa "como" (cada elemento COMO $videojuego).
            -->
            <?php foreach ($lista_videojuegos as $videojuego): ?>

                <!--
                    ==========================================
                    TARJETA (Card) de Bootstrap para cada videojuego
                    ==========================================
                    <div class="card shadow-sm mb-4">
                    - card: Clase Bootstrap que crea una tarjeta (contenedor
                      con bordes redondeados y fondo blanco).
                    - shadow-sm: Aplica una sombra pequeña (efecto de elevación).
                    - mb-4: Margen inferior de nivel 4 para separar las tarjetas.
                -->
                <div class="card shadow-sm mb-4">

                    <!--
                        CABECERA DE LA TARJETA (Card Header)
                        - card-header: Parte superior de la tarjeta.
                        - bg-primary: Fondo de color azul (color primario de Bootstrap).
                        - text-white: Texto blanco.
                        - d-flex: Activa Flexbox (modelo de diseño flexible).
                        - justify-content-between: Distribuye los elementos hijos
                          a los extremos (izquierda y derecha) del contenedor.
                        - align-items-center: Centra verticalmente los elementos hijos.
                    -->
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

                        <!--
                            Nombre del videojuego con htmlspecialchars()
                            - htmlspecialchars(): Función PHP que convierte caracteres
                              especiales HTML (<, >, ", ', &) en entidades HTML
                              (&lt;, &gt;, etc.). Esto PREVIENE ATAQUES XSS
                              (Cross-Site Scripting), donde un usuario malicioso
                              podría inyectar código JavaScript en el nombre.
                            - echo: Imprime (muestra) el valor en la página HTML.
                            - mb-0: Margin-bottom 0 (sin margen inferior).
                        -->
                        <h5 class="mb-0"><?php echo htmlspecialchars($videojuego['nombre']); ?></h5>

                        <!-- Contenedor para las estrellas y la calificación promedio -->
                        <div>
                            <?php
                            /**
                             * ---------------------------------------------
                             * SECCIÓN DE ESTRELLAS DEL PROMEDIO
                             * ---------------------------------------------
                             *
                             * $promedio = (float)$videojuego['promedio_calificacion'];
                             * - (float): Conversión de tipo (type casting) que asegura
                             *   que el valor sea tratado como número decimal.
                             *   Esto garantiza que las comparaciones y el redondeo
                             *   funcionen correctamente.
                             */
                            $promedio = (float)$videojuego['promedio_calificacion'];

                            /**
                             * for ($i = 1; $i <= 5; $i++):
                             * - Bucle for: Estructura de repetición que ejecuta un bloque
                             *   de código un número determinado de veces.
                             * - $i = 1: Inicialización (empieza en 1).
                             * - $i <= 5: Condición (repite mientras $i sea menor o igual a 5).
                             * - $i++: Incremento ($i aumenta en 1 en cada iteración).
                             * - Resultado: El bucle se ejecuta 5 veces (para 5 estrellas).
                             */
                            for ($i = 1; $i <= 5; $i++):
                                /**
                                 * if ($i <= round($promedio)):
                                 * - round(): Función PHP que redondea un número decimal
                                 *   al entero más cercano.
                                 *   Ejemplos: round(3.2) = 3, round(3.8) = 4, round(4.5) = 5.
                                 * - Si el número de estrella actual ($i) es menor o igual
                                 *   al promedio redondeado, mostramos estrella LLENA (★).
                                 * - Si no, mostramos estrella VACÍA (☆).
                                 *
                                 * Ejemplo: Si el promedio es 3.7, round(3.7) = 4.
                                 *   $i=1: 1 <= 4 -> ★
                                 *   $i=2: 2 <= 4 -> ★
                                 *   $i=3: 3 <= 4 -> ★
                                 *   $i=4: 4 <= 4 -> ★
                                 *   $i=5: 5 > 4  -> ☆
                                 * Resultado visual: ★★★★☆
                                 */
                                if ($i <= round($promedio)):
                                    // echo '★': Imprime el carácter Unicode de estrella llena (U+2605)
                                    echo '★';
                                else:
                                    // echo '☆': Imprime el carácter Unicode de estrella vacía (U+2606)
                                    echo '☆';
                                endif;
                            endfor; // Fin del bucle for de estrellas
                            ?>
                            <!--
                                <span class="ms-2">
                                - ms-2: Margin-start (margen izquierdo) de nivel 2.
                                - Muestra el valor numérico del promedio (ej: "4.0 / 5").
                            -->
                            <span class="ms-2"><?php echo $promedio; ?> / 5</span>
                        </div>
                    </div> <!-- Fin del card-header -->

                    <!--
                        CUERPO DE LA TARJETA (Card Body)
                        - card-body: Contenido principal de la tarjeta con padding interior.
                    -->
                    <div class="card-body">

                        <!--
                            ==========================================
                            FILA (Row) con datos del videojuego
                            ==========================================
                            <div class="row mb-3">
                            - row: Clase Bootstrap que crea una fila usando el sistema
                              de grillas (grid system). Bootstrap divide el ancho en
                              12 columnas virtuales.
                            - mb-3: Margin-bottom de nivel 3.

                            Sistema de grillas de Bootstrap:
                            La pantalla se divide en 12 columnas. Las clases col-md-4
                            indican que cada columna ocupa 4 de 12 columnas (1/3 del ancho)
                            en pantallas medianas (md = medium, >=768px) en adelante.
                        -->
                        <div class="row mb-3">

                            <!--
                                Columna 1: Género del videojuego
                                - col-md-4: Ocupa 4/12 columnas en pantallas medianas+
                                - <strong>: Etiqueta HTML que aplica NEGRITA al texto.
                            -->
                            <div class="col-md-4">
                                <strong>Género:</strong> <?php echo htmlspecialchars($videojuego['genero']); ?>
                            </div>

                            <!--
                                Columna 2: Plataforma del videojuego
                            -->
                            <div class="col-md-4">
                                <strong>Plataforma:</strong> <?php echo htmlspecialchars($videojuego['plataforma']); ?>
                            </div>

                            <!--
                                Columna 3: Fecha de lanzamiento del videojuego
                                NOTA: fecha_lanzamiento NO usa htmlspecialchars() porque
                                es un valor de tipo DATE proveniente de la BD y no puede
                                contener caracteres HTML peligrosos.
                            -->
                            <div class="col-md-4">
                                <strong>Lanzamiento:</strong> <?php echo $videojuego['fecha_lanzamiento']; ?>
                            </div>
                        </div> <!-- Fin de la fila de datos del videojuego -->

                        <!--
                            ==========================================
                            MOSTRAR DESCRIPCIÓN (si existe)
                            ==========================================
                            empty(): Función PHP que verifica si una variable está vacía.
                            - Retorna TRUE si la variable: no existe, es NULL, "",
                              array(), 0, "0", o false.
                            - Aquí verifica si el campo 'descripcion' NO está vacío.

                            El signo ! niega el resultado: !empty() = "NO está vacío".
                        -->
                        <?php if (!empty($videojuego['descripcion'])): ?>
                            <!--
                                <p class="text-muted">
                                - <p>: Párrafo HTML.
                                - text-muted: Clase Bootstrap que aplica un color gris
                                  tenue al texto (texto secundario o menos importante).
                            -->
                            <p class="text-muted"><?php echo htmlspecialchars($videojuego['descripcion']); ?></p>
                        <?php endif; ?>

                        <!--
                            ==========================================
                            TOTAL DE RESEÑAS (Badge)
                            ==========================================
                            <p class="mb-1">: Párrafo con margen inferior pequeño (nivel 1).

                            <span class="badge bg-secondary">
                            - badge: Clase Bootstrap que crea una insignia/etiqueta
                              (pequeño rectángulo con bordes redondeados).
                            - bg-secondary: Fondo gris (color secundario de Bootstrap).
                        -->
                        <p class="mb-1">
                            <strong>Total de reseñas:</strong>
                            <span class="badge bg-secondary"><?php echo $videojuego['total_resenas']; ?></span>
                        </p>

                        <!--
                            <hr>
                            Horizontal Rule: Línea horizontal divisoria.
                            Separa visualmente los datos del videojuego
                            de la sección de reseñas.
                        -->
                        <hr>

                        <!-- Subtítulo para la sección de reseñas -->
                        <h6>Reseñas de los usuarios:</h6>

                        <?php
                        /**
                         * ---------------------------------------------
                         * CONSULTA SQL: Obtener reseñas de UN videojuego
                         * ---------------------------------------------
                         *
                         * $id_actual = $videojuego['id'];
                         * - Guardamos el ID del videojuego actual en una variable
                         *   para usarlo como parámetro en la consulta SQL.
                         * - Esto evita acceder repetidamente al array.
                         */
                        $id_actual = $videojuego['id'];

                        /**
                         * $sql_resenas = "SELECT * FROM resenas WHERE videojuego_id = :id ..."
                         * - SELECT *: Selecciona TODAS las columnas de la tabla "resenas".
                         * - WHERE videojuego_id = :id: Filtra SOLO las reseñas que pertenecen
                         *   al videojuego actual. ":id" es un MARCADOR NOMBRADO (placeholder)
                         *   que será reemplazado por el valor real al ejecutar la consulta.
                         * - ORDER BY fecha_registro DESC: Ordena las reseñas por fecha
                         *   de registro de forma DESCENDENTE (más recientes primero).
                         *
                         * IMPORTANTE: Usamos prepare() en lugar de query() porque
                         * la consulta contiene un parámetro variable (:id).
                         * Esto previene INYECCIÓN SQL: el motor de BD trata el valor
                         * como dato, nunca como código SQL ejecutable.
                         */
                        $sql_resenas = "SELECT * FROM resenas WHERE videojuego_id = :id ORDER BY fecha_registro DESC";

                        /**
                         * $conexion_bd->prepare($sql_resenas)
                         * - prepare(): Método PDO que prepara una sentencia SQL para su ejecución.
                         *   Devuelve un objeto PDOStatement.
                         * - El motor de BD analiza y compila la consulta una sola vez,
                         *   lo que es más eficiente si se ejecuta múltiples veces.
                         */
                        $consulta_resenas = $conexion_bd->prepare($sql_resenas);

                        /**
                         * $consulta_resenas->execute([':id' => $id_actual])
                         * - execute(): Método PDOStatement que ejecuta la consulta preparada.
                         * - ['id' => $id_actual]: Array asociativo donde la clave es el
                         *   nombre del marcador (':id') y el valor es el dato que lo reemplaza.
                         *   PDO automáticamente escapa y sanitiza el valor para prevenir
                         *   inyección SQL.
                         */
                        $consulta_resenas->execute([':id' => $id_actual]);

                        /**
                         * $consulta_resenas->fetchAll()
                         * - Recupera todas las reseñas resultantes como un array asociativo.
                         * - Cada elemento del array es una reseña individual con sus datos.
                         */
                        $resenas_del_juego = $consulta_resenas->fetchAll();
                        ?>

                        <!--
                            ==========================================
                            MOSTRAR RESEÑAS (si existen)
                            ==========================================
                            Verificamos si el array de reseñas tiene al menos un elemento.
                        -->
                        <?php if (count($resenas_del_juego) > 0): ?>

                            <!--
                                BUCLE foreach: Recorrer cada reseña del videojuego actual
                                - $resenas_del_juego: Array de reseñas obtenido de la BD.
                                - $resena: Variable que contiene los datos de UNA reseña
                                  en cada iteración del bucle.
                            -->
                            <?php foreach ($resenas_del_juego as $resena): ?>

                                <!--
                                    Contenedor individual para cada reseña
                                    - border: Añade un borde al contenedor.
                                    - rounded: Bordes redondeados.
                                    - p-2: Padding de nivel 2 (espacio interior).
                                    - mb-2: Margen inferior de nivel 2.
                                    - bg-white: Fondo blanco.
                                -->
                                <div class="border rounded p-2 mb-2 bg-white">

                                    <!--
                                        d-flex justify-content-between:
                                        - d-flex: Activa Flexbox.
                                        - justify-content-between: Coloca los elementos
                                          hijos en los extremos opuestos (nombre a la izquierda,
                                          estrellas a la derecha).
                                    -->
                                    <div class="d-flex justify-content-between">
                                        <!-- Nombre del usuario que escribió la reseña (en negrita) -->
                                        <strong><?php echo htmlspecialchars($resena['nombre_usuario']); ?></strong>

                                        <!--
                                            <span class="text-warning">
                                            - text-warning: Color de texto amarillo/naranja
                                              (típico de estrellas de calificación).
                                        -->
                                        <span class="text-warning">
                                            <?php
                                            /**
                                             * ---------------------------------------------
                                             * ESTRELLAS DE LA RESEÑA INDIVIDUAL
                                             * ---------------------------------------------
                                             *
                                             * Bucle for: Itera 5 veces (una por cada estrella posible).
                                             *
                                             * OPERADOR TERNARIO: condicion ? valor_si_verdadero : valor_si_falso
                                             * - Es una forma abreviada de escribir un if-else simple.
                                             * - ($i <= $resena['calificacion']) ? '★' : '☆'
                                             *   Si $i es menor o igual a la calificación de la reseña,
                                             *   muestra estrella llena (★); si no, vacía (☆).
                                             *
                                             * Ejemplo: Si calificacion = 4
                                             *   $i=1: 1 <= 4 -> ★
                                             *   $i=2: 2 <= 4 -> ★
                                             *   $i=3: 3 <= 4 -> ★
                                             *   $i=4: 4 <= 4 -> ★
                                             *   $i=5: 5 > 4  -> ☆
                                             * Resultado visual: ★★★★☆
                                             */
                                            for ($i = 1; $i <= 5; $i++):
                                                echo ($i <= $resena['calificacion']) ? '★' : '☆';
                                            endfor;
                                            ?>
                                            <!-- Muestra la calificación numérica entre paréntesis (ej: "(4/5)") -->
                                            (<?php echo $resena['calificacion']; ?>/5)
                                        </span>
                                    </div>

                                    <!--
                                        ==========================================
                                        MOSTRAR COMENTARIO DE LA RESEÑA (si existe)
                                        ==========================================
                                        Verificamos si el campo 'comentario' NO está vacío.
                                    -->
                                    <?php if (!empty($resena['comentario'])): ?>
                                        <!--
                                            <p class="mb-0 mt-1">
                                            - mb-0: Sin margen inferior.
                                            - mt-1: Margen superior pequeño (nivel 1).
                                        -->
                                        <p class="mb-0 mt-1"><?php echo htmlspecialchars($resena['comentario']); ?></p>
                                    <?php endif; ?>

                                    <!--
                                        ==========================================
                                        FECHA DE LA RESEÑA
                                        ==========================================
                                        <small class="text-muted">
                                        - <small>: Etiqueta HTML que muestra texto con un
                                          tamaño de fuente más pequeño.
                                        - text-muted: Color gris tenue.

                                        date('d/m/Y H:i', strtotime($resena['fecha_registro']))
                                        - strtotime(): Función PHP que convierte una fecha en
                                          formato texto a una marca de tiempo UNIX (timestamp).
                                          Ej: "2024-01-15 14:30:00" -> 1705330200
                                        - date(): Función PHP que formatea una marca de tiempo
                                          UNIX según un formato especificado.
                                          - 'd': Día del mes con dos dígitos (01 a 31).
                                          - 'm': Mes con dos dígitos (01 a 12).
                                          - 'Y': Año con cuatro dígitos (ej: 2024).
                                          - 'H': Hora en formato 24 horas (00 a 23).
                                          - 'i': Minutos con dos dígitos (00 a 59).
                                          Resultado: "15/01/2024 14:30"
                                    -->
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($resena['fecha_registro'])); ?>
                                    </small>
                                </div> <!-- Fin del contenedor de una reseña -->

                            <?php endforeach; ?> <!-- Fin del bucle foreach de reseñas -->

                        <?php else: ?>
                            <!--
                                ==========================================
                                MENSAJE CUANDO NO HAY RESEÑAS
                                ==========================================
                                - text-muted: Color gris tenue.
                                - fst-italic: Fuente en cursiva (font-style: italic).
                                - Mensaje amigable que invita al usuario a ser el primero en opinar.
                            -->
                            <p class="text-muted fst-italic">Este videojuego aún no tiene reseñas. ¡Sé el primero en opinar!</p>
                        <?php endif; ?> <!-- Fin de la verificación de reseñas -->

                    </div> <!-- Fin del card-body -->
                </div> <!-- Fin de la tarjeta (card) del videojuego -->

            <?php endforeach; ?> <!-- Fin del bucle foreach de videojuegos -->

        <?php else: ?>
            <!--
                ==========================================
                MENSAJE CUANDO NO HAY VIDEOJUEGOS REGISTRADOS
                ==========================================
                <div class="alert alert-info">
                - alert: Clase Bootstrap que crea un mensaje de alerta.
                - alert-info: Color azul claro (informativo).

                <a href="registrar_videojuego.php" class="alert-link">
                - alert-link: Clase Bootstrap que estiliza el enlace para que
                  combine con el estilo de la alerta.
                - Enlace que redirige a la página de registro de videojuegos.
            -->
            <div class="alert alert-info">
                No hay videojuegos registrados aún.
                <a href="registrar_videojuego.php" class="alert-link">Registra el primero aquí.</a>
            </div>
        <?php endif; ?> <!-- Fin del if principal (¿hay videojuegos?) -->

    </div> <!-- Fin del contenedor principal (container) -->

    <!--
        ==========================================
        SCRIPT DE JAVASCRIPT DE BOOTSTRAP
        ==========================================
        <script src="...">
        Carga el bundle de JavaScript de Bootstrap desde CDN.
        Este archivo incluye:
        - Bootstrap JS (componentes interactivos: modales, dropdowns, tooltips, etc.)
        - Popper.js (librería para posicionamiento de tooltips y popovers).

        Se coloca al final del <body> para que el HTML se cargue primero
        y la página se muestre más rápido (buena práctica de rendimiento).
    -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

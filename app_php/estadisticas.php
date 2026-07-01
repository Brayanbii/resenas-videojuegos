<?php
// ---------------------------------------------------------------------------
// ARCHIVO: estadisticas.php
// PROPOSITO: Pagina que muestra las estadisticas y rankings obtenidos desde
//            la API de analitica construida con Flask y MongoDB.
//            Consulta dos endpoints:
//              1. /api/estadisticas       -> Datos generales (totales, promedios)
//              2. /api/mejores-videojuegos -> Ranking de los mejores videojuegos
// ---------------------------------------------------------------------------

/**
 * Bloque de documentacion (DocBlock) que describe el archivo.
 * - estadisticas.php: Nombre del archivo actual.
 * - Pagina que muestra las estadisticas obtenidas desde la API Flask.
 * - Consulta los endpoints: /api/estadisticas y /api/mejores-videojuegos
 *   Estos endpoints son proporcionados por el microservicio de analitica.
 */

// require_once: Incluye el archivo funciones_api.php UNA SOLA VEZ.
// Si ya fue incluido antes, PHP no lo vuelve a cargar, evitando errores
// de redeclaracion de funciones.
// funciones_api.php contiene las funciones que se comunican con la API Flask
// (consultar_estadisticas_api, consultar_mejores_videojuegos_api, etc.)
require_once 'funciones_api.php';

// --- LLAMADAS A LA API FLASK ---

// Variable $datos_estadisticas: Almacena el resultado de la consulta al
// endpoint /api/estadisticas de Flask.
// Esta funcion (definida en funciones_api.php) hace una peticion HTTP GET
// a la API y devuelve un array asociativo con datos como:
//   - total_videojuegos: cantidad de videojuegos registrados
//   - total_resenas: cantidad de resenas escritas
//   - promedio_general: calificacion promedio de todas las resenas
//   - mejor_calificado: nombre del videojuego con mejor promedio
//   - mas_resenado: nombre del videojuego con mas resenas
// Si la API no responde, devuelve null.
$datos_estadisticas = consultar_estadisticas_api();

// Variable $mejores_videojuegos: Almacena el resultado de la consulta al
// endpoint /api/mejores-videojuegos de Flask.
// Esta funcion hace una peticion HTTP GET y devuelve un array de arrays
// asociativos, donde cada elemento representa un videojuego con:
//   - nombre_videojuego: nombre del videojuego
//   - genero: genero del videojuego (accion, aventura, etc.)
//   - promedio_calificacion: calificacion promedio (escala 1-5)
//   - total_resenas: numero de resenas recibidas
// Si la API no responde, devuelve null.
$mejores_videojuegos = consultar_mejores_videojuegos_api();
?>

<!-- ===================================================================== -->
<!-- INICIO DEL DOCUMENTO HTML5                                            -->
<!-- La etiqueta <!DOCTYPE html> le indica al navegador que use el         -->
<!-- modo estandar HTML5 para renderizar la pagina.                        -->
<!-- El atributo lang="es" define el idioma del contenido como espanol.    -->
<!-- ===================================================================== -->
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- meta charset="UTF-8": Define la codificacion de caracteres como UTF-8,
         lo que permite mostrar caracteres especiales (tildes, enies, etc.)
         sin problemas. -->
    <meta charset="UTF-8">

    <!-- meta name="viewport": Hace que la pagina sea responsive (adaptable
         a dispositivos moviles). width=device-width ajusta el ancho al del
         dispositivo y initial-scale=1.0 establece el nivel de zoom inicial. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tag <title>: Define el titulo de la pestana del navegador.
         Aparece como "Estadisticas - Analitica". -->
    <title>Estadísticas - Analítica</title>

    <!-- Link a la hoja de estilos CSS de Bootstrap 5.3.0 desde CDN.
         Bootstrap proporciona clases predefinidas para diseno responsivo,
         componentes (cards, navbars, badges, etc.) y utilidades.
         Se carga desde jsdelivr.net para no almacenar el archivo localmente. -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<!-- <body>: Cuerpo del documento HTML. Todo el contenido visible de la
     pagina va aqui dentro.
     La clase "bg-light" de Bootstrap aplica un color de fondo gris claro
     a toda la pagina. -->
<body class="bg-light">

    <!-- ================================================================= -->
    <!-- BARRA DE NAVEGACION (NAVBAR)                                      -->
    <!-- Componente de Bootstrap que proporciona una barra de navegacion   -->
    <!-- responsiva con enlaces a las diferentes secciones de la aplicacion. -->
    <!-- ================================================================= -->
    <!-- <nav>: Elemento semantico HTML5 que agrupa enlaces de navegacion.
         - navbar: clase base de barra de navegacion Bootstrap
         - navbar-expand-lg: la barra se expande horizontalmente en pantallas
           grandes (large) y se colapsa en un menu hamburguesa en pantallas
           mas pequenas
         - navbar-dark: texto claro (blanco) sobre fondo oscuro
         - bg-dark: color de fondo oscuro (negro/casi negro) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <!-- <div class="container">: Contenedor Bootstrap que centra el
             contenido y le da un ancho maximo responsivo. -->
        <div class="container">
            <!-- <a class="navbar-brand">: Enlace que representa la marca/logotipo
                 del sitio. Suele ir a la pagina principal (index.php).
                 navbar-brand le da un estilo destacado (texto mas grande). -->
            <a class="navbar-brand" href="index.php">Reseñas Videojuegos</a>

            <!-- <div class="collapse navbar-collapse">: Contenedor que colapsa
                 los elementos de la barra en pantallas pequenas. Se muestra/oculta
                 con el boton hamburguesa (no implementado aqui). -->
            <div class="collapse navbar-collapse">
                <!-- <ul class="navbar-nav">: Lista no ordenada que contiene los
                     items de navegacion. navbar-nav alinea los items
                     horizontalmente en la barra. -->
                <ul class="navbar-nav">
                    <!-- Cada <li class="nav-item"> es un elemento de la barra
                         de navegacion. -->
                    <li class="nav-item">
                        <!-- Enlace a la pagina de inicio (index.php).
                             nav-link aplica el estilo de enlace de navegacion. -->
                        <a class="nav-link" href="index.php">Inicio</a>
                    </li>

                    <li class="nav-item">
                        <!-- Enlace a la pagina para registrar un nuevo videojuego -->
                        <a class="nav-link" href="registrar_videojuego.php">Registrar Videojuego</a>
                    </li>

                    <li class="nav-item">
                        <!-- Enlace a la pagina para registrar una nueva resena -->
                        <a class="nav-link" href="registrar_resena.php">Registrar Reseña</a>
                    </li>

                    <li class="nav-item">
                        <!-- Enlace a la pagina del catalogo de videojuegos -->
                        <a class="nav-link" href="catalogo.php">Ver Catálogo</a>
                    </li>

                    <li class="nav-item">
                        <!-- Enlace a ESTA MISMA pagina (estadisticas.php).
                             La clase "active" de Bootstrap resalta visualmente
                             el enlace para indicar que es la pagina actual. -->
                        <a class="nav-link active" href="estadisticas.php">Estadísticas</a>
                    </li>
                </ul>
                <!-- Fin de la lista de navegacion -->
            </div>
            <!-- Fin del contenedor colapsable -->
        </div>
        <!-- Fin del contenedor -->
    </nav>
    <!-- Fin de la barra de navegacion -->

    <!-- ================================================================= -->
    <!-- CONTENIDO PRINCIPAL                                               -->
    <!-- <div class="container mt-4">: Contenedor Bootstrap centrado con   -->
    <!-- margen superior (mt-4 = margin-top nivel 4, aprox. 1.5rem).       -->
    <!-- ================================================================= -->
    <div class="container mt-4">
        <!-- <h2>: Encabezado de nivel 2. Titulo principal de la seccion.
             mb-4: margen inferior (margin-bottom) nivel 4. -->
        <h2 class="mb-4">Estadísticas y Analítica</h2>

        <!-- <p class="text-muted">: Parrafo con texto en color gris tenue
             (muted) que indica la fuente de los datos mostrados. -->
        <p class="text-muted">Datos obtenidos desde el servicio de analítica (Flask + MongoDB).</p>

        <!-- <div class="row">: Fila del sistema de grilla (grid) de Bootstrap.
             Contiene columnas (col) que se distribuyen horizontalmente.
             En pantallas medianas (md) o mas grandes, las columnas se alinean
             lado a lado. En pantallas pequenas se apilan verticalmente. -->
        <div class="row">

            <!-- ============================================================= -->
            <!-- SECCION 1: ESTADISTICAS GENERALES                             -->
            <!-- col-md-6: Ocupa 6 de las 12 columnas de Bootstrap en pantallas -->
            <!-- medianas (>=768px) o mas grandes (50% del ancho).             -->
            <!-- mb-4: margen inferior para separar secciones al apilarse.     -->
            <!-- ============================================================= -->
            <div class="col-md-6 mb-4">
                <!-- <div class="card">: Componente Card de Bootstrap.
                     Crea una tarjeta con bordes redondeados y sombra.
                     shadow-sm: sombra pequena para dar efecto de elevacion.
                     h-100: altura 100% del contenedor padre (para igualar
                     alturas entre tarjetas lado a lado). -->
                <div class="card shadow-sm h-100">
                    <!-- <div class="card-header">: Cabecera de la tarjeta.
                         bg-success: fondo verde (Bootstrap color de exito).
                         text-white: texto en color blanco. -->
                    <div class="card-header bg-success text-white">
                        <!-- <h5 class="mb-0">: Encabezado nivel 5 sin margen
                             inferior (mb-0) para ajustarlo bien al header. -->
                        <h5 class="mb-0">Resumen de Estadísticas</h5>
                    </div>

                    <!-- <div class="card-body">: Cuerpo de la tarjeta donde
                         va el contenido principal. -->
                    <div class="card-body">
                        <!-- ================================================= -->
                        <!-- PHP: CONDICIONAL QUE VERIFICA SI HAY DATOS       -->
                        <!-- Si $datos_estadisticas NO es null (la API        -->
                        <!-- respondio correctamente), se muestra la lista    -->
                        <!-- de estadisticas.                                 -->
                        <!-- ================================================= -->
                        <?php if ($datos_estadisticas !== null): ?>
                            <!-- <ul class="list-group list-group-flush">:
                                 Lista no ordenada con estilo de Bootstrap.
                                 list-group-flush elimina bordes laterales para
                                 que la lista se integre visualmente con la card. -->
                            <ul class="list-group list-group-flush">

                                <!-- Item 1: Total de videojuegos registrados -->
                                <!-- d-flex: contenedor flexbox.
                                     justify-content-between: distribuye el espacio
                                     entre los elementos (uno a la izquierda, otro a la derecha). -->
                                <li class="list-group-item d-flex justify-content-between">
                                    <!-- <span>: Contenedor en linea generico. Texto descriptivo. -->
                                    <span>Total de videojuegos registrados:</span>
                                    <!-- <strong>: Texto en negrita (bold).
                                         <?php echo ... ?>: Imprime el valor de la variable PHP.
                                         $datos_estadisticas['total_videojuegos']: Accede al campo
                                         'total_videojuegos' del array asociativo.
                                         ?? 'N/D': Operador de fusion null (PHP 7+).
                                         Si el valor a la izquierda es null o no existe,
                                         se usa 'N/D' (No Disponible) como valor por defecto. -->
                                    <strong><?php echo $datos_estadisticas['total_videojuegos'] ?? 'N/D'; ?></strong>
                                </li>

                                <!-- Item 2: Total de resenas -->
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Total de reseñas:</span>
                                    <!-- Imprime el total de resenas desde la API.
                                         Si no existe, muestra 'N/D'. -->
                                    <strong><?php echo $datos_estadisticas['total_resenas'] ?? 'N/D'; ?></strong>
                                </li>

                                <!-- Item 3: Calificacion promedio general -->
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Calificación promedio general:</span>
                                    <!-- Muestra el promedio general seguido de " / 5"
                                         para indicar la escala de calificacion (1 a 5). -->
                                    <strong><?php echo $datos_estadisticas['promedio_general'] ?? 'N/D'; ?> / 5</strong>
                                </li>

                                <!-- Item 4: Videojuego mejor calificado -->
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Videojuego mejor calificado:</span>
                                    <!-- htmlspecialchars(): Funcion de PHP que convierte
                                         caracteres especiales (como <, >, &, ") en entidades
                                         HTML para prevenir ataques XSS (Cross-Site Scripting).
                                         Es una medida de seguridad esencial al mostrar datos
                                         que vienen de una fuente externa (API, base de datos). -->
                                    <strong><?php echo htmlspecialchars($datos_estadisticas['mejor_calificado'] ?? 'N/D'); ?></strong>
                                </li>

                                <!-- Item 5: Videojuego mas resenado -->
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Videojuego más reseñado:</span>
                                    <!-- Nuevamente se usa htmlspecialchars() por seguridad. -->
                                    <strong><?php echo htmlspecialchars($datos_estadisticas['mas_resenado'] ?? 'N/D'); ?></strong>
                                </li>
                            </ul>
                            <!-- Fin de la lista de estadisticas -->

                        <!-- ================================================= -->
                        <!-- PHP: BLOQUE else - SI LA API NO RESPONDE          -->
                        <!-- Se ejecuta cuando $datos_estadisticas es null     -->
                        <!-- (la API Flask no esta disponible o hubo error).   -->
                        <!-- ================================================= -->
                        <?php else: ?>
                            <!-- <div class="alert alert-warning">:
                                 Componente Alert de Bootstrap.
                                 alert-warning: fondo amarillo de advertencia.
                                 mb-0: sin margen inferior para que no haya
                                 espacio extra dentro de la card. -->
                            <div class="alert alert-warning mb-0">
                                <!-- Mensaje informativo para el usuario indicando
                                     que no se pudo conectar con el servicio de
                                     analitica y sugiriendo verificar la API Flask. -->
                                No se pudo conectar con el servicio de analítica.
                                Asegúrese de que la API Flask esté en ejecución.
                            </div>
                        <?php endif; ?>
                        <!-- Fin del condicional if/else de $datos_estadisticas -->

                    </div>
                    <!-- Fin del card-body -->
                </div>
                <!-- Fin de la tarjeta -->
            </div>
            <!-- Fin de la columna de estadisticas generales -->

            <!-- ============================================================= -->
            <!-- SECCION 2: TOP MEJORES VIDEOJUEGOS                            -->
            <!-- col-md-6: Ocupa la otra mitad (50%) en pantallas medianas+.   -->
            <!-- ============================================================= -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <!-- Cabecera de la tarjeta con color de advertencia (amarillo).
                         bg-warning: fondo amarillo Bootstrap.
                         text-dark: texto en color oscuro (negro) para contraste
                         sobre el fondo amarillo. -->
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Top - Mejores Videojuegos</h5>
                    </div>

                    <!-- Cuerpo de la tarjeta del ranking -->
                    <div class="card-body">
                        <!-- ================================================= -->
                        <!-- PHP: CONDICIONAL VERIFICANDO EL RANKING           -->
                        <!-- Se muestran los datos SOLO si:                    -->
                        <!-- 1. $mejores_videojuegos NO es null (API respondio) -->
                        <!-- 2. count($mejores_videojuegos) > 0 (hay al menos  -->
                        <!--    un videojuego en el ranking)                    -->
                        <!-- El operador && (AND logico) exige ambas condiciones. -->
                        <!-- ================================================= -->
                        <?php if ($mejores_videojuegos !== null && count($mejores_videojuegos) > 0): ?>
                            <!-- <ol class="list-group list-group-numbered">:
                                 Lista ORDENADA (numerada) de Bootstrap.
                                 list-group-numbered: agrega numeracion automatica
                                 (1, 2, 3...) a cada elemento de la lista. -->
                            <ol class="list-group list-group-numbered">

                                <!-- ============================================= -->
                                <!-- BUCLE foreach: RECORRE EL ARRAY DEL RANKING   -->
                                <!-- foreach ($mejores_videojuegos as $indice => $juego):
                                     Itera sobre cada elemento del array $mejores_videojuegos.
                                     - $indice: clave del elemento (0, 1, 2, ...)
                                     - $juego: valor del elemento (array asociativo
                                       con datos del videojuego: nombre_videojuego,
                                       genero, promedio_calificacion, total_resenas)
                                     El bucle se ejecuta una vez por cada videojuego. -->
                                <?php foreach ($mejores_videojuegos as $indice => $juego): ?>
                                    <!-- Cada <li> representa un videojuego en el ranking.
                                         align-items-start: alinea los elementos al
                                         inicio vertical (util para contenido multilinea). -->
                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                        <!-- <div class="ms-2 me-auto">:
                                             ms-2: margen izquierdo (margin-start) nivel 2.
                                             me-auto: margen derecho automatico (empuja el
                                             badge hacia la derecha). -->
                                        <div class="ms-2 me-auto">
                                            <!-- <div class="fw-bold">: Texto en negrita
                                                 (font-weight bold).
                                                 Muestra el nombre del videojuego.
                                                 Si el campo no existe, muestra 'Sin nombre'. -->
                                            <div class="fw-bold"><?php echo htmlspecialchars($juego['nombre_videojuego'] ?? 'Sin nombre'); ?></div>

                                            <!-- Muestra el genero del videojuego.
                                                 Si el campo no existe, muestra cadena vacia. -->
                                            <?php echo htmlspecialchars($juego['genero'] ?? ''); ?>
                                        </div>

                                        <!-- <span class="badge bg-primary rounded-pill">:
                                             Componente Badge (insignia/etiqueta) de Bootstrap.
                                             bg-primary: fondo azul.
                                             rounded-pill: bordes completamente redondeados
                                             (forma de pastilla).
                                             Muestra la calificacion promedio y el numero
                                             de resenas del videojuego. -->
                                        <span class="badge bg-primary rounded-pill">
                                            <!-- Promedio de calificacion sobre 5.
                                                 ?? '0': Si no existe el campo, muestra 0. -->
                                            <?php echo $juego['promedio_calificacion'] ?? '0'; ?> / 5
                                            <!-- Numero de resenas entre parentesis.
                                                 ?? 0: Si no existe el campo, muestra 0. -->
                                            (<?php echo $juego['total_resenas'] ?? 0; ?> reseñas)
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                                <!-- Fin del bucle foreach -->

                            </ol>
                            <!-- Fin de la lista ordenada -->

                        <!-- ================================================= -->
                        <!-- PHP: elseif - SI LA API NO RESPONDE (null)        -->
                        <!-- ================================================= -->
                        <?php elseif ($mejores_videojuegos === null): ?>
                            <!-- Alerta de advertencia igual que en la seccion
                                 anterior: no se pudo conectar a la API Flask. -->
                            <div class="alert alert-warning mb-0">
                                No se pudo conectar con el servicio de analítica.
                            </div>

                        <!-- ================================================= -->
                        <!-- PHP: else - LA API RESPONDE PERO NO HAY DATOS     -->
                        <!-- Se ejecuta cuando $mejores_videojuegos NO es null -->
                        <!-- pero count($mejores_videojuegos) es 0 (array vacio). -->
                        <!-- Significa que la API funciono pero no hay ranking. -->
                        <!-- ================================================= -->
                        <?php else: ?>
                            <!-- <p class="text-muted mb-0">: Parrafo con texto
                                 gris tenue sin margen inferior.
                                 Informa al usuario que no hay datos de ranking
                                 disponibles todavia. -->
                            <p class="text-muted mb-0">No hay datos de rankings disponibles aún.</p>
                        <?php endif; ?>
                        <!-- Fin del condicional if/elseif/else del ranking -->

                    </div>
                    <!-- Fin del card-body -->
                </div>
                <!-- Fin de la tarjeta -->
            </div>
            <!-- Fin de la columna del ranking -->

        </div>
        <!-- Fin de la fila (row) -->

    </div>
    <!-- Fin del contenedor principal -->

    <!-- ===================================================================== -->
    <!-- SCRIPT DE JAVASCRIPT DE BOOTSTRAP                                    -->
    <!-- Incluye el bundle de JavaScript de Bootstrap 5.3.0 desde CDN.         -->
    <!-- El bundle incluye Popper.js (para tooltips, dropdowns, etc.) y        -->
    <!-- los componentes JS de Bootstrap (colapso de navbar, modales, etc.).   -->
    <!-- Se coloca al final del <body> para no bloquear la carga de la pagina. -->
    <!-- ===================================================================== -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
<!-- Fin del cuerpo del documento -->

</html>
<!-- Fin del documento HTML -->

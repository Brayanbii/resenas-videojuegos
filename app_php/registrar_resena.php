<?php
/**
 * =============================================================================
 * registrar_resena.php
 * -----------------------------------------------------------------------------
 * PROPOSITO DEL ARCHIVO:
 * Este archivo contiene un formulario web que permite a los usuarios registrar
 * una reseña (opinión y calificación) sobre un videojuego que ya existe en la
 * base de datos. El proceso funciona de la siguiente manera:
 *
 * 1. Muestra un formulario HTML con campos para seleccionar un videojuego,
 *    ingresar el nombre de usuario, una calificación de 1 a 5 estrellas y
 *    un comentario opcional.
 *
 * 2. Cuando el usuario envía el formulario (método POST), el mismo archivo
 *    procesa los datos: los valida, los guarda en la base de datos MySQL a
 *    través de PDO y además envía la información a una API externa construida
 *    con Flask (Python) para fines de analítica y estadísticas.
 *
 * 3. Muestra mensajes de éxito o error según el resultado del proceso.
 *
 * FLUJO DE EJECUCIÓN:
 *    Cliente (navegador) -> POST -> PHP valida -> INSERT en MySQL -> envía a
 *    API Flask -> muestra resultado al usuario.
 * =============================================================================
 */

// ---------------------------------------------------------------------------
// INCLUSIÓN DE ARCHIVOS EXTERNOS
// ---------------------------------------------------------------------------

// require_once: incluye el archivo de conexión a la base de datos SOLO UNA VEZ.
// Si ya fue incluido antes, no lo vuelve a cargar. Esto evita errores de
// redeclaración de variables o funciones.
// 'conexion.php' contiene la variable $conexion_bd (objeto PDO) que nos permite
// comunicarnos con la base de datos MySQL.
require_once 'conexion.php';

// require_once: incluye el archivo con funciones auxiliares para comunicarnos
// con la API de Flask (servicio externo de analítica).
// 'funciones_api.php' contiene la función enviar_resena_a_api() que usaremos
// más adelante para enviar los datos de la reseña al servicio de analítica.
require_once 'funciones_api.php';

// ---------------------------------------------------------------------------
// DECLARACIÓN DE VARIABLES PARA MENSAJES Y CONTROL DE ERRORES
// ---------------------------------------------------------------------------

// $mensaje: cadena de texto que almacena el mensaje que se mostrará al usuario.
// Puede contener un mensaje de éxito (reseña registrada) o de error.
// Se inicializa como cadena vacía para que no se muestre nada al cargar la
// página por primera vez (antes de enviar el formulario).
$mensaje = '';

// $tipo_mensaje: cadena que define el estilo visual del mensaje en Bootstrap.
// Los valores posibles son: 'success' (verde, para éxito), 'danger' (rojo, para
// error). Se concatena a la clase CSS 'alert alert-'.
$tipo_mensaje = '';

// $errores: arreglo (array) asociativo que almacena los mensajes de error de
// validación. La clave es el nombre del campo (ej: 'videojuego_id') y el valor
// es el mensaje de error. Si el arreglo está vacío, significa que no hay
// errores de validación.
$errores = [];

// ===========================================================================
// PROCESAMIENTO DEL FORMULARIO: Esta sección solo se ejecuta cuando el
// formulario es enviado mediante el método POST.
// ===========================================================================

// $_SERVER: es una variable superglobal de PHP que contiene información sobre
// el servidor y la solicitud HTTP actual.
// $_SERVER['REQUEST_METHOD']: contiene el método HTTP usado para acceder a la
// página ('GET', 'POST', 'PUT', etc.).
// Operador ===: comparación estricta (compara valor Y tipo de dato).
// Si el método es POST, significa que el usuario envió el formulario.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // =======================================================================
    // PASO 1: OBTENCIÓN DE DATOS DEL FORMULARIO
    // =======================================================================

    // $_POST: variable superglobal que contiene los datos enviados por el
    // formulario a través del método POST. Es un arreglo asociativo donde las
    // claves son los nombres de los campos (atributo 'name' en el HTML).

    // trim(): función de PHP que elimina espacios en blanco (espacios,
    // tabulaciones, saltos de línea) del INICIO y del FINAL de una cadena.
    // Se usa para evitar que el usuario envíe solo espacios.

    // El operador ?? (null coalescing) asigna un valor por defecto si la
    // variable o índice del arreglo no existe (es null o no está definido).
    // En este caso, si $_POST['videojuego_id'] no existe, asigna '' (vacío).

    // (string) convierte explícitamente el valor a cadena de texto.

    // $videojuego_id: almacena el ID del videojuego seleccionado por el
    // usuario en el campo <select> del formulario.
    $videojuego_id = trim($_POST['videojuego_id'] ?? '');

    // $nombre_usuario: almacena el nombre de usuario ingresado en el campo
    // <input type="text"> del formulario.
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');

    // $calificacion: almacena la calificación seleccionada por el usuario
    // en el campo <select> del formulario (valor del 1 al 5).
    $calificacion = trim($_POST['calificacion'] ?? '');

    // $comentario: almacena el comentario (reseña escrita) ingresado por el
    // usuario en el campo <textarea> del formulario.
    $comentario = trim($_POST['comentario'] ?? '');

    // ===================================================================
    // PASO 2: VALIDACIONES POR CAMPO
    // Se verifica que cada campo cumpla con las reglas establecidas.
    // Si un campo no pasa la validación, se guarda un mensaje de error
    // en el arreglo $errores usando el nombre del campo como clave.
    // ===================================================================

    // -------------------------------------------------------------------
    // VALIDACIÓN: videojuego_id
    // Reglas:
    //   - No puede estar vacío (el usuario debe seleccionar un juego)
    //   - Debe ser un valor numérico
    //   - Debe ser un entero positivo (mayor o igual a 1)
    //   - Debe existir en la tabla 'videojuegos' de la base de datos
    // -------------------------------------------------------------------

    // empty(): función de PHP que verifica si una variable está vacía.
    // Devuelve TRUE si la variable es: "", 0, "0", null, false, array().
    // Aquí se usa para verificar si el usuario no seleccionó ningún videojuego.
    if (empty($videojuego_id)) {
        // Si el campo está vacío, agregamos un mensaje de error al arreglo.
        $errores['videojuego_id'] = 'Debe seleccionar un videojuego.';

    // is_numeric(): función de PHP que verifica si una variable es un número
    // o una cadena numérica (ej: "5", 5, "3.14" devuelven TRUE).
    // (int): convierte explícitamente el valor a un número entero (casting).
    // El operador || (OR lógico) ejecuta la segunda condición SOLO si la
    // primera es falsa (cortocircuito).
    // Verificamos que el ID sea numérico Y que sea mayor o igual a 1.
    } elseif (!is_numeric($videojuego_id) || (int)$videojuego_id < 1) {
        // El operador ! (NOT lógico) invierte el valor booleano.
        // Si is_numeric() es FALSE, !is_numeric() es TRUE -> entra al bloque.
        $errores['videojuego_id'] = 'La seleccion de videojuego no es válida.';
    } else {
        // Si el ID es numérico y positivo, verificamos que realmente exista
        // un videojuego con ese ID en la base de datos.

        // $conexion_bd: objeto PDO (PHP Data Objects) que representa la
        // conexión a la base de datos MySQL. Fue creado en 'conexion.php'.
        //
        // prepare(): método del objeto PDO que prepara una consulta SQL para
        // su ejecución. Retorna un objeto PDOStatement.
        // Usamos marcadores de parámetros (:id) en lugar de concatenar valores
        // directamente en la consulta. Esto previene inyección SQL.
        //
        // La consulta busca un registro en la tabla 'videojuegos' donde la
        // columna 'id' coincida con el valor del parámetro :id.
        $verificar = $conexion_bd->prepare("SELECT id, nombre FROM videojuegos WHERE id = :id");

        // execute(): método de PDOStatement que ejecuta la consulta preparada.
        // Recibe un arreglo asociativo donde las claves son los nombres de los
        // parámetros (incluyendo los dos puntos ':') y los valores son los
        // datos que reemplazan a esos parámetros.
        //
        // (int)$videojuego_id: convertimos el ID a entero para asegurar que
        // sea del tipo correcto en la base de datos.
        $verificar->execute([':id' => (int)$videojuego_id]);

        // rowCount(): método de PDOStatement que devuelve el número de filas
        // afectadas o devueltas por la consulta SELECT.
        // Si es 0, no se encontró ningún videojuego con ese ID.
        if ($verificar->rowCount() === 0) {
            // El operador === (idéntico) verifica igualdad de valor Y tipo.
            // 0 === 0 es TRUE, pero "0" === 0 es FALSE.
            $errores['videojuego_id'] = 'El videojuego seleccionado no existe.';
        }
    }

    // -------------------------------------------------------------------
    // VALIDACIÓN: nombre_usuario
    // Reglas:
    //   - No puede estar vacío (es obligatorio)
    //   - Debe tener al menos 3 caracteres de longitud
    //   - No debe superar los 150 caracteres (límite de la base de datos)
    // -------------------------------------------------------------------

    // Verificamos si el campo está vacío.
    if (empty($nombre_usuario)) {
        $errores['nombre_usuario'] = 'El nombre de usuario es obligatorio.';

    // strlen(): función de PHP que devuelve la longitud (número de caracteres)
    // de una cadena de texto. Incluye espacios y caracteres especiales.
    // Verificamos que tenga al menos 3 caracteres.
    } elseif (strlen($nombre_usuario) < 3) {
        // El operador < (menor que) compara si la longitud es inferior a 3.
        $errores['nombre_usuario'] = 'El nombre debe tener al menos 3 caracteres.';

    // Verificamos que no exceda los 150 caracteres.
    } elseif (strlen($nombre_usuario) > 150) {
        // El operador > (mayor que) compara si la longitud supera 150.
        $errores['nombre_usuario'] = 'El nombre no debe superar los 150 caracteres.';
    }

    // -------------------------------------------------------------------
    // VALIDACIÓN: calificacion
    // Reglas:
    //   - No puede estar vacía (es obligatorio)
    //   - Debe ser un valor numérico
    //   - Debe estar en el rango de 1 a 5 (inclusive)
    // -------------------------------------------------------------------

    // Verificamos si el campo está vacío.
    if (empty($calificacion)) {
        $errores['calificacion'] = 'La calificación es obligatoria.';

    // Verificamos que sea numérica y esté entre 1 y 5.
    // Los operadores lógicos se evalúan de izquierda a derecha con
    // cortocircuito (si la primera condición es falsa, no evalúa el resto).
    } elseif (!is_numeric($calificacion) || (int)$calificacion < 1 || (int)$calificacion > 5) {
        // El operador || (OR lógico): si CUALQUIERA de las tres condiciones es
        // verdadera, se ejecuta este bloque.
        $errores['calificacion'] = 'La calificación debe ser un número entre 1 y 5.';
    }

    // -------------------------------------------------------------------
    // VALIDACIÓN: comentario (campo OPCIONAL)
    // Reglas:
    //   - Si se proporciona, no debe superar los 2000 caracteres
    //   - Si está vacío, no hay error (es opcional)
    // -------------------------------------------------------------------

    // El operador ! (NOT lógico) antes de empty(): "si NO está vacío".
    // Solo validamos la longitud si el usuario escribió algo.
    // El operador && (AND lógico): AMBAS condiciones deben ser verdaderas.
    if (!empty($comentario) && strlen($comentario) > 2000) {
        $errores['comentario'] = 'El comentario no debe superar los 2000 caracteres.';
    }

    // ===================================================================
    // PASO 3: INSERCIÓN EN LA BASE DE DATOS (si no hay errores)
    // ===================================================================

    // empty($errores): verifica si el arreglo de errores está vacío.
    // Si NO hay mensajes de error, procedemos a guardar la reseña.
    if (empty($errores)) {
        // try-catch: estructura de control para manejo de excepciones.
        // El código dentro de 'try' se ejecuta normalmente. Si ocurre una
        // excepción (error), la ejecución salta al bloque 'catch'.
        try {
            // -------------------------------------------------------------------
            // PREPARACIÓN DE LA CONSULTA SQL INSERT
            // -------------------------------------------------------------------

            // $sql: variable que contiene la sentencia SQL en formato de cadena.
            // INSERT INTO: comando SQL para insertar un nuevo registro en una tabla.
            // Los marcadores (:videojuego_id, :nombre_usuario, etc.) son
            // parámetros nombrados que serán reemplazados por los valores reales
            // al ejecutar la consulta. Esto previene inyección SQL.
            //
            // La tabla 'resenas' tiene las columnas: videojuego_id, nombre_usuario,
            // calificacion, comentario. El campo 'id' es autoincremental, así que
            // no necesitamos especificarlo; MySQL lo genera automáticamente.
            $sql = "INSERT INTO resenas (videojuego_id, nombre_usuario, calificacion, comentario)
                    VALUES (:videojuego_id, :nombre_usuario, :calificacion, :comentario)";

            // $conexion_bd->prepare($sql): preparamos la consulta SQL.
            // PDO analiza y compila la consulta del lado del servidor de base
            // de datos. Esto es más eficiente y seguro que ejecutar SQL crudo.
            $consulta = $conexion_bd->prepare($sql);

            // -------------------------------------------------------------------
            // VINCULACIÓN DE PARÁMETROS
            // -------------------------------------------------------------------

            // bindParam(): método de PDOStatement que vincula (asocia) una
            // variable PHP a un parámetro de la consulta SQL.
            //
            // Primer argumento: nombre del parámetro en la consulta (con ':').
            // Segundo argumento: variable PHP que contiene el valor.
            // Tercer argumento (opcional): tipo de dato PDO.
            //
            // Ventaja de bindParam: la variable se pasa por REFERENCIA, lo que
            // significa que si cambia antes de execute(), se usará el nuevo valor.

            // PDO::PARAM_INT: constante de PDO que indica que el parámetro es
            // un número entero (integer). PDO tratará el valor como INT al
            // enviarlo a la base de datos.
            $consulta->bindParam(':videojuego_id', $videojuego_id, PDO::PARAM_INT);

            // bindParam sin tercer argumento: por defecto, PDO trata el
            // parámetro como cadena de texto (PDO::PARAM_STR).
            $consulta->bindParam(':nombre_usuario', $nombre_usuario);

            // PDO::PARAM_INT: la calificación es un número entero (1 a 5).
            $consulta->bindParam(':calificacion', $calificacion, PDO::PARAM_INT);

            // bindParam para el comentario: se envía como cadena de texto.
            $consulta->bindParam(':comentario', $comentario);

            // -------------------------------------------------------------------
            // EJECUCIÓN DE LA CONSULTA
            // -------------------------------------------------------------------

            // execute(): ejecuta la consulta preparada con los parámetros
            // vinculados. En este punto, los datos se envían realmente a la
            // base de datos MySQL y se inserta el nuevo registro.
            // Retorna TRUE si la ejecución fue exitosa, FALSE si falló.
            $consulta->execute();

            // ===============================================================
            // PASO 4: ENVÍO DE DATOS A LA API FLASK (ANALÍTICA)
            // ===============================================================

            // Volvemos a ejecutar la consulta de verificación que preparamos
            // antes para obtener el NOMBRE del videojuego (ya que la API de
            // Flask también necesita este dato para su procesamiento).
            // Reutilizamos la variable $verificar del paso de validación.
            $verificar->execute([':id' => (int)$videojuego_id]);

            // fetch(): método de PDOStatement que obtiene la siguiente fila
            // de los resultados como un arreglo asociativo.
            // Las claves del arreglo son los nombres de las columnas de la
            // tabla (en este caso: 'id' y 'nombre').
            // Solo esperamos UNA fila porque el ID es único.
            $datos_videojuego = $verificar->fetch();

            // Accedemos al valor de la columna 'nombre' del arreglo asociativo
            // usando la sintaxis $arreglo['clave'].
            $nombre_videojuego = $datos_videojuego['nombre'];

            // enviar_resena_a_api(): función definida en 'funciones_api.php'.
            // Esta función envía una petición HTTP a la API de Flask con:
            //   - $videojuego_id: ID del videojuego reseñado
            //   - $calificacion: calificación otorgada (1-5)
            //   - $nombre_videojuego: nombre del videojuego para referencia
            // La API de Flask recibe estos datos y los procesa para generar
            // estadísticas y análisis de las reseñas.
            $resultado_api = enviar_resena_a_api($videojuego_id, $calificacion, $nombre_videojuego);

            // Establecemos el mensaje de éxito que se mostrará al usuario.
            // El operador . (punto) concatena (une) cadenas de texto en PHP.
            $mensaje = "¡Reseña registrada correctamente! Se enviaron datos a la API de analítica.";

            // Definimos el tipo de mensaje como 'success' para que Bootstrap
            // muestre un recuadro verde (clase CSS: alert-success).
            $tipo_mensaje = 'success';

        // Bloque catch: se ejecuta SOLO si ocurre una excepción dentro del
        // bloque try. La excepción se captura en la variable especificada.
        //
        // PDOException: clase de excepción específica de PDO que se lanza
        // cuando ocurre un error en operaciones de base de datos (conexión
        // fallida, error de sintaxis SQL, violación de restricciones, etc.).
        //
        // $error_insercion: variable que almacena la excepción capturada.
        } catch (PDOException $error_insercion) {

            // getMessage(): método de la clase Exception (heredado por
            // PDOException) que devuelve el mensaje descriptivo del error.
            // Concatenamos el mensaje genérico con el mensaje específico.
            $mensaje = "Error al registrar la reseña: " . $error_insercion->getMessage();

            // Tipo 'danger': Bootstrap mostrará un recuadro rojo indicando
            // que ocurrió un error (clase CSS: alert-danger).
            $tipo_mensaje = 'danger';
        }
    } else {
        // Si el arreglo $errores NO está vacío (hay errores de validación),
        // mostramos un mensaje genérico pidiendo al usuario que corrija.
        $mensaje = "Por favor corrija los errores indicados.";
        $tipo_mensaje = 'danger';
    }
}

// ===========================================================================
// CARGA DE DATOS PARA EL FORMULARIO
// ===========================================================================

// Esta consulta se ejecuta SIEMPRE (independientemente de si el formulario
// fue enviado o no) porque necesitamos la lista de videojuegos disponibles
// para llenar el campo <select> del formulario HTML.

// $conexion_bd->query(): método de PDO que ejecuta una consulta SQL y
// devuelve un objeto PDOStatement con los resultados.
// A diferencia de prepare() + execute(), query() se usa para consultas
// simples que no requieren parámetros ni vinculación.
//
// Consulta SQL: selecciona id, nombre y plataforma de todos los videojuegos
// ordenados alfabéticamente por nombre (ORDER BY nombre ASC).
// ASC: orden ascendente (A-Z, 1-9). Por defecto MySQL ordena ASC si no se
// especifica dirección.
//
// $lista_videojuegos: objeto PDOStatement que contiene TODOS los registros
// de la tabla videojuegos. Se recorrerá con while() + fetch() en el HTML.
$lista_videojuegos = $conexion_bd->query("SELECT id, nombre, plataforma FROM videojuegos ORDER BY nombre ASC");
?>
<!--
===============================================================================
INICIO DEL DOCUMENTO HTML
===============================================================================
-->

<!-- Declaración DOCTYPE: indica al navegador que este documento usa HTML5 -->
<!DOCTYPE html>
<!-- Atributo lang="es": define el idioma principal como español -->
<html lang="es">
<head>
    <!-- meta charset="UTF-8": define la codificación de caracteres como UTF-8,
         lo que permite mostrar caracteres especiales como tildes y eñes. -->
    <meta charset="UTF-8">
    <!-- meta viewport: configura la visualización en dispositivos móviles.
         width=device-width: el ancho de la página se ajusta al ancho del
         dispositivo. initial-scale=1.0: el nivel de zoom inicial es 1 (100%). -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título de la pestaña del navegador -->
    <title>Registrar Reseña</title>
    <!-- Enlace CSS: importa Bootstrap 5.3 desde un CDN (Content Delivery Network).
         Esto proporciona estilos predefinidos para botones, formularios, etc. -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<!-- Clase bg-light de Bootstrap: establece un fondo gris claro para toda la página. -->
<body class="bg-light">

    <!-- ======================================================================= -->
    <!-- BARRA DE NAVEGACIÓN (NAVBAR) -->
    <!-- navegación superior con enlaces a las diferentes secciones de la app -->
    <!-- ======================================================================= -->

    <!-- <nav>: etiqueta HTML5 que define una sección de navegación.
         Clases Bootstrap:
         - navbar: componente base de barra de navegación
         - navbar-expand-lg: se expande horizontalmente en pantallas grandes (>=992px)
         - navbar-dark: texto claro sobre fondo oscuro
         - bg-dark: color de fondo oscuro (negro/gris) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <!-- container: centra el contenido y aplica padding lateral adaptativo -->
        <div class="container">
            <!-- navbar-brand: logotipo o nombre de la aplicación, normalmente
                 enlaza a la página principal (index.php) -->
            <a class="navbar-brand" href="index.php">Reseñas Videojuegos</a>
            <!-- collapse navbar-collapse: agrupa los enlaces que se colapsan en
                 un menú hamburguesa en pantallas pequeñas -->
            <div class="collapse navbar-collapse">
                <!-- navbar-nav: lista de enlaces de navegación -->
                <ul class="navbar-nav">
                    <!-- Cada <li> es un elemento de la lista. nav-item: aplica
                         estilos de navegación a cada elemento. -->
                    <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="registrar_videojuego.php">Registrar Videojuego</a></li>
                    <!-- nav-link active: indica que esta es la página actual
                         (se resalta visualmente en la barra) -->
                    <li class="nav-item"><a class="nav-link active" href="registrar_resena.php">Registrar Reseña</a></li>
                    <li class="nav-item"><a class="nav-link" href="catalogo.php">Ver Catálogo</a></li>
                    <li class="nav-item"><a class="nav-link" href="estadisticas.php">Estadísticas</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ======================================================================= -->
    <!-- CONTENIDO PRINCIPAL -->
    <!-- ======================================================================= -->

    <!-- container: centra el contenido con un ancho máximo adaptativo.
         mt-4: margin-top (margen superior) de nivel 4 (aproximadamente 1.5rem) -->
    <div class="container mt-4">

        <!-- Título principal de la página. mb-4: margin-bottom de nivel 4 -->
        <h2 class="mb-4">Registrar Nueva Reseña</h2>

        <!-- =================================================================== -->
        <!-- BLOQUE DE MENSAJES DE ÉXITO O ERROR -->
        <!-- =================================================================== -->

        <!-- Sintaxis alternativa de PHP para estructuras de control en HTML:
             ':' inicia el bloque, 'endif;' lo cierra. Es más legible que las
             llaves {} cuando se mezcla PHP con HTML. -->

        <!-- if (!empty($mensaje)): verifica si la variable $mensaje tiene
             contenido. Si está vacía, no se muestra nada. -->
        <?php if (!empty($mensaje)): ?>
            <!-- Alert de Bootstrap: componente para mostrar mensajes al usuario.
                 alert-dismissible: permite cerrar la alerta con un botón X.
                 fade show: animación de aparición/desaparición.
                 role="alert": atributo de accesibilidad para lectores de pantalla.

                 echo $tipo_mensaje: imprime el tipo de mensaje ('success' o
                 'danger'), que se concatena con 'alert-' para formar la clase
                 CSS completa (alert-success o alert-danger).
                 Bootstrap aplica color verde para success y rojo para danger. -->
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <!-- echo $mensaje: imprime el texto del mensaje de éxito o error -->
                <?php echo $mensaje; ?>
                <!-- btn-close: botón X para cerrar la alerta.
                     data-bs-dismiss="alert": atributo de Bootstrap que permite
                     cerrar la alerta al hacer clic sin necesidad de JavaScript. -->
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- =================================================================== -->
        <!-- FORMULARIO DE REGISTRO DE RESEÑA -->
        <!-- =================================================================== -->

        <!-- card: componente de Bootstrap que agrupa contenido en un recuadro
             con bordes redondeados y sombra.
             shadow-sm: sombra pequeña para dar relieve al recuadro. -->
        <div class="card shadow-sm">
            <!-- card-body: área interna de la card con padding -->
            <div class="card-body">
                <!-- <form>: etiqueta HTML que define un formulario.
                     method="POST": los datos se envían en el cuerpo de la
                     petición HTTP (más seguro y sin límite de longitud vs GET).
                     action="registrar_resena.php": el formulario se envía a sí
                     mismo (este mismo archivo procesa los datos).
                     novalidate: desactiva la validación nativa del navegador
                     para usar nuestra propia validación en PHP y Bootstrap. -->
                <form method="POST" action="registrar_resena.php" novalidate>

                    <!-- =========================================================== -->
                    <!-- CAMPO: SELECCIÓN DE VIDEOJUEGO -->
                    <!-- =========================================================== -->

                    <!-- mb-3: margin-bottom de nivel 3 (1rem) entre campos -->
                    <div class="mb-3">
                        <!-- <label>: etiqueta descriptiva del campo.
                             for="videojuego_id": vincula el label con el elemento
                             que tiene id="videojuego_id" (accesibilidad).
                             form-label: clase Bootstrap para estilizar labels. -->
                        <label for="videojuego_id" class="form-label">Videojuego *</label>

                        <!-- <select>: campo de selección desplegable (dropdown).
                             form-select: clase Bootstrap que estiliza el select.
                             Operador ternario condensado en HTML:
                               isset($errores['videojuego_id']) ? 'is-invalid' : ''
                             Esto significa: si existe un error para este campo,
                             agrega la clase 'is-invalid' (borde rojo de error);
                             de lo contrario, agrega una cadena vacía. -->
                        <select class="form-select <?php echo isset($errores['videojuego_id']) ? 'is-invalid' : ''; ?>"
                                id="videojuego_id" name="videojuego_id">
                            <!-- Opción por defecto: no tiene valor (value="") y
                                 actúa como placeholder para que el usuario sepa
                                 que debe seleccionar algo. -->
                            <option value="">-- Seleccione un videojuego --</option>

                            <!-- Bucle while: recorre el objeto PDOStatement
                                 $lista_videojuegos fila por fila.
                                 fetch(): obtiene la siguiente fila como arreglo
                                 asociativo. Cuando no hay más filas, fetch()
                                 devuelve FALSE y el bucle termina.
                                 En cada iteración, $juego contiene los datos
                                 de un videojuego: $juego['id'], ['nombre'],
                                 ['plataforma']. -->
                            <?php while ($juego = $lista_videojuegos->fetch()): ?>
                                <!-- Creamos una opción del select por cada
                                     videojuego en la base de datos. -->

                                <!-- value="<?php echo $juego['id']; ?>":
                                     el valor que se enviará al servidor cuando
                                     se seleccione esta opción. -->

                                <!-- Operador ternario para mantener la selección
                                     después de enviar el formulario:
                                     Si $videojuego_id está definido (el usuario
                                     ya seleccionó uno) Y coincide con el ID
                                     actual, agrega el atributo 'selected' que
                                     marca esta opción como seleccionada.

                                     Esto es importante para no perder la
                                     selección cuando hay errores de validación. -->
                                <option value="<?php echo $juego['id']; ?>"
                                    <?php echo (isset($videojuego_id) && $videojuego_id == $juego['id']) ? 'selected' : ''; ?>>
                                    <!-- htmlspecialchars(): función de seguridad
                                         que convierte caracteres especiales HTML
                                         (<, >, ", ', &) en entidades HTML.
                                         Previene ataques XSS (Cross-Site Scripting)
                                         si el nombre o plataforma contienen
                                         caracteres maliciosos. -->
                                    <?php echo htmlspecialchars($juego['nombre']) . ' (' . htmlspecialchars($juego['plataforma']) . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <!-- Mensaje de error de validación para este campo -->
                        <!-- isset(): verifica si la variable o índice del arreglo
                             EXISTE y NO es null. Si existe el error para este
                             campo, mostramos el mensaje en un div con la clase
                             invalid-feedback de Bootstrap (texto rojo pequeño
                             debajo del campo). -->
                        <?php if (isset($errores['videojuego_id'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['videojuego_id']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- =========================================================== -->
                    <!-- CAMPO: NOMBRE DE USUARIO -->
                    <!-- =========================================================== -->

                    <div class="mb-3">
                        <label for="nombre_usuario" class="form-label">Tu Nombre de Usuario *</label>
                        <!-- <input type="text">: campo de texto de una sola línea.
                             form-control: clase Bootstrap que da estilo al input.
                             Operador ternario: agrega 'is-invalid' si hay error.
                             maxlength="150": atributo HTML que limita la cantidad
                             máxima de caracteres que el usuario puede escribir
                             en el navegador. Igual que la validación PHP. -->
                        <input type="text" class="form-control <?php echo isset($errores['nombre_usuario']) ? 'is-invalid' : ''; ?>"
                               id="nombre_usuario" name="nombre_usuario" maxlength="150"
                               <!-- value: establece el valor inicial del campo.
                                    htmlspecialchars($nombre_usuario): sanitiza
                                    el valor para prevenir XSS.
                                    Si $nombre_usuario está definido (el usuario
                                    ya escribió algo), se mantiene en el campo.
                                    Si no, se muestra vacío. -->
                               value="<?php echo isset($nombre_usuario) ? htmlspecialchars($nombre_usuario) : ''; ?>"
                               placeholder="Ej: GamerFan123">
                        <?php if (isset($errores['nombre_usuario'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['nombre_usuario']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- =========================================================== -->
                    <!-- CAMPO: CALIFICACIÓN -->
                    <!-- =========================================================== -->

                    <div class="mb-3">
                        <label for="calificacion" class="form-label">Calificación * (1 a 5 estrellas)</label>
                        <select class="form-select <?php echo isset($errores['calificacion']) ? 'is-invalid' : ''; ?>"
                                id="calificacion" name="calificacion">
                            <option value="">-- Seleccione una calificación --</option>

                            <!-- Bucle for: genera opciones del 1 al 5.
                                 for ($i = 1; $i <= 5; $i++):
                                 - Inicialización: $i empieza en 1
                                 - Condición: mientras $i sea menor o igual a 5
                                 - Incremento: $i++ aumenta $i en 1 cada iteración
                                 Se ejecutará 5 veces (i=1,2,3,4,5). -->
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <!-- value="<?php echo $i; ?>": el valor enviado
                                     al servidor (1,2,3,4,5). -->

                                <!-- Operador ternario para mantener la calificación
                                     seleccionada después de un envío con errores:
                                     compara la calificación enviada con el valor
                                     actual del bucle. -->
                                <option value="<?php echo $i; ?>"
                                    <?php echo (isset($calificacion) && $calificacion == $i) ? 'selected' : ''; ?>>
                                    <!-- Operador ternario en PHP dentro del HTML:
                                         Genera texto singular o plural:
                                         Si i > 1, escribe 's' (plural: estrellas).
                                         Si i == 1, escribe '' (singular: estrella).
                                         Resultado: "1 estrella", "2 estrellas", etc. -->
                                    <?php echo $i . ' estrella' . ($i > 1 ? 's' : ''); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <?php if (isset($errores['calificacion'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['calificacion']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- =========================================================== -->
                    <!-- CAMPO: COMENTARIO -->
                    <!-- =========================================================== -->

                    <div class="mb-3">
                        <label for="comentario" class="form-label">Comentario</label>
                        <!-- <textarea>: campo de texto multilínea.
                             rows="4": altura inicial de 4 líneas visibles.
                             maxlength="2000": límite de 2000 caracteres.
                             El texto del comentario se coloca ENTRE las etiquetas
                             de apertura y cierre de <textarea> (no en value como
                             en <input>). -->
                        <textarea class="form-control <?php echo isset($errores['comentario']) ? 'is-invalid' : ''; ?>"
                                  id="comentario" name="comentario" rows="4" maxlength="2000"
                                  placeholder="Escribe tu opinión sobre el juego..."><?php echo isset($comentario) ? htmlspecialchars($comentario) : ''; ?></textarea>
                        <?php if (isset($errores['comentario'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['comentario']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- =========================================================== -->
                    <!-- BOTONES DE ENVÍO Y CANCELACIÓN -->
                    <!-- =========================================================== -->

                    <!-- <button type="submit">: botón que ENVÍA el formulario.
                         btn y btn-success: clases Bootstrap.
                         btn: estilos base de botón (bordes, padding, etc.).
                         btn-success: color verde (indica acción positiva). -->
                    <button type="submit" class="btn btn-success">Registrar Reseña</button>

                    <!-- <a>: enlace (<a> estilizado como botón) que redirige
                         al usuario a la página principal.
                         btn btn-secondary: botón gris.
                         ms-2: margin-start (margen izquierdo) de nivel 2. -->
                    <a href="index.php" class="btn btn-secondary ms-2">Cancelar</a>
                </form>
            </div>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- SCRIPT DE JAVASCRIPT DE BOOTSTRAP -->
    <!-- ======================================================================= -->

    <!-- Importa el JavaScript de Bootstrap 5.3 desde CDN.
         bundle: incluye Bootstrap JS + Popper.js (necesario para tooltips,
         dropdowns, etc.) usando un solo archivo.
         Se coloca al final del <body> para que el HTML se cargue primero y
         la página se muestre más rápido. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

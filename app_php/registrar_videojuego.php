<?php
/**
 * ============================================================
 * registrar_videojuego.php
 * Formulario para registrar un nuevo videojuego en PostgreSQL.
 * ============================================================
 *
 * PROPOSITO:
 * Permite al usuario ingresar los datos de un videojuego
 * (nombre, genero, plataforma, descripcion, fecha de lanzamiento)
 * y guardarlos en la base de datos PostgreSQL.
 *
 * FLUJO DE LA PAGINA:
 * 1. GET (primera visita): Muestra el formulario vacio.
 * 2. POST (envio del formulario): Valida los datos, si todo
 *    esta bien los guarda en PostgreSQL y muestra mensaje de exito.
 *    Si hay errores, recarga el formulario con los mensajes de error.
 *
 * VALIDACIONES INCLUIDAS:
 * - Campos obligatorios: nombre, genero, plataforma, fecha
 * - Longitud maxima: nombre (200 chars), descripcion (2000 chars)
 * - Formato de fecha: valida que sea una fecha real (checkdate)
 *
 * SEGURIDAD:
 * - Consultas preparadas con bindParam() para evitar SQL injection
 * - htmlspecialchars() para evitar XSS al mostrar datos del usuario
 * - Validacion del lado del servidor (nunca confiar solo en HTML5)
 */

// ------------------------------------------------------------
// CONEXION A LA BASE DE DATOS
// require_once asegura que el archivo solo se incluya una vez
// Si ya se incluyo antes, no lo vuelve a cargar
// ------------------------------------------------------------
require_once 'conexion.php';

// ------------------------------------------------------------
// VARIABLES DE ESTADO
// Estas variables controlan que mensajes se muestran al usuario
// ------------------------------------------------------------
$mensaje = '';       // Texto del mensaje (exito o error)
$tipo_mensaje = '';  // Tipo de mensaje: 'success' (verde) o 'danger' (rojo)
$errores = [];       // Array asociativo con errores por campo
                     // Ejemplo: ['nombre' => 'El nombre es obligatorio']

// ------------------------------------------------------------
// PROCESAMIENTO DEL FORMULARIO (METODO POST)
// $_SERVER['REQUEST_METHOD'] contiene el metodo HTTP usado
// Solo procesamos datos si el formulario fue enviado (POST)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --------------------------------------------------------
    // PASO 1: OBTENER DATOS DEL FORMULARIO
    // $_POST es una superglobal que contiene los datos enviados
    // por el formulario con method="POST".
    //
    // trim(): elimina espacios en blanco al inicio y final.
    // Evita que el usuario registre solo espacios.
    //
    // El operador ?? (null coalescing) asigna un valor por defecto
    // si la variable no existe. Ejemplo: $_POST['nombre'] ?? ''
    // significa: si existe $_POST['nombre'], usalo; si no, usa ''.
    // --------------------------------------------------------
    $nombre = trim($_POST['nombre'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $plataforma = trim($_POST['plataforma'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_lanzamiento = trim($_POST['fecha_lanzamiento'] ?? '');

    // --------------------------------------------------------
    // PASO 2: VALIDACIONES POR CAMPO
    // Cada campo se valida de forma independiente.
    // Los errores se almacenan en el array $errores usando
    // el nombre del campo como clave.
    //
    // empty(): devuelve true si la variable es:
    //   - "" (cadena vacia)
    //   - 0, "0", null, false, array vacio
    // strlen(): devuelve la longitud de una cadena
    // --------------------------------------------------------

    // --- Validacion: NOMBRE DEL VIDEOJUEGO ---
    // Reglas: obligatorio, minimo 2 caracteres, maximo 200
    if (empty($nombre)) {
        $errores['nombre'] = 'El nombre del videojuego es obligatorio.';
    } elseif (strlen($nombre) < 2) {
        $errores['nombre'] = 'El nombre debe tener al menos 2 caracteres.';
    } elseif (strlen($nombre) > 200) {
        $errores['nombre'] = 'El nombre no debe superar los 200 caracteres.';
    }

    // --- Validacion: GENERO ---
    // Reglas: obligatorio (no puede estar vacio)
    if (empty($genero)) {
        $errores['genero'] = 'El género es obligatorio.';
    }

    // --- Validacion: PLATAFORMA ---
    // Reglas: obligatorio
    if (empty($plataforma)) {
        $errores['plataforma'] = 'La plataforma es obligatoria.';
    }

    // --- Validacion: DESCRIPCION (opcional) ---
    // Reglas: si se llena, maximo 2000 caracteres
    if (!empty($descripcion) && strlen($descripcion) > 2000) {
        $errores['descripcion'] = 'La descripción no debe superar los 2000 caracteres.';
    }

    // --- Validacion: FECHA DE LANZAMIENTO ---
    // Reglas: obligatorio, formato de fecha valida
    if (empty($fecha_lanzamiento)) {
        $errores['fecha_lanzamiento'] = 'La fecha de lanzamiento es obligatoria.';
    } else {
        // Validamos el formato YYYY-MM-DD
        // explode() divide la cadena por el caracter '-'
        // Ejemplo: '2024-06-15' -> ['2024', '06', '15']
        $partes_fecha = explode('-', $fecha_lanzamiento);

        // checkdate(mes, dia, año): funcion de PHP que valida
        // si una fecha existe en el calendario gregoriano.
        // Ejemplo: checkdate(2, 29, 2024) = true (año bisiesto)
        //          checkdate(2, 29, 2023) = false
        // (int) convierte string a entero para la validacion
        if (count($partes_fecha) !== 3 ||
            !checkdate((int)$partes_fecha[1], (int)$partes_fecha[2], (int)$partes_fecha[0])) {
            $errores['fecha_lanzamiento'] = 'La fecha no es válida. Use el formato AAAA-MM-DD.';
        }
    }

    // --------------------------------------------------------
    // PASO 3: INSERCION EN LA BASE DE DATOS
    // Solo insertamos si NO hay errores de validacion
    // empty($errores) = true cuando el array esta vacio
    // --------------------------------------------------------
    if (empty($errores)) {
        try {
            // --- CONSULTA PREPARADA ---
            // Los marcadores :nombre, :genero, etc. son placeholders
            // que se reemplazan de forma segura con bindParam().
            // Esto previene INYECCION SQL: el usuario no puede
            // modificar la estructura de la consulta porque los
            // datos se envian separados de la estructura SQL.
            $sql = "INSERT INTO videojuegos (nombre, genero, plataforma, descripcion, fecha_lanzamiento)
                    VALUES (:nombre, :genero, :plataforma, :descripcion, :fecha_lanzamiento)";

            // prepare() envia la estructura SQL al motor de BD
            $consulta = $conexion_bd->prepare($sql);

            // bindParam() asocia cada marcador con una variable PHP
            // PDO envia los datos de forma segura, escapando
            // caracteres peligrosos automaticamente
            $consulta->bindParam(':nombre', $nombre);
            $consulta->bindParam(':genero', $genero);
            $consulta->bindParam(':plataforma', $plataforma);
            $consulta->bindParam(':descripcion', $descripcion);
            $consulta->bindParam(':fecha_lanzamiento', $fecha_lanzamiento);

            // execute() ejecuta la consulta con los datos vinculados
            $consulta->execute();

            // Si llegamos aqui, la insercion fue exitosa
            $mensaje = "¡Videojuego '$nombre' registrado correctamente!";
            $tipo_mensaje = 'success';

        } catch (PDOException $error_insercion) {
            // Si hay un error de BD (ej: violacion de restriccion),
            // capturamos la excepcion y mostramos el error
            $mensaje = "Error al registrar: " . $error_insercion->getMessage();
            $tipo_mensaje = 'danger';
        }
    } else {
        // Si hay errores de validacion, mostramos un mensaje general
        $mensaje = "Por favor corrija los errores indicados.";
        $tipo_mensaje = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Videojuego</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- ============================================================
    BARRA DE NAVEGACION
    ============================================================ -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Reseñas Videojuegos</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link active" href="registrar_videojuego.php">Registrar Videojuego</a></li>
                    <li class="nav-item"><a class="nav-link" href="registrar_resena.php">Registrar Reseña</a></li>
                    <li class="nav-item"><a class="nav-link" href="catalogo.php">Ver Catálogo</a></li>
                    <li class="nav-item"><a class="nav-link" href="estadisticas.php">Estadísticas</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ============================================================
    FORMULARIO DE REGISTRO
    ============================================================ -->
    <div class="container mt-4">
        <h2 class="mb-4">Registrar Nuevo Videojuego</h2>

        <!-- --- Mensaje de exito o error --- -->
        <!-- Solo se muestra si $mensaje NO esta vacio -->
        <?php if (!empty($mensaje)): ?>
            <!--
            alert: clase base de alerta Bootstrap
            alert-success: fondo verde (exito)
            alert-danger: fondo rojo (error)
            alert-dismissible: permite cerrar la alerta con el boton X
            fade show: animacion de aparicion
            -->
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <!-- btn-close: boton X para cerrar, data-bs-dismiss="alert" cierra la alerta -->
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- --- Tarjeta del formulario --- -->
        <div class="card shadow-sm">
            <div class="card-body">
                <!--
                method="POST": envia los datos de forma oculta (no en la URL)
                action="registrar_videojuego.php": se envia a si mismo
                novalidate: desactiva validacion HTML5 nativa para usar solo nuestras validaciones PHP
                -->
                <form method="POST" action="registrar_videojuego.php" novalidate>

                    <!-- ============================================
                    CAMPO: NOMBRE
                    ============================================
                    is-invalid: clase Bootstrap que pone borde rojo si hay error
                    htmlspecialchars(): convierte caracteres especiales
                    (<, >, ", ') en entidades HTML para prevenir XSS
                    ============================================ -->
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Videojuego *</label>
                        <input type="text"
                               class="form-control <?php echo isset($errores['nombre']) ? 'is-invalid' : ''; ?>"
                               id="nombre"
                               name="nombre"
                               maxlength="200"
                               value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>"
                               placeholder="Ej: The Legend of Zelda">
                        <!-- invalid-feedback: muestra el mensaje de error debajo del campo -->
                        <?php if (isset($errores['nombre'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['nombre']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- ============================================
                    CAMPO: GENERO (SELECT)
                    ============================================
                    Usamos <select> para limitar las opciones.
                    Esto ayuda a mantener datos consistentes (evita
                    que escriban "accion", "Acción", "acción", etc.)
                    ============================================ -->
                    <div class="mb-3">
                        <label for="genero" class="form-label">Género *</label>
                        <select class="form-select <?php echo isset($errores['genero']) ? 'is-invalid' : ''; ?>"
                                id="genero" name="genero">
                            <option value="">-- Seleccione un género --</option>
                            <?php
                            // Lista de generos predefinidos para mantener consistencia
                            $lista_generos = ['Acción', 'Aventura', 'RPG', 'RPG de Acción',
                                              'Shooter', 'Deportes', 'Estrategia', 'Terror',
                                              'Simulación', 'Plataformas'];
                            foreach ($lista_generos as $gen):
                                // selected: mantiene la seleccion del usuario si hay error en otro campo
                                $seleccionado = (isset($genero) && $genero === $gen) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $gen; ?>" <?php echo $seleccionado; ?>>
                                    <?php echo $gen; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errores['genero'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['genero']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- ============================================
                    CAMPO: PLATAFORMA (SELECT)
                    ============================================ -->
                    <div class="mb-3">
                        <label for="plataforma" class="form-label">Plataforma *</label>
                        <select class="form-select <?php echo isset($errores['plataforma']) ? 'is-invalid' : ''; ?>"
                                id="plataforma" name="plataforma">
                            <option value="">-- Seleccione una plataforma --</option>
                            <?php
                            $lista_plataformas = ['PC', 'PS5', 'PS4', 'Xbox Series X', 'Nintendo Switch', 'Móvil'];
                            foreach ($lista_plataformas as $plat):
                                $seleccionado = (isset($plataforma) && $plataforma === $plat) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $plat; ?>" <?php echo $seleccionado; ?>>
                                    <?php echo $plat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errores['plataforma'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['plataforma']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- ============================================
                    CAMPO: DESCRIPCION (TEXTAREA, opcional)
                    ============================================ -->
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control <?php echo isset($errores['descripcion']) ? 'is-invalid' : ''; ?>"
                                  id="descripcion" name="descripcion" rows="4" maxlength="2000"
                                  placeholder="Describe el videojuego..."><?php echo isset($descripcion) ? htmlspecialchars($descripcion) : ''; ?></textarea>
                        <?php if (isset($errores['descripcion'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['descripcion']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- ============================================
                    CAMPO: FECHA DE LANZAMIENTO (DATE PICKER)
                    ============================================
                    type="date": input especial de HTML5 que muestra
                    un selector de fecha nativo del navegador.
                    ============================================ -->
                    <div class="mb-4">
                        <label for="fecha_lanzamiento" class="form-label">Fecha de Lanzamiento *</label>
                        <input type="date"
                               class="form-control <?php echo isset($errores['fecha_lanzamiento']) ? 'is-invalid' : ''; ?>"
                               id="fecha_lanzamiento" name="fecha_lanzamiento"
                               value="<?php echo isset($fecha_lanzamiento) ? htmlspecialchars($fecha_lanzamiento) : ''; ?>">
                        <?php if (isset($errores['fecha_lanzamiento'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['fecha_lanzamiento']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Botones de accion -->
                    <button type="submit" class="btn btn-primary">Registrar Videojuego</button>
                    <a href="index.php" class="btn btn-secondary ms-2">Cancelar</a>
                    <!-- ms-2: margin-start (izquierda) 2, separa los botones -->
                </form>
            </div>
        </div>

        <!-- ============================================================
        TABLA DE VIDEOJUEGOS REGISTRADOS (VISTA PREVIA)
        Muestra los videojuegos que ya existen en la base de datos
        para que el usuario vea lo que hay registrado.
        ============================================================ -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Videojuegos Registrados</h5>
            </div>
            <div class="card-body">
                <?php
                // Consulta SQL para obtener todos los videojuegos
                // ORDER BY fecha_registro DESC: los mas recientes primero
                $consulta_lista = $conexion_bd->query("SELECT * FROM videojuegos ORDER BY fecha_registro DESC");

                // rowCount(): devuelve el numero de filas obtenidas
                if ($consulta_lista->rowCount() > 0):
                ?>
                <!-- table-responsive: permite scroll horizontal en pantallas pequeñas -->
                <div class="table-responsive">
                    <!-- table-striped: filas alternas con colores -->
                    <!-- table-hover: resalta la fila al pasar el mouse -->
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Género</th>
                                <th>Plataforma</th>
                                <th>Lanzamiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($fila = $consulta_lista->fetch()): ?>
                            <tr>
                                <td><?php echo $fila['id']; ?></td>
                                <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($fila['genero']); ?></td>
                                <td><?php echo htmlspecialchars($fila['plataforma']); ?></td>
                                <td><?php echo $fila['fecha_lanzamiento']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-muted">No hay videojuegos registrados aún.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

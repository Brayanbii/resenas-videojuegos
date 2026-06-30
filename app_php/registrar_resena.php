<?php
/**
 * registrar_resena.php
 * Formulario para registrar una resena de un videojuego existente.
 * Al registrar la resena, tambien envia datos a la API Flask.
 */

// Incluimos la conexion a la base de datos
require_once 'conexion.php';
// Incluimos las funciones para comunicarnos con la API Flask
require_once 'funciones_api.php';

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';
$errores = [];

/**
 * Procesamos el formulario cuando se envia por POST
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---------------------------------------------------
    // 1. Obtenemos los datos del formulario
    // ---------------------------------------------------
    $videojuego_id = trim($_POST['videojuego_id'] ?? '');
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $calificacion = trim($_POST['calificacion'] ?? '');
    $comentario = trim($_POST['comentario'] ?? '');

    // ---------------------------------------------------
    // 2. VALIDACIONES POR CAMPO
    // ---------------------------------------------------

    // Validacion: videojuego_id (debe ser un numero entero positivo)
    if (empty($videojuego_id)) {
        $errores['videojuego_id'] = 'Debe seleccionar un videojuego.';
    } elseif (!is_numeric($videojuego_id) || (int)$videojuego_id < 1) {
        $errores['videojuego_id'] = 'La seleccion de videojuego no es válida.';
    } else {
        // Verificamos que el videojuego exista en la base de datos
        $verificar = $conexion_bd->prepare("SELECT id, nombre FROM videojuegos WHERE id = :id");
        $verificar->execute([':id' => (int)$videojuego_id]);
        if ($verificar->rowCount() === 0) {
            $errores['videojuego_id'] = 'El videojuego seleccionado no existe.';
        }
    }

    // Validacion: nombre de usuario
    if (empty($nombre_usuario)) {
        $errores['nombre_usuario'] = 'El nombre de usuario es obligatorio.';
    } elseif (strlen($nombre_usuario) < 3) {
        $errores['nombre_usuario'] = 'El nombre debe tener al menos 3 caracteres.';
    } elseif (strlen($nombre_usuario) > 150) {
        $errores['nombre_usuario'] = 'El nombre no debe superar los 150 caracteres.';
    }

    // Validacion: calificacion (debe ser un numero entre 1 y 5)
    if (empty($calificacion)) {
        $errores['calificacion'] = 'La calificación es obligatoria.';
    } elseif (!is_numeric($calificacion) || (int)$calificacion < 1 || (int)$calificacion > 5) {
        $errores['calificacion'] = 'La calificación debe ser un número entre 1 y 5.';
    }

    // Validacion: comentario (opcional, con limite)
    if (!empty($comentario) && strlen($comentario) > 2000) {
        $errores['comentario'] = 'El comentario no debe superar los 2000 caracteres.';
    }

    // ---------------------------------------------------
    // 3. Si no hay errores, insertamos en la base de datos
    // ---------------------------------------------------
    if (empty($errores)) {
        try {
            // Preparamos la consulta SQL para insertar la resena
            $sql = "INSERT INTO resenas (videojuego_id, nombre_usuario, calificacion, comentario)
                    VALUES (:videojuego_id, :nombre_usuario, :calificacion, :comentario)";

            $consulta = $conexion_bd->prepare($sql);

            // Vinculamos los parametros
            $consulta->bindParam(':videojuego_id', $videojuego_id, PDO::PARAM_INT);
            $consulta->bindParam(':nombre_usuario', $nombre_usuario);
            $consulta->bindParam(':calificacion', $calificacion, PDO::PARAM_INT);
            $consulta->bindParam(':comentario', $comentario);

            // Ejecutamos la insercion
            $consulta->execute();

            // ---------------------------------------------------
            // 4. Enviamos los datos a la API Flask para analitica
            //    Obtenemos el nombre del videojuego para enviarlo
            // ---------------------------------------------------
            $verificar->execute([':id' => (int)$videojuego_id]);
            $datos_videojuego = $verificar->fetch();
            $nombre_videojuego = $datos_videojuego['nombre'];

            $resultado_api = enviar_resena_a_api($videojuego_id, $calificacion, $nombre_videojuego);

            // Mensaje de exito
            $mensaje = "¡Reseña registrada correctamente! Se enviaron datos a la API de analítica.";
            $tipo_mensaje = 'success';

        } catch (PDOException $error_insercion) {
            $mensaje = "Error al registrar la reseña: " . $error_insercion->getMessage();
            $tipo_mensaje = 'danger';
        }
    } else {
        $mensaje = "Por favor corrija los errores indicados.";
        $tipo_mensaje = 'danger';
    }
}

// Cargamos la lista de videojuegos para el campo select
$lista_videojuegos = $conexion_bd->query("SELECT id, nombre, plataforma FROM videojuegos ORDER BY nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Reseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Barra de navegacion -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Reseñas Videojuegos</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="registrar_videojuego.php">Registrar Videojuego</a></li>
                    <li class="nav-item"><a class="nav-link active" href="registrar_resena.php">Registrar Reseña</a></li>
                    <li class="nav-item"><a class="nav-link" href="catalogo.php">Ver Catálogo</a></li>
                    <li class="nav-item"><a class="nav-link" href="estadisticas.php">Estadísticas</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Registrar Nueva Reseña</h2>

        <!-- Mensaje de exito o error -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario de registro de resena -->
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="registrar_resena.php" novalidate>

                    <!-- Campo: Seleccion de videojuego -->
                    <div class="mb-3">
                        <label for="videojuego_id" class="form-label">Videojuego *</label>
                        <select class="form-select <?php echo isset($errores['videojuego_id']) ? 'is-invalid' : ''; ?>"
                                id="videojuego_id" name="videojuego_id">
                            <option value="">-- Seleccione un videojuego --</option>
                            <?php while ($juego = $lista_videojuegos->fetch()): ?>
                                <option value="<?php echo $juego['id']; ?>"
                                    <?php echo (isset($videojuego_id) && $videojuego_id == $juego['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($juego['nombre']) . ' (' . htmlspecialchars($juego['plataforma']) . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <?php if (isset($errores['videojuego_id'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['videojuego_id']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Campo: Nombre de usuario -->
                    <div class="mb-3">
                        <label for="nombre_usuario" class="form-label">Tu Nombre de Usuario *</label>
                        <input type="text" class="form-control <?php echo isset($errores['nombre_usuario']) ? 'is-invalid' : ''; ?>"
                               id="nombre_usuario" name="nombre_usuario" maxlength="150"
                               value="<?php echo isset($nombre_usuario) ? htmlspecialchars($nombre_usuario) : ''; ?>"
                               placeholder="Ej: GamerFan123">
                        <?php if (isset($errores['nombre_usuario'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['nombre_usuario']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Campo: Calificación -->
                    <div class="mb-3">
                        <label for="calificacion" class="form-label">Calificación * (1 a 5 estrellas)</label>
                        <select class="form-select <?php echo isset($errores['calificacion']) ? 'is-invalid' : ''; ?>"
                                id="calificacion" name="calificacion">
                            <option value="">-- Seleccione una calificación --</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"
                                    <?php echo (isset($calificacion) && $calificacion == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i . ' estrella' . ($i > 1 ? 's' : ''); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <?php if (isset($errores['calificacion'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['calificacion']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Campo: Comentario -->
                    <div class="mb-3">
                        <label for="comentario" class="form-label">Comentario</label>
                        <textarea class="form-control <?php echo isset($errores['comentario']) ? 'is-invalid' : ''; ?>"
                                  id="comentario" name="comentario" rows="4" maxlength="2000"
                                  placeholder="Escribe tu opinión sobre el juego..."><?php echo isset($comentario) ? htmlspecialchars($comentario) : ''; ?></textarea>
                        <?php if (isset($errores['comentario'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['comentario']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Boton de envio -->
                    <button type="submit" class="btn btn-success">Registrar Reseña</button>
                    <a href="index.php" class="btn btn-secondary ms-2">Cancelar</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

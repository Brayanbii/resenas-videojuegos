<?php
/**
 * registrar_videojuego.php
 * Formulario para registrar un nuevo videojuego en la base de datos.
 * Incluye validaciones del lado del servidor por cada campo.
 */

// Incluimos la conexion a la base de datos
require_once 'conexion.php';

// Variable para almacenar mensajes de error o exito
$mensaje = '';
$tipo_mensaje = ''; // success o danger

// Variable para guardar errores de validacion por campo
$errores = [];

/**
 * Verificamos si el formulario fue enviado por metodo POST.
 * $_SERVER['REQUEST_METHOD'] nos dice que metodo HTTP se uso.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---------------------------------------------------
    // 1. Obtenemos los datos del formulario
    //    Usamos trim() para quitar espacios en blanco al inicio y final
    // ---------------------------------------------------
    $nombre = trim($_POST['nombre'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $plataforma = trim($_POST['plataforma'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_lanzamiento = trim($_POST['fecha_lanzamiento'] ?? '');

    // ---------------------------------------------------
    // 2. VALIDACIONES POR CAMPO
    // ---------------------------------------------------

    // Validacion del campo "nombre"
    if (empty($nombre)) {
        $errores['nombre'] = 'El nombre del videojuego es obligatorio.';
    } elseif (strlen($nombre) < 2) {
        $errores['nombre'] = 'El nombre debe tener al menos 2 caracteres.';
    } elseif (strlen($nombre) > 200) {
        $errores['nombre'] = 'El nombre no debe superar los 200 caracteres.';
    }

    // Validacion del campo "genero"
    if (empty($genero)) {
        $errores['genero'] = 'El género es obligatorio.';
    }

    // Validacion del campo "plataforma"
    if (empty($plataforma)) {
        $errores['plataforma'] = 'La plataforma es obligatoria.';
    }

    // Validacion del campo "descripcion" (opcional pero con limite)
    if (!empty($descripcion) && strlen($descripcion) > 2000) {
        $errores['descripcion'] = 'La descripción no debe superar los 2000 caracteres.';
    }

    // Validacion del campo "fecha_lanzamiento"
    if (empty($fecha_lanzamiento)) {
        $errores['fecha_lanzamiento'] = 'La fecha de lanzamiento es obligatoria.';
    } else {
        // Validamos que la fecha tenga el formato correcto (YYYY-MM-DD)
        $partes_fecha = explode('-', $fecha_lanzamiento);
        if (count($partes_fecha) !== 3 || !checkdate((int)$partes_fecha[1], (int)$partes_fecha[2], (int)$partes_fecha[0])) {
            $errores['fecha_lanzamiento'] = 'La fecha no es válida. Use el formato AAAA-MM-DD.';
        }
    }

    // ---------------------------------------------------
    // 3. Si no hay errores, insertamos en la base de datos
    // ---------------------------------------------------
    if (empty($errores)) {
        try {
            // Preparamos la consulta SQL con marcadores de posicion (:nombre, :genero, etc.)
            $sql = "INSERT INTO videojuegos (nombre, genero, plataforma, descripcion, fecha_lanzamiento)
                    VALUES (:nombre, :genero, :plataforma, :descripcion, :fecha_lanzamiento)";

            $consulta = $conexion_bd->prepare($sql);

            // Vinculamos los parametros para evitar inyeccion SQL
            $consulta->bindParam(':nombre', $nombre);
            $consulta->bindParam(':genero', $genero);
            $consulta->bindParam(':plataforma', $plataforma);
            $consulta->bindParam(':descripcion', $descripcion);
            $consulta->bindParam(':fecha_lanzamiento', $fecha_lanzamiento);

            // Ejecutamos la consulta
            $consulta->execute();

            // Mensaje de exito
            $mensaje = "¡Videojuego '$nombre' registrado correctamente!";
            $tipo_mensaje = 'success';

        } catch (PDOException $error_insercion) {
            $mensaje = "Error al registrar: " . $error_insercion->getMessage();
            $tipo_mensaje = 'danger';
        }
    } else {
        // Si hay errores de validacion, mostramos mensaje general
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

    <!-- Barra de navegacion (igual que index.php) -->
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

    <div class="container mt-4">
        <h2 class="mb-4">Registrar Nuevo Videojuego</h2>

        <!-- Mostrar mensaje de exito o error -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario de registro -->
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="registrar_videojuego.php" novalidate>

                    <!-- Campo: Nombre del videojuego -->
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Videojuego *</label>
                        <input type="text" class="form-control <?php echo isset($errores['nombre']) ? 'is-invalid' : ''; ?>"
                               id="nombre" name="nombre" maxlength="200"
                               value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>"
                               placeholder="Ej: The Legend of Zelda">
                        <?php if (isset($errores['nombre'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['nombre']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Campo: Género -->
                    <div class="mb-3">
                        <label for="genero" class="form-label">Género *</label>
                        <select class="form-select <?php echo isset($errores['genero']) ? 'is-invalid' : ''; ?>"
                                id="genero" name="genero">
                            <option value="">-- Seleccione un género --</option>
                            <option value="Acción" <?php echo (isset($genero) && $genero === 'Acción') ? 'selected' : ''; ?>>Acción</option>
                            <option value="Aventura" <?php echo (isset($genero) && $genero === 'Aventura') ? 'selected' : ''; ?>>Aventura</option>
                            <option value="RPG" <?php echo (isset($genero) && $genero === 'RPG') ? 'selected' : ''; ?>>RPG</option>
                            <option value="RPG de Acción" <?php echo (isset($genero) && $genero === 'RPG de Acción') ? 'selected' : ''; ?>>RPG de Acción</option>
                            <option value="Shooter" <?php echo (isset($genero) && $genero === 'Shooter') ? 'selected' : ''; ?>>Shooter</option>
                            <option value="Deportes" <?php echo (isset($genero) && $genero === 'Deportes') ? 'selected' : ''; ?>>Deportes</option>
                            <option value="Estrategia" <?php echo (isset($genero) && $genero === 'Estrategia') ? 'selected' : ''; ?>>Estrategia</option>
                            <option value="Terror" <?php echo (isset($genero) && $genero === 'Terror') ? 'selected' : ''; ?>>Terror</option>
                            <option value="Simulación" <?php echo (isset($genero) && $genero === 'Simulación') ? 'selected' : ''; ?>>Simulación</option>
                            <option value="Plataformas" <?php echo (isset($genero) && $genero === 'Plataformas') ? 'selected' : ''; ?>>Plataformas</option>
                        </select>
                        <?php if (isset($errores['genero'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['genero']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Campo: Plataforma -->
                    <div class="mb-3">
                        <label for="plataforma" class="form-label">Plataforma *</label>
                        <select class="form-select <?php echo isset($errores['plataforma']) ? 'is-invalid' : ''; ?>"
                                id="plataforma" name="plataforma">
                            <option value="">-- Seleccione una plataforma --</option>
                            <option value="PC" <?php echo (isset($plataforma) && $plataforma === 'PC') ? 'selected' : ''; ?>>PC</option>
                            <option value="PS5" <?php echo (isset($plataforma) && $plataforma === 'PS5') ? 'selected' : ''; ?>>PS5</option>
                            <option value="PS4" <?php echo (isset($plataforma) && $plataforma === 'PS4') ? 'selected' : ''; ?>>PS4</option>
                            <option value="Xbox Series X" <?php echo (isset($plataforma) && $plataforma === 'Xbox Series X') ? 'selected' : ''; ?>>Xbox Series X</option>
                            <option value="Nintendo Switch" <?php echo (isset($plataforma) && $plataforma === 'Nintendo Switch') ? 'selected' : ''; ?>>Nintendo Switch</option>
                            <option value="Móvil" <?php echo (isset($plataforma) && $plataforma === 'Móvil') ? 'selected' : ''; ?>>Móvil</option>
                        </select>
                        <?php if (isset($errores['plataforma'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['plataforma']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Campo: Descripción -->
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control <?php echo isset($errores['descripcion']) ? 'is-invalid' : ''; ?>"
                                  id="descripcion" name="descripcion" rows="4" maxlength="2000"
                                  placeholder="Describe el videojuego..."><?php echo isset($descripcion) ? htmlspecialchars($descripcion) : ''; ?></textarea>
                        <?php if (isset($errores['descripcion'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['descripcion']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Campo: Fecha de lanzamiento -->
                    <div class="mb-4">
                        <label for="fecha_lanzamiento" class="form-label">Fecha de Lanzamiento *</label>
                        <input type="date" class="form-control <?php echo isset($errores['fecha_lanzamiento']) ? 'is-invalid' : ''; ?>"
                               id="fecha_lanzamiento" name="fecha_lanzamiento"
                               value="<?php echo isset($fecha_lanzamiento) ? htmlspecialchars($fecha_lanzamiento) : ''; ?>">
                        <?php if (isset($errores['fecha_lanzamiento'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['fecha_lanzamiento']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Boton de envio -->
                    <button type="submit" class="btn btn-primary">Registrar Videojuego</button>
                    <a href="index.php" class="btn btn-secondary ms-2">Cancelar</a>
                </form>
            </div>
        </div>

        <!-- Tabla de videojuegos registrados (vista previa) -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Videojuegos Registrados</h5>
            </div>
            <div class="card-body">
                <?php
                // Consultamos todos los videojuegos para mostrarlos en una tabla
                $consulta_lista = $conexion_bd->query("SELECT * FROM videojuegos ORDER BY fecha_registro DESC");

                if ($consulta_lista->rowCount() > 0):
                ?>
                <div class="table-responsive">
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

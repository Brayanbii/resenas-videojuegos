<?php
/**
 * catalogo.php
 * Vista publica que muestra todos los videojuegos registrados
 * junto con sus respectivas resenas.
 */

// Incluimos la conexion a la base de datos
require_once 'conexion.php';

// Consultamos todos los videojuegos con el promedio de calificaciones
// Usamos LEFT JOIN para incluir juegos sin resenas
// COALESCE evita que el promedio sea NULL, devuelve 0 en su lugar
$sql = "SELECT v.*,
               COALESCE(ROUND(AVG(r.calificacion)::numeric, 1), 0) AS promedio_calificacion,
               COUNT(r.id) AS total_resenas
        FROM videojuegos v
        LEFT JOIN resenas r ON v.id = r.videojuego_id
        GROUP BY v.id
        ORDER BY promedio_calificacion DESC, v.nombre ASC";

$consulta_videojuegos = $conexion_bd->query($sql);
$lista_videojuegos = $consulta_videojuegos->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Videojuegos</title>
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
                    <li class="nav-item"><a class="nav-link" href="registrar_resena.php">Registrar Reseña</a></li>
                    <li class="nav-item"><a class="nav-link active" href="catalogo.php">Ver Catálogo</a></li>
                    <li class="nav-item"><a class="nav-link" href="estadisticas.php">Estadísticas</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Catálogo de Videojuegos</h2>

        <?php if (count($lista_videojuegos) > 0): ?>
            <?php foreach ($lista_videojuegos as $videojuego): ?>
                <!-- Tarjeta para cada videojuego -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($videojuego['nombre']); ?></h5>
                        <div>
                            <?php
                            // Mostramos estrellas segun el promedio de calificacion
                            $promedio = (float)$videojuego['promedio_calificacion'];
                            for ($i = 1; $i <= 5; $i++):
                                if ($i <= round($promedio)):
                                    echo '★'; // Estrella llena
                                else:
                                    echo '☆'; // Estrella vacia
                                endif;
                            endfor;
                            ?>
                            <span class="ms-2"><?php echo $promedio; ?> / 5</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Datos del videojuego -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Género:</strong> <?php echo htmlspecialchars($videojuego['genero']); ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Plataforma:</strong> <?php echo htmlspecialchars($videojuego['plataforma']); ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Lanzamiento:</strong> <?php echo $videojuego['fecha_lanzamiento']; ?>
                            </div>
                        </div>

                        <?php if (!empty($videojuego['descripcion'])): ?>
                            <p class="text-muted"><?php echo htmlspecialchars($videojuego['descripcion']); ?></p>
                        <?php endif; ?>

                        <p class="mb-1">
                            <strong>Total de reseñas:</strong>
                            <span class="badge bg-secondary"><?php echo $videojuego['total_resenas']; ?></span>
                        </p>

                        <!-- Seccion de resenas del videojuego -->
                        <hr>
                        <h6>Reseñas de los usuarios:</h6>

                        <?php
                        // Consultamos las resenas especificas de este videojuego
                        $id_actual = $videojuego['id'];
                        $sql_resenas = "SELECT * FROM resenas WHERE videojuego_id = :id ORDER BY fecha_registro DESC";
                        $consulta_resenas = $conexion_bd->prepare($sql_resenas);
                        $consulta_resenas->execute([':id' => $id_actual]);
                        $resenas_del_juego = $consulta_resenas->fetchAll();
                        ?>

                        <?php if (count($resenas_del_juego) > 0): ?>
                            <?php foreach ($resenas_del_juego as $resena): ?>
                                <div class="border rounded p-2 mb-2 bg-white">
                                    <div class="d-flex justify-content-between">
                                        <strong><?php echo htmlspecialchars($resena['nombre_usuario']); ?></strong>
                                        <span class="text-warning">
                                            <?php
                                            // Estrellas de la resena individual
                                            for ($i = 1; $i <= 5; $i++):
                                                echo ($i <= $resena['calificacion']) ? '★' : '☆';
                                            endfor;
                                            ?>
                                            (<?php echo $resena['calificacion']; ?>/5)
                                        </span>
                                    </div>
                                    <?php if (!empty($resena['comentario'])): ?>
                                        <p class="mb-0 mt-1"><?php echo htmlspecialchars($resena['comentario']); ?></p>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($resena['fecha_registro'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted fst-italic">Este videojuego aún no tiene reseñas. ¡Sé el primero en opinar!</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Mensaje si no hay videojuegos registrados -->
            <div class="alert alert-info">
                No hay videojuegos registrados aún.
                <a href="registrar_videojuego.php" class="alert-link">Registra el primero aquí.</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

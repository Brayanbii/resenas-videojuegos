<?php
/**
 * estadisticas.php
 * Pagina que muestra las estadisticas obtenidas desde la API Flask.
 * Consulta los endpoints: /api/estadisticas y /api/mejores-videojuegos
 */

// Incluimos las funciones de la API
require_once 'funciones_api.php';

// Obtenemos las estadisticas desde la API Flask
$datos_estadisticas = consultar_estadisticas_api();
$mejores_videojuegos = consultar_mejores_videojuegos_api();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Analítica</title>
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
                    <li class="nav-item"><a class="nav-link" href="catalogo.php">Ver Catálogo</a></li>
                    <li class="nav-item"><a class="nav-link active" href="estadisticas.php">Estadísticas</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Estadísticas y Analítica</h2>
        <p class="text-muted">Datos obtenidos desde el servicio de analítica (Flask + MongoDB).</p>

        <div class="row">

            <!-- Seccion: Estadisticas generales -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Resumen de Estadísticas</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($datos_estadisticas !== null): ?>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Total de videojuegos registrados:</span>
                                    <strong><?php echo $datos_estadisticas['total_videojuegos'] ?? 'N/D'; ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Total de reseñas:</span>
                                    <strong><?php echo $datos_estadisticas['total_resenas'] ?? 'N/D'; ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Calificación promedio general:</span>
                                    <strong><?php echo $datos_estadisticas['promedio_general'] ?? 'N/D'; ?> / 5</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Videojuego mejor calificado:</span>
                                    <strong><?php echo htmlspecialchars($datos_estadisticas['mejor_calificado'] ?? 'N/D'); ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Videojuego más reseñado:</span>
                                    <strong><?php echo htmlspecialchars($datos_estadisticas['mas_resenado'] ?? 'N/D'); ?></strong>
                                </li>
                            </ul>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                No se pudo conectar con el servicio de analítica.
                                Asegúrese de que la API Flask esté en ejecución.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Seccion: Mejores videojuegos -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Top - Mejores Videojuegos</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($mejores_videojuegos !== null && count($mejores_videojuegos) > 0): ?>
                            <ol class="list-group list-group-numbered">
                                <?php foreach ($mejores_videojuegos as $indice => $juego): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                        <div class="ms-2 me-auto">
                                            <div class="fw-bold"><?php echo htmlspecialchars($juego['nombre_videojuego'] ?? 'Sin nombre'); ?></div>
                                            <?php echo htmlspecialchars($juego['genero'] ?? ''); ?>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo $juego['promedio_calificacion'] ?? '0'; ?> / 5
                                            (<?php echo $juego['total_resenas'] ?? 0; ?> reseñas)
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php elseif ($mejores_videojuegos === null): ?>
                            <div class="alert alert-warning mb-0">
                                No se pudo conectar con el servicio de analítica.
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No hay datos de rankings disponibles aún.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

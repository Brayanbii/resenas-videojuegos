<?php
/**
 * index.php
 * Pagina principal con menu de navegacion para acceder a todas las funciones.
 * Actua como punto de entrada de la aplicacion web.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseñas de Videojuegos - Inicio</title>
    <!-- Bootstrap CDN para estilos basicos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Barra de navegacion -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Reseñas Videojuegos</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu_navegacion">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menu_navegacion">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="registrar_videojuego.php">Registrar Videojuego</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="registrar_resena.php">Registrar Reseña</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="catalogo.php">Ver Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="estadisticas.php">Estadísticas</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="container mt-5">
        <div class="text-center">
            <h1 class="mb-4">Sistema de Reseñas de Videojuegos</h1>
            <p class="lead">Registra videojuegos, escribe reseñas y consulta el catálogo completo.</p>
        </div>

        <!-- Tarjetas de acceso rapido -->
        <div class="row mt-5">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Registrar Videojuego</h5>
                        <p class="card-text">Agrega un nuevo videojuego al catálogo con todos sus datos.</p>
                        <a href="registrar_videojuego.php" class="btn btn-primary">Ir al formulario</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Registrar Reseña</h5>
                        <p class="card-text">Escribe una reseña y califica un videojuego del catálogo.</p>
                        <a href="registrar_resena.php" class="btn btn-success">Ir al formulario</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Ver Catálogo</h5>
                        <p class="card-text">Consulta todos los videojuegos registrados y sus reseñas.</p>
                        <a href="catalogo.php" class="btn btn-info text-white">Ver catálogo</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

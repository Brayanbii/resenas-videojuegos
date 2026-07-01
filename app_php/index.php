<?php
/**
 * ============================================================
 * index.php
 * Pagina principal del sistema de resenas de videojuegos.
 * ============================================================
 *
 * PROPOSITO:
 * Actua como punto de entrada y menu de navegacion principal.
 * Desde aqui el usuario puede acceder a todas las funcionalidades
 * del sistema: registrar videojuegos, escribir resenas, consultar
 * el catalogo y ver estadisticas.
 *
 * TECNOLOGIAS USADAS:
 * - HTML5: estructura semantica de la pagina
 * - Bootstrap 5 (CDN): framework CSS para diseño responsive
 *   - CDN = Content Delivery Network, cargamos Bootstrap desde
 *     internet sin necesidad de instalar archivos locales
 *   - La clase 'container' centra el contenido con margenes
 *   - La clase 'navbar' crea una barra de navegacion
 *   - La clase 'card' crea tarjetas con sombra
 *   - La clase 'row' y 'col-md-4' crean un sistema de columnas
 *
 * ESTRUCTURA DE LA PAGINA:
 * 1. Barra de navegacion (navbar) con enlaces a todas las secciones
 * 2. Titulo principal y descripcion del sistema
 * 3. Tres tarjetas de acceso rapido a las funciones principales
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- meta charset: define la codificacion de caracteres -->
    <!-- UTF-8 soporta caracteres especiales como ñ, acentos, etc. -->
    <meta charset="UTF-8">

    <!-- meta viewport: hace que la pagina sea responsive en moviles -->
    <!-- width=device-width: el ancho se ajusta al dispositivo -->
    <!-- initial-scale=1.0: nivel de zoom inicial -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reseñas de Videojuegos - Inicio</title>

    <!-- Bootstrap CSS desde CDN (Content Delivery Network) -->
    <!-- La version 5.3.0 es estable y ampliamente usada -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- bg-light: clase de Bootstrap que pone fondo gris claro a toda la pagina -->

    <!-- ============================================================
    BARRA DE NAVEGACION (NAVBAR)
    ============================================================
    La barra de navegacion permite al usuario moverse entre las
    diferentes paginas del sistema. Se mantiene igual en todas
    las paginas para dar consistencia a la interfaz.

    Clases de Bootstrap usadas:
    - navbar-expand-lg: en pantallas grandes, muestra los enlaces
      horizontalmente. En pantallas pequeñas, los colapsa en un menu
      tipo "hamburguesa".
    - navbar-dark bg-dark: fondo oscuro con texto claro.
    - active: resalta el enlace de la pagina actual.
    ============================================================ -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <!-- Marca/nombre de la aplicacion, siempre visible -->
            <a class="navbar-brand" href="index.php">Reseñas Videojuegos</a>

            <!-- Boton hamburguesa para pantallas moviles -->
            <!-- data-bs-toggle="collapse": activa/desactiva el menu colapsable -->
            <!-- data-bs-target: indica cual elemento colapsar (por ID) -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu_navegacion">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menu de navegacion colapsable -->
            <!-- collapse navbar-collapse: oculta los enlaces en movil -->
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

    <!-- ============================================================
    CONTENIDO PRINCIPAL
    ============================================================
    container: clase Bootstrap que centra el contenido y le da
    margenes automaticos. Es el contenedor principal de la pagina.
    mt-5: margin-top 5 (espacio superior grande).
    ============================================================ -->
    <div class="container mt-5">

        <!-- Encabezado principal -->
        <div class="text-center">
            <h1 class="mb-4">Sistema de Reseñas de Videojuegos</h1>
            <p class="lead">Registra videojuegos, escribe reseñas y consulta el catálogo completo.</p>
            <!-- lead: clase Bootstrap que hace el texto mas grande y ligero -->
        </div>

        <!-- ============================================================
        TARJETAS DE ACCESO RAPIDO
        ============================================================
        row: crea una fila en el sistema de grid de Bootstrap
        col-md-4: en pantallas medianas (md) o mas grandes,
                  cada tarjeta ocupa 4 de 12 columnas = 1/3 del ancho
        mb-4: margin-bottom 4 (espacio inferior)
        shadow-sm: sombra pequeña para dar profundidad
        h-100: altura 100% para que todas las tarjetas midan igual
        ============================================================ -->
        <div class="row mt-5">

            <!-- Tarjeta 1: Registrar Videojuego -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Registrar Videojuego</h5>
                        <p class="card-text">Agrega un nuevo videojuego al catálogo con todos sus datos.</p>
                        <!-- btn-primary: boton azul de Bootstrap -->
                        <a href="registrar_videojuego.php" class="btn btn-primary">Ir al formulario</a>
                    </div>
                </div>
            </div>

            <!-- Tarjeta 2: Registrar Reseña -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Registrar Reseña</h5>
                        <p class="card-text">Escribe una reseña y califica un videojuego del catálogo.</p>
                        <!-- btn-success: boton verde de Bootstrap -->
                        <a href="registrar_resena.php" class="btn btn-success">Ir al formulario</a>
                    </div>
                </div>
            </div>

            <!-- Tarjeta 3: Ver Catalogo -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Ver Catálogo</h5>
                        <p class="card-text">Consulta todos los videojuegos registrados y sus reseñas.</p>
                        <!-- btn-info: boton celeste, text-white para contraste -->
                        <a href="catalogo.php" class="btn btn-info text-white">Ver catálogo</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript (necesario para el menu colapsable y alertas) -->
    <!-- bundle incluye Popper.js que maneja los dropdowns y tooltips -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

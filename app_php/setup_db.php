<?php
/**
 * setup_db.php
 * Script para inicializar las tablas de la base de datos PostgreSQL.
 * Ejecuta el script SQL de creacion de tablas y datos de ejemplo.
 *
 * USO:
 *   Accede a: https://tu-app.onrender.com/setup_db.php
 *   O ejecuta: php setup_db.php desde la terminal
 *
 * IMPORTANTE: Despues de ejecutar, ELIMINA este archivo del servidor
 *              o protegelo con una contraseña para evitar accesos no autorizados.
 */

// Incluimos la conexion
require_once 'conexion.php';

echo "<h2>Inicializando Base de Datos</h2>";
echo "<pre>";

// Leemos el archivo SQL
$ruta_sql = __DIR__ . '/sql/script.sql';

if (!file_exists($ruta_sql)) {
    die("ERROR: No se encontro el archivo script.sql en: $ruta_sql");
}

echo "Archivo SQL encontrado: $ruta_sql\n\n";

$contenido_sql = file_get_contents($ruta_sql);

// Separamos las instrucciones SQL por punto y coma
// Ignoramos lineas vacias y comentarios
$instrucciones = array_filter(
    array_map('trim', explode(';', $contenido_sql)),
    function($linea) {
        $linea = trim($linea);
        return !empty($linea) && !str_starts_with($linea, '--');
    }
);

$exitos = 0;
$fallos = 0;

foreach ($instrucciones as $indice => $sql) {
    // Saltamos SELECTs (son de verificacion al final)
    if (stripos(trim($sql), 'SELECT') === 0) {
        echo "Saltando SELECT de verificacion...\n";
        continue;
    }

    // Saltamos DROP TABLE si es primera ejecucion (no existen)
    // pero los ejecutamos igual por si acaso

    try {
        $conexion_bd->exec($sql);
        $exitos++;
        echo "[OK] Instruccion " . ($indice + 1) . " ejecutada.\n";
    } catch (PDOException $error) {
        $fallos++;
        echo "[ERROR] Instruccion " . ($indice + 1) . ": " . $error->getMessage() . "\n";

        // Si el error es porque la tabla ya existe, no es grave
        if (strpos($error->getMessage(), 'already exists') !== false) {
            echo "       (La tabla ya existe, no es un problema)\n";
        }
    }
}

echo "\n========================================\n";
echo "RESUMEN: $exitos exitos, $fallos fallos\n";

// Verificamos que las tablas se crearon
try {
    $verificacion = $conexion_bd->query("SELECT COUNT(*) as cuenta FROM videojuegos");
    $resultado = $verificacion->fetch();
    echo "Videojuegos en BD: " . $resultado['cuenta'] . "\n";

    $verificacion2 = $conexion_bd->query("SELECT COUNT(*) as cuenta FROM resenas");
    $resultado2 = $verificacion2->fetch();
    echo "Resenas en BD: " . $resultado2['cuenta'] . "\n";
} catch (PDOException $error) {
    echo "Error al verificar: " . $error->getMessage() . "\n";
}

echo "========================================\n";
echo "Inicializacion completada.\n";
echo "</pre>";
echo "<p><strong>IMPORTANTE:</strong> Elimina este archivo (setup_db.php) del servidor por seguridad.</p>";
echo "<p><a href='index.php'>Ir a la pagina principal</a></p>";

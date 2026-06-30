<?php
/**
 * setup_db.php
 * Script para inicializar las tablas de la base de datos PostgreSQL.
 *
 * IMPORTANTE: Despues de ejecutar, ELIMINA este archivo del servidor
 */

require_once 'conexion.php';

echo "<h2>Inicializando Base de Datos</h2>";
echo "<pre>";

$ruta_sql = __DIR__ . '/sql/script.sql';

if (!file_exists($ruta_sql)) {
    die("ERROR: No se encontro el archivo script.sql");
}

echo "Archivo SQL encontrado\n\n";

$contenido_sql = file_get_contents($ruta_sql);

// Paso 1: Eliminar TODOS los comentarios (lineas que empiezan con --)
$lineas = explode("\n", $contenido_sql);
$lineas_limpias = [];
foreach ($lineas as $linea) {
    $linea_trim = trim($linea);
    if (!empty($linea_trim) && !str_starts_with($linea_trim, '--')) {
        $lineas_limpias[] = $linea;
    }
}
$sql_sin_comentarios = implode("\n", $lineas_limpias);

// Paso 2: Separar por punto y coma cada instruccion
$instrucciones = explode(';', $sql_sin_comentarios);

$exitos = 0;
$fallos = 0;
$numero = 0;

foreach ($instrucciones as $sql) {
    $sql = trim($sql);
    if (empty($sql)) {
        continue; // Saltamos vacios
    }

    $numero++;

    // Saltamos los SELECT del final (son de comprobacion en el script)
    if (stripos($sql, 'SELECT') === 0) {
        echo "[$numero] Saltando SELECT de verificacion...\n";
        continue;
    }

    try {
        $conexion_bd->exec($sql);
        $exitos++;
        // Mostramos solo las primeras palabras de la instruccion
        $resumen = substr($sql, 0, 60);
        echo "[$numero] OK: $resumen...\n";
    } catch (PDOException $error) {
        $fallos++;
        echo "[$numero] ERROR: " . $error->getMessage() . "\n";

        // Si la tabla ya existe, no es grave
        if (strpos($error->getMessage(), 'already exists') !== false ||
            strpos($error->getMessage(), 'does not exist') !== false) {
            echo "       (No es grave - la tabla ya existia o no existia)\n";
            $exitos++; // Lo contamos como exito
            $fallos--;
        }
    }
}

echo "\n========================================\n";
echo "RESUMEN: $exitos instrucciones ejecutadas correctamente\n";
if ($fallos > 0) {
    echo "         $fallos instrucciones con error\n";
}
echo "========================================\n";

// Verificacion final
echo "\n--- Verificando datos en la base ---\n";
try {
    $v = $conexion_bd->query("SELECT COUNT(*) as c FROM videojuegos")->fetch();
    echo "Videojuegos en BD: {$v['c']}\n";
    $r = $conexion_bd->query("SELECT COUNT(*) as c FROM resenas")->fetch();
    echo "Resenas en BD: {$r['c']}\n";

    if ($v['c'] > 0) {
        echo "\n--- Lista de videojuegos ---\n";
        $lista = $conexion_bd->query("SELECT id, nombre, genero FROM videojuegos");
        foreach ($lista as $juego) {
            echo "  [{$juego['id']}] {$juego['nombre']} ({$juego['genero']})\n";
        }
    }

    echo "\n*** BASE DE DATOS INICIALIZADA CORRECTAMENTE ***\n";
} catch (PDOException $error) {
    echo "Error al verificar: " . $error->getMessage() . "\n";
}

echo "</pre>";
echo "<p><strong>IMPORTANTE:</strong> Elimina este archivo (setup_db.php) del servidor por seguridad.</p>";
echo "<p><a href='index.php'>Ir a la pagina principal</a></p>";

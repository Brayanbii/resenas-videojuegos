<?php
/**
 * conexion.php
 * Archivo de conexion a la base de datos PostgreSQL usando PDO.
 * Usa variables de entorno para produccion y valores por defecto para desarrollo local.
 */

// Obtenemos los datos de conexion desde variables de entorno
// Si no existen, usamos valores de ejemplo para desarrollo local
$host_bd = getenv('DB_HOST') ?: 'localhost';
$puerto_bd = getenv('DB_PORT') ?: '5432';
$nombre_bd = getenv('DB_NAME') ?: 'videojuegos_db';
$usuario_bd = getenv('DB_USER') ?: 'postgres';
$clave_bd = getenv('DB_PASSWORD') ?: 'postgres';

// Cadena de conexion DSN (Data Source Name) para PostgreSQL
$dsn = "pgsql:host=$host_bd;port=$puerto_bd;dbname=$nombre_bd";

try {
    // Creamos la conexion PDO
    // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION: lanza excepciones si hay error
    // PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC: devuelve arrays asociativos
    $conexion_bd = new PDO($dsn, $usuario_bd, $clave_bd, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // Si llega aqui, la conexion fue exitosa
    // No mostramos mensaje para no interferir con HTML
} catch (PDOException $error_conexion) {
    // Si falla la conexion, mostramos el error y detenemos la ejecucion
    die("Error de conexion a la base de datos: " . $error_conexion->getMessage());
}

<?php
/**
 * ============================================================
 * conexion.php
 * Archivo de conexion a la base de datos PostgreSQL usando PDO.
 * ============================================================
 *
 * ¿QUE ES PDO?
 * PDO (PHP Data Objects) es una capa de abstraccion de base de datos.
 * Permite conectarse a diferentes motores de BD (PostgreSQL, MySQL,
 * SQLite, etc.) usando la misma sintaxis.
 *
 * VENTAJAS DE PDO:
 * - Consultas preparadas: evitan inyeccion SQL al separar
 *   la estructura SQL de los datos del usuario.
 * - Manejo de errores con excepciones (try/catch).
 * - Portabilidad: cambiar de PostgreSQL a MySQL solo requiere
 *   cambiar el DSN, no todo el codigo.
 *
 * ¿POR QUE VARIABLES DE ENTORNO?
 * Las variables de entorno (getenv) permiten que el mismo codigo
 * funcione en diferentes entornos (local, Render, Docker) sin
 * modificar el archivo. En produccion, los valores sensibles
 * (host, usuario, password) nunca deben estar escritos en el codigo.
 */

// ------------------------------------------------------------
// CONFIGURACION DE CONEXION
// Cada constante se lee desde variables de entorno.
// Si no existen (desarrollo local), se usa un valor por defecto.
// ------------------------------------------------------------

// Host donde esta alojada la base de datos PostgreSQL
// En Render es algo como: dpg-xxxx.oregon-postgres.render.com
// En Docker local es: postgres (nombre del servicio en docker-compose)
// En XAMPP local es: localhost
$host_bd = getenv('DB_HOST') ?: 'localhost';

// Puerto de PostgreSQL. Por defecto es 5432
$puerto_bd = getenv('DB_PORT') ?: '5432';

// Nombre de la base de datos a la que nos conectamos
$nombre_bd = getenv('DB_NAME') ?: 'videojuegos_db';

// Usuario de la base de datos con permisos de lectura/escritura
$usuario_bd = getenv('DB_USER') ?: 'postgres';

// Contraseña del usuario de base de datos
// En produccion NUNCA se escribe directamente, siempre por variable de entorno
$clave_bd = getenv('DB_PASSWORD') ?: 'postgres';

// ------------------------------------------------------------
// CONSTRUCCION DEL DSN (Data Source Name)
// Es la cadena que le dice a PDO que motor de BD usar y donde esta.
//
// Formato: motor:host=DIRECCION;port=PUERTO;dbname=NOMBRE_BD
// - pgsql:  indica que usamos PostgreSQL
// - host:   direccion del servidor de BD
// - port:   puerto de conexion
// - dbname: nombre de la base de datos
// ------------------------------------------------------------
$dsn = "pgsql:host=$host_bd;port=$puerto_bd;dbname=$nombre_bd";

// ------------------------------------------------------------
// CREACION DE LA CONEXION PDO
// Usamos try/catch para capturar posibles errores de conexion
// sin que la aplicacion se detenga con un error fatal.
// ------------------------------------------------------------
try {

    // new PDO() crea una nueva conexion a la base de datos
    // Parametros:
    //   1. DSN: cadena de conexion con motor, host, puerto, bd
    //   2. Usuario: nombre de usuario de la BD
    //   3. Password: contraseña del usuario
    //   4. Opciones: array asociativo con configuracion adicional
    $conexion_bd = new PDO($dsn, $usuario_bd, $clave_bd, [
        // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        // Hace que PDO lance excepciones (PDOException) cuando algo falla.
        // Esto permite usar try/catch en lugar de verificar cada consulta manualmente.
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        // PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        // Cuando hacemos fetch(), devuelve un array asociativo.
        // Ejemplo: $fila['nombre'] en lugar de $fila[0].
        // Esto hace el codigo mas legible.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // PDO::ATTR_EMULATE_PREPARES => false
        // Desactiva la emulacion de consultas preparadas.
        // PostgreSQL soporta preparacion nativa, lo cual es mas seguro.
        // La BD recibe la estructura SQL separada de los datos.
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Si llegamos aqui, la conexion fue exitosa.
    // La variable $conexion_bd estara disponible para todos los archivos
    // que hagan require_once 'conexion.php';

} catch (PDOException $error_conexion) {
    // Si ocurre un error de conexion, mostramos un mensaje claro
    // y detenemos la ejecucion con die().
    // getMessage() devuelve la descripcion del error.
    die("Error de conexion a la base de datos: " . $error_conexion->getMessage());
}

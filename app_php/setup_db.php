<?php
/**
 * setup_db.php
 *
 * SCRIPT DE CONFIGURACION INICIAL:
 * Este archivo se encarga de crear e inicializar todas las tablas necesarias
 * en la base de datos PostgreSQL. Lee un archivo .sql que contiene las sentencias
 * CREATE TABLE y INSERT, las ejecuta una por una, y al final verifica que los
 * datos se hayan insertado correctamente.
 *
 * FLUJO DEL SCRIPT:
 *   1. Cargar la conexion a la base de datos (desde conexion.php)
 *   2. Leer el archivo SQL con las instrucciones
 *   3. Filtrar comentarios y lineas vacias
 *   4. Separar las instrucciones por punto y coma (;)
 *   5. Ejecutar cada instruccion una por una
 *   6. Verificar que los datos existen en las tablas
 *
 * IMPORTANTE: Despues de ejecutar, ELIMINA este archivo del servidor
 * por razones de seguridad. Si alguien accede a el sin autorizacion,
 * podria reiniciar la base de datos y borrar toda la informacion.
 */

// require_once: Incluye el archivo de conexion a la base de datos UNA SOLA VEZ.
// A diferencia de require, require_once se asegura de que el archivo no se
// incluya multiples veces, evitando errores de redeclaracion de variables o funciones.
// conexion.php contiene la variable $conexion_bd que es el objeto PDO
// con la conexion activa a PostgreSQL.
require_once 'conexion.php';

// ABRIMOS LA SALIDA HTML:
// <h2>: Encabezado de nivel 2 que indica el titulo del proceso.
// Se usa HTML porque este script se ejecuta desde el navegador web.
echo "<h2>Inicializando Base de Datos</h2>";

// <pre>: Etiqueta HTML que muestra el texto en formato preformateado.
// Conserva los saltos de linea (\n) y espacios, ideal para logs de ejecucion.
echo "<pre>";

// __DIR__: Constante magica de PHP que contiene la ruta absoluta del directorio
//         donde se encuentra ESTE archivo (setup_db.php).
// . '/sql/script.sql': Concatenamos la subcarpeta 'sql' y el nombre del archivo SQL.
// Esto construye una ruta completa independientemente de desde donde se ejecute el script.
// Ejemplo: C:\xampp\htdocs\proyecto\sql\script.sql
$ruta_sql = __DIR__ . '/sql/script.sql';

// file_exists(): Funcion de PHP que verifica si un archivo o directorio existe.
// Retorna true si existe, false si no.
// El operador ! (negacion) invierte el resultado: si NO existe, entramos al if.
// Esto evita que el script intente leer un archivo inexistente y falle sin control.
if (!file_exists($ruta_sql)) {
    // die(): Funcion que detiene la ejecucion del script inmediatamente
    //        y muestra un mensaje de error al usuario.
    // Es una forma de "matar" (die = morir) el proceso cuando algo critico falla.
    // Aqui falla porque el archivo SQL con las tablas no se encuentra.
    die("ERROR: No se encontro el archivo script.sql");
}

// Confirmamos al usuario que el archivo existe y podemos continuar.
// \n: Secuencia de escape que produce un salto de linea en el texto.
//     Solo se ve reflejado dentro de la etiqueta <pre>.
echo "Archivo SQL encontrado\n\n";

// file_get_contents(): Lee TODO el contenido de un archivo y lo devuelve como un string.
// Aqui cargamos el script SQL completo en memoria para procesarlo.
// $contenido_sql: Variable que almacena el texto completo del archivo script.sql,
//                 incluyendo todas las sentencias CREATE TABLE, INSERT, etc.
$contenido_sql = file_get_contents($ruta_sql);

// ==========================================
// PASO 1: ELIMINAR COMENTARIOS DEL SCRIPT SQL
// ==========================================
// Los comentarios en SQL empiezan con '--'. Debemos eliminarlos porque
// cuando separamos las instrucciones por punto y coma, un comentario podria
// quedar como una instruccion vacia o mal formada.

// explode(): Divide un string en un array usando un delimitador.
// "\n" es el delimitador (salto de linea). Cada elemento del array $lineas
// sera una linea individual del archivo SQL.
// Ejemplo: "CREATE TABLE\nINSERT INTO\n" se convierte en ["CREATE TABLE", "INSERT INTO", ""]
$lineas = explode("\n", $contenido_sql);

// $lineas_limpias: Array vacio donde guardaremos solo las lineas que NO son comentarios
//                  y NO estan vacias. Lo inicializamos como array vacio con [].
$lineas_limpias = [];

// foreach: Bucle que recorre cada elemento de un array.
// En cada iteracion, la variable $linea contiene la linea actual.
foreach ($lineas as $linea) {
    // trim(): Elimina espacios en blanco al inicio y final de un string.
    // Esto nos permite detectar correctamente si una linea esta "vacia"
    // (solo contiene espacios o tabulaciones) o si empieza con '--'.
    $linea_trim = trim($linea);

    // empty(): Verifica si una variable esta vacia.
    // Retorna true para: "", 0, "0", null, false, array vacio, etc.
    // Aqui negamos con ! para quedarnos SOLO con lineas que tienen contenido.
    //
    // str_starts_with(): Funcion de PHP 8.0+ que verifica si un string
    //                    EMPIEZA con un prefijo especifico.
    // Aqui verificamos si la linea empieza con '--' (comentario SQL).
    // Tambien negamos con ! para quedarnos SOLO con lineas que NO son comentarios.
    //
    // Operador && (AND logico): Ambas condiciones deben ser verdaderas:
    //   1. La linea NO esta vacia
    //   2. La linea NO empieza con '--'
    if (!empty($linea_trim) && !str_starts_with($linea_trim, '--')) {
        // $lineas_limpias[] = $linea: Agregamos la linea original (sin trim)
        // al final del array $lineas_limpias.
        // Los corchetes [] sin indice hacen un "push" al final del array.
        $lineas_limpias[] = $linea;
    }
}

// implode(): Lo contrario de explode. Une los elementos de un array en un string,
//            usando un separador entre cada elemento.
// "\n" es el separador (salto de linea). Reconstruimos el script SQL
// pero ahora SIN comentarios y SIN lineas vacias.
// $sql_sin_comentarios: String con todas las instrucciones SQL validas,
//                        listas para ser separadas por punto y coma.
$sql_sin_comentarios = implode("\n", $lineas_limpias);

// ==========================================
// PASO 2: SEPARAR INSTRUCCIONES POR PUNTO Y COMA
// ==========================================
// En SQL, cada sentencia (CREATE TABLE, INSERT, etc.) termina con ';'.
// Separamos el texto por ';' para obtener un array donde cada elemento
// es UNA instruccion SQL individual que podemos ejecutar.
//
// NOTA: Esto asume que no hay punto y coma DENTRO de las instrucciones
//       (como en strings). Para scripts SQL simples funciona correctamente.
$instrucciones = explode(';', $sql_sin_comentarios);

// ==========================================
// CONTADORES PARA EL RESUMEN FINAL
// ==========================================
// $exitos: Contador de instrucciones ejecutadas correctamente.
//          Se incrementa cuando una sentencia SQL se ejecuta sin errores.
$exitos = 0;

// $fallos: Contador de instrucciones que fallaron con error.
//          Se incrementa cuando PDO lanza una excepcion.
$fallos = 0;

// $numero: Contador secuencial para numerar cada instruccion en el log.
//          Se muestra al usuario como [1], [2], [3], etc.
//          Sirve para identificar que instruccion fallo en caso de error.
$numero = 0;

// ==========================================
// EJECUTAR CADA INSTRUCCION SQL
// ==========================================
// foreach: Recorremos el array $instrucciones que contiene cada sentencia SQL.
// $sql: En cada iteracion contiene una instruccion SQL individual.
foreach ($instrucciones as $sql) {
    // trim(): Eliminamos espacios, tabs y saltos de linea al inicio y final.
    // Esto es importante porque despues del explode algunas instrucciones
    // pueden tener espacios o saltos de linea extra.
    $sql = trim($sql);

    // empty(): Si la instruccion esta vacia (por ejemplo, un ';' suelto
    //          o espacios entre instrucciones), la saltamos con continue.
    // continue: Salta a la siguiente iteracion del foreach sin ejecutar
    //           el resto del codigo dentro del bucle.
    if (empty($sql)) {
        continue; // Saltamos vacios
    }

    // $numero++: Incrementamos el contador en 1 ANTES de usarlo.
    // El operador ++ DESPUES de la variable (post-incremento) significa:
    // se usa el valor actual y luego se incrementa. Pero como es una linea sola,
    // simplemente aumenta el contador para esta instruccion.
    // La primera instruccion sera la numero 1.
    $numero++;

    // ==========================================
    // SALTAR SENTENCIAS SELECT DE VERIFICACION
    // ==========================================
    // El archivo script.sql puede contener SELECT al final para verificar datos.
    // No queremos ejecutarlos aqui porque:
    //   1. No son instrucciones de creacion/insercion
    //   2. Las ejecutamos manualmente en la seccion de verificacion mas abajo
    //   3. Los SELECT pueden fallar si las tablas no estan preparadas
    //
    // stripos(): Funcion que busca la posicion de un substring en un string,
    //            SIN distinguir mayusculas de minusculas (case-Insensitive).
    // Retorna la posicion (indice) donde encontro el substring, o false si no lo encuentra.
    //
    // Comparacion === 0: Verifica que el resultado sea EXACTAMENTE 0
    //                    (el substring esta al INICIO del string).
    // Usamos === (identico) en vez de == (igual) para asegurar que
    // sea 0 y no false (que en PHP, false == 0 daria verdadero).
    // Si la instruccion EMPIEZA con 'SELECT', la saltamos.
    if (stripos($sql, 'SELECT') === 0) {
        // Informamos al usuario que estamos saltando esta instruccion.
        // [$numero]: Mostramos el numero entre corchetes para referencia.
        echo "[$numero] Saltando SELECT de verificacion...\n";
        // continue: Pasamos a la siguiente instruccion sin ejecutar esta.
        continue;
    }

    // ==========================================
    // BLOQUE try-catch: MANEJO DE ERRORES
    // ==========================================
    // try: Intentamos ejecutar codigo que podria fallar.
    // Si ocurre un error, el flujo salta inmediatamente al bloque catch.
    // Esto evita que el script entero falle por un error en una instruccion.
    try {
        // $conexion_bd: Objeto PDO definido en conexion.php.
        //               Representa la conexion activa a PostgreSQL.
        //
        // exec(): Metodo de PDO que ejecuta una sentencia SQL que NO devuelve resultados
        //         (como CREATE TABLE, INSERT, UPDATE, DELETE).
        // Retorna el numero de filas afectadas.
        // A diferencia de query(), exec() NO se usa para SELECT.
        // Si la sentencia falla, PDO lanza una PDOException (que atrapa el catch).
        $conexion_bd->exec($sql);

        // Si llegamos aqui, la instruccion se ejecuto sin errores.
        // Incrementamos el contador de exitos en 1.
        $exitos++;

        // substr(): Extrae una porcion de un string.
        // Parametros:
        //   - $sql: El string original (la instruccion SQL completa)
        //   - 0:    Posicion inicial (desde el principio)
        //   - 60:   Longitud maxima (primeros 60 caracteres)
        // Esto crea un RESUMEN corto de la instruccion para mostrarlo en el log,
        // en vez de imprimir toda la sentencia SQL que puede ser muy larga.
        $resumen = substr($sql, 0, 60);

        // Mostramos confirmacion: numero de instruccion, estado OK, y resumen.
        // El ... al final indica que la instruccion fue truncada.
        echo "[$numero] OK: $resumen...\n";

    // catch: Captura la excepcion si algo falla dentro del try.
    // PDOException: Tipo de excepcion especifica de PDO para errores de base de datos.
    // $error: Variable que contiene el objeto de la excepcion con detalles del error.
    } catch (PDOException $error) {
        // Incrementamos el contador de fallos.
        $fallos++;

        // getMessage(): Metodo del objeto excepcion que devuelve el mensaje de error.
        // Por ejemplo: "SQLSTATE[42P07]: Duplicate table: 7 ERROR: relation "videojuegos" already exists"
        echo "[$numero] ERROR: " . $error->getMessage() . "\n";

        // ==========================================
        // MANEJO DE ERRORES NO GRAVES
        // ==========================================
        // Algunos errores no son problematicos:
        //   - "already exists": La tabla o restriccion ya fue creada antes.
        //       Puede pasar si ejecutamos el script dos veces.
        //   - "does not exist": Intentamos borrar o modificar algo que no existe.
        //       Puede pasar en DROP TABLE IF EXISTS o ALTER TABLE.
        //
        // strpos(): Similar a stripos() pero DISTINGUE mayusculas de minusculas.
        // Busca un substring dentro de otro y devuelve la posicion o false.
        //
        // !== false: Verifica que el substring SI fue encontrado (no retorno false).
        // Usamos !== (no identico) en vez de != (no igual) por la misma razon:
        // en PHP, false == 0 es verdadero, pero false !== 0 tambien es verdadero.
        //
        // Operador || (OR logico): Si CUALQUIERA de las dos condiciones es verdadera:
        //   1. El mensaje contiene "already exists"
        //   2. El mensaje contiene "does not exist"
        if (strpos($error->getMessage(), 'already exists') !== false ||
            strpos($error->getMessage(), 'does not exist') !== false) {
            // Informamos que no es un error grave, el script puede continuar.
            echo "       (No es grave - la tabla ya existia o no existia)\n";

            // Consideramos esto como un exito porque la intencion se cumplio:
            // si la tabla ya existe, no necesitamos crearla de nuevo.
            $exitos++; // Lo contamos como exito

            // Decrementamos el contador de fallos en 1 porque no fue un error real.
            // Esto compensa el incremento que hicimos al entrar al catch.
            $fallos--;
        }
    }
}

// ==========================================
// RESUMEN FINAL DE LA EJECUCION
// ==========================================
// Mostramos un separador visual con signos =
// \n: Cada \n produce un salto de linea. Multiples \n juntos dejan lineas en blanco.
echo "\n========================================\n";

// Mostramos el total de instrucciones ejecutadas correctamente.
// Las variables $exitos y $fallos se interpolan directamente dentro de comillas dobles.
echo "RESUMEN: $exitos instrucciones ejecutadas correctamente\n";

// Solo mostramos la linea de fallos si hubo al menos un error.
// Si $fallos es 0, no mostramos nada para no preocupar al usuario.
if ($fallos > 0) {
    echo "         $fallos instrucciones con error\n";
}

// Cerramos el separador visual.
echo "========================================\n";

// ==========================================
// VERIFICACION FINAL DE DATOS
// ==========================================
// Despues de crear las tablas e insertar los datos, verificamos que
// todo este correcto consultando la base de datos con SELECT.
echo "\n--- Verificando datos en la base ---\n";

// Otro bloque try-catch para la verificacion.
// Si algo falla al consultar (por ejemplo, la tabla no se creo),
// atrapamos el error y lo mostramos sin detener el script.
try {
    // $conexion_bd->query(): Metodo de PDO que ejecuta una sentencia SQL
    //                        que DEVUELVE resultados (SELECT).
    // A diferencia de exec(), query() retorna un objeto PDOStatement
    // que contiene los resultados de la consulta.
    //
    // ->fetch(): Metodo de PDOStatement que obtiene la PRIMERA fila del resultado
    //            como un array asociativo. Las claves del array son los nombres
    //            de las columnas (en este caso, 'c').
    //
    // SQL: SELECT COUNT(*) as c FROM videojuegos
    //      COUNT(*): Cuenta todas las filas de la tabla.
    //      as c: Le da el alias 'c' a la columna del resultado.
    //      Esto cuenta cuantos videojuegos hay en la tabla.
    //
    // $v: Array asociativo con el resultado. Ejemplo: ['c' => 5]
    $v = $conexion_bd->query("SELECT COUNT(*) as c FROM videojuegos")->fetch();

    // Mostramos la cantidad de videojuegos encontrados.
    // {$v['c']}: Accedemos al campo 'c' del array $v.
    //            Las llaves {} son necesarias dentro de comillas dobles
    //            para acceder a arrays asociativos.
    echo "Videojuegos en BD: {$v['c']}\n";

    // Hacemos lo mismo para la tabla resenas:
    // Contamos cuantas resenas hay en la base de datos.
    // $r: Array asociativo con el conteo de resenas.
    $r = $conexion_bd->query("SELECT COUNT(*) as c FROM resenas")->fetch();

    // Mostramos la cantidad de resenas encontradas.
    echo "Resenas en BD: {$r['c']}\n";

    // Si hay al menos 1 videojuego en la base de datos, mostramos la lista completa.
    // Esto le da al usuario una vista rapida de los datos insertados.
    if ($v['c'] > 0) {
        echo "\n--- Lista de videojuegos ---\n";

        // query(): Ejecutamos un SELECT para obtener id, nombre y genero
        //          de todos los videojuegos. Esto retorna un PDOStatement.
        // $lista: PDOStatement que contiene todas las filas de la tabla videojuegos.
        $lista = $conexion_bd->query("SELECT id, nombre, genero FROM videojuegos");

        // foreach: Iteramos sobre el PDOStatement $lista.
        // PDOStatement es iterable, asi que podemos usarlo directamente en foreach.
        // En cada iteracion, $juego es un array asociativo con las columnas
        // 'id', 'nombre' y 'genero' de un videojuego.
        foreach ($lista as $juego) {
            // Mostramos cada videojuego en formato: [id] nombre (genero)
            // Ejemplo: [1] The Legend of Zelda (Aventura)
            echo "  [{$juego['id']}] {$juego['nombre']} ({$juego['genero']})\n";
        }
    }

    // Mensaje final de exito con asteriscos para destacarlo visualmente.
    // Se muestra si todas las consultas de verificacion funcionaron.
    echo "\n*** BASE DE DATOS INICIALIZADA CORRECTAMENTE ***\n";

} catch (PDOException $error) {
    // Si la verificacion falla (tablas no existen, error de conexion, etc.),
    // mostramos el mensaje de error pero NO detenemos el script.
    // El usuario puede ver el error y diagnosticar el problema.
    echo "Error al verificar: " . $error->getMessage() . "\n";
}

// Cerramos la etiqueta <pre> de HTML.
// Todo el texto despues de esto se mostrara con formato normal.
echo "</pre>";

// Mensaje de advertencia de seguridad en HTML.
// <p>: Etiqueta de parrafo en HTML.
// <strong>: Etiqueta que pone el texto en NEGRITA.
// Recordamos al usuario que elimine este archivo del servidor por seguridad,
// ya que si se deja accesible, cualquiera podria reiniciar la base de datos.
echo "<p><strong>IMPORTANTE:</strong> Elimina este archivo (setup_db.php) del servidor por seguridad.</p>";

// Enlace HTML para volver a la pagina principal.
// <a>: Etiqueta de enlace (anchor).
// href='index.php': URL de destino. Al hacer clic, navega al index.php.
// Esto es una cortesia para que el usuario no tenga que escribir la URL manualmente.
echo "<p><a href='index.php'>Ir a la pagina principal</a></p>";

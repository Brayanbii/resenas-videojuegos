<?php
/**
 * ============================================================
 * funciones_api.php
 * Funciones auxiliares para comunicarse con la API de Flask.
 * Estas funciones permiten que PHP (PostgreSQL) envie datos
 * al servicio de analitica en Python (MongoDB).
 * ============================================================
 *
 * ¿COMO SE COMUNICA PHP CON FLASK?
 *
 * PHP (cliente)  ---->  HTTP (JSON)  ---->  Flask (servidor API)
 *
 * La aplicacion PHP actua como CLIENTE HTTP, enviando peticiones
 * a los endpoints de la API Flask. Esto se conoce como "consumir
 * una API REST".
 *
 * METODOS DE COMUNICACION USADOS:
 *
 * 1. cURL (Client URL Library):
 *    - Biblioteca nativa de PHP para hacer peticiones HTTP.
 *    - Soporta GET, POST, PUT, DELETE, headers personalizados.
 *    - Mas potente y configurable que file_get_contents.
 *    - Se usa en la funcion: enviar_resena_a_api() (POST)
 *
 * 2. file_get_contents():
 *    - Funcion simple de PHP para leer archivos y URLs.
 *    - Con un stream_context, puede hacer peticiones HTTP GET.
 *    - Menos configurable pero mas simple para GET.
 *    - Se usa en: consultar_estadisticas_api() (GET)
 *
 * FORMATO DE DATOS: JSON (JavaScript Object Notation)
 *    - Ambos sistemas (PHP y Flask) usan JSON para intercambiar datos.
 *    - PHP: json_encode($array) -> convierte array a string JSON
 *           json_decode($string) -> convierte string JSON a array
 *    - Flask: request.get_json() -> recibe JSON y lo convierte a diccionario
 *             jsonify($dict)     -> convierte diccionario a respuesta JSON
 */


/**
 * ============================================================
 * enviar_resena_a_api()
 * ============================================================
 * Envia los datos de una nueva reseña a la API de Flask
 * para que el servicio de analitica actualice las estadisticas
 * en MongoDB.
 *
 * METODO HTTP: POST
 * ENDPOINT:    /api/videojuegos
 *
 * FLUJO DE DATOS:
 *   1. PHP recibe el formulario de reseña
 *   2. PHP guarda la reseña en PostgreSQL
 *   3. PHP llama a esta funcion para notificar a Flask
 *   4. Flask recibe los datos y actualiza MongoDB
 *
 * @param int    $videojuego_id    - ID del videojuego en PostgreSQL
 * @param int    $calificacion     - Calificacion de 1 a 5 estrellas
 * @param string $nombre_videojuego - Nombre del videojuego (para mostrar en estadisticas)
 * @return string - Mensaje con el resultado de la peticion (exito o error)
 */
function enviar_resena_a_api($videojuego_id, $calificacion, $nombre_videojuego) {

    // URL base de la API Flask
    // En Render: https://resenas-flask.onrender.com
    // En Docker local: http://flask:10000
    // En desarrollo: http://localhost:5000
    $url_base = getenv('API_FLASK_URL') ?: 'http://localhost:5000';

    // Construimos la URL completa concatenando el endpoint
    // Esto permite cambiar solo la URL base segun el entorno
    $url_api = $url_base . '/api/videojuegos';

    // --------------------------------------------------------
    // PREPARACION DE DATOS
    // Convertimos los datos PHP a un array asociativo
    // que luego se transformara a JSON
    // --------------------------------------------------------
    $datos = [
        'videojuego_id'    => $videojuego_id,      // ID del juego en PostgreSQL
        'calificacion'     => $calificacion,         // Calificacion de 1 a 5
        'nombre_videojuego' => $nombre_videojuego    // Nombre para mostrar en rankings
    ];

    // --------------------------------------------------------
    // CONFIGURACION DE cURL
    // curl_init() crea un manejador de sesion cURL
    // --------------------------------------------------------
    $curl = curl_init();

    // curl_setopt_array() configura multiples opciones de una vez
    // Cada opcion tiene un prefijo CURLOPT_ que indica su proposito
    curl_setopt_array($curl, [
        // La URL a la que haremos la peticion
        CURLOPT_URL => $url_api,

        // CURLOPT_RETURNTRANSFER: true = devuelve la respuesta como string
        // false = imprime la respuesta directamente (no lo queremos)
        CURLOPT_RETURNTRANSFER => true,

        // CURLOPT_POST: true = el metodo HTTP sera POST
        // false = el metodo HTTP sera GET (por defecto)
        CURLOPT_POST => true,

        // CURLOPT_POSTFIELDS: datos a enviar en el cuerpo de la peticion
        // json_encode() convierte el array PHP a un string JSON
        // Ejemplo: {"videojuego_id":"1","calificacion":"5","nombre_videojuego":"Zelda"}
        CURLOPT_POSTFIELDS => json_encode($datos),

        // CURLOPT_HTTPHEADER: cabeceras HTTP personalizadas
        // Content-Type: application/json -> indica al servidor que enviamos JSON
        // Accept: application/json -> indica que esperamos respuesta JSON
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],

        // CURLOPT_TIMEOUT: tiempo maximo de espera en segundos
        // Si el servidor no responde en 10 segundos, cURL aborta
        CURLOPT_TIMEOUT => 10
    ]);

    // --------------------------------------------------------
    // EJECUCION DE LA PETICION
    // curl_exec() envia la peticion y espera la respuesta
    // --------------------------------------------------------
    $respuesta = curl_exec($curl);

    // curl_error() devuelve un mensaje si hubo error en la peticion
    // (problemas de red, DNS, timeout, etc.)
    $error_curl = curl_error($curl);

    // curl_getinfo() obtiene metadatos de la peticion
    // CURLINFO_HTTP_CODE devuelve el codigo de estado HTTP
    // 200 = OK, 201 = Created, 400 = Bad Request, 500 = Error interno
    $codigo_http = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    // curl_close() libera los recursos de la sesion cURL
    // IMPORTANTE: siempre cerrar despues de usar
    curl_close($curl);

    // --------------------------------------------------------
    // MANEJO DE RESULTADOS
    // --------------------------------------------------------
    if ($error_curl) {
        // Si hubo error de conexion (red, DNS, timeout)
        return "Error cURL: " . $error_curl;
    }

    // Si la peticion fue exitosa, devolvemos el codigo HTTP y la respuesta
    return "API respondio con codigo $codigo_http: " . $respuesta;
}


/**
 * ============================================================
 * consultar_estadisticas_api()
 * ============================================================
 * Consulta las estadisticas generales desde la API Flask.
 * Esta funcion se llama desde estadisticas.php para mostrar
 * los datos analiticos almacenados en MongoDB.
 *
 * METODO HTTP: GET
 * ENDPOINT:    /api/estadisticas
 *
 * ¿POR QUE file_get_contents() EN LUGAR DE cURL?
 * Para peticiones GET simples, file_get_contents() es suficiente.
 * No necesitamos la potencia de cURL para solo leer datos.
 * Sin embargo, en un proyecto real se recomendaria usar cURL
 * para todo por consistencia y mejor manejo de errores.
 *
 * @return array|null - Array con las estadisticas, o null si falla la conexion
 */
function consultar_estadisticas_api() {

    // URL base de la API Flask
    $url_base = getenv('API_FLASK_URL') ?: 'http://localhost:5000';

    // Construimos la URL completa del endpoint de estadisticas
    $url_api = $url_base . '/api/estadisticas';

    // --------------------------------------------------------
    // STREAM CONTEXT
    // stream_context_create() permite configurar opciones para
    // funciones de flujo de PHP como file_get_contents().
    //
    // Esto es necesario para:
    // - Establecer el metodo HTTP (GET)
    // - Enviar cabeceras personalizadas (Accept: application/json)
    // - Configurar timeout
    // --------------------------------------------------------
    $contexto = stream_context_create([
        'http' => [
            'method' => 'GET',                    // Metodo HTTP
            'header' => "Accept: application/json\r\n",  // Esperamos JSON
            'timeout' => 10                       // Timeout de 10 segundos
        ]
    ]);

    // file_get_contents() con URL + contexto hace una peticion HTTP GET
    // El @ suprime los warnings para manejarlos nosotros con if/else
    $respuesta = @file_get_contents($url_api, false, $contexto);

    // Si la respuesta es false, hubo un error de conexion
    if ($respuesta === false) {
        return null; // Devolvemos null para que estadisticas.php muestre mensaje de error
    }

    // json_decode() convierte el string JSON en un array asociativo de PHP
    // El segundo parametro (true) indica que queremos array asociativo, no objeto
    return json_decode($respuesta, true);
}


/**
 * ============================================================
 * consultar_mejores_videojuegos_api()
 * ============================================================
 * Consulta el ranking de los mejores videojuegos desde la API Flask.
 * Devuelve una lista ordenada por promedio de calificacion (descendente).
 *
 * METODO HTTP: GET
 * ENDPOINT:    /api/mejores-videojuegos
 *
 * @return array|null - Array con los mejores videojuegos, o null si falla
 */
function consultar_mejores_videojuegos_api() {

    // URL base de la API Flask
    $url_base = getenv('API_FLASK_URL') ?: 'http://localhost:5000';

    // Construimos la URL del endpoint de mejores videojuegos
    $url_api = $url_base . '/api/mejores-videojuegos';

    // Configuramos el contexto HTTP para la peticion GET
    $contexto = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n",
            'timeout' => 10
        ]
    ]);

    // Realizamos la peticion GET al endpoint
    $respuesta = @file_get_contents($url_api, false, $contexto);

    // Verificamos si la peticion fue exitosa
    if ($respuesta === false) {
        return null;
    }

    // Convertimos la respuesta JSON a array PHP
    return json_decode($respuesta, true);
}

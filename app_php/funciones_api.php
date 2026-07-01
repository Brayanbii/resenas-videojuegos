<?php
/**
 * funciones_api.php
 * Funciones auxiliares para comunicarse con la API de Flask (Python).
 * Se usa cURL basico para enviar datos al servicio de analitica.
 */

/**
 * Envia los datos de una nueva resena a la API de Flask
 * para que actualice las estadisticas en MongoDB.
 */
function enviar_resena_a_api($videojuego_id, $calificacion, $nombre_videojuego) {
    // URL base del servicio Flask (sin slash al final)
    // Se construye la ruta completa concatenando el endpoint
    $url_base = getenv('API_FLASK_URL') ?: 'http://localhost:5000';
    $url_api = $url_base . '/api/videojuegos';

    // Datos que enviaremos en formato JSON
    $datos = [
        'videojuego_id' => $videojuego_id,
        'calificacion' => $calificacion,
        'nombre_videojuego' => $nombre_videojuego
    ];

    // Inicializamos cURL
    $curl = curl_init();

    // Configuramos las opciones de cURL
    curl_setopt_array($curl, [
        CURLOPT_URL => $url_api,
        CURLOPT_RETURNTRANSFER => true,      // Devuelve la respuesta como string
        CURLOPT_POST => true,                  // Metodo POST
        CURLOPT_POSTFIELDS => json_encode($datos), // Datos en JSON
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',   // Indicamos que enviamos JSON
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 10                  // Timeout de 10 segundos
    ]);

    // Ejecutamos la peticion
    $respuesta = curl_exec($curl);
    $error_curl = curl_error($curl);
    $codigo_http = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    // Cerramos la conexion cURL
    curl_close($curl);

    // Devolvemos el resultado (puede servir para depuracion)
    if ($error_curl) {
        return "Error cURL: " . $error_curl;
    }

    return "API respondio con codigo $codigo_http: " . $respuesta;
}

/**
 * Consulta las estadisticas desde la API de Flask.
 * Usa file_get_contents como alternativa simple a cURL.
 */
function consultar_estadisticas_api() {
    $url_base = getenv('API_FLASK_URL') ?: 'http://localhost:5000';
    $url_api = $url_base . '/api/estadisticas';

    // Usamos file_get_contents con contexto para hacer GET
    $contexto = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n",
            'timeout' => 10
        ]
    ]);

    $respuesta = @file_get_contents($url_api, false, $contexto);

    if ($respuesta === false) {
        return null; // Error al conectar con la API
    }

    return json_decode($respuesta, true); // Convertimos JSON a array asociativo
}

/**
 * Consulta los mejores videojuegos desde la API de Flask.
 */
function consultar_mejores_videojuegos_api() {
    $url_base = getenv('API_FLASK_URL') ?: 'http://localhost:5000';
    $url_api = $url_base . '/api/mejores-videojuegos';

    $contexto = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n",
            'timeout' => 10
        ]
    ]);

    $respuesta = @file_get_contents($url_api, false, $contexto);

    if ($respuesta === false) {
        return null;
    }

    return json_decode($respuesta, true);
}

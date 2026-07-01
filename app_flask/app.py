# -*- coding: utf-8 -*-
"""
================================================================================
 app.py
 API REST de Analítica de Videojuegos desarrollada en Flask.
 Se conecta a MongoDB Atlas para almacenar y consultar datos estadísticos.

 Endpoints disponibles:
   POST /api/videojuegos       -> Registrar reseña y actualizar estadísticas
   GET  /api/estadisticas      -> Consultar estadísticas generales
   GET  /api/mejores-videojuegos -> Listar videojuegos mejor calificados

 Autor: Proyecto Plan de Mejora
================================================================================
"""

# ============================================================================
# IMPORTACIÓN DE BIBLIOTECAS
# ============================================================================

# Flask: Es un "micro-framework" para crear aplicaciones web en Python.
#   - Flask (con mayúscula): Es la clase principal que representa nuestra
#     aplicación web. Con ella creamos rutas, manejamos peticiones y
#     respuestas, etc.
#   - request: Es un objeto global que Flask nos proporciona y que contiene
#     TODA la información de la petición HTTP que acaba de llegar (datos
#     enviados por el cliente, cabeceras, método HTTP usado, etc.).
#   - jsonify: Es una función de Flask que convierte un diccionario de Python
#     en una respuesta HTTP con formato JSON. Además, automáticamente añade la
#     cabecera "Content-Type: application/json" a la respuesta.
from flask import Flask, request, jsonify

# PyMongo: Es la librería oficial de MongoDB para Python.
#   - MongoClient: Es la clase que nos permite conectarnos a un servidor de
#     MongoDB (local o en la nube como MongoDB Atlas). Una vez conectados,
#     podemos seleccionar bases de datos y colecciones para hacer operaciones
#     CRUD (Create, Read, Update, Delete).
#     Piensa en MongoClient como "el puente" entre nuestro código Python y
#     el motor de base de datos MongoDB.
from pymongo import MongoClient

# os: Es un módulo de la biblioteca estándar de Python para interactuar con
#     el sistema operativo. En este proyecto lo usamos específicamente para:
#     1. Leer variables de entorno (os.environ.get): Nos permite obtener
#        valores de configuración sensibles (como la URI de MongoDB) sin
#        tener que escribirlos directamente en el código. Esto es una
#        BUENA PRÁCTICA de seguridad.
#     2. Acceder a la variable PORT que plataformas como Render asignan
#        automáticamente para decirle a nuestra app en qué puerto escuchar.
import os

# datetime: Es un módulo de la biblioteca estándar de Python para trabajar
#     con fechas y horas.
#   - datetime.utcnow(): Nos da la fecha y hora actual en formato UTC
#     (Tiempo Universal Coordinado). Usamos UTC para evitar problemas de
#     zonas horarias cuando la aplicación se ejecuta en diferentes países.
#   - .isoformat(): Convierte la fecha a un string en formato ISO 8601
#     (ej: "2026-06-30T14:30:00"), que es un estándar internacional fácil
#     de leer y procesar.
from datetime import datetime

# ============================================================================
# CREACIÓN DE LA APLICACIÓN FLASK
# ============================================================================

# Aquí creamos la instancia de nuestra aplicación Flask.
# Flask(__name__) hace dos cosas importantes:
#   1. Le dice a Flask dónde está ubicado este archivo (app.py) para que
#      pueda encontrar recursos como plantillas HTML y archivos estáticos.
#   2. __name__ es una variable especial de Python: si ejecutamos este
#      archivo directamente, vale "__main__"; si es importado desde otro
#      archivo, vale el nombre del módulo. Esto ayuda a Flask a configurarse
#      correctamente.
aplicacion = Flask(__name__)

# ============================================================================
# CONEXIÓN A MONGODB ATLAS
# ============================================================================

# Variables de entorno: Son valores de configuración almacenados en el sistema
# operativo, NO en el código fuente. Esto es CRUCIAL para la seguridad porque
# evita que contraseñas y URIs de conexión queden expuestas en el repositorio
# de código (GitHub, GitLab, etc.).
#
# os.environ.get('MONGO_URI', 'mongodb://localhost:27017/') hace lo siguiente:
#   1. Busca una variable de entorno llamada MONGO_URI.
#   2. Si la encuentra, usa ese valor (ej: la URI de MongoDB Atlas en la nube).
#   3. Si NO la encuentra (porque estamos en desarrollo local), usa el valor
#      por defecto: 'mongodb://localhost:27017/' que apunta a un MongoDB
#      instalado en nuestra propia máquina.
uri_mongo = os.environ.get('MONGO_URI', 'mongodb://localhost:27017/')

# MongoClient(uri_mongo): Crea una conexión al servidor de MongoDB usando la
# URI. MongoDB es una base de datos NoSQL (No solo SQL) orientada a documentos.
# En lugar de usar tablas con filas y columnas como SQL, MongoDB guarda la
# información en "documentos" que son similares a objetos JSON.
cliente_mongo = MongoClient(uri_mongo)

# Seleccionamos (o creamos) la base de datos "analitica_videojuegos".
# En MongoDB, si la base de datos no existe, se crea automáticamente cuando
# insertes el primer documento. No necesitas un comando CREATE DATABASE.
# Esto es diferente a SQL donde primero debes crear la base de datos.
base_datos = cliente_mongo['analitica_videojuegos']

# Seleccionamos (o creamos) la colección "estadisticas_videojuegos".
# UNA COLECCIÓN EN MONGODB ES EQUIVALENTE A UNA TABLA EN SQL.
# - Tabla SQL = Colección MongoDB
# - Fila SQL   = Documento MongoDB
# - Columna SQL = Campo del documento MongoDB
# La gran diferencia es que en MongoDB cada documento puede tener una
# estructura diferente (campos distintos), mientras que en SQL todas las
# filas de una tabla deben tener las mismas columnas.
coleccion_estadisticas = base_datos['estadisticas_videojuegos']

# Mensaje de confirmación: Esto aparece en la consola/terminal cuando
# iniciamos la aplicación, para saber que la conexión fue exitosa.
print("Conectado a MongoDB - Base de datos: analitica_videojuegos")


# ============================================================================
# ENDPOINT 1: POST /api/videojuegos
# Registra una reseña y actualiza las estadísticas del videojuego
# ============================================================================

# @aplicacion.route(...): ES UN DECORADOR DE FLASK.
#
# ¿QUÉ ES UN DECORADOR EN PYTHON?
# Un decorador es una función especial que "envuelve" a otra función para
# añadirle funcionalidad extra sin modificar su código. Se escribe con @
# antes de la definición de la función.
#
# ¿QUÉ HACE @aplicacion.route en Flask?
# Registra una función para que sea ejecutada cuando llegue una petición
# HTTP a una URL específica. En otras palabras, le dice a Flask:
# "Cuando alguien visite /api/videojuegos con método POST, ejecuta la
# función registrar_resena()".
#
# Parámetros del decorador:
#   - '/api/videojuegos': La RUTA o URL donde este endpoint estará disponible.
#   - methods=['POST']: SÓLO acepta peticiones con el método HTTP POST.
#     Si alguien intenta acceder con GET, PUT, DELETE u otro método,
#     Flask responderá automáticamente con un error 405 (Method Not Allowed).
#
# MÉTODO HTTP POST:
# - POST se usa para ENVIAR/ENVIAR datos al servidor y CREAR o ACTUALIZAR
#   recursos. Es el método que usan los formularios web para enviar datos.
# - A diferencia de GET, los datos viajan en el CUERPO de la petición,
#   no en la URL, lo que permite enviar grandes cantidades de información.
# - POST NO es idempotente: enviar la misma petición varias veces puede
#   crear múltiples registros (en nuestro caso, múltiples reseñas).
@aplicacion.route('/api/videojuegos', methods=['POST'])
def registrar_resena():
    """
    FUNCIÓN MANEJADORA DEL ENDPOINT POST /api/videojuegos

    Esta función se ejecuta CUANDO el sistema PHP (u otro cliente) envía una
    reseña de un videojuego a través de una petición POST.

    FLUJO DE TRABAJO:
      1. Recibe datos JSON con: videojuego_id, calificacion, nombre_videojuego
      2. Valida que los datos sean correctos
      3. Busca si el videojuego ya existe en MongoDB
      4. Si existe: ACTUALIZA sus estadísticas (total de reseñas, promedio, etc.)
      5. Si no existe: CREA un nuevo documento con los datos iniciales
      6. Responde con un JSON confirmando la operación

    Datos esperados en el cuerpo JSON de la petición:
      - videojuego_id: ID del videojuego en PostgreSQL (entero)
      - calificacion:  Calificación del 1 al 5 (entero)
      - nombre_videojuego: Nombre del videojuego (string)
    """

    # --------------------------------------------------------------------
    # request.get_json(): Lee y decodifica el cuerpo de la petición HTTP.
    #
    # ¿QUÉ HACE EXACTAMENTE?
    # 1. Lee los bytes enviados en el cuerpo de la petición POST.
    # 2. Los interpreta como texto JSON.
    # 3. Convierte ese JSON a un diccionario de Python.
    #
    # EJEMPLO: Si el cliente envía {"calificacion": 5, "videojuego_id": 42},
    # request.get_json() devuelve el diccionario Python:
    #    {'calificacion': 5, 'videojuego_id': 42}
    #
    # Si el cuerpo está vacío o no es JSON válido, devuelve None.
    # --------------------------------------------------------------------
    datos = request.get_json()

    # Validación 1: ¿Se recibieron datos? Si datos es None o un diccionario
    # vacío, significa que el cliente no envió nada útil. Respondemos con
    # un error 400 (Bad Request) para indicar que la petición es incorrecta.
    if not datos:
        # jsonify(): Convierte el diccionario de Python {'error': '...'} en
        # una respuesta HTTP con formato JSON y la cabecera Content-Type
        # adecuada. El código 400 significa "Bad Request" (Petición incorrecta).
        return jsonify({'error': 'No se recibieron datos JSON'}), 400

    # Extraemos cada campo del diccionario usando .get().
    # .get() es un método de los diccionarios de Python que obtiene el valor
    # asociado a una clave. Si la clave NO existe, devuelve None o el valor
    # por defecto que le pasemos como segundo argumento.
    # Ej: datos.get('nombre_videojuego', 'Sin nombre') -> Si el campo
    # 'nombre_videojuego' no está en el JSON, usa 'Sin nombre' como valor.
    videojuego_id = datos.get('videojuego_id')
    calificacion = datos.get('calificacion')
    nombre_videojuego = datos.get('nombre_videojuego', 'Sin nombre')

    # Validación 2: ¿Faltan campos obligatorios?
    # videojuego_id y calificacion son REQUERIDOS. Si cualquiera de ellos
    # es None (no se envió), respondemos con error 400 y un mensaje
    # descriptivo para que el cliente sepa qué corregir.
    if not videojuego_id or not calificacion:
        return jsonify({'error': 'Faltan campos obligatorios: videojuego_id y calificacion'}), 400

    # Validación 3: ¿La calificación es un número válido?
    # Usamos try/except para manejar errores de conversión.
    # int(calificacion) intenta convertir el valor a entero. Si el usuario
    # envió "cinco" o 3.7, int() lanzará una excepción ValueError.
    # También capturamos TypeError por si enviaron algo como un array.
    try:
        calificacion = int(calificacion)
        if calificacion < 1 or calificacion > 5:
            return jsonify({'error': 'La calificacion debe estar entre 1 y 5'}), 400
    except (ValueError, TypeError):
        return jsonify({'error': 'La calificacion debe ser un numero entero'}), 400

    # ====================================================================
    # MONGODB: OPERACIÓN find_one()
    # ====================================================================
    # find_one() busca en la colección el PRIMER documento que coincida con
    # el filtro y lo devuelve como un diccionario de Python.
    # Si NO encuentra ningún documento que cumpla la condición, devuelve None.
    #
    # En este caso, buscamos un documento cuyo campo 'videojuego_id' sea
    # igual al ID que recibimos en la petición. Esto nos permite saber si
    # ya tenemos estadísticas para ese videojuego.
    #
    # DIFERENCIA CON find(): find_one devuelve UN SOLO documento (o None),
    # mientras que find() devuelve un CURSOR (iterador) sobre TODOS los
    # documentos que coinciden.
    documento_existente = coleccion_estadisticas.find_one({'videojuego_id': videojuego_id})

    # LÓGICA: ¿El videojuego ya existe en nuestra base de datos?
    if documento_existente:
        # ================================================================
        # CASO 1: El videojuego YA EXISTE -> ACTUALIZAMOS sus estadísticas
        # ================================================================

        # Calculamos los NUEVOS valores:
        # - nuevo_total: Sumamos 1 al contador de reseñas existente.
        #   Si antes tenía 10 reseñas, ahora tendrá 11.
        nuevo_total = documento_existente['total_resenas'] + 1

        # - nueva_suma: Sumamos la nueva calificación a la suma acumulada.
        #   Esto nos permite luego calcular el promedio sin guardar todas
        #   las calificaciones individuales (solo guardamos LA SUMA).
        nueva_suma = documento_existente['suma_calificaciones'] + calificacion

        # - nuevo_promedio: Calculamos el promedio dividiendo la suma total
        #   entre el número total de reseñas. round() redondea a 2 decimales.
        #   Ej: suma=42, total=10 -> promedio=4.2
        nuevo_promedio = round(nueva_suma / nuevo_total, 2)

        # ================================================================
        # MONGODB: OPERACIÓN update_one()
        # ================================================================
        # update_one() modifica el PRIMER documento que coincida con el filtro.
        #
        # Parámetros:
        #   1. FILTRO: {'videojuego_id': videojuego_id}
        #      Indica QUÉ documento modificar (el del videojuego específico).
        #
        #   2. ACTUALIZACIÓN: {'$set': { ... }}
        #      $set es un OPERADOR de MongoDB que reemplaza los valores de
        #      los campos especificados. Si un campo no existía, lo crea.
        #      SÓLO modifica los campos indicados; el resto del documento
        #      queda intacto.
        #
        #      Campos que actualizamos:
        #        - total_resenas: Cuántas reseñas se han hecho en total.
        #        - suma_calificaciones: Suma de todas las calificaciones.
        #        - promedio_calificacion: Promedio recalculado.
        #        - ultima_resena: Fecha/hora de la reseña más reciente.
        #        - ultima_calificacion: El valor de la última calificación.
        #
        # DIFERENCIA CON update_many(): update_one modifica UN documento,
        # update_many modifica TODOS los que coincidan con el filtro.
        #
        # datetime.utcnow(): Obtiene la fecha/hora UTC actual.
        # .isoformat(): La convierte a texto ISO 8601 (ej: "2026-06-30T18:00:00").
        coleccion_estadisticas.update_one(
            {'videojuego_id': videojuego_id},
            {'$set': {
                'total_resenas': nuevo_total,
                'suma_calificaciones': nueva_suma,
                'promedio_calificacion': nuevo_promedio,
                'ultima_resena': datetime.utcnow().isoformat(),
                'ultima_calificacion': calificacion
            }}
        )

        # Preparamos el mensaje de éxito para la respuesta
        mensaje = f"Estadisticas actualizadas para '{nombre_videojuego}'"

    else:
        # ================================================================
        # CASO 2: El videojuego NO EXISTE -> CREAMOS un nuevo documento
        # ================================================================

        # Construimos el diccionario con TODA la información inicial.
        # Este diccionario será el documento que se guarde en MongoDB.
        nuevo_documento = {
            'videojuego_id': videojuego_id,           # ID del videojuego en PostgreSQL
            'nombre_videojuego': nombre_videojuego,    # Nombre del videojuego
            'genero': datos.get('genero', 'No especificado'),  # Género (opcional)
            'total_resenas': 1,                        # Empieza con 1 reseña
            'suma_calificaciones': calificacion,       # La suma inicial = esta calificación
            'promedio_calificacion': float(calificacion),  # El promedio inicial
            'primera_resena': datetime.utcnow().isoformat(), # Fecha de la primera reseña
            'ultima_resena': datetime.utcnow().isoformat(),  # Fecha de la última (misma que primera)
            'ultima_calificacion': calificacion        # Última calificación registrada
        }

        # ================================================================
        # MONGODB: OPERACIÓN insert_one()
        # ================================================================
        # insert_one() INSERTA un NUEVO documento en la colección.
        #
        # - Recibe un diccionario de Python que MongoDB convierte a BSON
        #   (Binary JSON, el formato interno de MongoDB) y lo guarda.
        # - MongoDB genera AUTOMÁTICAMENTE un campo '_id' único para el
        #   documento si no lo proporcionamos. Este _id es el identificador
        #   único del documento (equivalente a una PRIMARY KEY en SQL).
        # - Si la colección no existe, MongoDB la CREA automáticamente
        #   al insertar el primer documento.
        #
        # DIFERENCIA CON insert_many(): insert_one inserta UN documento,
        # insert_many inserta UNA LISTA de documentos de una sola vez.
        coleccion_estadisticas.insert_one(nuevo_documento)

        # Preparamos el mensaje de éxito
        mensaje = f"Nuevo videojuego registrado: '{nombre_videojuego}'"

    # --------------------------------------------------------------------
    # RESPUESTA FINAL DEL ENDPOINT
    # --------------------------------------------------------------------
    # jsonify() convierte el diccionario en una respuesta HTTP JSON.
    # El código 201 significa "Created" (Creado), indicando que se creó
    # un nuevo recurso o se actualizó uno existente exitosamente.
    #
    # La respuesta que recibe el cliente tiene este formato:
    # {
    #   "mensaje": "Estadisticas actualizadas para 'Zelda'",
    #   "videojuego_id": 5,
    #   "estado": "ok"
    # }
    return jsonify({
        'mensaje': mensaje,
        'videojuego_id': videojuego_id,
        'estado': 'ok'
    }), 201


# ============================================================================
# ENDPOINT 2: GET /api/estadisticas
# Devuelve estadísticas generales de todos los videojuegos registrados
# ============================================================================

# MÉTODO HTTP GET:
# - GET se usa para SOLICITAR/CONSULTAR datos del servidor, NUNCA para
#   modificarlos. Es el método que usa el navegador cuando escribes una URL.
# - Los parámetros viajan en la URL (ej: /api/estadisticas?filtro=accion).
# - GET ES IDEMPOTENTE: hacer la misma petición varias veces produce
#   siempre el mismo resultado y no modifica nada en el servidor.
# - GET ES SEGURO (safe): no tiene efectos secundarios en el servidor.
#
# @aplicacion.route('/api/estadisticas', methods=['GET']):
# Cuando alguien visite /api/estadisticas con GET, se ejecuta la función
# obtener_estadisticas(). Cualquier otro método (POST, DELETE, etc.)
# recibirá error 405.
@aplicacion.route('/api/estadisticas', methods=['GET'])
def obtener_estadisticas():
    """
    FUNCIÓN MANEJADORA DEL ENDPOINT GET /api/estadisticas

    Esta función se ejecuta cuando el sistema PHP (u otro cliente) solicita
    un RESUMEN de todas las estadísticas de videojuegos almacenadas en MongoDB.

    DATOS QUE DEVUELVE:
      - Total de videojuegos registrados
      - Total de reseñas acumuladas entre todos los videojuegos
      - Promedio general de calificaciones (de TODOS los videojuegos)
      - Videojuego mejor calificado (el de mayor promedio)
      - Videojuego con más reseñas (el más reseñado)

    TODOS los datos se calculan a partir de los documentos en MongoDB.
    NO se necesita PHP para este endpoint; cualquier cliente HTTP puede
    consumir esta API (Postman, navegador, curl, otra aplicación, etc.).
    """

    # ====================================================================
    # MONGODB: OPERACIÓN count_documents()
    # ====================================================================
    # count_documents() cuenta CUÁNTOS documentos en la colección cumplen
    # con el filtro especificado.
    #
    # count_documents({}): El filtro {} (diccionario vacío) significa
    # "SIN FILTRO", es decir, cuenta TODOS los documentos de la colección.
    #
    # También podríamos filtrar, por ejemplo:
    #   count_documents({'genero': 'Acción'})  -> Cuenta solo los de acción
    #
    # DIFERENCIA CON estimated_document_count():
    # count_documents() es PRECISO pero puede ser más lento en colecciones
    # enormes. estimated_document_count() es una ESTIMACIÓN rápida basada
    # en metadatos (útil para colecciones con millones de documentos).
    total_videojuegos = coleccion_estadisticas.count_documents({})

    # Caso especial: Si no hay ningún documento en la colección, devolvemos
    # una respuesta con todos los valores en 0 o "Ninguno" para que el
    # frontend (PHP/HTML) no tenga que manejar valores nulos.
    # Esto evita errores de "undefined" o "null" en el cliente.
    if total_videojuegos == 0:
        # El código HTTP 200 significa "OK" (la petición fue exitosa).
        # Incluso cuando no hay datos, es una respuesta válida.
        return jsonify({
            'total_videojuegos': 0,
            'total_resenas': 0,
            'promedio_general': 0,
            'mejor_calificado': 'Ninguno',
            'mas_resenado': 'Ninguno'
        })

    # ====================================================================
    # MONGODB: OPERACIÓN aggregate() - PIPELINE DE AGREGACIÓN
    # ====================================================================
    #
    # aggregate() es una de las operaciones MÁS PODEROSAS de MongoDB.
    # Permite procesar documentos a través de una SERIE DE ETAPAS (stages)
    # donde cada etapa transforma los datos y pasa el resultado a la
    # siguiente etapa (como una tubería o "pipeline").
    #
    # ES EQUIVALENTE A: GROUP BY, SUM, AVG, COUNT de SQL, pero mucho más
    # flexible porque puedes encadenar múltiples etapas de transformación.
    #
    # NUESTRO PIPELINE (UNA SOLA ETAPA):
    #   Etapa 1 - $group: Agrupa TODOS los documentos en UN SOLO GRUPO
    #     Parámetros:
    #       - '_id': None  -> Agrupa TODOS los documentos juntos sin
    #         separación por categoría (como GROUP BY sin columna en SQL).
    #         Si quisiéramos agrupar por género, usaríamos '_id': '$genero'.
    #
    #       - 'total_resenas': {'$sum': '$total_resenas'}
    #         $sum es un ACUMULADOR. Suma el valor del campo 'total_resenas'
    #         de CADA documento y lo guarda en un campo llamado 'total_resenas'.
    #         Resultado: la suma de TODAS las reseñas de TODOS los videojuegos.
    #
    #       - 'suma_calificaciones': {'$sum': '$suma_calificaciones'}
    #         Similar al anterior: suma el campo 'suma_calificaciones' de
    #         cada documento. Resultado: la suma de todas las calificaciones.
    #
    #   RESULTADO DEL PIPELINE:
    #     Si tenemos 3 videojuegos con calificaciones:
    #       Juego A: 10 reseñas, suma=42
    #       Juego B: 5 reseñas,  suma=20
    #       Juego C: 3 reseñas,  suma=11
    #     El pipeline devuelve:
    #       { total_resenas: 18, suma_calificaciones: 73 }
    #
    #   El símbolo $ antes de un campo (ej: '$total_resenas') le dice a
    #   MongoDB que se refiere al VALOR de ese campo en cada documento,
    #   no a un texto literal.
    pipeline = [
        {
            '$group': {
                '_id': None,
                'total_resenas': {'$sum': '$total_resenas'},
                'suma_calificaciones': {'$sum': '$suma_calificaciones'}
            }
        }
    ]

    # Ejecutamos el pipeline de agregación.
    # aggregate() devuelve un CURSOR (iterador), igual que find().
    # Lo convertimos a lista con list() para obtener los resultados.
    # Como agrupamos con _id=None, la lista tendrá UN SOLO elemento.
    resultado_agregado = list(coleccion_estadisticas.aggregate(pipeline))

    # Procesamos el resultado de la agregación
    if resultado_agregado:
        # Extraemos los totales del primer (y único) elemento de la lista
        total_resenas = resultado_agregado[0]['total_resenas']
        suma_total = resultado_agregado[0]['suma_calificaciones']

        # Calculamos el promedio general: suma_total / total_resenas
        # Usamos un operador ternario: si total_resenas > 0, calculamos;
        # si no, devolvemos 0. Esto evita división por cero.
        # round() redondea a 2 decimales para una presentación más limpia.
        promedio_general = round(suma_total / total_resenas, 2) if total_resenas > 0 else 0
    else:
        # Caso de seguridad: si el pipeline no devolvió nada (no debería
        # ocurrir si hay documentos), ponemos valores en cero.
        total_resenas = 0
        promedio_general = 0

    # ====================================================================
    # MONGODB: find_one() CON ORDENAMIENTO
    # ====================================================================
    #
    # Buscamos el videojuego MEJOR CALIFICADO:
    #   - Filtro {}: Sin filtro, considera TODOS los documentos.
    #   - sort=[('promedio_calificacion', -1)]: Ordena por el campo
    #     'promedio_calificacion' en orden DESCENDENTE (-1).
    #     El valor -1 significa "del mayor al menor" (descendente).
    #     El valor 1 significaría "del menor al mayor" (ascendente).
    #   - Como usamos find_one(), SOLO obtenemos el primer documento
    #     del resultado, que será el de MAYOR promedio (el mejor).
    mejor_calificado = coleccion_estadisticas.find_one(
        {},
        sort=[('promedio_calificacion', -1)]
    )

    # Buscamos el videojuego CON MÁS RESEÑAS:
    #   - Mismo filtro vacío (todos los documentos).
    #   - sort=[('total_resenas', -1)]: Ordena por cantidad de reseñas
    #     en orden DESCENDENTE.
    #   - El primer documento será el que tenga el mayor número de reseñas.
    mas_resenado = coleccion_estadisticas.find_one(
        {},
        sort=[('total_resenas', -1)]
    )

    # Construimos el diccionario de respuesta.
    # Usamos operadores ternarios (valor_si_verdad if condicion else valor_si_falso)
    # para manejar el caso en que find_one() devuelva None (no debería pasar aquí,
    # pero es una buena práctica de programación defensiva).
    estadisticas = {
        'total_videojuegos': total_videojuegos,                    # Cuántos videojuegos registrados
        'total_resenas': total_resenas,                            # Cuántas reseñas en total
        'promedio_general': promedio_general,                      # Promedio de TODAS las calificaciones
        'mejor_calificado': mejor_calificado['nombre_videojuego'] if mejor_calificado else 'Ninguno',
        'mejor_calificado_promedio': mejor_calificado['promedio_calificacion'] if mejor_calificado else 0,
        'mas_resenado': mas_resenado['nombre_videojuego'] if mas_resenado else 'Ninguno',
        'mas_resenado_total': mas_resenado['total_resenas'] if mas_resenado else 0
    }

    # jsonify() convierte el diccionario a respuesta JSON.
    # Código HTTP 200: OK (éxito).
    # La respuesta tiene este formato:
    # {
    #   "total_videojuegos": 15,
    #   "total_resenas": 342,
    #   "promedio_general": 3.87,
    #   "mejor_calificado": "The Legend of Zelda",
    #   "mejor_calificado_promedio": 4.9,
    #   "mas_resenado": "Minecraft",
    #   "mas_resenado_total": 58
    # }
    return jsonify(estadisticas), 200


# ============================================================================
# ENDPOINT 3: GET /api/mejores-videojuegos
# Devuelve una lista de videojuegos ordenados por mejor calificación
# ============================================================================

# Tercer endpoint: También usa GET porque es una CONSULTA de datos,
# no una modificación. La diferencia con /api/estadisticas es que
# este endpoint devuelve una LISTA DETALLADA de videojuegos en lugar
# de un resumen estadístico.
@aplicacion.route('/api/mejores-videojuegos', methods=['GET'])
def obtener_mejores_videojuegos():
    """
    FUNCIÓN MANEJADORA DEL ENDPOINT GET /api/mejores-videojuegos

    Devuelve una lista COMPLETA de videojuegos con sus estadísticas
    individuales, ordenados desde el MEJOR calificado hasta el PEOR.

    Cada elemento de la lista contiene:
      - videojuego_id: ID del videojuego
      - nombre_videojuego: Nombre
      - genero: Género del videojuego
      - total_resenas: Cuántas veces ha sido reseñado
      - promedio_calificacion: Su calificación promedio (1.0 a 5.0)
    """

    # ====================================================================
    # MONGODB: OPERACIÓN find() CON PROYECCIÓN
    # ====================================================================
    # find(): Busca TODOS los documentos que coinciden con el filtro y
    # devuelve un CURSOR sobre ellos. Un cursor es un iterador que permite
    # recorrer los resultados uno por uno sin cargarlos todos en memoria.
    #
    # Parámetros de find():
    #   1. FILTRO: {} (diccionario vacío) = SIN FILTRO, todos los documentos.
    #      Si quisiéramos filtrar por género: {'genero': 'Acción'}
    #
    #   2. PROYECCIÓN: Especifica QUÉ CAMPOS incluir o excluir del resultado.
    #      - '_id': 0 -> EXCLUIR el campo _id (MongoDB siempre lo incluye
    #        por defecto; con 0 le decimos que NO lo queremos).
    #      - 'videojuego_id': 1 -> INCLUIR este campo.
    #      - 'nombre_videojuego': 1 -> INCLUIR este campo.
    #      - 'genero': 1 -> INCLUIR este campo.
    #      - 'total_resenas': 1 -> INCLUIR este campo.
    #      - 'promedio_calificacion': 1 -> INCLUIR este campo.
    #
    #      TODOS los demás campos (suma_calificaciones, ultima_resena, etc.)
    #      se EXCLUYEN automáticamente porque no los incluimos.
    #
    #      REGLA DE PROYECCIÓN: No se puede mezclar INCLUSIÓN (1) y
    #      EXCLUSIÓN (0) excepto para el campo _id. Es decir, o defines
    #      qué campos INCLUIR (y el resto se excluye), o defines qué
    #      campos EXCLUIR (y el resto se incluye), pero no ambos a la vez.
    #
    # .sort('promedio_calificacion', -1): Encadena un ordenamiento al cursor.
    #   - Primer argumento: el campo por el cual ordenar.
    #   - Segundo argumento: DIRECCIÓN (-1 = DESCENDENTE, 1 = ASCENDENTE).
    #   -1 descendente significa que los mejor calificados aparecen PRIMERO.
    #
    # NOTA: find() + sort() es diferente a find_one() + sort:
    #   - find() + sort: Devuelve TODOS los documentos ordenados.
    #   - find_one() + sort: Devuelve SOLO el PRIMER documento ordenado.
    lista_mejores = coleccion_estadisticas.find(
        {},
        {
            '_id': 0,
            'videojuego_id': 1,
            'nombre_videojuego': 1,
            'genero': 1,
            'total_resenas': 1,
            'promedio_calificacion': 1
        }
    ).sort('promedio_calificacion', -1)

    # list(): Convierte el cursor en una lista de diccionarios de Python.
    # Esto es necesario porque el cursor es un iterador "perezoso" (lazy):
    # los datos se obtienen de MongoDB a medida que los recorres, no todos
    # de golpe. Al convertir a lista, forzamos a que se lean todos los
    # documentos en este momento para poder serializarlos a JSON.
    #
    # Si tuviéramos MILLONES de documentos, sería mejor NO convertir a
    # lista y usar paginación con .skip() y .limit() para no cargar
    # todo en memoria.
    resultado = list(lista_mejores)

    # RESPUESTA:
    # jsonify() serializa la lista de diccionarios a JSON.
    # Código 200: OK.
    #
    # Formato de respuesta:
    # [
    #   {
    #     "videojuego_id": 42,
    #     "nombre_videojuego": "Zelda",
    #     "genero": "Aventura",
    #     "total_resenas": 15,
    #     "promedio_calificacion": 4.8
    #   },
    #   { ... siguiente videojuego ... },
    #   ...
    # ]
    return jsonify(resultado), 200


# ============================================================================
# RUTA DE BIENVENIDA (RAÍZ)
# Verifica que la API está funcionando
# ============================================================================

# Ruta raíz ("/"): Es la página principal de la API.
# Cuando alguien visite la URL base de la aplicación sin ninguna ruta
# adicional (ej: http://localhost:5000/), se ejecuta esta función.
# Es útil para verificar RÁPIDAMENTE que el servidor está corriendo
# y mostrar la documentación básica de los endpoints disponibles.
@aplicacion.route('/', methods=['GET'])
def inicio():
    """
    Ruta de bienvenida.
    Devuelve un JSON con un mensaje de confirmación y la lista de
    endpoints disponibles para que el desarrollador sepa qué rutas
    puede usar.
    """
    return jsonify({
        'mensaje': 'API de Analitica de Videojuegos funcionando',
        'endpoints': {
            'POST /api/videojuegos': 'Registrar resena y actualizar estadisticas',
            'GET /api/estadisticas': 'Consultar estadisticas generales',
            'GET /api/mejores-videojuegos': 'Listar mejores videojuegos'
        }
    }), 200


# ============================================================================
# PUNTO DE ENTRADA DE LA APLICACIÓN
# ============================================================================

# Esta es una CONVENCIÓN de Python:
# if __name__ == '__main__': significa "si este archivo se está ejecutando
# DIRECTAMENTE (no importado como módulo desde otro archivo)".
#
# Esto permite que el mismo archivo funcione de dos maneras:
#   1. python app.py      -> Ejecuta el servidor (__name__ = '__main__')
#   2. import app         -> No ejecuta el servidor (__name__ = 'app')
if __name__ == '__main__':
    # Variable de entorno PORT:
    # Plataformas como Render, Heroku o Railway asignan automáticamente
    # la variable de entorno PORT con el número de puerto donde nuestra
    # app DEBE escuchar. Nosotros la leemos con os.environ.get().
    # Si no existe (desarrollo local), usamos el puerto 5000 por defecto.
    puerto = int(os.environ.get('PORT', 5000))

    # aplicacion.run(): Inicia el servidor web de desarrollo de Flask.
    # Parámetros:
    #   - host='0.0.0.0': Acepta conexiones desde CUALQUIER dirección IP
    #     (no solo desde localhost). Necesario para que funcione en la nube.
    #     '0.0.0.0' significa "escucha en TODAS las interfaces de red".
    #   - port=puerto: Puerto donde escucha el servidor.
    #   - debug=True: Activa el MODO DEBUG de Flask, que proporciona:
    #       * Recarga automática al detectar cambios en el código.
    #       * Mensajes de error detallados en el navegador.
    #       * Consola interactiva de depuración.
    #     ⚠ IMPORTANTE: debug=True SOLO debe usarse en DESARROLLO.
    #     En PRODUCCIÓN debe ser False por seguridad (expone información
    #     sensible y permite ejecución de código arbitrario).
    aplicacion.run(host='0.0.0.0', port=puerto, debug=True)

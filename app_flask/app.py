# -*- coding: utf-8 -*-
"""
app.py
API REST de Analitica de Videojuegos desarrollada en Flask.
Se conecta a MongoDB Atlas para almacenar y consultar datos estadisticos.

Endpoints:
  POST /api/videojuegos       -> Registrar resena y actualizar estadisticas
  GET  /api/estadisticas      -> Consultar estadisticas generales
  GET  /api/mejores-videojuegos -> Listar videojuegos mejor calificados
"""

# Importamos las bibliotecas necesarias
from flask import Flask, request, jsonify  # Flask para la API web
from pymongo import MongoClient            # PyMongo para conectar con MongoDB
import os                                   # Para leer variables de entorno
from datetime import datetime               # Para manejar fechas

# Creamos la aplicacion Flask
aplicacion = Flask(__name__)

# ------------------------------------------------------------
# Conexion a MongoDB Atlas
# Usamos variables de entorno para la seguridad
# ------------------------------------------------------------
# La URI de conexion debe estar en una variable de entorno
uri_mongo = os.environ.get('MONGO_URI', 'mongodb://localhost:27017/')
# Si no hay variable de entorno, usamos localhost para desarrollo

# Creamos el cliente de MongoDB
cliente_mongo = MongoClient(uri_mongo)

# Seleccionamos la base de datos (se crea automaticamente si no existe)
base_datos = cliente_mongo['analitica_videojuegos']

# Seleccionamos la coleccion (equivalente a una tabla en SQL)
coleccion_estadisticas = base_datos['estadisticas_videojuegos']

print("Conectado a MongoDB - Base de datos: analitica_videojuegos")

# ------------------------------------------------------------
# ENDPOINT 1: POST /api/videojuegos
# Registra una nueva resena y actualiza las estadisticas
# ------------------------------------------------------------
@aplicacion.route('/api/videojuegos', methods=['POST'])
def registrar_resena():
    """
    Recibe los datos de una resena desde la aplicacion PHP
    y actualiza las estadisticas del videojuego en MongoDB.

    Datos esperados (JSON):
      - videojuego_id: ID del videojuego en PostgreSQL
      - calificacion: calificacion del 1 al 5
      - nombre_videojuego: nombre del videojuego
    """
    # Obtenemos los datos enviados en formato JSON
    datos = request.get_json()

    # Verificamos que los datos no esten vacios
    if not datos:
        return jsonify({'error': 'No se recibieron datos JSON'}), 400

    # Extraemos los campos del JSON
    videojuego_id = datos.get('videojuego_id')
    calificacion = datos.get('calificacion')
    nombre_videojuego = datos.get('nombre_videojuego', 'Sin nombre')

    # Validamos campos obligatorios
    if not videojuego_id or not calificacion:
        return jsonify({'error': 'Faltan campos obligatorios: videojuego_id y calificacion'}), 400

    # Validamos que la calificacion sea un numero valido
    try:
        calificacion = int(calificacion)
        if calificacion < 1 or calificacion > 5:
            return jsonify({'error': 'La calificacion debe estar entre 1 y 5'}), 400
    except (ValueError, TypeError):
        return jsonify({'error': 'La calificacion debe ser un numero entero'}), 400

    # Buscamos si el videojuego ya existe en MongoDB
    documento_existente = coleccion_estadisticas.find_one({'videojuego_id': videojuego_id})

    if documento_existente:
        # Si ya existe, actualizamos sus estadisticas
        nuevo_total = documento_existente['total_resenas'] + 1
        nueva_suma = documento_existente['suma_calificaciones'] + calificacion
        nuevo_promedio = round(nueva_suma / nuevo_total, 2)

        # Actualizamos el documento en MongoDB
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
        mensaje = f"Estadisticas actualizadas para '{nombre_videojuego}'"
    else:
        # Si no existe, creamos un nuevo documento con los datos iniciales
        nuevo_documento = {
            'videojuego_id': videojuego_id,
            'nombre_videojuego': nombre_videojuego,
            'genero': datos.get('genero', 'No especificado'),
            'total_resenas': 1,
            'suma_calificaciones': calificacion,
            'promedio_calificacion': float(calificacion),
            'primera_resena': datetime.utcnow().isoformat(),
            'ultima_resena': datetime.utcnow().isoformat(),
            'ultima_calificacion': calificacion
        }
        coleccion_estadisticas.insert_one(nuevo_documento)
        mensaje = f"Nuevo videojuego registrado: '{nombre_videojuego}'"

    return jsonify({
        'mensaje': mensaje,
        'videojuego_id': videojuego_id,
        'estado': 'ok'
    }), 201

# ------------------------------------------------------------
# ENDPOINT 2: GET /api/estadisticas
# Devuelve estadisticas generales de todos los videojuegos
# ------------------------------------------------------------
@aplicacion.route('/api/estadisticas', methods=['GET'])
def obtener_estadisticas():
    """
    Consulta y devuelve estadisticas generales:
      - Total de videojuegos registrados
      - Total de resenas acumuladas
      - Promedio general de calificaciones
      - Videojuego mejor calificado
      - Videojuego con mas resenas
    """
    # Contamos el total de documentos (videojuegos) en la coleccion
    total_videojuegos = coleccion_estadisticas.count_documents({})

    # Si no hay datos, devolvemos valores en cero
    if total_videojuegos == 0:
        return jsonify({
            'total_videojuegos': 0,
            'total_resenas': 0,
            'promedio_general': 0,
            'mejor_calificado': 'Ninguno',
            'mas_resenado': 'Ninguno'
        })

    # Calculamos total de resenas y suma de calificaciones usando agregacion
    pipeline = [
        {
            '$group': {
                '_id': None,
                'total_resenas': {'$sum': '$total_resenas'},
                'suma_calificaciones': {'$sum': '$suma_calificaciones'}
            }
        }
    ]
    resultado_agregado = list(coleccion_estadisticas.aggregate(pipeline))

    if resultado_agregado:
        total_resenas = resultado_agregado[0]['total_resenas']
        suma_total = resultado_agregado[0]['suma_calificaciones']
        promedio_general = round(suma_total / total_resenas, 2) if total_resenas > 0 else 0
    else:
        total_resenas = 0
        promedio_general = 0

    # Buscamos el videojuego mejor calificado (orden descendente por promedio)
    mejor_calificado = coleccion_estadisticas.find_one(
        {},
        sort=[('promedio_calificacion', -1)]
    )

    # Buscamos el videojuego con mas resenas
    mas_resenado = coleccion_estadisticas.find_one(
        {},
        sort=[('total_resenas', -1)]
    )

    # Construimos la respuesta JSON
    estadisticas = {
        'total_videojuegos': total_videojuegos,
        'total_resenas': total_resenas,
        'promedio_general': promedio_general,
        'mejor_calificado': mejor_calificado['nombre_videojuego'] if mejor_calificado else 'Ninguno',
        'mejor_calificado_promedio': mejor_calificado['promedio_calificacion'] if mejor_calificado else 0,
        'mas_resenado': mas_resenado['nombre_videojuego'] if mas_resenado else 'Ninguno',
        'mas_resenado_total': mas_resenado['total_resenas'] if mas_resenado else 0
    }

    return jsonify(estadisticas), 200

# ------------------------------------------------------------
# ENDPOINT 3: GET /api/mejores-videojuegos
# Devuelve los videojuegos ordenados por mejor calificacion
# ------------------------------------------------------------
@aplicacion.route('/api/mejores-videojuegos', methods=['GET'])
def obtener_mejores_videojuegos():
    """
    Devuelve una lista de videojuegos ordenados desde el mejor
    calificado hasta el peor, mostrando su promedio y total de resenas.
    """
    # Consultamos todos los documentos ordenados por promedio (descendente)
    lista_mejores = coleccion_estadisticas.find(
        {},
        {
            '_id': 0,                    # No mostramos el _id de MongoDB
            'videojuego_id': 1,
            'nombre_videojuego': 1,
            'genero': 1,
            'total_resenas': 1,
            'promedio_calificacion': 1
        }
    ).sort('promedio_calificacion', -1)

    # Convertimos el cursor a una lista de diccionarios
    resultado = list(lista_mejores)

    return jsonify(resultado), 200


# ------------------------------------------------------------
# Ruta de bienvenida (para verificar que la API funciona)
# ------------------------------------------------------------
@aplicacion.route('/', methods=['GET'])
def inicio():
    return jsonify({
        'mensaje': 'API de Analitica de Videojuegos funcionando',
        'endpoints': {
            'POST /api/videojuegos': 'Registrar resena y actualizar estadisticas',
            'GET /api/estadisticas': 'Consultar estadisticas generales',
            'GET /api/mejores-videojuegos': 'Listar mejores videojuegos'
        }
    }), 200


# ------------------------------------------------------------
# Punto de entrada de la aplicacion
# ------------------------------------------------------------
if __name__ == '__main__':
    # Leemos el puerto de la variable de entorno (Render lo asigna automaticamente)
    puerto = int(os.environ.get('PORT', 5000))
    # Ejecutamos la aplicacion en modo debug (desactivar en produccion)
    aplicacion.run(host='0.0.0.0', port=puerto, debug=True)

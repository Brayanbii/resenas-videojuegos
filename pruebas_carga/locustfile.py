"""
locustfile.py
Pruebas de carga y estres usando Locust para la aplicacion de reseñas de videojuegos.

Simula usuarios concurrentes realizando las siguientes operaciones:
  1. Consulta de videojuegos (catalogo)
  2. Registro de reseñas
  3. Consulta de estadisticas (API Flask)

Instalacion:
  pip install locust

Ejecucion:
  locust -f locustfile.py --host=http://localhost:3000
  Luego abre http://localhost:8089 en tu navegador para iniciar la prueba.

Variables a configurar:
  - host: URL de tu aplicacion PHP
  - api_host: URL de tu API Flask (para pruebas directas a la API)

Metricas que se analizan:
  - Tiempo de respuesta (response time): promedio, minimo, maximo, percentiles
  - Throughput (RPS - Requests Per Second): peticiones por segundo
  - Porcentaje de errores: % de peticiones fallidas
"""

from locust import HttpUser, task, between
import random
import json


class UsuarioResenas(HttpUser):
    """
    Clase que simula un usuario interactuando con la aplicacion.
    Cada usuario realiza tareas aleatorias con pausas entre ellas.
    """

    # Tiempo de espera entre tareas (en segundos)
    # Simula que un usuario real espera entre 1 y 5 segundos entre acciones
    wait_time = between(1, 5)

    def on_start(self):
        """
        Se ejecuta cuando el usuario virtual inicia.
        Aqui no necesitamos login porque la aplicacion es publica.
        """
        print("Usuario virtual iniciado")

    # ------------------------------------------------------------
    # TAREA 1: Consulta de catalogo de videojuegos
    # Peso 3: Esta tarea se ejecuta 3 veces mas frecuente que
    # las tareas con peso 1 (simula que mas usuarios consultan)
    # ------------------------------------------------------------
    @task(3)
    def consultar_catalogo(self):
        """
        Simula un usuario consultando el catalogo de videojuegos.
        Es la operacion mas comun (los usuarios navegan el catalogo).
        """
        # Hacemos una peticion GET a la pagina del catalogo
        respuesta = self.client.get("/catalogo.php", name="GET /catalogo")

        # Verificamos que la respuesta sea exitosa (codigo 200)
        if respuesta.status_code == 200:
            # Verificamos que el contenido HTML contenga datos esperados
            if "Catálogo de Videojuegos" in respuesta.text:
                respuesta.success()
            else:
                respuesta.failure("La pagina no contiene 'Catálogo de Videojuegos'")
        else:
            respuesta.failure(f"Codigo HTTP inesperado: {respuesta.status_code}")

    # ------------------------------------------------------------
    # TAREA 2: Registro de reseña (operacion POST)
    # Peso 1: Menos frecuente que la consulta del catalogo
    # ------------------------------------------------------------
    @task(1)
    def registrar_resena(self):
        """
        Simula un usuario registrando una nueva reseña.
        Envia datos por POST al formulario de reseñas.
        """
        # Datos de la reseña (simulados con valores aleatorios)
        # NOTA: El videojuego_id debe ser un ID valido en tu base de datos
        # Ajusta el rango segun los IDs que tengas registrados
        videojuego_id = random.randint(1, 5)  # IDs del 1 al 5

        datos_resena = {
            "videojuego_id": str(videojuego_id),
            "nombre_usuario": f"Usuario_Locust_{random.randint(1000, 9999)}",
            "calificacion": str(random.randint(3, 5)),  # Calificaciones positivas (3-5)
            "comentario": f"Prueba de carga automatizada numero {random.randint(1, 1000)}"
        }

        # Enviamos los datos por POST al formulario
        respuesta = self.client.post(
            "/registrar_resena.php",
            data=datos_resena,
            name="POST /registrar_resena"
        )

        if respuesta.status_code == 200:
            if "correctamente" in respuesta.text.lower():
                respuesta.success()
            else:
                respuesta.failure("La respuesta no indica exito en el registro")
        else:
            respuesta.failure(f"Codigo HTTP inesperado: {respuesta.status_code}")

    # ------------------------------------------------------------
    # TAREA 3: Consulta de estadisticas (desde pagina PHP)
    # Peso 1: Similar frecuencia al registro
    # ------------------------------------------------------------
    @task(1)
    def consultar_estadisticas(self):
        """
        Simula un usuario consultando la pagina de estadisticas.
        Esta pagina internamente consulta la API Flask.
        """
        respuesta = self.client.get("/estadisticas.php", name="GET /estadisticas")

        if respuesta.status_code == 200:
            if "Estadísticas" in respuesta.text:
                respuesta.success()
            else:
                respuesta.failure("La pagina no contiene 'Estadísticas'")
        else:
            respuesta.failure(f"Codigo HTTP inesperado: {respuesta.status_code}")

    # ------------------------------------------------------------
    # TAREA 4: Registro de videojuego (menos frecuente)
    # Peso 1
    # ------------------------------------------------------------
    @task(1)
    def registrar_videojuego(self):
        """
        Simula un administrador registrando un nuevo videojuego.
        """
        generos = ["Acción", "Aventura", "RPG", "Shooter", "Deportes", "Estrategia"]
        plataformas = ["PC", "PS5", "Xbox Series X", "Nintendo Switch"]

        datos_juego = {
            "nombre": f"Juego_Locust_{random.randint(10000, 99999)}",
            "genero": random.choice(generos),
            "plataforma": random.choice(plataformas),
            "descripcion": f"Videojuego generado por prueba de carga Locust.",
            "fecha_lanzamiento": f"2024-{random.randint(1,12):02d}-{random.randint(1,28):02d}"
        }

        respuesta = self.client.post(
            "/registrar_videojuego.php",
            data=datos_juego,
            name="POST /registrar_videojuego"
        )

        if respuesta.status_code == 200:
            if "registrado correctamente" in respuesta.text:
                respuesta.success()
            else:
                respuesta.failure("La respuesta no confirma el registro")
        else:
            respuesta.failure(f"Codigo HTTP inesperado: {respuesta.status_code}")


# ============================================================
# Clase para probar directamente la API Flask
# (Usa un host diferente: el de la API Flask)
# ============================================================
class UsuarioAPI(HttpUser):
    """
    Usuario que prueba directamente los endpoints de la API Flask.
    Util para medir el rendimiento del servicio de analitica sin pasar por PHP.
    """
    wait_time = between(0.5, 2)

    @task(2)
    def get_estadisticas(self):
        """Consulta el endpoint GET /api/estadisticas"""
        respuesta = self.client.get("/api/estadisticas", name="API GET /estadisticas")
        if respuesta.status_code == 200:
            respuesta.success()
        else:
            respuesta.failure(f"Codigo HTTP: {respuesta.status_code}")

    @task(1)
    def post_resena(self):
        """Envia una resena al endpoint POST /api/videojuegos"""
        datos = {
            "videojuego_id": random.randint(1, 5),
            "calificacion": random.randint(1, 5),
            "nombre_videojuego": f"Juego de Prueba {random.randint(1, 100)}"
        }
        respuesta = self.client.post(
            "/api/videojuegos",
            json=datos,
            name="API POST /videojuegos"
        )
        if respuesta.status_code in [200, 201]:
            respuesta.success()
        else:
            respuesta.failure(f"Codigo HTTP: {respuesta.status_code}")

    @task(1)
    def get_mejores(self):
        """Consulta el endpoint GET /api/mejores-videojuegos"""
        respuesta = self.client.get("/api/mejores-videojuegos", name="API GET /mejores")
        if respuesta.status_code == 200:
            respuesta.success()
        else:
            respuesta.failure(f"Codigo HTTP: {respuesta.status_code}")

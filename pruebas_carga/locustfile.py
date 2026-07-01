"""
================================================================================
locustfile.py
================================================================================

ARCHIVO DE PRUEBAS DE CARGA Y ESTRÉS USANDO LOCUST
==================================================

Este archivo define las pruebas de carga para la aplicación de reseñas de
videojuegos. Locust es una herramienta de pruebas de carga escrita en Python
que permite simular miles de usuarios concurrentes (llamados "enjambres" o
"swarms") interactuando con una aplicación web.

¿QUÉ ES LOCUST?
---------------
Locust es un framework de pruebas de carga "code-first" donde las pruebas se
escriben en Python puro. Cada "usuario virtual" ejecuta tareas definidas con
decoradores @task. Locust recolecta métricas como:
  - Tiempo de respuesta (promedio, mínimo, máximo, percentiles p50/p90/p99)
  - Throughput (RPS = Requests Per Second = peticiones por segundo)
  - Porcentaje de fallos (% de peticiones con error)
  - Número de usuarios concurrentes simulados

FLUJO DE LA APLICACIÓN BAJO PRUEBA
-----------------------------------
La aplicación tiene dos componentes principales:
  1. Frontend en PHP (host principal) que maneja:
     - Catálogo de videojuegos (consulta GET)
     - Registro de reseñas (envío POST)
     - Página de estadísticas (consulta GET)
  2. API en Flask (host secundario) que expone:
     - Endpoint de estadísticas
     - Endpoint para registrar reseñas vía API
     - Endpoint de mejores videojuegos

OPERACIONES SIMULADAS EN ESTE ARCHIVO
--------------------------------------
  Clase UsuarioResenas (pruebas al frontend PHP):
    1. Consulta de catálogo de videojuegos      (peso 3, la más frecuente)
    2. Registro de una reseña                    (peso 1)
    3. Consulta de estadísticas                  (peso 1)
    4. Registro de un videojuego nuevo           (peso 1)

  Clase UsuarioAPI (pruebas directas a la API Flask):
    1. GET /api/estadisticas                     (peso 2)
    2. POST /api/videojuegos                     (peso 1)
    3. GET /api/mejores-videojuegos              (peso 1)

REQUISITOS DE INSTALACIÓN
--------------------------
  pip install locust

CÓMO EJECUTAR ESTA PRUEBA
--------------------------
  1. Asegúrate de que la aplicación PHP y la API Flask estén corriendo.
  2. Ejecuta Locust desde la terminal:
       locust -f locustfile.py --host=http://localhost:3000
  3. Abre tu navegador en http://localhost:8089
  4. Configura:
       - Número total de usuarios a simular (ej: 100)
       - Tasa de aparición de usuarios por segundo (ej: 10)
       - Host (si no lo pasaste por línea de comandos)
  5. Haz clic en "Start swarming" para iniciar la prueba.

VARIABLES A CONFIGURAR SEGÚN TU ENTORNO
----------------------------------------
  - host: URL base de tu aplicación PHP (--host en línea de comandos)
  - api_host: URL de tu API Flask (para pruebas con UsuarioAPI)
  - IDs de videojuegos en las tareas: ajusta random.randint(1, 5) según los
    IDs que existan realmente en tu base de datos.

MÉTRICAS QUE SE ANALIZAN EN EL REPORTE DE LOCUST
-------------------------------------------------
  - Response Time (ms): Tiempo que tarda el servidor en responder.
      * Average (promedio): Media aritmética de todos los tiempos.
      * Min / Max: El menor y mayor tiempo registrado.
      * Percentiles (p50, p90, p99): El 50%, 90% o 99% de las peticiones
        fueron más rápidas que este valor. Un p99 alto indica que hay picos
        de latencia que afectan a pocos usuarios.
  - Requests/s (RPS): Número de peticiones exitosas por segundo.
      * Mide el throughput o capacidad de procesamiento del sistema.
  - Failures/s: Número de peticiones fallidas por segundo.
      * Idealmente debe ser 0. Si no es 0, hay que investigar los errores.
  - % de fallos: Porcentaje de peticiones que resultaron en error.
  - Número de usuarios: Usuarios virtuales simultáneos activos.

CONCEPTOS CLAVE DE LOCUST
--------------------------
  - HttpUser: Clase base que representa un usuario HTTP. Cada instancia
    tiene su propia sesión (client) que mantiene cookies.
  - @task: Decorador que marca un método como una tarea ejecutable por el
    usuario virtual. El peso indica la frecuencia relativa.
  - between(): Define el tiempo de espera (wait_time) entre tareas
    consecutivas, simulando pausas de un usuario real.
  - self.client: Cliente HTTP (similar a requests.Session) que Locust
    provee automáticamente. Todas las peticiones hechas con este cliente
    son monitoreadas y sus métricas recolectadas.
  - name="..." en las peticiones: Permite agrupar métricas bajo un nombre
    legible en el reporte, independientemente de la URL exacta.
================================================================================
"""

# ===========================================================================
# SECCIÓN DE IMPORTACIONES
# ===========================================================================

# Importamos la clase HttpUser: es la clase base de Locust para simular
# usuarios que hacen peticiones HTTP. Cada instancia de HttpUser ejecuta
# tareas en un hilo (greenlet) independiente.
# Importamos el decorador @task: se usa para marcar métodos como tareas
# que el usuario virtual ejecutará de forma repetida durante la prueba.
# Importamos la función between: define un tiempo de espera aleatorio entre
# el mínimo y máximo especificados, simulando pausas humanas entre acciones.
from locust import HttpUser, task, between

# Importamos random para generar valores aleatorios en los datos de prueba
# (nombres de usuario, calificaciones, IDs de videojuegos, etc.). Esto evita
# que todas las peticiones sean idénticas y permite simular variedad real.
import random

# Importamos json (aunque no se usa explícitamente en el código actual, es
# común tenerlo disponible para parsear respuestas JSON de la API o para
# enviar datos en formato JSON en el cuerpo de las peticiones POST).
import json


# ===========================================================================
# CLASE PRINCIPAL: UsuarioResenas
# Simula un usuario interactuando con el frontend PHP de la aplicación
# ===========================================================================
class UsuarioResenas(HttpUser):
    """
    CLASE UsuarioResenas -> HEREDA DE HttpUser
    ===========================================

    Representa un usuario virtual que interactúa con la aplicación web
    PHP de reseñas de videojuegos.

    CADA INSTANCIA DE ESTA CLASE:
      - Tiene su propio cliente HTTP (self.client) con su propia sesión
        y cookies independientes.
      - Ejecuta tareas en un bucle infinito: elige una tarea según los
        pesos definidos, la ejecuta, espera un tiempo aleatorio (wait_time),
        y repite.
      - Se ejecuta en un greenlet (hilo ligero) propio, lo que permite
        que Locust maneje miles de usuarios concurrentes de forma eficiente.

    FLUJO DE VIDA DE UN USUARIO VIRTUAL:
      1. on_start() -> se ejecuta UNA vez al iniciar el usuario.
      2. Bucle de tareas -> ejecuta tareas repetidamente hasta que la
         prueba termina o el usuario es detenido.
      3. on_stop() (opcional) -> se ejecuta UNA vez al finalizar.
    """

    # =========================================================================
    # ATRIBUTO DE CLASE: wait_time
    # Define cuánto tiempo espera el usuario virtual entre la ejecución de
    # una tarea y la siguiente. Esto SIMULA EL COMPORTAMIENTO HUMANO REAL:
    # un usuario no hace clics instantáneos; lee, piensa, escribe.
    #
    # between(1, 5) significa que la espera será un valor aleatorio
    # uniforme entre 1 y 5 segundos. Mientras más alto el wait_time,
    # menos peticiones por segundo generará cada usuario individual.
    # =========================================================================
    wait_time = between(1, 5)

    # =========================================================================
    # MÉTODO: on_start
    # Se ejecuta UNA SOLA VEZ cuando el usuario virtual es creado/iniciado,
    # antes de comenzar a ejecutar tareas.
    #
    # USOS TÍPICOS DE on_start:
    #   - Hacer login en la aplicación.
    #   - Cargar datos iniciales necesarios para las tareas.
    #   - Inicializar variables de instancia.
    #
    # En este caso la aplicación es pública (no requiere autenticación),
    # por lo que on_start solo imprime un mensaje de confirmación.
    # =========================================================================
    def on_start(self):
        """
        MÉTODO on_start
        ===============
        Callback del ciclo de vida del usuario virtual.
        Locust llama a este método automáticamente UNA vez cuando el
        usuario es creado, ANTES de comenzar el bucle de tareas.

        Como la aplicación bajo prueba no requiere login (es pública),
        este método solo imprime un mensaje informativo en consola.
        Si la aplicación requiriera autenticación, aquí se haría el POST
        al endpoint de login y se guardarían las cookies/token.

        PARÁMETROS: None (self solamente)
        RETORNA: None
        """
        print("Usuario virtual iniciado")

    # =========================================================================
    # TAREA 1: consultar_catalogo
    # Peso = 3 (se ejecuta 3 veces más frecuente que una tarea con peso 1)
    #
    # SIMULA: Un usuario navegando por el catálogo de videojuegos.
    # Esta es la operación MÁS COMÚN en la aplicación, por eso tiene peso 3.
    # En un escenario real, la mayoría de usuarios entra a ver el catálogo
    # antes de decidir si registra una reseña o no.
    # =========================================================================

    # @task: Decorador de Locust que registra este método como una tarea
    # ejecutable. El parámetro numérico (3) es el PESO de la tarea.
    #
    # ¿CÓMO FUNCIONA EL PESO?
    # Locust elige aleatoriamente la siguiente tarea a ejecutar ponderando
    # por los pesos. Con pesos (3, 1, 1, 1), la probabilidad de cada tarea es:
    #   consultar_catalogo:      3/6 = 50%
    #   registrar_resena:        1/6 ≈ 16.7%
    #   consultar_estadisticas:  1/6 ≈ 16.7%
    #   registrar_videojuego:    1/6 ≈ 16.7%
    # Esto refleja que en la vida real se consulta el catálogo mucho más
    # frecuentemente que las otras operaciones.
    @task(3)
    def consultar_catalogo(self):
        """
        TAREA: consultar_catalogo (PESO 3 - LA MÁS FRECUENTE)
        =====================================================

        Simula a un usuario visitando la página del catálogo de videojuegos.
        Esta es la operación de solo lectura más común del sistema.

        FLUJO DE LA TAREA:
          1. Realiza una petición GET a /catalogo.php.
          2. Verifica que el código de estado HTTP sea 200 (OK).
          3. Verifica que el contenido HTML incluya el texto esperado
             "Catálogo de Videojuegos" para confirmar que la página
             se cargó correctamente.
          4. Si todo está bien, marca la petición como exitosa con
             respuesta.success().
          5. Si algo falla, marca la petición como fallida con
             respuesta.failure() e incluye un mensaje descriptivo.

        MÉTRICA GENERADA: Aparecerá en el reporte como "GET /catalogo"
        gracias al parámetro name= en la petición.
        """
        # Realizamos la petición GET al endpoint del catálogo.
        # self.client es el cliente HTTP que Locust provee automáticamente.
        # Es similar a requests.get() pero con monitoreo integrado.
        # El parámetro name="GET /catalogo" agrupa todas las peticiones
        # a esta URL bajo un nombre legible en el dashboard de Locust.
        respuesta = self.client.get("/catalogo.php", name="GET /catalogo")

        # Verificamos el código de estado HTTP.
        # 200 significa "OK", la página se entregó correctamente.
        if respuesta.status_code == 200:
            # Verificación adicional de contenido: nos aseguramos de que
            # el HTML devuelto realmente contenga la información esperada.
            # Esto detecta casos donde el servidor responde 200 pero
            # entrega una página de error vacía o incompleta.
            if "Catálogo de Videojuegos" in respuesta.text:
                # Marca explícitamente la petición como exitosa.
                # Aunque por defecto Locust considera éxito los códigos 2xx,
                # llamar a success() es buena práctica cuando además
                # validamos el contenido de la respuesta.
                respuesta.success()
            else:
                # El servidor respondió 200 pero el contenido no es el
                # esperado. Marcamos la petición como fallida con un
                # mensaje que aparecerá en la pestaña "Failures" del
                # dashboard de Locust.
                respuesta.failure("La pagina no contiene 'Catálogo de Videojuegos'")
        else:
            # El código de estado no fue 200. Puede ser 404 (no encontrado),
            # 500 (error interno), 502 (bad gateway), etc.
            # Registramos el código recibido para facilitar el diagnóstico.
            respuesta.failure(f"Codigo HTTP inesperado: {respuesta.status_code}")

    # =========================================================================
    # TAREA 2: registrar_resena
    # Peso = 1
    #
    # SIMULA: Un usuario escribiendo y enviando una reseña de un videojuego.
    # Es una operación POST que envía datos de formulario al servidor PHP.
    # Menos frecuente que consultar el catálogo, pero crítica porque escribe
    # en la base de datos.
    # =========================================================================
    @task(1)
    def registrar_resena(self):
        """
        TAREA: registrar_resena (PESO 1)
        ================================

        Simula a un usuario registrando una nueva reseña de videojuego.
        Esta es una operación de ESCRITURA (POST) que inserta datos
        en la base de datos a través del formulario PHP.

        DATOS ENVIADOS (SIMULADOS):
          - videojuego_id: ID del videojuego reseñado (aleatorio entre 1 y 5).
            IMPORTANTE: Este rango debe coincidir con IDs reales en tu BD.
          - nombre_usuario: Nombre falso generado aleatoriamente para
            simular diferentes usuarios reales.
          - calificacion: Puntuación entre 3 y 5 (simulamos reseñas
            mayormente positivas, como suele ocurrir en la realidad).
          - comentario: Texto genérico de prueba.

        VERIFICACIÓN DE ÉXITO:
          Comprobamos que la respuesta contenga la palabra "correctamente"
          (en minúsculas) para confirmar que el registro se procesó.

        MÉTRICA GENERADA: Aparecerá como "POST /registrar_resena" en el
        dashboard de Locust.
        """
        # Generamos un ID de videojuego aleatorio entre 1 y 5.
        # random.randint(a, b) devuelve un entero aleatorio N tal que a <= N <= b.
        # NOTA IMPORTANTE: Debes ajustar estos números según los IDs
        # que realmente existan en la tabla de videojuegos de tu BD.
        # Si envías un ID que no existe, el servidor podría devolver error.
        videojuego_id = random.randint(1, 5)

        # Construimos el diccionario con los datos que enviaremos en el POST.
        # Estos simulan los campos de un formulario HTML enviado con
        # Content-Type: application/x-www-form-urlencoded.
        # Locust automáticamente codifica el diccionario cuando usamos
        # el parámetro data= en self.client.post().
        datos_resena = {
            # ID del videojuego (como string, pues los formularios HTML
            # envían todo como texto).
            "videojuego_id": str(videojuego_id),
            # Nombre de usuario falso: "Usuario_Locust_" + número aleatorio.
            # random.randint(1000, 9999) genera un número de 4 dígitos.
            # Esto evita colisiones de nombres si se valida unicidad.
            "nombre_usuario": f"Usuario_Locust_{random.randint(1000, 9999)}",
            # Calificación entre 3 y 5 estrellas (como string).
            # Elegimos un rango positivo (3-5) porque en pruebas de carga
            # queremos simular el caso común. Para pruebas de estrés
            # podrías variar más el rango.
            "calificacion": str(random.randint(3, 5)),
            # Comentario genérico con un número aleatorio para evitar
            # que todas las reseñas sean idénticas (lo que podría
            # disparar detección de spam o cachés).
            "comentario": f"Prueba de carga automatizada numero {random.randint(1, 1000)}"
        }

        # Realizamos la petición POST al endpoint que procesa el formulario.
        # self.client.post() es similar a requests.post().
        # El parámetro data= envía los datos como formulario HTML
        # (application/x-www-form-urlencoded). Si quisiéramos enviar JSON
        # usaríamos el parámetro json= en su lugar.
        respuesta = self.client.post(
            "/registrar_resena.php",
            data=datos_resena,
            name="POST /registrar_resena"
        )

        # Verificamos que el servidor haya respondido con código 200.
        # 200 indica que el formulario se procesó (aunque podría haber
        # errores de validación a nivel de aplicación).
        if respuesta.status_code == 200:
            # Verificación semántica: buscamos la palabra "correctamente"
            # en el cuerpo de la respuesta (en minúsculas para ser
            # insensibles a mayúsculas/minúsculas). Esto confirma que
            # el servidor PHP procesó exitosamente la inserción en la BD.
            if "correctamente" in respuesta.text.lower():
                respuesta.success()
            else:
                # El servidor respondió 200 pero no confirmó el registro.
                # Podría ser un error de validación (campos vacíos,
                # ID inexistente, etc.).
                respuesta.failure("La respuesta no indica exito en el registro")
        else:
            # Error HTTP: el servidor no pudo procesar la solicitud.
            # Puede deberse a sobrecarga (503), error interno (500),
            # timeout del servidor, etc.
            respuesta.failure(f"Codigo HTTP inesperado: {respuesta.status_code}")

    # =========================================================================
    # TAREA 3: consultar_estadisticas
    # Peso = 1
    #
    # SIMULA: Un usuario visitando la página de estadísticas.
    # Esta página PHP internamente hace peticiones a la API Flask para
    # obtener datos agregados (promedios, rankings, etc.).
    # Es una operación más pesada que el catálogo porque involucra
    # consultas con agregaciones en la base de datos.
    # =========================================================================
    @task(1)
    def consultar_estadisticas(self):
        """
        TAREA: consultar_estadisticas (PESO 1)
        =====================================

        Simula a un usuario consultando la página de estadísticas.
        Esta página PHP internamente consume la API Flask para mostrar
        datos como:
          - Promedio de calificaciones por videojuego
          - Ranking de mejores videojuegos
          - Distribución de reseñas por género o plataforma

        ¿POR QUÉ ES IMPORTANTE PROBAR ESTE ENDPOINT?
        Porque las estadísticas involucran consultas SQL con agregaciones
        (AVG, COUNT, GROUP BY, JOIN) que son más costosas computacionalmente
        que una simple consulta de catálogo. Bajo alta carga, estas consultas
        pueden convertirse en el cuello de botella del sistema.

        MÉTRICA GENERADA: Aparecerá como "GET /estadisticas" en el dashboard.
        """
        # Realizamos la petición GET a la página de estadísticas PHP.
        # Esta página internamente consume la API Flask (hace HTTP requests
        # server-to-server), por lo que el tiempo de respuesta incluye:
        #   1. Tiempo de procesamiento PHP
        #   2. Tiempo de la llamada a la API Flask
        #   3. Tiempo de consulta a la base de datos en Flask
        #   4. Tiempo de renderizado HTML
        respuesta = self.client.get("/estadisticas.php", name="GET /estadisticas")

        # Validación HTTP estándar: ¿el servidor respondió correctamente?
        if respuesta.status_code == 200:
            # Validación de contenido: ¿la página incluye la palabra
            # "Estadísticas" (con tilde)? Esto confirma que la página
            # se renderizó correctamente y no es una página de error
            # genérica del servidor web.
            if "Estadísticas" in respuesta.text:
                respuesta.success()
            else:
                # Posibles causas: error de conexión con la API Flask,
                # timeout de la API, datos vacíos en la BD, error PHP.
                respuesta.failure("La pagina no contiene 'Estadísticas'")
        else:
            respuesta.failure(f"Codigo HTTP inesperado: {respuesta.status_code}")

    # =========================================================================
    # TAREA 4: registrar_videojuego
    # Peso = 1
    #
    # SIMULA: Un administrador (o usuario con permisos) registrando un
    # nuevo videojuego en el catálogo. Es una operación de escritura que
    # inserta un nuevo registro en la tabla de videojuegos.
    # Es la operación menos frecuente porque no todos los usuarios pueden
    # agregar videojuegos (en un sistema real requeriría permisos de admin).
    # =========================================================================
    @task(1)
    def registrar_videojuego(self):
        """
        TAREA: registrar_videojuego (PESO 1)
        ====================================

        Simula a un usuario (posiblemente un administrador) registrando
        un nuevo videojuego en el catálogo del sistema.

        DATOS ENVIADOS:
          - nombre: Nombre falso del videojuego, con sufijo numérico para
            garantizar unicidad. El formato "Juego_Locust_XXXXX" también
            facilita identificar registros de prueba en la BD.
          - genero: Seleccionado aleatoriamente de una lista predefinida
            de géneros comunes de videojuegos.
          - plataforma: Seleccionada aleatoriamente de plataformas actuales.
          - descripcion: Texto fijo para pruebas.
          - fecha_lanzamiento: Fecha aleatoria en 2024, con formato ISO
            (YYYY-MM-DD). El día se limita a 28 para evitar fechas inválidas
            (evitar 30 de febrero, etc.).

        VERIFICACIÓN DE ÉXITO:
          Comprobamos que la respuesta contenga "registrado correctamente".

        MÉTRICA GENERADA: Aparecerá como "POST /registrar_videojuego".
        """
        # Lista de géneros de videojuegos comunes.
        # random.choice() selecciona un elemento aleatorio de la lista.
        generos = ["Acción", "Aventura", "RPG", "Shooter", "Deportes", "Estrategia"]

        # Lista de plataformas de juego actuales.
        # Se usan las plataformas más populares para simular datos realistas.
        plataformas = ["PC", "PS5", "Xbox Series X", "Nintendo Switch"]

        # Construimos el diccionario de datos del formulario.
        datos_juego = {
            # Nombre único: "Juego_Locust_" + número de 5 dígitos.
            # random.randint(10000, 99999) da 90,000 combinaciones posibles,
            # suficiente para pruebas de carga sin colisiones frecuentes.
            "nombre": f"Juego_Locust_{random.randint(10000, 99999)}",
            # Género aleatorio de la lista definida arriba.
            "genero": random.choice(generos),
            # Plataforma aleatoria.
            "plataforma": random.choice(plataformas),
            # Descripción fija de prueba.
            "descripcion": f"Videojuego generado por prueba de carga Locust.",
            # Fecha de lanzamiento aleatoria en 2024.
            # :02d formatea el entero con 2 dígitos, rellenando con cero
            # a la izquierda si es necesario (ej: 3 -> "03").
            # Limitamos el día a 28 para evitar fechas inválidas como
            # 31 de febrero o 31 de abril de forma segura.
            "fecha_lanzamiento": f"2024-{random.randint(1,12):02d}-{random.randint(1,28):02d}"
        }

        # Enviamos el formulario por POST.
        # El servidor PHP procesará los datos e insertará un nuevo registro
        # en la tabla de videojuegos.
        respuesta = self.client.post(
            "/registrar_videojuego.php",
            data=datos_juego,
            name="POST /registrar_videojuego"
        )

        # Verificación HTTP: código 200.
        if respuesta.status_code == 200:
            # Verificación semántica: el mensaje de éxito debe incluir
            # la frase "registrado correctamente".
            if "registrado correctamente" in respuesta.text:
                respuesta.success()
            else:
                # El registro pudo fallar por: nombre duplicado,
                # campos obligatorios faltantes, error de base de datos, etc.
                respuesta.failure("La respuesta no confirma el registro")
        else:
            respuesta.failure(f"Codigo HTTP inesperado: {respuesta.status_code}")


# ===========================================================================
# CLASE SECUNDARIA: UsuarioAPI
# Simula un usuario que consume directamente la API Flask de analítica
# ===========================================================================
class UsuarioAPI(HttpUser):
    """
    CLASE UsuarioAPI -> HEREDA DE HttpUser
    ======================================

    Usuario virtual que prueba DIRECTAMENTE los endpoints de la API Flask
    de analítica y estadísticas, SIN pasar por el frontend PHP.

    ¿POR QUÉ UNA CLASE SEPARADA PARA LA API?
    - Para medir el rendimiento del backend Flask de forma aislada,
      sin el overhead del procesamiento PHP ni el renderizado HTML.
    - Para detectar si un cuello de botella está en PHP o en Flask.
    - Para probar la API con diferentes patrones de carga (wait_time más
      corto = más peticiones por segundo por usuario, simulando llamadas
      programáticas en lugar de navegación humana).

    DIFERENCIAS CON UsuarioResenas:
    - wait_time más corto (0.5-2s vs 1-5s): las APIs se consumen más rápido
      que las páginas web. Un script o aplicación móvil hace peticiones
      más frecuentes que un humano navegando.
    - Tareas enfocadas exclusivamente en endpoints REST de la API Flask.
    - Este usuario debe configurarse con el host de la API Flask, no el
      del frontend PHP (ver instrucciones de ejecución).

    NOTA SOBRE EL HOST:
    Al ejecutar Locust solo puedes definir UN host con --host. Para probar
    ambos hosts (PHP y Flask) en una misma sesión, hay dos opciones:
      1. Ejecutar dos instancias separadas de Locust.
      2. Usar self.client con URLs absolutas en una de las clases.
    En este archivo, se asume que UsuarioAPI se ejecuta en una instancia
    separada apuntando al host de Flask.
    """
    # Tiempo de espera entre 0.5 y 2 segundos.
    # Más corto que en UsuarioResenas porque las llamadas a API suelen ser
    # automatizadas (desde un frontend SPA, una app móvil, o un script),
    # no manuales como la navegación humana.
    # Un wait_time más corto = mayor carga por usuario virtual.
    wait_time = between(0.5, 2)

    # =========================================================================
    # TAREA: get_estadisticas (API)
    # Peso = 2 (más frecuente que las otras tareas de API)
    #
    # SIMULA: Un cliente (frontend, otra API, app) consultando el endpoint
    # de estadísticas generales de la API Flask.
    # =========================================================================
    @task(2)
    def get_estadisticas(self):
        """
        TAREA API: get_estadisticas (PESO 2)
        ====================================

        Consulta el endpoint GET /api/estadisticas de la API Flask.

        Este endpoint típicamente devuelve datos agregados como:
          - Total de videojuegos registrados
          - Total de reseñas
          - Promedio general de calificaciones
          - Distribución por género o plataforma

        Es una operación de SOLO LECTURA. En Locust, las operaciones GET
        son más rápidas que las POST y por eso esta tarea tiene peso 2
        (se ejecuta más frecuentemente).

        VALIDACIÓN: Solo verificamos el código HTTP (200 = éxito).
        No validamos el contenido de la respuesta JSON porque asumimos
        que si la API responde 200, los datos son correctos.
        Si quisiéramos validar el JSON, podríamos usar respuesta.json()
        y verificar campos específicos.

        MÉTRICA GENERADA: Aparecerá como "API GET /estadisticas" en el
        dashboard de Locust, permitiendo diferenciar estas peticiones
        de las que van al frontend PHP.
        """
        # Realizamos GET al endpoint de estadísticas de la API Flask.
        # El parámetro name= agrupa esta métrica bajo un nombre
        # personalizado en el dashboard, independiente de la URL real.
        respuesta = self.client.get("/api/estadisticas", name="API GET /estadisticas")

        # Validación simple por código HTTP.
        # 200 = OK, el endpoint respondió correctamente con los datos.
        # Cualquier otro código (500, 502, 503, etc.) se considera fallo.
        if respuesta.status_code == 200:
            respuesta.success()
        else:
            # Registramos el código de error para diagnóstico.
            # En el dashboard de Locust, en la pestaña "Failures",
            # se agruparán los fallos por este mensaje.
            respuesta.failure(f"Codigo HTTP: {respuesta.status_code}")

    # =========================================================================
    # TAREA: post_resena (API)
    # Peso = 1
    #
    # SIMULA: Un cliente enviando una reseña directamente a la API Flask
    # (sin pasar por el formulario PHP). Útil para probar el endpoint
    # de creación de reseñas de forma aislada.
    # =========================================================================
    @task(1)
    def post_resena(self):
        """
        TAREA API: post_resena (PESO 1)
        ===============================

        Envía una reseña al endpoint POST /api/videojuegos de la API Flask.

        A DIFERENCIA de la tarea registrar_resena() en UsuarioResenas:
        - Los datos se envían como JSON (parámetro json=) en lugar de
          como formulario HTML (data=).
        - El endpoint es diferente: /api/videojuegos vs /registrar_resena.php
        - Es una llamada REST pura, sin renderizado HTML de por medio.

        DATOS ENVIADOS (en formato JSON):
          - videojuego_id: ID del videojuego (entero, no string).
          - calificacion: Puntuación de 1 a 5 (entero).
          - nombre_videojuego: Nombre descriptivo del videojuego.

        VALIDACIÓN:
          Aceptamos tanto 200 (OK) como 201 (Created). En REST,
          201 es el código canónico para indicar que un recurso fue creado
          exitosamente. Sin embargo, muchas APIs devuelven 200 por simplicidad.

        MÉTRICA GENERADA: "API POST /videojuegos"
        """
        # Construimos los datos en formato diccionario Python.
        # Locust los serializará a JSON automáticamente porque usamos
        # el parámetro json= en self.client.post().
        datos = {
            # ID del videojuego (entero aleatorio 1-5).
            # Ajusta el rango según los IDs existentes en tu BD.
            "videojuego_id": random.randint(1, 5),
            # Calificación aleatoria entre 1 y 5 (esta vez incluimos
            # todo el rango, no solo positivas como en la clase PHP).
            "calificacion": random.randint(1, 5),
            # Nombre del videojuego para referencia.
            "nombre_videojuego": f"Juego de Prueba {random.randint(1, 100)}"
        }

        # Realizamos POST con json= (envía Content-Type: application/json).
        # A diferencia de data= (que envía application/x-www-form-urlencoded),
        # json= envía el cuerpo como JSON, que es el formato estándar de REST.
        respuesta = self.client.post(
            "/api/videojuegos",
            json=datos,
            name="API POST /videojuegos"
        )

        # Validación: aceptamos 200 (OK estándar) o 201 (Created, REST).
        # La verificación con "in [200, 201]" es más permisiva y realista
        # que exigir un código específico, porque distintas APIs usan
        # uno u otro para creación de recursos.
        if respuesta.status_code in [200, 201]:
            respuesta.success()
        else:
            respuesta.failure(f"Codigo HTTP: {respuesta.status_code}")

    # =========================================================================
    # TAREA: get_mejores (API)
    # Peso = 1
    #
    # SIMULA: Consulta del ranking de mejores videojuegos desde la API.
    # Es una consulta de solo lectura similar a get_estadisticas pero
    # con un endpoint diferente (probablemente con lógica de ordenamiento
    # y filtrado más específica).
    # =========================================================================
    @task(1)
    def get_mejores(self):
        """
        TAREA API: get_mejores (PESO 1)
        ================================

        Consulta el endpoint GET /api/mejores-videojuegos de la API Flask.

        Este endpoint probablemente devuelve un ranking ordenado de los
        videojuegos mejor calificados (por promedio de reseñas). Es una
        consulta que típicamente involucra:
          - JOIN entre tablas de videojuegos y reseñas
          - AVG() para calcular promedio de calificaciones
          - ORDER BY + LIMIT para obtener el top N
          - GROUP BY para agrupar por videojuego

        Desde el punto de vista de pruebas de carga, este endpoint es
        interesante porque las consultas con ordenamiento y agregación
        pueden ser costosas en bases de datos grandes.

        VALIDACIÓN: Código HTTP 200 = éxito.

        MÉTRICA GENERADA: "API GET /mejores"
        """
        # Petición GET al endpoint de ranking de mejores videojuegos.
        respuesta = self.client.get("/api/mejores-videojuegos", name="API GET /mejores")

        # Validación estándar por código de estado HTTP.
        if respuesta.status_code == 200:
            respuesta.success()
        else:
            respuesta.failure(f"Codigo HTTP: {respuesta.status_code}")

# ===========================================================================
# FIN DEL ARCHIVO locustfile.py
# ===========================================================================
#
# RESUMEN DE LO QUE HACE ESTE ARCHIVO:
#   - Define 2 tipos de usuarios virtuales (HttpUser).
#   - UsuarioResenas: Simula navegación humana en el frontend PHP
#     con 4 tareas: consultar catálogo, registrar reseña, ver estadísticas,
#     registrar videojuego.
#   - UsuarioAPI: Simula consumo programático de la API Flask con 3
#     tareas: obtener estadísticas, publicar reseña, obtener ranking.
#   - Cada tarea hace peticiones HTTP, valida la respuesta (código y
#     contenido), y reporta éxito o fallo a Locust.
#   - Locust recolecta y muestra las métricas en tiempo real en el
#     dashboard web (http://localhost:8089).
#
# CONSEJOS PARA PRUEBAS DE CARGA EFECTIVAS:
#   1. Empieza con pocos usuarios (10-20) y ve subiendo gradualmente
#      para encontrar el punto de quiebre del sistema.
#   2. Observa los percentiles (p90, p99) además del promedio: un promedio
#      bajo puede esconder picos de latencia que afectan a algunos usuarios.
#   3. Si ves muchos fallos, revisa la pestaña "Failures" para ver los
#      mensajes de error y diagnosticar la causa.
#   4. Monitorea también el servidor durante la prueba (CPU, memoria,
#      conexiones a BD) para identificar cuellos de botella.
#   5. Ejecuta las pruebas desde una máquina diferente al servidor para
#      no competir por recursos.
# ===========================================================================

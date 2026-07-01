# ============================================================
# Dockerfile - API Flask con Gunicorn
# Imagen para desplegar en Render como Web Service (Docker)
# ============================================================
#
# EXPLICACION GENERAL (estilo estudiante):
# Esta imagen contiene una aplicacion Flask (framework web de Python)
# servida con Gunicorn (servidor WSGI de produccion).
#
# ¿Por que no usar "flask run" directamente?
# Flask incluye un servidor de DESARROLLO (Werkzeug) que muestra
# una advertencia naranja: "WARNING: This is a development server.
# Do not use it in a production deployment." Ese servidor:
#   - Es monohilo (atiende una peticion a la vez)
#   - No maneja concurrencia
#   - No tiene protecciones de seguridad
#   - No es eficiente con recursos
# Gunicorn en cambio es un servidor WSGI multi-trabajador listo para
# entornos reales.
# ============================================================

# Usamos una imagen ligera de Python.
#
# ¿Por que python:3.11-slim?
#   - python:      Imagen oficial de Python mantenida por Docker.
#   - 3.11:        Version especifica (3.11.x). La fijamos para evitar
#                  sorpresas si una version nueva rompe algo.
#   - -slim:       Variante MINIMA basada en Debian.
#                  NO incluye compiladores, headers, ni herramientas
#                  innecesarias. Pesa ~120 MB vs ~900 MB de la imagen
#                  completa (python:3.11).
#
# Comparativa de variantes de la imagen python:
#   - python:3.11          -> Imagen COMPLETA (~900 MB): trae gcc, make, headers,
#                             herramientas de compilacion. Sobra para produccion.
#   - python:3.11-slim     -> Imagen SLIM (~120 MB): Debian minimo + Python.
#                             Ideal para produccion: pequeña, rapida de descargar,
#                             menos superficie de ataque (menos paquetes = menos
#                             vulnerabilidades potenciales).
#   - python:3.11-alpine   -> Imagen ALPINE (~50 MB): basada en Alpine Linux (musl
#                             en vez de glibc). Aun mas pequeña, pero puede causar
#                             problemas con paquetes que requieren compilacion (por
#                             diferencias entre musl y glibc). Elegimos slim como
#                             termino medio seguro.
FROM python:3.11-slim

# Instalamos solo lo necesario para compilar dependencias.
#
# ¿Por que gcc (GNU Compiler Collection)?
# Muchos paquetes de Python (psycopg2, por ejemplo, si se usara PostgreSQL
# desde Flask) tienen componentes escritos en C que deben COMPILARSE al
# instalarse con pip. Para compilar codigo C se necesita un compilador.
# gcc es EL compilador estandar en Linux.
#
# La imagen slim NO trae gcc (por eso es slim), asi que debemos instalarlo.
#
# apt-get clean + rm -rf /var/lib/apt/lists/*
# Misma logica que en el Dockerfile de PHP: limpiamos para ahorrar espacio
# en la imagen final. Las listas de paquetes y los .deb descargados no
# sirven en tiempo de ejecucion.
#
# NOTA: gcc se queda instalado en la imagen final (~50 MB extra).
# En un entorno de produccion optimizado se podria usar un "multi-stage build"
# para compilar en una etapa y solo copiar los binarios a la etapa final,
# eliminando asi gcc de la imagen de produccion. Pero para este proyecto
# mantenemos la simplicidad.
RUN apt-get update && apt-get install -y \
    gcc \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Establecemos el directorio de trabajo dentro del contenedor.
#
# WORKDIR hace dos cosas:
#   1. Crea el directorio si no existe (mkdir -p).
#   2. Cambia el directorio actual a /app para todas las instrucciones
#      siguientes (RUN, COPY, CMD, ENTRYPOINT).
#
# Es equivalente a hacer:
#   RUN mkdir -p /app
#   WORKDIR /app   (o RUN cd /app, aunque cd no persiste entre instrucciones)
#
# Todas las rutas relativas a partir de ahora seran relativas a /app.
# Por ejemplo: COPY requirements.txt .  copia a /app/requirements.txt
WORKDIR /app

# Copiamos primero requirements.txt para aprovechar la cache de Docker.
#
# ESTRATEGIA DE OPTIMIZACION DE CAPAS (muy importante):
# Docker cachea cada instruccion por separado. Si una capa cambia,
# TODAS las capas posteriores se REconstruyen (invalidan su cache).
#
# Si copiaramos TODO el codigo primero (COPY . .) y luego instalaramos
# dependencias (RUN pip install...), CADA cambio en app.py haria que
# pip install se ejecute de nuevo, descargando e instalando TODO
# desde cero (puede tomar minutos).
#
# En cambio, copiando SOLO requirements.txt PRIMERO:
#   - Mientras requirements.txt no cambie, la capa de pip install
#     se USA DESDE LA CACHE (tarda milisegundos).
#   - Solo cuando añades/actualizas una dependencia en requirements.txt
#     se invalida la cache y se reinstala todo.
#   - Los cambios en app.py solo invalidan la capa COPY . . (la ultima),
#     que es instantanea.
#
# Esta tecnica reduce drasticamente los tiempos de build durante desarrollo.
COPY requirements.txt .

# Instalamos las dependencias de Python.
#
# pip install:
#   -r requirements.txt  -> Modo "requirements": lee la lista de paquetes
#                           desde el archivo requirements.txt (uno por linea,
#                           con posibles versiones fijadas, ej: flask==3.0.0).
#
# --no-cache-dir:
#   - pip por defecto guarda una cache de los paquetes descargados en
#     ~/.cache/pip/ para no tener que re-descargarlos en futuras instalaciones.
#   - En un contenedor Docker, esa cache NUNCA se va a reutilizar (cada build
#     parte de cero), asi que solo ocupa espacio al pedo.
#   - --no-cache-dir desactiva la cache. En una imagen puede ahorrar 50-100 MB
#     dependiendo de cuantos paquetes instales.
RUN pip install --no-cache-dir -r requirements.txt

# Copiamos el resto del codigo (app.py, etc.).
#
# COPY . .  -> Copia TODO el contenido del build context (./app_flask/)
#              dentro del directorio de trabajo actual (/app).
#
# ¿Por que esto no sobreescribe requirements.txt?
# Si, lo sobreescribe, pero el contenido es el mismo. No afecta en nada.
# Lo importante es que la capa de pip install YA se ejecuto y esta cacheada.
# Esta capa (COPY . .) es la que cambia frecuentemente durante desarrollo.
COPY . .

# Exponemos el puerto (Render usa la variable PORT, por defecto 10000).
#
# EXPOSE 10000:
#   - Documenta que la aplicacion ESCUCHA en el puerto 10000.
#   - Render por defecto asigna el puerto 10000 a los Web Services.
#   - Como en el Dockerfile de PHP, EXPOSE es INFORMATIVO. El puerto
#     realmente se publica con la configuracion de Render o con -p en docker run.
#
# ¿Por que puerto 10000 y no 5000 (el clasico de Flask)?
# Render asigna automaticamente un puerto via variable de entorno PORT.
# Por defecto Render usa el 10000, pero en local podemos mapear otro.
EXPOSE 10000

# Comando para iniciar la aplicacion con Gunicorn.
# Render inyecta la variable de entorno PORT automaticamente.
#
# CMD define el comando por defecto que se ejecuta cuando el contenedor
# arranca (a menos que se pase otro comando en docker run).
#
# Desglose de la linea de comando:
#
#   gunicorn
#     Servidor WSGI (Web Server Gateway Interface) para aplicaciones Python.
#     Actua como intermediario entre el servidor web (ej: Nginx) y tu
#     aplicacion Python. Maneja multiples peticiones concurrentes usando
#     un modelo pre-fork: un proceso maestro + varios workers.
#     Es el estandar de facto para servir Flask/Django en produccion.
#
#   app:aplicacion
#     Notacion "modulo:objeto" de Gunicorn.
#       - app:    Es el modulo Python (archivo app.py SIN la extension .py).
#       - aplicacion: Es el nombre de la VARIABLE dentro de app.py que
#                     contiene la instancia de Flask.
#                     Ejemplo: aplicacion = Flask(__name__)
#     Gunicorn importa el modulo "app" y busca la variable "aplicacion"
#     que debe ser una aplicacion WSGI (la instancia de Flask).
#     Si tu variable se llamara "app", seria: gunicorn app:app
#
#   --bind 0.0.0.0:${PORT:-10000}
#     --bind (o -b): Define la direccion IP y puerto donde Gunicorn escucha.
#       0.0.0.0: Escucha en TODAS las interfaces de red del contenedor.
#         - 127.0.0.1 (localhost) solo acepta conexiones desde DENTRO del contenedor.
#         - 0.0.0.0 acepta conexiones desde FUERA (tu maquina, internet, etc.).
#           NECESARIO en Docker porque las peticiones llegan desde fuera del contenedor.
#       ${PORT:-10000}: Expansión de variable de shell con valor por defecto.
#         - $PORT:       Lee la variable de entorno PORT (Render la inyecta automaticamente).
#         - ${VAR:-default}: Si $PORT esta definida y no es vacia, usa ese valor.
#                            Si NO esta definida, usa el valor por defecto "10000".
#         - Esto permite que funcione TANTO en Render (donde PORT es dinamico)
#           COMO en local (donde PORT no existe y usa 10000).
#
#   --workers 2
#     Numero de procesos WORKER que Gunicorn crea.
#     Cada worker es un proceso independiente que puede manejar una peticion
#     a la vez (por defecto con worker sincrono).
#       - Con 2 workers puedes atender 2 peticiones simultaneas.
#       - La formula tipica es: (2 * CPUs) + 1. Como Render da pocos recursos,
#         2 workers es razonable para no consumir demasiada memoria.
#       - Mas workers = mas concurrencia pero mas RAM. Flask no es async,
#         asi que cada worker solo hace una cosa a la vez.
#       - En desarrollo local con --reload se suele usar 1 solo worker.
#
#   --timeout 120
#     Tiempo maximo (en segundos) que un worker puede tardar en responder
#     una peticion antes de que Gunicorn lo mate y lo reinicie.
#       - 120 segundos = 2 minutos. Es un valor alto, util si hay consultas
#         a MongoDB que pueden tardar (agregaciones complejas, etc.).
#       - El valor por defecto de Gunicorn es 30 segundos, que puede ser
#         insuficiente para operaciones pesadas.
#       - Si una peticion excede el timeout, Gunicorn envia SIGKILL al worker
#         y crea uno nuevo. El cliente recibe un error 502 Bad Gateway.
CMD gunicorn app:aplicacion --bind 0.0.0.0:${PORT:-10000} --workers 2 --timeout 120

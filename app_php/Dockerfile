# ============================================================
# Dockerfile - Aplicacion PHP con Apache
# Imagen para desplegar en Render como Web Service (Docker)
# ============================================================
#
# EXPLICACION GENERAL (estilo estudiante):
# Un Dockerfile es como una "receta de cocina" que le dice a Docker
# como construir una imagen. Cada instruccion (FROM, RUN, COPY, etc.)
# crea una "capa" (layer). Las capas se cachean para que las
# siguientes construcciones sean mas rapidas si nada cambio.
#
# Flujo de este archivo:
#   1. Partimos de una imagen base (PHP + Apache ya instalados)
#   2. Instalamos librerias del sistema para que PHP pueda hablar con PostgreSQL
#   3. Activamos modulos de Apache
#   4. Copiamos nuestro codigo
#   5. Ajustamos permisos
#   6. Declaramos el puerto
# ============================================================

# Usamos la imagen oficial de PHP con Apache incluido.
#
# ¿Por que php:8.2-apache?
#   - php:8.2      -> Version 8.2 de PHP (estable, con soporte activo).
#   - -apache       -> Variante que YA trae Apache preinstalado y configurado
#                      para ejecutar archivos .php mediante mod_php.
#   - Es una imagen OFICIAL mantenida por Docker y la comunidad PHP,
#     por lo que recibe parches de seguridad regularmente.
#
# Alternativas:
#   - php:8.2-fpm  -> Solo PHP-FPM (sin servidor web), necesitarias Nginx aparte.
#   - php:8.2-cli  -> Solo linea de comandos, sin servidor.
#
# La etiqueta "apache" nos ahorra instalar y configurar Apache manualmente.
FROM php:8.2-apache

# Instalamos las dependencias necesarias para PostgreSQL.
#
# Desglose del comando (cada parte separada por &&):
#
#   apt-get update
#     Actualiza la lista de paquetes disponibles desde los repositorios
#     de Debian. Sin esto, apt-get install podria fallar porque la lista
#     esta desactualizada (las imagenes Docker suelen venir con listas
#     vacias para ahorrar espacio).
#
#   apt-get install -y libpq-dev
#     Instala el paquete libpq-dev.
#       - libpq-dev: Libreria de DESARROLLO de PostgreSQL (headers y archivos
#         .so necesarios para COMPILAR extensiones). Sin esto, docker-php-ext-install
#         no puede compilar pdo_pgsql porque le faltan los archivos de cabecera.
#       - La flag -y: Responde "yes" automaticamente a la pregunta de confirmacion,
#         necesario porque Docker no es interactivo.
#
#   docker-php-ext-install pdo_pgsql
#     Comando PROPIO de la imagen base php (no es un comando de Linux).
#     Compila e instala una extension de PHP desde su codigo fuente interno.
#       - pdo_pgsql: El driver PDO para PostgreSQL. PDO (PHP Data Objects)
#         es una capa de abstraccion de base de datos en PHP que permite
#         usar el mismo codigo con diferentes motores (MySQL, PostgreSQL, SQLite, etc.).
#         pdo_pgsql es el driver especifico que implementa PDO para PostgreSQL.
#       - NOTA: Si usaramos MySQL seria pdo_mysql. Para SQLite seria pdo_sqlite.
#
#   apt-get clean
#     Limpia la cache local de paquetes .deb descargados. Reduce el tamaño
#     de la imagen final (buena practica en Dockerfiles).
#
#   rm -rf /var/lib/apt/lists/*
#     Borra las listas de paquetes descargadas por apt-get update.
#     Ocupan varios MB que no necesitamos en tiempo de ejecucion.
#     Se vuelven a generar si se ejecuta apt-get update otra vez.
#
# ¿Por que todo en un solo RUN?
#   Cada instruccion RUN crea una CAPA separada en la imagen Docker.
#   Si separaramos apt-get update, install y clean en RUNs distintos,
#   la capa de update+install guardaria los .deb en la cache PARA SIEMPRE
#   (aunque luego hagas clean en otra capa, la capa anterior sigue ocupando
#   espacio). Juntarlo todo en un solo RUN minimiza el tamaño de la imagen.
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilitamos mod_rewrite de Apache (util para URLs amigables).
#
# a2enmod = "Apache 2 Enable Module".
# Es un script de Debian/Ubuntu que activa modulos de Apache creando
# enlaces simbolicos en /etc/apache2/mods-enabled/.
#
# mod_rewrite: Permite reescribir URLs en tiempo real usando reglas
# en archivos .htaccess o en la configuracion de Apache. Ejemplos:
#   - /productos/5  ->  /productos.php?id=5  (URLs amigables)
#   - Redirecciones 301/302 (cambios de dominio, HTTP a HTTPS)
#   - Bloquear acceso a ciertos archivos
RUN a2enmod rewrite

# Configuramos el DocumentRoot de Apache (directorio publico).
#
# ENV establece una VARIABLE DE ENTORNO dentro del contenedor.
#   - APACHE_DOCUMENT_ROOT: Esta variable es leida por el script de
#     entrada de la imagen php:apache. Al cambiarla, Apache servira
#     los archivos desde la ruta que especifiquemos.
#   - /var/www/html: Es el directorio por defecto donde Apache busca
#     los archivos a servir (el "document root").
#
# Las variables de entorno (ENV) persisten en TODAS las capas siguientes
# y tambien en el contenedor en ejecucion.
ENV APACHE_DOCUMENT_ROOT=/var/www/html

# Copiamos el codigo PHP al directorio publico de Apache.
#
# COPY tiene dos argumentos:
#   - Origen ( . ): El directorio donde esta el Dockerfile (build context).
#     Todo lo que este en ./app_php/ se copia dentro del contenedor.
#   - Destino ( /var/www/html/ ): Ruta DENTRO del contenedor.
#
# IMPORTANTE sobre el orden de COPY:
#   Docker cachea cada capa. Si copiaramos TODO el codigo antes de
#   instalar dependencias, cualquier cambio en un archivo .php
#   invalidaria la cache y obligaria a reinstalar todo.
#   En ESTE caso no hay problema porque apt-get va antes del COPY.
#   (En el Dockerfile de Flask si aprovechamos mejor la cache,
#    copiando requirements.txt antes que el codigo).
COPY . /var/www/html/

# Le damos permisos adecuados a los archivos.
#
# chown -R www-data:www-data /var/www/html
#   - chown = Change Owner (cambia propietario).
#   - -R = Recursivo (aplica a TODOS los archivos y subcarpetas).
#   - www-data: Es el USUARIO y GRUPO bajo el cual se ejecuta Apache
#     en Debian/Ubuntu. Por seguridad, Apache NO corre como root.
#     Si los archivos pertenecen a root, Apache podria no poder leerlos
#     o escribir en ellos (uploads, logs, etc.).
#   - www-data:www-data = usuario:grupo (se llaman igual por convencion).
#
# chmod -R 755 /var/www/html
#   - chmod = Change Mode (cambia permisos).
#   - 755 = Notacion octal de permisos:
#       7 (dueño)   = rwx = leer(4) + escribir(2) + ejecutar(1)
#       5 (grupo)   = r-x = leer(4) + ejecutar(1)
#       5 (otros)   = r-x = leer(4) + ejecutar(1)
#     Esto permite que Apache lea y "ejecute" (recorra) los directorios,
#     pero no permite que usuarios externos escriban en ellos.
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exponemos el puerto 80 (Render lo mapea automaticamente al puerto que asigna).
#
# EXPOSE es una instruccion DOCUMENTAL (informativa).
# NO publica el puerto por si sola. Sirve para:
#   - Decirle a otros desarrolladores que esta aplicacion ESCUCHA en el puerto 80.
#   - Herramientas como docker-compose o Render leen EXPOSE para saber
#     que puerto mapear automaticamente.
#   - Para PUBLICAR realmente el puerto se usa "docker run -p 80:80" o
#     la seccion "ports:" en docker-compose.
#
# Apache por defecto escucha en el puerto 80 (HTTP).
EXPOSE 80

# Apache se inicia automaticamente con la imagen base.
# No necesitamos CMD adicional.
#
# ¿Por que no hay CMD?
# La imagen base php:8.2-apache YA tiene un CMD definido que inicia
# Apache en primer plano (foreground). Si no estuviera, el contenedor
# se detendria inmediatamente despues de construir.
#
# El CMD heredado es equivalente a:
#   CMD ["apache2-foreground"]
#
# apache2-foreground es un script que inicia Apache sin lanzarlo como
# daemon (proceso en segundo plano), porque Docker necesita que el
# proceso principal (PID 1) permanezca vivo. Si PID 1 muere, el
# contenedor se detiene.

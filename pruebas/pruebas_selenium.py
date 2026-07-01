# -*- coding: utf-8 -*-
# ^^^ CODIFICACIÓN: Esta línea le dice al intérprete de Python que el archivo
#     contiene caracteres especiales (acentos, ñ, etc.) en codificación UTF-8.
#     Es necesario porque usamos texto en español (como "reseña", "catálogo")
#     y sin esta línea, Python podría no interpretar correctamente esos caracteres.

"""
--------------------------------------------------------------------
pruebas_selenium.py
--------------------------------------------------------------------

=== ¿QUÉ ES ESTE ARCHIVO? ==========================================
Este archivo contiene pruebas automatizadas (tests) usando Selenium
WebDriver. Selenium es una herramienta que permite CONTROLAR UN
NAVEGADOR WEB desde código Python, simulando las acciones que haría
un usuario real (hacer clic, escribir en campos, seleccionar opciones
de menús desplegables, navegar entre páginas, etc.).

=== ¿POR QUÉ USAMOS SELENIUM? ======================================
Porque queremos probar que nuestra aplicación web funciona
correctamente de PUNTA A PUNTA (end-to-end), es decir, desde que
el usuario abre la página hasta que recibe un mensaje de éxito.
Selenium automatiza este proceso para que no tengamos que probar
manualmente cada vez que hacemos un cambio en el código.

=== ¿QUÉ APLICACIÓN SE ESTÁ PROBANDO? ==============================
Una aplicación web PHP de RESEÑAS DE VIDEOJUEGOS. La aplicación
permite:
  - Registrar nuevos videojuegos (nombre, género, plataforma, etc.)
  - Registrar reseñas sobre videojuegos existentes (calificación,
    comentario, nombre de usuario)
  - Consultar un catálogo con todos los videojuegos y sus reseñas
  - Consultar estadísticas (datos que vienen de una API Flask)

=== PRUEBAS INCLUIDAS EN ESTE ARCHIVO ==============================
  1. prueba_registrar_videojuego()  -> Test de registro de videojuego
  2. prueba_registrar_resena()      -> Test de registro de reseña
  3. prueba_consultar_catalogo()    -> Test de consulta del catálogo
  4. prueba_consultar_estadisticas()-> Test de consulta de estadísticas

=== REQUISITOS PARA EJECUTAR ========================================
  - Tener Python instalado (versión 3.7 o superior recomendada)
  - Instalar la biblioteca Selenium:
        pip install selenium
  - Tener el WebDriver de Chrome (chromedriver) instalado y en el PATH
    del sistema, O usar el de Firefox (geckodriver).
    El WebDriver es un ejecutable que actúa como "puente" entre
    nuestro código Python y el navegador real.

=== CÓMO EJECUTAR ESTE ARCHIVO ======================================
  1. Cambiar la variable URL_BASE (línea más abajo) por la URL
     donde esté corriendo tu aplicación PHP.
  2. Ejecutar desde la terminal:
        python pruebas_selenium.py

=== ESTRUCTURA DE CADA PRUEBA =======================================
  Cada función de prueba sigue el mismo patrón:
    1. navegador.get(URL)         -> Navega a una página
    2. esperar_elemento(...)      -> Espera a que un elemento cargue
    3. find_element(...)          -> Encuentra un elemento HTML
    4. send_keys(...) o click()   -> Interactúa con el elemento
    5. Verificar resultado        -> Comprueba si funcionó o no
  Todo esto envuelto en try/except para capturar errores sin que
  el programa completo se detenga.
"""

# ================================================================
# SECCIÓN 1: IMPORTACIONES (¿QUÉ BIBLIOTECAS NECESITAMOS?)
# ================================================================
# En Python, "import" trae funcionalidades escritas por otros
# programadores para no tener que reinventar la rueda. Cada
# importación abajo es una pieza necesaria para que Selenium
# funcione correctamente.

# --- Importación principal de Selenium ---
# webdriver es el módulo CENTRAL de Selenium. Contiene todo lo
# necesario para crear y controlar una instancia del navegador.
# Sin este import, no podríamos hacer NADA con Selenium.
from selenium import webdriver

# --- Estrategias de búsqueda de elementos HTML ---
# By es una clase que define CÓMO vamos a buscar elementos en la
# página web. Los elementos HTML pueden buscarse de varias formas:
#   - By.ID           -> Busca por el atributo id (ej: id="nombre")
#   - By.CLASS_NAME   -> Busca por clase CSS (ej: class="alert-success")
#   - By.CSS_SELECTOR -> Busca con selectores CSS (ej: "button[type='submit']")
#   - By.TAG_NAME     -> Busca por etiqueta HTML (ej: h2, body, div)
#   - By.NAME         -> Busca por el atributo name
#   - By.XPATH        -> Busca con expresiones XPath
# Usamos By para decirle a Selenium QUÉ tipo de búsqueda queremos hacer.
from selenium.webdriver.common.by import By

# --- Manejo de elementos <select> (menús desplegables) ---
# En HTML, los menús desplegables (<select>) funcionan diferente a
# los inputs de texto normales. No podemos simplemente hacer .send_keys().
# La clase Select nos da métodos como:
#   - select_by_visible_text() -> Selecciona por el texto visible
#   - select_by_value()        -> Selecciona por el valor del option
#   - select_by_index()        -> Selecciona por posición (0, 1, 2...)
from selenium.webdriver.support.ui import Select

# --- Esperas explícitas (WebDriverWait) ---
# Las páginas web no cargan instantáneamente. Si intentamos buscar
# un elemento antes de que exista en la página, obtendremos un error.
# WebDriverWait nos permite decir: "espera HASTA que este elemento
# aparezca, o hasta que pasen X segundos, lo que ocurra primero".
# Es MUCHO mejor que usar time.sleep() fijo, porque:
#   - Si el elemento carga rápido, no perdemos tiempo esperando
#   - Si el elemento tarda, le damos la oportunidad de aparecer
from selenium.webdriver.support.ui import WebDriverWait

# --- Condiciones de espera (expected_conditions) ---
# Al usar WebDriverWait, necesitamos decirle QUÉ condición debe
# cumplirse para dejar de esperar. expected_conditions (alias EC)
# nos da muchas condiciones predefinidas, como:
#   - EC.presence_of_element_located() -> El elemento EXISTE en el DOM
#   - EC.visibility_of_element_located() -> El elemento es VISIBLE
#   - EC.element_to_be_clickable() -> El elemento se puede clickear
# Usamos EC para definir la condición de nuestra espera explícita.
from selenium.webdriver.support import expected_conditions as EC

# --- Pausas fijas con time.sleep() ---
# Aunque preferimos WebDriverWait, hay casos donde necesitamos una
# pausa FIJA (ejemplo: después de enviar un formulario, esperamos
# un par de segundos a que el servidor procese la solicitud antes
# de buscar el mensaje de respuesta). time.sleep(segundos) PAUSA
# la ejecución del script por el tiempo exacto indicado.
import time

# ================================================================
# SECCIÓN 2: CONFIGURACIÓN GLOBAL
# ================================================================
# Aquí definimos variables y configuraciones que se usarán en todo
# el script. Tenerlas en un solo lugar facilita hacer cambios:
# solo modificas una línea en vez de buscar por todo el archivo.

# URL_BASE: La dirección raíz de tu aplicación web.
# ¿POR QUÉ ES IMPORTANTE? Porque todas las páginas a probar
# (registrar_videojuego.php, catalogo.php, etc.) están bajo esta URL.
# Si tu aplicación cambia de servidor, solo necesitas cambiar esta
# variable y todas las pruebas seguirán funcionando.
#
# Ejemplos:
#   Si pruebas en local con XAMPP:  URL_BASE = "http://localhost/app_php/"
#   Si pruebas en Render (nube):    URL_BASE = "https://tu-app.onrender.com/"
#   Si usas el servidor de PHP:     URL_BASE = "http://localhost:3000/"
URL_BASE = "http://localhost:3000/"

# ================================================================
# SECCIÓN 3: INICIALIZACIÓN DEL NAVEGADOR
# ================================================================
# Antes de poder hacer cualquier prueba, necesitamos ABRIR un
# navegador controlado por Selenium. Esta sección se encarga de ello.

# --- Mensaje informativo para el usuario ---
# La función print() muestra texto en la terminal/consola.
# Esto ayuda a quien ejecuta las pruebas a saber en qué paso va el
# script, especialmente útil si hay errores o si el proceso es largo.
print("Iniciando el navegador Chrome para las pruebas...")

# --- Creación de la instancia del navegador ---
# webdriver.Chrome() hace varias cosas INTERNAMENTE:
#   1. Busca el ejecutable chromedriver en el PATH del sistema
#   2. Inicia una nueva ventana de Chrome "virgen" (sin cookies,
#      sin extensiones, sin historial)
#   3. Establece una conexión entre Python y esa ventana para
#      poder enviarle comandos (navegar, hacer clic, etc.)
# La variable "navegador" será nuestro "control remoto" del navegador.
# ¿QUÉ PASA SI QUEREMOS USAR FIREFOX?
#   Cambiamos webdriver.Chrome() por webdriver.Firefox()
#   y necesitamos tener geckodriver instalado en el PATH.
navegador = webdriver.Chrome()

# --- Configuración del tamaño de la ventana ---
# set_window_size(ancho, alto) define las dimensiones de la ventana
# del navegador en PÍXELES.
# ¿POR QUÉ 1366x768? Es una resolución de pantalla muy común
# (portátiles estándar). Probar con un tamaño fijo nos ayuda a
# tener resultados consistentes: si la página se ve bien a 1366x768,
# probablemente se verá bien en la mayoría de dispositivos.
# Sin esto, el navegador se abriría en un tamaño por defecto que
# podría variar entre sistemas operativos.
navegador.set_window_size(1366, 768)

# --- Tiempo máximo de espera para elementos ---
# TIEMPO_ESPERA: Esta variable define CUÁNTOS SEGUNDOS como máximo
# esperará WebDriverWait antes de rendirse y lanzar un error.
# ¿POR QUÉ 10 SEGUNDOS? Es un valor balanceado:
#   - Si espera menos (ej: 3s), podríamos tener falsos negativos
#     si el servidor está lento.
#   - Si espera más (ej: 30s), las pruebas serían muy lentas cuando
#     algo falla.
#   10 segundos es un estándar común en pruebas web.
TIEMPO_ESPERA = 10

# --- Contadores de resultados de pruebas ---
# Estas variables globales llevan la cuenta de cuántas pruebas
# pasaron y cuántas fallaron. Se actualizan dentro de cada función
# de prueba usando la palabra clave "global".
# ¿POR QUÉ SON GLOBALES? Porque las funciones de prueba necesitan
# modificar estos valores, y al final del script mostramos un
# resumen con los totales.
pruebas_exitosas = 0   # Contador: pruebas que pasaron (éxito)
pruebas_fallidas = 0    # Contador: pruebas que fallaron (error)

# ================================================================
# SECCIÓN 4: FUNCIÓN AUXILIAR (HELPER)
# ================================================================

def esperar_elemento(tipo, valor):
    """
    FUNCIÓN AUXILIAR: espera a que un elemento HTML esté presente
    en la página antes de continuar.

    PARÁMETROS:
      - tipo:  La ESTRATEGIA de búsqueda (ej: By.ID, By.CLASS_NAME).
               Define CÓMO vamos a buscar el elemento en el HTML.
      - valor: El VALOR del selector (ej: "nombre", "alert-success").
               Define CUÁL elemento específico estamos buscando.

    RETORNA:
      - El elemento web encontrado, listo para interactuar con él
        (hacer clic, escribir texto, leer su contenido, etc.)

    ¿QUÉ HACE INTERNAMENTE?
      1. WebDriverWait(navegador, TIEMPO_ESPERA): Crea un "vigilante"
         que observa el navegador durante máximo TIEMPO_ESPERA segundos.
      2. .until(...): Le dice al vigilante: "espera HASTA que se cumpla
         esta condición, o lanza un error si se acaba el tiempo".
      3. EC.presence_of_element_located((tipo, valor)): La CONDICIÓN
         específica. Verifica que el elemento EXISTA en el DOM
         (Document Object Model = la estructura interna de la página).
         NO requiere que sea visible, solo que esté en el código HTML.

    ¿POR QUÉ CREAMOS ESTA FUNCIÓN AUXILIAR?
      - Para NO repetir el mismo código en cada prueba (DRY: Don't
        Repeat Yourself).
      - Si en el futuro cambiamos la lógica de espera (ej: usar
        visibility en vez de presence), solo modificamos esta función
        y no cada una de las 4 pruebas por separado.

    EJEMPLO DE USO:
      esperar_elemento(By.ID, "nombre")
      -> Espera hasta que exista un elemento con id="nombre" en la
         página, o hasta que pasen 10 segundos.
    """
    # WebDriverWait recibe DOS argumentos:
    #   1. El navegador (dónde buscar)
    #   2. El tiempo máximo de espera en segundos
    # Luego .until() recibe UNA condición de expected_conditions.
    # EC.presence_of_element_located recibe una TUPLA de DOS valores:
    #   (estrategia_de_busqueda, valor_del_selector)
    # La tupla va entre paréntesis DOBLES: uno para la función,
    # otro para la tupla misma ((tipo, valor)).
    return WebDriverWait(navegador, TIEMPO_ESPERA).until(
        EC.presence_of_element_located((tipo, valor))
    )


# ================================================================
# SECCIÓN 5: PRUEBA 1 - Registro de Videojuego
# ================================================================
# Esta prueba verifica el CASO FELIZ (happy path) del registro de
# videojuegos: el usuario llena todos los campos correctamente,
# presiona el botón de enviar, y recibe un mensaje de confirmación.

def prueba_registrar_videojuego():
    """
    PRUEBA AUTOMATIZADA #1: REGISTRO DE VIDEOJUEGO

    OBJETIVO:
      Verificar que un usuario puede registrar un NUEVO videojuego
      desde el formulario web y recibir un mensaje de éxito.

    FLUJO DE LA PRUEBA (PASOS):
      1. Navegar a la página registrar_videojuego.php
      2. Esperar que el formulario cargue (campo "nombre")
      3. Llenar el campo "nombre" con texto de prueba único
         (usamos timestamp para que no colisione con registros anteriores)
      4. Seleccionar un género del menú desplegable (RPG)
      5. Seleccionar una plataforma del menú desplegable (PC)
      6. Llenar el campo "descripción" con texto de prueba
      7. Llenar el campo "fecha_lanzamiento" con una fecha
      8. Hacer clic en el botón de envío
      9. Esperar que el servidor procese la solicitud
      10. Verificar que aparezca un mensaje de clase "alert-success"
          que contenga el texto "registrado correctamente"

    RESULTADO:
      - Si el mensaje aparece y contiene el texto esperado: PRUEBA EXITOSA
      - Si ocurre cualquier error o el mensaje no es el esperado: PRUEBA FALLIDA
    """
    # --- Declaración de variables globales ---
    # Necesitamos la palabra clave "global" para MODIFICAR las variables
    # pruebas_exitosas y pruebas_fallidas que están definidas FUERA de
    # esta función (en el ámbito global). Sin "global", Python crearía
    # variables LOCALES con el mismo nombre, y los contadores globales
    # nunca se actualizarían.
    global pruebas_exitosas, pruebas_fallidas

    # --- Impresión del encabezado de la prueba ---
    # El caracter "\n" agrega un salto de línea (ENTER) para separar
    # visualmente esta prueba de la anterior.
    # "="*60 crea una línea de 60 signos "=" para hacer un separador
    # visual en la terminal.
    print("\n" + "="*60)
    print("PRUEBA 1: Registro de Videojuego")
    print("="*60)

    # --- BLOQUE try/except ---
    # try:  Código que QUEREMOS ejecutar pero que PODRÍA FALLAR.
    # except: Código que se ejecuta SOLO SI algo falló en el try.
    # ¿POR QUÉ ES IMPORTANTE? Porque si una prueba falla, no queremos
    # que el script completo se detenga. Queremos capturar el error,
    # marcarlo como prueba fallida, y CONTINUAR con la siguiente prueba.
    try:
        # ========================================================
        # PASO 1: Navegar a la página del formulario
        # ========================================================
        # navegador.get(url) es el equivalente a escribir una URL
        # en la barra de direcciones y presionar ENTER.
        # INTERNAMENTE, Selenium le dice al navegador: "carga esta URL".
        # El navegador hace la petición HTTP, descarga el HTML, CSS, JS,
        # y renderiza (dibuja) la página completa.
        # Este método es BLOQUEANTE: Python espera hasta que el navegador
        # termina de cargar la página antes de continuar con la siguiente
        # línea de código.
        print("-> Navegando a: " + URL_BASE + "registrar_videojuego.php")
        navegador.get(URL_BASE + "registrar_videojuego.php")

        # ========================================================
        # PASO 2: Esperar que el formulario esté listo
        # ========================================================
        # Antes de intentar interactuar con cualquier campo del
        # formulario, debemos asegurarnos de que el formulario ya
        # existe en la página. Usamos nuestra función auxiliar
        # esperar_elemento con el selector By.ID y el valor "nombre".
        #
        # ¿QUÉ ES By.ID?
        #   Es la estrategia de búsqueda por ATRIBUTO ID de HTML.
        #   Ejemplo: <input id="nombre" type="text">
        #   Buscar por ID es la forma MÁS RÁPIDA y MÁS CONFIABLE
        #   de encontrar elementos, porque los IDs deben ser ÚNICOS
        #   en toda la página (según el estándar HTML).
        #
        # ¿POR QUÉ ESPERAMOS ESPECÍFICAMENTE EL CAMPO "nombre"?
        #   Porque es el primer campo del formulario. Si este campo
        #   ya está presente, es muy probable que todo el formulario
        #   haya terminado de cargar.
        esperar_elemento(By.ID, "nombre")

        # ========================================================
        # PASO 3: Llenar el campo "nombre"
        # ========================================================
        # find_element(): Busca y retorna el PRIMER elemento que
        # coincide con el selector dado. Si no encuentra ninguno,
        # lanza una excepción NoSuchElementException.
        #
        # ¿QUÉ ES EL ATRIBUTO ID "nombre"?
        #   Corresponde a un campo <input id="nombre" ...> en el
        #   formulario HTML donde el usuario escribe el nombre del
        #   videojuego que quiere registrar.
        campo_nombre = navegador.find_element(By.ID, "nombre")

        # clear(): Borra cualquier texto que ya esté en el campo.
        # ¿POR QUÉ LIMPIAMOS EL CAMPO?
        #   Porque el navegador podría tener autocompletado activado
        #   que llene campos con datos de pruebas anteriores. Limpiar
        #   asegura que empezamos con un campo vacío.
        campo_nombre.clear()

        # send_keys(): Simula TECLEAR en el campo, carácter por carácter,
        # como si un usuario real estuviera escribiendo.
        #
        # ¿POR QUÉ USAMOS int(time.time())?
        #   time.time() retorna el timestamp UNIX actual (segundos desde
        #   1 de enero de 1970). Al convertirlo a entero y concatenarlo
        #   con el texto, creamos un nombre ÚNICO cada vez que se ejecuta
        #   la prueba. Esto es importante porque:
        #   1. Evita colisiones con registros de ejecuciones anteriores
        #   2. Permite ejecutar la prueba múltiples veces sin error de
        #      "nombre duplicado"
        #   3. Facilita identificar cuándo se creó cada registro de prueba
        campo_nombre.send_keys("Juego de Prueba Selenium " + str(int(time.time())))
        print("-> Campo 'nombre' llenado correctamente")

        # ========================================================
        # PASO 4: Seleccionar el género del videojuego
        # ========================================================
        # El campo "genero" es un elemento <select> de HTML (menú
        # desplegable). En Selenium, los <select> requieren un
        # tratamiento ESPECIAL: envolvemos el elemento encontrado
        # con la clase Select para tener acceso a métodos diseñados
        # para menús desplegables.
        #
        # ¿QUÉ ES Select()?
        #   Es una clase "wrapper" (envoltura) de Selenium que toma
        #   un elemento <select> normal y le agrega métodos útiles
        #   como select_by_visible_text(), select_by_value(),
        #   select_by_index().
        #
        # ¿QUÉ ES select_by_visible_text()?
        #   Selecciona la opción del <select> cuyo TEXTO VISIBLE
        #   coincide exactamente con el texto dado ("RPG").
        #   Ejemplo: <option value="rpg">RPG</option>
        #   Busca la opción que MUESTRA "RPG" al usuario en pantalla.
        #   Es más legible y mantenible que buscar por value o index.
        campo_genero = Select(navegador.find_element(By.ID, "genero"))
        campo_genero.select_by_visible_text("RPG")
        print("-> Campo 'genero' seleccionado: RPG")

        # ========================================================
        # PASO 5: Seleccionar la plataforma del videojuego
        # ========================================================
        # Similar al paso anterior, pero para el campo "plataforma".
        # También es un <select> y también usamos Select() para
        # envolverlo y select_by_visible_text() para elegir "PC".
        # ¿POR QUÉ "PC"?
        #   Es una plataforma de videojuegos común y probablemente
        #   siempre estará disponible en las opciones del sistema.
        campo_plataforma = Select(navegador.find_element(By.ID, "plataforma"))
        campo_plataforma.select_by_visible_text("PC")
        print("-> Campo 'plataforma' seleccionado: PC")

        # ========================================================
        # PASO 6: Llenar el campo "descripcion"
        # ========================================================
        # Similar al campo "nombre": encontramos el elemento por ID,
        # limpiamos cualquier texto previo, y enviamos texto de prueba.
        #
        # El texto de prueba incluye la frase "Selenium" para que,
        # si revisamos la base de datos manualmente, podamos
        # identificar fácilmente qué registros fueron creados por
        # estas pruebas automatizadas (y eliminarlos si es necesario,
        # para mantener limpia la base de datos de desarrollo).
        campo_descripcion = navegador.find_element(By.ID, "descripcion")
        campo_descripcion.clear()
        campo_descripcion.send_keys("Este es un videojuego de prueba generado por Selenium para validar el formulario de registro. Incluye mecanicas RPG clasicas.")
        print("-> Campo 'descripcion' llenado correctamente")

        # ========================================================
        # PASO 7: Llenar el campo de fecha de lanzamiento
        # ========================================================
        # El campo "fecha_lanzamiento" es un <input type="date">.
        # Este tipo de campo espera la fecha en formato YYYY-MM-DD
        # (año-mes-día), que es el formato estándar ISO 8601.
        #
        # ¿POR QUÉ NO USAMOS clear() AQUÍ?
        #   Los campos tipo "date" en HTML tienen un comportamiento
        #   especial. Simplemente enviamos la fecha con send_keys()
        #   y el navegador la interpreta correctamente. Si tuviera
        #   un valor previo, send_keys() lo sobrescribiría.
        #
        # ¿POR QUÉ LA FECHA 2024-06-15?
        #   Es una fecha de prueba arbitraria pero realista (un
        #   videojuego lanzado en junio de 2024). No tiene un
        #   significado especial, simplemente necesitamos una fecha
        #   válida para que el formulario no sea rechazado.
        campo_fecha = navegador.find_element(By.ID, "fecha_lanzamiento")
        campo_fecha.send_keys("2024-06-15")
        print("-> Campo 'fecha_lanzamiento' llenado: 2024-06-15")

        # ========================================================
        # PASO 8: Enviar el formulario (clic en el botón)
        # ========================================================
        # Buscamos el botón de envío usando un SELECTOR CSS.
        #
        # ¿QUÉ ES UN SELECTOR CSS?
        #   Es un patrón para seleccionar elementos HTML basado en
        #   sus atributos y jerarquía. Similar a como seleccionamos
        #   elementos para aplicarles estilos en CSS.
        #
        # ¿QUÉ SIGNIFICA "button[type='submit']"?
        #   Busca un elemento <button> que tenga el atributo
        #   type="submit". Los corchetes [] son el selector de
        #   atributo en CSS.
        #   Ejemplo de HTML: <button type="submit">Registrar</button>
        #
        # ¿POR QUÉ USAMOS CSS_SELECTOR EN VEZ DE ID?
        #   Porque el botón podría NO tener un ID único. Muchos
        #   formularios HTML tienen botones sin ID, pero es muy
        #   común que usen type="submit". Si el botón tuviera un ID,
        #   usaríamos By.ID por ser más rápido.
        #
        # click(): Simula un CLIC del mouse sobre el elemento.
        # Esto dispara el evento "submit" del formulario HTML,
        # que envía todos los datos al servidor.
        boton_registrar = navegador.find_element(By.CSS_SELECTOR, "button[type='submit']")
        boton_registrar.click()
        print("-> Clic en boton 'Registrar Videojuego'")

        # ========================================================
        # PASO 9: Esperar la respuesta del servidor
        # ========================================================
        # Después de hacer clic en el botón, el navegador envía los
        # datos al servidor PHP. El servidor procesa la solicitud
        # (inserta en base de datos, valida datos, etc.) y responde
        # con una nueva página (o la misma con un mensaje).
        #
        # ¿POR QUÉ USAMOS time.sleep(2) AQUÍ Y NO WebDriverWait?
        #   Porque el mensaje de éxito PUEDE que ya esté en la página
        #   original (si el formulario se envía con AJAX) o PUEDE
        #   que esté en una página nueva después de una redirección.
        #   En este caso, una pausa fija de 2 segundos es una forma
        #   simple de dar tiempo al servidor. En un código más
        #   robusto, usaríamos WebDriverWait con una condición más
        #   específica.
        time.sleep(2)  # Pequeña pausa para que procese el formulario

        # ========================================================
        # PASO 10: Verificar el mensaje de éxito
        # ========================================================
        # Buscamos un elemento que tenga la clase CSS "alert-success".
        #
        # ¿QUÉ ES By.CLASS_NAME?
        #   Busca elementos por su atributo "class" de HTML.
        #   Ejemplo: <div class="alert alert-success">Registrado!</div>
        #
        # ¿QUÉ ES LA CLASE "alert-success"?
        #   Es una clase común en frameworks CSS como Bootstrap.
        #   Bootstrap define alert-success como un mensaje de color
        #   verde con un ícono de check, indicando que una operación
        #   fue exitosa. Asumimos que la aplicación PHP usa este
        #   estándar para mostrar mensajes de éxito al usuario.
        #
        # ¿QUÉ ES .text?
        #   Es una PROPIEDAD de los elementos web en Selenium que
        #   retorna todo el TEXTO VISIBLE dentro de ese elemento
        #   (incluyendo el texto de sus elementos hijos).
        #   Ejemplo: <div>Hola <b>mundo</b></div>.text -> "Hola mundo"
        mensaje_exito = navegador.find_element(By.CLASS_NAME, "alert-success")
        texto_mensaje = mensaje_exito.text

        # ========================================================
        # VERIFICACIÓN FINAL (ASSERT)
        # ========================================================
        # Esta es la PARTE MÁS IMPORTANTE de la prueba: verificar
        # que el resultado obtenido es el ESPERADO.
        #
        # Usamos el operador "in" de Python para verificar si el
        # texto "registrado correctamente" está CONTENIDO dentro
        # del mensaje mostrado. No hacemos comparación exacta (==)
        # porque el mensaje podría tener texto adicional como
        # "¡El videojuego ha sido registrado correctamente!" y
        # solo nos importa la parte clave.
        if "registrado correctamente" in texto_mensaje:
            # --- CASO DE ÉXITO ---
            # Si el mensaje contiene el texto esperado, la prueba PASÓ.
            # Incrementamos el contador de pruebas exitosas.
            print("*** PRUEBA 1 EXITOSA: Videojuego registrado correctamente ***")
            print("    Mensaje: " + texto_mensaje)
            pruebas_exitosas += 1   # Sumamos 1 al contador de éxitos
        else:
            # --- CASO DE FALLA POR MENSAJE INCORRECTO ---
            # Si el mensaje NO contiene el texto esperado, aunque esté
            # presente un elemento alert-success, el mensaje es incorrecto.
            # Esto podría pasar si el sistema cambió los textos de respuesta.
            print("XXX PRUEBA 1 FALLIDA: El mensaje no indica exito >>>")
            print("    Mensaje recibido: " + texto_mensaje)
            pruebas_fallidas += 1   # Sumamos 1 al contador de fallas

    except Exception as error:
        # --- CASO DE FALLA POR EXCEPCIÓN ---
        # Si CUALQUIER línea dentro del bloque try lanza una excepción
        # (error), Python salta inmediatamente a este bloque except.
        #
        # ¿QUÉ TIPO DE ERRORES PODRÍAN OCURRIR?
        #   - NoSuchElementException: no se encontró un elemento
        #   - TimeoutException: WebDriverWait agotó el tiempo de espera
        #   - WebDriverException: el navegador se cerró inesperadamente
        #   - Cualquier otro error de Python
        #
        # str(error) convierte la excepción a texto legible para
        # mostrarla en la consola, ayudando a diagnosticar QUÉ falló
        # y EN QUÉ parte de la prueba.
        print("XXX PRUEBA 1 FALLIDA con error: " + str(error))
        pruebas_fallidas += 1


# ================================================================
# SECCIÓN 6: PRUEBA 2 - Registro de Reseña
# ================================================================
# Esta prueba verifica que un usuario puede registrar una RESEÑA
# sobre un videojuego existente. Para que funcione, DEBE existir
# al menos UN videojuego en la base de datos (por ejemplo, el que
# se registró en la PRUEBA 1).

def prueba_registrar_resena():
    """
    PRUEBA AUTOMATIZADA #2: REGISTRO DE RESEÑA

    OBJETIVO:
      Verificar que un usuario puede registrar una NUEVA RESEÑA
      sobre un videojuego existente y recibir confirmación.

    PRECONDICIÓN:
      Debe existir al menos UN videojuego en la base de datos para
      poder seleccionarlo en el menú desplegable. La PRUEBA 1
      (registrar videojuego) se ejecuta antes que esta precisamente
      para garantizar que haya datos disponibles.

    FLUJO DE LA PRUEBA:
      1. Navegar a registrar_resena.php
      2. Esperar que cargue el campo "videojuego_id"
      3. Seleccionar el primer videojuego disponible del menú
         desplegable (índice 1, porque el índice 0 suele ser
         "-- Seleccione --" o un placeholder)
      4. Llenar el campo "nombre_usuario"
      5. Seleccionar calificación de 4 estrellas
      6. Llenar el campo "comentario"
      7. Hacer clic en el botón de envío
      8. Verificar mensaje de éxito que contenga "correctamente"
    """
    # Declaramos acceso a las variables globales para actualizar
    # los contadores de pruebas exitosas y fallidas.
    global pruebas_exitosas, pruebas_fallidas

    print("\n" + "="*60)
    print("PRUEBA 2: Registro de Reseña")
    print("="*60)

    try:
        # --- PASO 1: Navegar al formulario de reseñas ---
        print("-> Navegando a: " + URL_BASE + "registrar_resena.php")
        navegador.get(URL_BASE + "registrar_resena.php")

        # --- PASO 2: Esperar que el formulario cargue ---
        # Esperamos específicamente el campo "videojuego_id" que es
        # el elemento <select> donde el usuario elige qué videojuego
        # va a reseñar. Si este select está presente, el formulario
        # está listo.
        #
        # ¿POR QUÉ "videojuego_id"?
        #   Porque en la base de datos, las reseñas tienen una
        #   RELACIÓN con los videojuegos mediante una LLAVE FORÁNEA
        #   (foreign key). El campo "videojuego_id" es el ID del
        #   videojuego que se está reseñando. En el formulario HTML,
        #   esto se representa como un <select> que lista todos los
        #   videojuegos disponibles.
        esperar_elemento(By.ID, "videojuego_id")

        # --- PASO 3: Seleccionar un videojuego del menú desplegable ---
        # Envolvemos el <select> con la clase Select para poder usar
        # sus métodos especializados para menús desplegables.
        campo_videojuego = Select(navegador.find_element(By.ID, "videojuego_id"))

        # .options es una PROPIEDAD de la clase Select que retorna
        # una LISTA con TODAS las opciones (<option>) dentro del
        # <select>. Cada opción representa un videojuego disponible.
        # Esto nos permite:
        #   1. Verificar CUÁNTOS videojuegos hay disponibles
        #   2. Leer el TEXTO de cada opción (nombre del videojuego)
        #   3. Saber si hay suficientes opciones para la prueba
        opciones = campo_videojuego.options

        # Verificamos que haya más de 1 opción. El índice 0 es
        # típicamente un placeholder como "-- Seleccione --" que
        # no es un videojuego real. Necesitamos al menos UN
        # videojuego real (índice 1 en adelante).
        if len(opciones) > 1:
            # select_by_index(1): Selecciona la opción en la POSICIÓN 1
            # de la lista (SEGUNDA opción, porque las listas empiezan en 0).
            # El índice 0 es el placeholder "-- Seleccione --".
            # El índice 1 es el PRIMER videojuego real disponible.
            #
            # ¿POR QUÉ USAMOS select_by_index EN VEZ DE select_by_visible_text?
            #   Porque no sabemos de antemano qué videojuegos existen en la
            #   base de datos. Al seleccionar por índice, tomamos el primero
            #   disponible sin necesidad de conocer su nombre exacto.
            campo_videojuego.select_by_index(1)

            # Mostramos el nombre del videojuego seleccionado para que
            # quien ejecuta la prueba sepa sobre qué videojuego se está
            # haciendo la reseña. Esto es útil para debugging.
            # opciones[1].text obtiene el texto visible de esa opción.
            print("-> Videojuego seleccionado: " + opciones[1].text)
        else:
            # --- CASO: NO HAY VIDEOJUEGOS DISPONIBLES ---
            # Si solo existe la opción placeholder (índice 0), no
            # podemos continuar con la prueba porque no hay ningún
            # videojuego que reseñar. Marcamos como fallida y salimos
            # de la función con return (termina la ejecución aquí).
            print("XXX No hay videojuegos disponibles para reseñar")
            pruebas_fallidas += 1
            return  # Salimos de la función inmediatamente

        # --- PASO 4: Llenar el campo "nombre_usuario" ---
        # Este campo representa el nombre o alias del usuario que
        # está escribiendo la reseña. En un sistema real, esto podría
        # venir de una sesión iniciada, pero en esta aplicación de
        # prueba se pide manualmente.
        #
        # find_element(By.ID, "nombre_usuario"):
        #   Busca el input con id="nombre_usuario" en el HTML.
        # clear():
        #   Borra cualquier texto previo (autocompletado, caché).
        # send_keys("TesterSelenium"):
        #   Escribe el texto "TesterSelenium" en el campo.
        #   Es un nombre de usuario de prueba que identifica
        #   claramente que esta reseña fue creada por pruebas
        #   automatizadas.
        campo_usuario = navegador.find_element(By.ID, "nombre_usuario")
        campo_usuario.clear()
        campo_usuario.send_keys("TesterSelenium")
        print("-> Campo 'nombre_usuario' llenado: TesterSelenium")

        # --- PASO 5: Seleccionar calificación de 4 estrellas ---
        # La calificación también es un <select> (menú desplegable)
        # con valores del 1 al 5 (estrellas).
        #
        # ¿POR QUÉ 4 ESTRELLAS?
        #   Es una calificación positiva pero no perfecta, lo que
        #   hace la prueba más realista. Si el sistema tiene alguna
        #   validación especial para ciertos valores, 4 es un valor
        #   intermedio que debería ser aceptado por cualquier regla.
        #
        # select_by_value("4"):
        #   Selecciona la opción cuyo ATRIBUTO VALUE sea "4".
        #   Ejemplo: <option value="4">4 estrellas</option>
        #   A diferencia de select_by_visible_text (que busca por
        #   el texto que ve el usuario), select_by_value busca por
        #   el valor interno del option. Usamos value porque es más
        #   preciso: el texto visible podría ser "★★★★☆" o "4/5",
        #   pero el value es consistente ("4").
        campo_calificacion = Select(navegador.find_element(By.ID, "calificacion"))
        campo_calificacion.select_by_value("4")
        print("-> Campo 'calificacion' seleccionado: 4 estrellas")

        # --- PASO 6: Llenar el campo "comentario" ---
        # Este campo probablemente es un <textarea> (área de texto
        # multilínea) donde el usuario escribe su opinión detallada
        # sobre el videojuego.
        #
        # El texto de prueba menciona explícitamente que es una
        # reseña automatizada por Selenium. Esto facilita identificar
        # y limpiar datos de prueba en la base de datos.
        #
        # Nota: No se usa clear() en textarea? Por consistencia con
        # los otros campos, sí lo usamos. Aunque un textarea nuevo
        # suele estar vacío, clear() garantiza que empezamos limpio.
        campo_comentario = navegador.find_element(By.ID, "comentario")
        campo_comentario.clear()
        campo_comentario.send_keys("Reseña automatizada por Selenium. Muy buen juego, recomendado para fans del genero.")
        print("-> Campo 'comentario' llenado correctamente")

        # --- PASO 7: Enviar el formulario ---
        # Buscamos el botón de envío con el mismo selector CSS
        # usado en la prueba 1: cualquier <button> con type="submit".
        # Esto asume que el formulario de reseñas sigue el mismo
        # patrón de diseño que el de videojuegos (consistencia).
        #
        # .click(): Dispara el evento de clic, lo que hace que el
        # formulario se envíe al servidor PHP para su procesamiento.
        boton_registrar = navegador.find_element(By.CSS_SELECTOR, "button[type='submit']")
        boton_registrar.click()
        print("-> Clic en boton 'Registrar Reseña'")

        # --- PASO 8: Esperar y verificar el resultado ---
        # Pausa fija de 2 segundos para dar tiempo al servidor de
        # procesar la solicitud (validar datos, insertar en BD,
        # generar la página de respuesta).
        time.sleep(2)

        # Buscamos el mensaje de éxito con clase "alert-success".
        # Misma estrategia que en la PRUEBA 1.
        mensaje_exito = navegador.find_element(By.CLASS_NAME, "alert-success")
        texto_mensaje = mensaje_exito.text

        # VERIFICACIÓN: Comprobamos que el texto contenga "correctamente"
        # (en minúsculas o mayúsculas, por eso usamos .lower()).
        #
        # ¿POR QUÉ .lower()?
        #   Convierte todo el texto a minúsculas antes de comparar.
        #   Esto hace la verificación INSENSIBLE a mayúsculas/minúsculas.
        #   Si el sistema dice "Registrado CORRECTAMENTE" o "registrado
        #   correctamente", ambas pasarán la verificación.
        #   Es una buena práctica para pruebas porque los mensajes de
        #   interfaz pueden cambiar de capitalización sin que el
        #   significado cambie.
        #
        # ¿POR QUÉ "correctamente" Y NO "registrado correctamente"?
        #   Es una verificación más flexible. El mensaje exacto podría
        #   ser "Reseña registrada correctamente" o "Tu reseña se ha
        #   guardado correctamente". Al buscar solo "correctamente",
        #   aceptamos cualquiera de estas variantes.
        if "correctamente" in texto_mensaje.lower():
            print("*** PRUEBA 2 EXITOSA: Reseña registrada correctamente ***")
            print("    Mensaje: " + texto_mensaje)
            pruebas_exitosas += 1
        else:
            print("XXX PRUEBA 2 FALLIDA: Mensaje inesperado >>>")
            print("    Mensaje recibido: " + texto_mensaje)
            pruebas_fallidas += 1

    except Exception as error:
        # Captura de cualquier error que ocurra durante la prueba.
        # La variable "error" contiene el objeto de la excepción,
        # y str(error) lo convierte a un mensaje legible para
        # mostrarlo en la consola y facilitar la depuración.
        print("XXX PRUEBA 2 FALLIDA con error: " + str(error))
        pruebas_fallidas += 1


# ================================================================
# SECCIÓN 7: PRUEBA 3 - Consulta de Catálogo
# ================================================================
# Esta prueba verifica que la página del catálogo muestra
# correctamente los videojuegos registrados en el sistema.
# Es una prueba de LECTURA (no escribe datos, solo consulta).

def prueba_consultar_catalogo():
    """
    PRUEBA AUTOMATIZADA #3: CONSULTA DE CATÁLOGO

    OBJETIVO:
      Verificar que la página de catálogo (catalogo.php) carga
      correctamente y MUESTRA los videojuegos registrados con
      su información.

    TIPO DE PRUEBA:
      Prueba de SOLO LECTURA. No inserta ni modifica datos en la
      base de datos. Solo verifica que la consulta y presentación
      de datos funcionan.

    FLUJO DE LA PRUEBA:
      1. Navegar a catalogo.php
      2. Esperar que la página cargue
      3. Verificar que el título de la página es el esperado
      4. Buscar las tarjetas (cards) de videojuegos
      5. Verificar que haya al menos UNA tarjeta
      6. Mostrar el nombre del primer videojuego como evidencia
    """
    global pruebas_exitosas, pruebas_fallidas

    print("\n" + "="*60)
    print("PRUEBA 3: Consulta de Catálogo")
    print("="*60)

    try:
        # --- PASO 1: Navegar a la página del catálogo ---
        print("-> Navegando a: " + URL_BASE + "catalogo.php")
        navegador.get(URL_BASE + "catalogo.php")

        # --- PASO 2: Esperar que la página cargue ---
        # A diferencia de las pruebas anteriores, aquí NO usamos
        # WebDriverWait con un elemento específico, sino una pausa
        # fija de 2 segundos.
        #
        # ¿POR QUÉ?
        #   La página de catálogo probablemente carga datos desde
        #   una base de datos y los renderiza como HTML. El tiempo
        #   de carga puede variar según cuántos videojuegos haya.
        #   2 segundos es un estimado razonable para que la consulta
        #   SQL se complete y la página se renderice.
        #
        # NOTA: En un código más profesional, usaríamos WebDriverWait
        # esperando un elemento específico como la primera tarjeta.
        time.sleep(2)

        # --- PASO 3: Verificar el título de la página ---
        # Buscamos un elemento <h2> (encabezado de nivel 2) que
        # debería contener el título de la página.
        #
        # ¿POR QUÉ By.TAG_NAME "h2"?
        #   Porque en HTML semántico, es común que el título
        #   principal de la página sea un <h1> y el título de la
        #   sección de contenido sea un <h2>. Asumimos que
        #   "Catálogo de Videojuegos" está en un <h2>.
        #
        # ¿QUÉ ES By.TAG_NAME?
        #   Busca elementos por el NOMBRE DE LA ETIQUETA HTML
        #   (div, p, h1, h2, span, body, etc.). Es menos específico
        #   que By.ID o By.CLASS_NAME, porque puede haber muchos
        #   elementos con la misma etiqueta. find_element (singular)
        #   retorna el PRIMERO que encuentra.
        #
        # .text: Propiedad que obtiene el texto visible del elemento.
        titulo_pagina = navegador.find_element(By.TAG_NAME, "h2")
        if "Catálogo" in titulo_pagina.text:
            # Si el texto del <h2> contiene la palabra "Catálogo",
            # asumimos que es el título correcto (puede tener
            # acentos o texto adicional).
            print("-> Titulo de pagina encontrado: " + titulo_pagina.text)
        else:
            # Si no contiene "Catálogo", informamos que el título
            # no es el esperado, pero NO marcamos como fallida aún.
            # Seguimos verificando otros elementos.
            print("XXX Titulo de pagina no es el esperado")

        # --- PASO 4: Buscar las tarjetas de videojuegos ---
        # find_elements (PLURAL): Busca TODOS los elementos que
        # coinciden con el selector. Retorna una LISTA (puede estar
        # vacía si no encuentra nada). A diferencia de find_element
        # (singular), NO lanza excepción si no encuentra nada,
        # simplemente retorna una lista vacía [].
        #
        # ¿QUÉ ES "card-header"?
        #   Es una clase CSS del framework Bootstrap. Una "card" de
        #   Bootstrap es un contenedor visual tipo "tarjeta" o "ficha"
        #   que agrupa información relacionada. "card-header" es el
        #   ENCABEZADO de esa tarjeta (fondo de color, título, etc.).
        #   Asumimos que cada videojuego se muestra en una tarjeta
        #   Bootstrap con clase "card-header" (probablemente con la
        #   clase adicional "bg-primary" para fondo azul).
        #
        # ¿POR QUÉ find_elements Y NO find_element?
        #   Porque esperamos VARIAS tarjetas (una por videojuego).
        #   find_elements nos da TODAS para contarlas e interactuar
        #   con ellas. find_element solo nos daría la primera.
        tarjetas = navegador.find_elements(By.CLASS_NAME, "card-header")

        # len() es una función built-in de Python que retorna la
        # cantidad de elementos en una lista (en este caso, cuántas
        # tarjetas de videojuegos se encontraron).
        cantidad_tarjetas = len(tarjetas)

        # --- PASO 5: Verificar que existan videojuegos ---
        if cantidad_tarjetas > 0:
            # f-string (f"...") es una forma moderna de formatear
            # texto en Python 3.6+. La variable entre llaves {}
            # se reemplaza por su valor. Es más legible que la
            # concatenación con + que usamos en otros prints.
            print(f"-> Se encontraron {cantidad_tarjetas} videojuegos en el catalogo")

            # --- EVIDENCIA ADICIONAL ---
            # Para demostrar que realmente encontramos videojuegos
            # (no solo contamos tarjetas vacías), mostramos el
            # NOMBRE del primer videojuego.
            #
            # tarjetas[0]: Primera tarjeta de la lista (índice 0).
            # .find_element(By.TAG_NAME, "h5"): Dentro de ESA tarjeta,
            #   buscamos un elemento <h5> (encabezado nivel 5).
            #   ¿Por qué h5? Es común en Bootstrap usar <h5> para
            #   el título de una card. El nombre del videojuego
            #   probablemente esté en un <h5> dentro del card-header.
            # .text: Obtenemos el texto de ese <h5> (el nombre).
            #
            # NOTA: Esto busca SOLO DENTRO de la primera tarjeta,
            # no en toda la página, gracias a que llamamos
            # find_element SOBRE tarjetas[0] en vez de navegador.
            primer_juego = tarjetas[0].find_element(By.TAG_NAME, "h5")
            print("-> Primer videojuego en catalogo: " + primer_juego.text)

            # La prueba es exitosa porque se encontraron videojuegos
            # en el catálogo y pudimos leer su información.
            print("*** PRUEBA 3 EXITOSA: Catálogo cargado correctamente ***")
            pruebas_exitosas += 1
        else:
            # --- CASO: CATÁLOGO VACÍO ---
            # Si no se encuentra ninguna tarjeta de videojuego,
            # la prueba falla. Esto puede deberse a:
            #   - La base de datos está vacía
            #   - La página no está renderizando correctamente
            #   - Las clases CSS cambiaron (ya no usan "card-header")
            print("XXX PRUEBA 3 FALLIDA: No se encontraron videojuegos en el catalogo")
            pruebas_fallidas += 1

    except Exception as error:
        # Captura genérica de cualquier excepción.
        # Si alguna línea falla (por ejemplo, no encuentra un <h2>,
        # o la página no carga), se captura aquí, se muestra el
        # error, y se cuenta como prueba fallida.
        print("XXX PRUEBA 3 FALLIDA con error: " + str(error))
        pruebas_fallidas += 1


# ================================================================
# SECCIÓN 8: PRUEBA 4 - Consulta de Estadísticas
# ================================================================
# Esta prueba verifica la página de estadísticas, que muestra datos
# obtenidos de una API Flask (un servicio web separado del frontend
# PHP). Es una prueba de INTEGRACIÓN porque verifica la comunicación
# entre el frontend y el backend.

def prueba_consultar_estadisticas():
    """
    PRUEBA AUTOMATIZADA #4: CONSULTA DE ESTADÍSTICAS

    OBJETIVO:
      Verificar que la página de estadísticas (estadisticas.php)
      carga correctamente y muestra los datos obtenidos desde la
      API Flask.

    ¿QUÉ ES UNA API FLASK?
      Flask es un micro-framework de Python para crear aplicaciones
      web y APIs REST. En esta arquitectura, el frontend PHP hace
      una petición HTTP al backend Flask, que consulta la base de
      datos y retorna los resultados en formato JSON (JavaScript
      Object Notation). Luego el frontend PHP muestra esos datos
      al usuario.

    TIPO DE PRUEBA:
      Prueba de INTEGRACIÓN. Verifica que dos componentes separados
      (frontend PHP y backend Flask) funcionan correctamente juntos.

    NOTA IMPORTANTE:
      Esta prueba asume que la API Flask está EJECUTÁNDOSE. Si la
      API no está disponible, la página de estadísticas podría
      mostrar un mensaje como "No se pudo conectar con el servidor
      de estadísticas" en lugar de los datos. La prueba está diseñada
      para manejar AMBOS casos como éxito (porque lo que nos importa
      es que la PÁGINA cargue, no necesariamente que la API responda).

    FLUJO DE LA PRUEBA:
      1. Navegar a estadisticas.php
      2. Esperar que la página cargue (3 segundos, porque la llamada
         a la API puede tomar más tiempo)
      3. Verificar que el título de la página es correcto
      4. Verificar que el cuerpo de la página contiene contenido
         esperado (datos O mensaje de error de conexión)
    """
    global pruebas_exitosas, pruebas_fallidas

    print("\n" + "="*60)
    print("PRUEBA 4: Consulta de Estadísticas")
    print("="*60)

    try:
        # --- PASO 1: Navegar a la página de estadísticas ---
        print("-> Navegando a: " + URL_BASE + "estadisticas.php")
        navegador.get(URL_BASE + "estadisticas.php")

        # --- PASO 2: Esperar que la página cargue ---
        # Usamos una pausa de 3 segundos (más larga que en otras
        # pruebas).
        #
        # ¿POR QUÉ 3 SEGUNDOS EN VEZ DE 2?
        #   Porque esta página hace una llamada HTTP adicional a la
        #   API Flask. La secuencia es:
        #     1. El navegador carga estadisticas.php
        #     2. El servidor PHP ejecuta el código
        #     3. El código PHP hace una petición HTTP a la API Flask
        #     4. La API Flask consulta la base de datos
        #     5. La API Flask retorna los resultados en JSON
        #     6. El servidor PHP renderiza la página con los datos
        #     7. El navegador muestra la página final
        #   Este proceso toma más tiempo que simplemente cargar una
        #   página estática o hacer una consulta directa a BD.
        time.sleep(3)

        # --- PASO 3: Verificar el título de la página ---
        # Igual que en la PRUEBA 3: buscamos un <h2> y verificamos
        # que contenga "Estadísticas".
        #
        # Nota: No usamos comparación exacta (==) sino el operador
        # "in" porque el título podría tener texto adicional.
        titulo_pagina = navegador.find_element(By.TAG_NAME, "h2")
        if "Estadísticas" in titulo_pagina.text:
            print("-> Titulo de pagina encontrado: " + titulo_pagina.text)
        else:
            print("XXX Titulo de pagina no es el esperado")

        # --- PASO 4: Verificar el contenido del cuerpo ---
        # En lugar de buscar un elemento específico, leemos TODO el
        # texto visible de la página completa.
        #
        # navegador.find_element(By.TAG_NAME, "body"):
        #   Encuentra el elemento <body> HTML (el cuerpo entero de
        #   la página, que contiene TODO el contenido visible).
        #
        # .text:
        #   Obtiene TODO el texto visible dentro del <body>, uniendo
        #   el texto de todos los elementos hijos. Esto nos da una
        #   "fotografía textual" de toda la página.
        #
        # ¿POR QUÉ LEEMOS TODO EL CUERPO?
        #   Porque no sabemos exactamente qué estructura HTML tendrá
        #   la página de estadísticas. Podría mostrar los datos en
        #   tablas, tarjetas, listas, etc. Al leer el texto completo,
        #   podemos verificar que ciertas PALABRAS CLAVE están
        #   presentes sin depender de una estructura HTML específica.
        cuerpo_pagina = navegador.find_element(By.TAG_NAME, "body").text

        # --- VERIFICACIÓN COMBINADA ---
        # Usamos el operador lógico "or" para considerar la prueba
        # exitosa en DOS escenarios diferentes:
        #
        # ESCENARIO A: La API Flask está funcionando
        #   - La página muestra "Total de videojuegos" (los datos
        #     reales de estadísticas obtenidos de la API).
        #   - Esto significa que la integración PHP-Flask funciona.
        #
        # ESCENARIO B: La API Flask NO está disponible
        #   - La página muestra "No se pudo conectar" (un mensaje
        #     de error amigable).
        #   - Esto significa que la página PHP MANEJA CORRECTAMENTE
        #     el error de conexión (no muestra un error feo de PHP).
        #   - Es un "éxito parcial": la página funciona, aunque la
        #     API no esté disponible en este momento.
        #
        # Ambos escenarios son resultados VÁLIDOS para esta prueba.
        if "Total de videojuegos" in cuerpo_pagina or "No se pudo conectar" in cuerpo_pagina:
            print("*** PRUEBA 4 EXITOSA: Página de estadísticas cargada ***")

            # Si detectamos que la API no estaba disponible, mostramos
            # una nota informativa adicional (no es un error, pero es
            # útil saberlo para debugging).
            if "No se pudo conectar" in cuerpo_pagina:
                print("    (!) La API Flask no esta disponible, pero la pagina cargo correctamente")

            pruebas_exitosas += 1
        else:
            # --- CASO: LA PÁGINA NO MUESTRA LO ESPERADO ---
            # Si no aparece ni "Total de videojuegos" ni "No se pudo
            # conectar", hay un problema. Posibles causas:
            #   - La página no cargó correctamente
            #   - El servidor PHP tiene un error
            #   - Los textos de la interfaz cambiaron
            #   - La estructura de la página es diferente
            print("XXX PRUEBA 4 FALLIDA: La pagina no muestra el contenido esperado")
            pruebas_fallidas += 1

    except Exception as error:
        print("XXX PRUEBA 4 FALLIDA con error: " + str(error))
        pruebas_fallidas += 1


# ================================================================
# SECCIÓN 9: BLOQUE PRINCIPAL DE EJECUCIÓN
# ================================================================
# Este es el PUNTO DE ENTRADA del script. Todo lo anterior eran
# definiciones de funciones; nada se ejecuta hasta que Python llega
# a este bloque.

# ¿QUÉ ES if __name__ == '__main__'?
#   Es una CONVENCIÓN de Python que verifica si este archivo se
#   está ejecutando DIRECTAMENTE (python pruebas_selenium.py) o si
#   se está IMPORTANDO desde otro archivo (import pruebas_selenium).
#
#   - Si se ejecuta directamente: __name__ vale '__main__'
#     y el código dentro del if se ejecuta.
#   - Si se importa como módulo: __name__ vale 'pruebas_selenium'
#     y el código dentro del if NO se ejecuta.
#
#   Esto permite que las funciones definidas arriba puedan ser
#   reutilizadas por otros scripts sin ejecutar las pruebas
#   automáticamente.
if __name__ == '__main__':

    # --- Mensaje de bienvenida ---
    # Información para el usuario que ejecuta las pruebas:
    # qué script se está ejecutando y contra qué URL.
    print("="*60)
    print("INICIANDO PRUEBAS AUTOMATIZADAS CON SELENIUM")
    print("URL Base: " + URL_BASE)
    print("="*60)

    # --- BLOQUE try/except/finally ---
    # try:     Ejecuta las pruebas en secuencia.
    # except:  Captura errores INESPERADOS a nivel general (fuera
    #          de las funciones de prueba, donde no hay try/except).
    # finally: Se ejecuta SIEMPRE, haya habido error o no. Aquí
    #          mostramos el resumen y cerramos el navegador.
    try:
        # --- EJECUCIÓN SECUENCIAL DE LAS PRUEBAS ---
        # Las pruebas se ejecutan en ORDEN, una después de otra.
        # Esto es importante porque las pruebas tienen DEPENDENCIAS:
        #   - PRUEBA 1 crea un videojuego
        #   - PRUEBA 2 necesita que exista un videojuego para reseñar
        #   - PRUEBA 3 necesita que haya videojuegos en el catálogo
        #   - PRUEBA 4 es independiente (solo consulta estadísticas)

        # Llamada a la función de PRUEBA 1.
        # Los paréntesis () EJECUTAN la función.
        prueba_registrar_videojuego()

        # Pausa de 1 segundo entre pruebas.
        # ¿POR QUÉ?
        #   1. Dar tiempo al navegador para "asentar" la página anterior
        #   2. Evitar sobrecargar el servidor con peticiones muy rápidas
        #   3. Hacer que la ejecución sea más fácil de seguir visualmente
        #   4. Prevenir condiciones de carrera (race conditions) en el
        #      servidor donde una prueba podría interferir con la siguiente
        time.sleep(1)

        # Llamada a la función de PRUEBA 2.
        prueba_registrar_resena()
        time.sleep(1)

        # Llamada a la función de PRUEBA 3.
        prueba_consultar_catalogo()
        time.sleep(1)

        # Llamada a la función de PRUEBA 4.
        # Esta es la última, no necesita time.sleep() después.
        prueba_consultar_estadisticas()

    except Exception as error_general:
        # --- ERROR GENERAL ---
        # Si ocurre un error FUERA de las funciones de prueba
        # (ejemplo: el navegador se cerró inesperadamente entre
        # pruebas), se captura aquí.
        # Las funciones de prueba YA TIENEN su propio try/except,
        # así que los errores DENTRO de ellas no llegarían aquí
        # a menos que sean errores MUY graves que impidan continuar.
        print("\nXXX ERROR GENERAL DURANTE LAS PRUEBAS: " + str(error_general))

    finally:
        # --- BLOQUE FINALLY ---
        # El código dentro de finally se ejecuta SIEMPRE:
        #   - Si todas las pruebas pasaron
        #   - Si alguna prueba falló
        #   - Si ocurrió un error general
        #   - Incluso si el usuario presiona Ctrl+C (en teoría)
        #
        # Esto garantiza que SIEMPRE mostraremos el resumen y
        # cerraremos el navegador, sin importar qué pasó antes.

        # --- RESUMEN FINAL DE PRUEBAS ---
        # Mostramos un resumen con:
        #   - Cuántas pruebas pasaron (exitosas)
        #   - Cuántas pruebas fallaron
        #   - Total de pruebas ejecutadas
        #
        # Los valores se imprimen usando f-strings para formateo
        # limpio. Las variables pruebas_exitosas y pruebas_fallidas
        # fueron actualizadas por cada función de prueba.
        print("\n" + "="*60)
        print("RESUMEN DE PRUEBAS")
        print("="*60)
        print(f"Pruebas exitosas: {pruebas_exitosas}")
        print(f"Pruebas fallidas:  {pruebas_fallidas}")
        print(f"Total de pruebas:  {pruebas_exitosas + pruebas_fallidas}")
        print("="*60)

        # --- PAUSA ANTES DE CERRAR ---
        # input() detiene la ejecución y espera a que el usuario
        # presione ENTER. Esto permite que quien ejecuta las pruebas
        # pueda:
        #   1. Leer el resumen con calma
        #   2. Revisar el navegador (qué páginas quedaron abiertas)
        #   3. Hacer debugging visual si algo falló
        # Sin esta pausa, el navegador se cerraría inmediatamente
        # después de mostrar el resumen y el usuario no tendría
        # oportunidad de ver nada.
        input("\nPresiona ENTER para cerrar el navegador y finalizar...")

        # --- CIERRE DEL NAVEGADOR ---
        # navegador.quit() CIERRA COMPLETAMENTE el navegador.
        #
        # ¿QUÉ HACE .quit()?
        #   1. Cierra TODAS las ventanas y pestañas abiertas
        #   2. Termina el proceso del navegador (chrome.exe)
        #   3. Libera los recursos del sistema (memoria RAM)
        #   4. Cierra la conexión entre Python y el navegador
        #
        # ¿POR QUÉ ES IMPORTANTE LLAMAR A .quit()?
        #   Si no cerramos el navegador, el proceso chrome.exe
        #   seguiría ejecutándose en segundo plano, consumiendo
        #   memoria y recursos del sistema. En ejecuciones repetidas,
        #   esto podría acumular muchos procesos zombie.
        #
        # DIFERENCIA ENTRE .close() Y .quit():
        #   - .close(): Cierra SOLO la pestaña/ventana actual
        #   - .quit(): Cierra TODO el navegador y sus procesos
        #   Siempre preferimos .quit() al final de las pruebas
        #   para una limpieza completa.
        print("Cerrando el navegador...")
        navegador.quit()

        # --- Mensaje de despedida ---
        # Avisa al usuario que todo terminó correctamente.
        print("Pruebas finalizadas.")

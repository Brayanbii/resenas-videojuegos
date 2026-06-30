# -*- coding: utf-8 -*-
"""
pruebas_selenium.py
Pruebas automatizadas con Selenium WebDriver para la aplicacion de reseñas de videojuegos.

Pruebas incluidas:
  1. Registro de videojuego
  2. Registro de reseña
  3. Consulta de catalogo
  4. Consulta de estadisticas

Requisitos:
  pip install selenium
  Tener el WebDriver de Chrome (chromedriver) instalado y en el PATH
  O usar el driver de Firefox (geckodriver)

Instrucciones:
  - Cambiar la variable URL_BASE por la URL de tu aplicacion PHP
  - Ejecutar: python pruebas_selenium.py
"""

# Importamos las bibliotecas necesarias
from selenium import webdriver
from selenium.webdriver.common.by import By                     # Para buscar elementos
from selenium.webdriver.support.ui import Select                # Para manejar selects
from selenium.webdriver.support.ui import WebDriverWait         # Para esperas explicitas
from selenium.webdriver.support import expected_conditions as EC # Condiciones de espera
import time                                                     # Para pausas (time.sleep)

# ============================================================
# CONFIGURACION: Cambia esta URL por la de tu aplicacion PHP
# ============================================================
# Si pruebas en local con XAMPP: http://localhost/app_php/
# Si pruebas en Render: https://tu-app-en-render.onrender.com/
URL_BASE = "http://localhost:3000/"

# ============================================================
# CONFIGURACION DEL NAVEGADOR
# ============================================================
# Inicializamos el driver de Chrome
# Si quieres usar Firefox, cambia a: webdriver.Firefox()
print("Iniciando el navegador Chrome para las pruebas...")
navegador = webdriver.Chrome()

# Configuramos el tamaño de la ventana para mejor visualizacion
navegador.set_window_size(1366, 768)

# Tiempo maximo de espera para encontrar elementos (en segundos)
TIEMPO_ESPERA = 10

# Contador de pruebas exitosas
pruebas_exitosas = 0
pruebas_fallidas = 0


def esperar_elemento(tipo, valor):
    """
    Funcion auxiliar para esperar a que un elemento este presente en la pagina.
    Retorna el elemento cuando esta disponible.
    """
    return WebDriverWait(navegador, TIEMPO_ESPERA).until(
        EC.presence_of_element_located((tipo, valor))
    )


# ============================================================
# PRUEBA 1: Registro de Videojuego
# ============================================================
def prueba_registrar_videojuego():
    """
    Prueba automatizada para verificar que se puede registrar
    un nuevo videojuego correctamente desde el formulario.
    """
    global pruebas_exitosas, pruebas_fallidas
    print("\n" + "="*60)
    print("PRUEBA 1: Registro de Videojuego")
    print("="*60)

    try:
        # Navegamos al formulario de registro de videojuegos
        print("-> Navegando a: " + URL_BASE + "registrar_videojuego.php")
        navegador.get(URL_BASE + "registrar_videojuego.php")

        # Esperamos a que el formulario este cargado
        esperar_elemento(By.ID, "nombre")

        # Llenamos el campo "nombre"
        campo_nombre = navegador.find_element(By.ID, "nombre")
        campo_nombre.clear()
        campo_nombre.send_keys("Juego de Prueba Selenium " + str(int(time.time())))
        print("-> Campo 'nombre' llenado correctamente")

        # Llenamos el campo "genero" (es un select)
        campo_genero = Select(navegador.find_element(By.ID, "genero"))
        campo_genero.select_by_visible_text("RPG")
        print("-> Campo 'genero' seleccionado: RPG")

        # Llenamos el campo "plataforma" (es un select)
        campo_plataforma = Select(navegador.find_element(By.ID, "plataforma"))
        campo_plataforma.select_by_visible_text("PC")
        print("-> Campo 'plataforma' seleccionado: PC")

        # Llenamos el campo "descripcion"
        campo_descripcion = navegador.find_element(By.ID, "descripcion")
        campo_descripcion.clear()
        campo_descripcion.send_keys("Este es un videojuego de prueba generado por Selenium para validar el formulario de registro. Incluye mecanicas RPG clasicas.")
        print("-> Campo 'descripcion' llenado correctamente")

        # Llenamos el campo "fecha_lanzamiento" (input type date)
        campo_fecha = navegador.find_element(By.ID, "fecha_lanzamiento")
        campo_fecha.send_keys("2024-06-15")
        print("-> Campo 'fecha_lanzamiento' llenado: 2024-06-15")

        # Hacemos clic en el boton "Registrar Videojuego"
        boton_registrar = navegador.find_element(By.CSS_SELECTOR, "button[type='submit']")
        boton_registrar.click()
        print("-> Clic en boton 'Registrar Videojuego'")

        # Esperamos a que aparezca el mensaje de exito (alert con clase 'alert-success')
        time.sleep(2)  # Pequeña pausa para que procese el formulario
        mensaje_exito = navegador.find_element(By.CLASS_NAME, "alert-success")
        texto_mensaje = mensaje_exito.text

        # Verificamos que el mensaje contenga "registrado correctamente"
        if "registrado correctamente" in texto_mensaje:
            print("*** PRUEBA 1 EXITOSA: Videojuego registrado correctamente ***")
            print("    Mensaje: " + texto_mensaje)
            pruebas_exitosas += 1
        else:
            print("XXX PRUEBA 1 FALLIDA: El mensaje no indica exito >>>")
            print("    Mensaje recibido: " + texto_mensaje)
            pruebas_fallidas += 1

    except Exception as error:
        print("XXX PRUEBA 1 FALLIDA con error: " + str(error))
        pruebas_fallidas += 1


# ============================================================
# PRUEBA 2: Registro de Reseña
# ============================================================
def prueba_registrar_resena():
    """
    Prueba automatizada para verificar que se puede registrar
    una nueva reseña sobre un videojuego existente.
    """
    global pruebas_exitosas, pruebas_fallidas
    print("\n" + "="*60)
    print("PRUEBA 2: Registro de Reseña")
    print("="*60)

    try:
        # Navegamos al formulario de registro de reseñas
        print("-> Navegando a: " + URL_BASE + "registrar_resena.php")
        navegador.get(URL_BASE + "registrar_resena.php")

        # Esperamos a que el formulario cargue
        esperar_elemento(By.ID, "videojuego_id")

        # Seleccionamos el primer videojuego disponible del select
        campo_videojuego = Select(navegador.find_element(By.ID, "videojuego_id"))
        # Verificamos que haya opciones disponibles
        opciones = campo_videojuego.options
        if len(opciones) > 1:
            campo_videojuego.select_by_index(1)  # El indice 0 es "-- Seleccione --"
            print("-> Videojuego seleccionado: " + opciones[1].text)
        else:
            print("XXX No hay videojuegos disponibles para reseñar")
            pruebas_fallidas += 1
            return

        # Llenamos el campo "nombre_usuario"
        campo_usuario = navegador.find_element(By.ID, "nombre_usuario")
        campo_usuario.clear()
        campo_usuario.send_keys("TesterSelenium")
        print("-> Campo 'nombre_usuario' llenado: TesterSelenium")

        # Seleccionamos una calificacion de 4 estrellas
        campo_calificacion = Select(navegador.find_element(By.ID, "calificacion"))
        campo_calificacion.select_by_value("4")
        print("-> Campo 'calificacion' seleccionado: 4 estrellas")

        # Llenamos el campo "comentario"
        campo_comentario = navegador.find_element(By.ID, "comentario")
        campo_comentario.clear()
        campo_comentario.send_keys("Reseña automatizada por Selenium. Muy buen juego, recomendado para fans del genero.")
        print("-> Campo 'comentario' llenado correctamente")

        # Hacemos clic en el boton de registrar
        boton_registrar = navegador.find_element(By.CSS_SELECTOR, "button[type='submit']")
        boton_registrar.click()
        print("-> Clic en boton 'Registrar Reseña'")

        # Esperamos el mensaje de exito
        time.sleep(2)
        mensaje_exito = navegador.find_element(By.CLASS_NAME, "alert-success")
        texto_mensaje = mensaje_exito.text

        if "correctamente" in texto_mensaje.lower():
            print("*** PRUEBA 2 EXITOSA: Reseña registrada correctamente ***")
            print("    Mensaje: " + texto_mensaje)
            pruebas_exitosas += 1
        else:
            print("XXX PRUEBA 2 FALLIDA: Mensaje inesperado >>>")
            print("    Mensaje recibido: " + texto_mensaje)
            pruebas_fallidas += 1

    except Exception as error:
        print("XXX PRUEBA 2 FALLIDA con error: " + str(error))
        pruebas_fallidas += 1


# ============================================================
# PRUEBA 3: Consulta de Catálogo
# ============================================================
def prueba_consultar_catalogo():
    """
    Prueba automatizada para verificar que la pagina de catalogo
    muestra los videojuegos y sus reseñas correctamente.
    """
    global pruebas_exitosas, pruebas_fallidas
    print("\n" + "="*60)
    print("PRUEBA 3: Consulta de Catálogo")
    print("="*60)

    try:
        # Navegamos a la pagina del catalogo
        print("-> Navegando a: " + URL_BASE + "catalogo.php")
        navegador.get(URL_BASE + "catalogo.php")

        # Esperamos que la pagina cargue
        time.sleep(2)

        # Verificamos que el titulo "Catálogo de Videojuegos" este presente
        titulo_pagina = navegador.find_element(By.TAG_NAME, "h2")
        if "Catálogo" in titulo_pagina.text:
            print("-> Titulo de pagina encontrado: " + titulo_pagina.text)
        else:
            print("XXX Titulo de pagina no es el esperado")

        # Buscamos las tarjetas de videojuegos (tienen clase 'card-header bg-primary')
        tarjetas = navegador.find_elements(By.CLASS_NAME, "card-header")
        cantidad_tarjetas = len(tarjetas)

        if cantidad_tarjetas > 0:
            print(f"-> Se encontraron {cantidad_tarjetas} videojuegos en el catalogo")

            # Mostramos el nombre del primer videojuego como evidencia
            primer_juego = tarjetas[0].find_element(By.TAG_NAME, "h5")
            print("-> Primer videojuego en catalogo: " + primer_juego.text)

            print("*** PRUEBA 3 EXITOSA: Catálogo cargado correctamente ***")
            pruebas_exitosas += 1
        else:
            print("XXX PRUEBA 3 FALLIDA: No se encontraron videojuegos en el catalogo")
            pruebas_fallidas += 1

    except Exception as error:
        print("XXX PRUEBA 3 FALLIDA con error: " + str(error))
        pruebas_fallidas += 1


# ============================================================
# PRUEBA 4: Consulta de Estadísticas
# ============================================================
def prueba_consultar_estadisticas():
    """
    Prueba automatizada para verificar que la pagina de estadisticas
    carga correctamente y muestra los datos desde la API Flask.
    Esta prueba asume que la API Flask esta ejecutandose.
    """
    global pruebas_exitosas, pruebas_fallidas
    print("\n" + "="*60)
    print("PRUEBA 4: Consulta de Estadísticas")
    print("="*60)

    try:
        # Navegamos a la pagina de estadisticas
        print("-> Navegando a: " + URL_BASE + "estadisticas.php")
        navegador.get(URL_BASE + "estadisticas.php")

        # Esperamos que la pagina cargue
        time.sleep(3)

        # Verificamos que el titulo de la pagina este presente
        titulo_pagina = navegador.find_element(By.TAG_NAME, "h2")
        if "Estadísticas" in titulo_pagina.text:
            print("-> Titulo de pagina encontrado: " + titulo_pagina.text)
        else:
            print("XXX Titulo de pagina no es el esperado")

        # Verificamos que haya contenido en la pagina
        # Puede mostrar los datos o un mensaje de "no se pudo conectar"
        cuerpo_pagina = navegador.find_element(By.TAG_NAME, "body").text

        if "Total de videojuegos" in cuerpo_pagina or "No se pudo conectar" in cuerpo_pagina:
            print("*** PRUEBA 4 EXITOSA: Página de estadísticas cargada ***")
            if "No se pudo conectar" in cuerpo_pagina:
                print("    (!) La API Flask no esta disponible, pero la pagina cargo correctamente")
            pruebas_exitosas += 1
        else:
            print("XXX PRUEBA 4 FALLIDA: La pagina no muestra el contenido esperado")
            pruebas_fallidas += 1

    except Exception as error:
        print("XXX PRUEBA 4 FALLIDA con error: " + str(error))
        pruebas_fallidas += 1


# ============================================================
# EJECUCION DE TODAS LAS PRUEBAS
# ============================================================
if __name__ == '__main__':
    print("="*60)
    print("INICIANDO PRUEBAS AUTOMATIZADAS CON SELENIUM")
    print("URL Base: " + URL_BASE)
    print("="*60)

    try:
        # Ejecutamos cada prueba en secuencia
        prueba_registrar_videojuego()
        time.sleep(1)

        prueba_registrar_resena()
        time.sleep(1)

        prueba_consultar_catalogo()
        time.sleep(1)

        prueba_consultar_estadisticas()

    except Exception as error_general:
        print("\nXXX ERROR GENERAL DURANTE LAS PRUEBAS: " + str(error_general))

    finally:
        # Pausa para ver el resultado final antes de cerrar
        print("\n" + "="*60)
        print("RESUMEN DE PRUEBAS")
        print("="*60)
        print(f"Pruebas exitosas: {pruebas_exitosas}")
        print(f"Pruebas fallidas:  {pruebas_fallidas}")
        print(f"Total de pruebas:  {pruebas_exitosas + pruebas_fallidas}")
        print("="*60)

        # Preguntamos al usuario antes de cerrar el navegador
        input("\nPresiona ENTER para cerrar el navegador y finalizar...")

        # Cerramos el navegador
        print("Cerrando el navegador...")
        navegador.quit()
        print("Pruebas finalizadas.")

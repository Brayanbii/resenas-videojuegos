# ============================================================
# Dockerfile - API Flask con Gunicorn
# Imagen para desplegar en Render como Web Service (Docker)
# ============================================================

# Usamos una imagen ligera de Python
FROM python:3.11-slim

# Instalamos solo lo necesario para compilar dependencias
RUN apt-get update && apt-get install -y \
    gcc \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Establecemos el directorio de trabajo dentro del contenedor
WORKDIR /app

# Copiamos primero requirements.txt para aprovechar la cache de Docker
COPY requirements.txt .

# Instalamos las dependencias de Python
RUN pip install --no-cache-dir -r requirements.txt

# Copiamos el resto del codigo (app.py, etc.)
COPY . .

# Exponemos el puerto (Render usa la variable PORT, por defecto 10000)
EXPOSE 10000

# Comando para iniciar la aplicacion con Gunicorn
# Render inyecta la variable de entorno PORT automaticamente
CMD gunicorn app:aplicacion --bind 0.0.0.0:${PORT:-10000} --workers 2 --timeout 120

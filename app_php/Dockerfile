# ============================================================
# Dockerfile - Aplicacion PHP con Apache
# Imagen para desplegar en Render como Web Service (Docker)
# ============================================================

# Usamos la imagen oficial de PHP con Apache incluido
FROM php:8.2-apache

# Instalamos las dependencias necesarias para PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilitamos mod_rewrite de Apache (util para URLs amigables)
RUN a2enmod rewrite

# Configuramos el DocumentRoot de Apache (directorio publico)
ENV APACHE_DOCUMENT_ROOT=/var/www/html

# Copiamos el codigo PHP al directorio publico de Apache
COPY . /var/www/html/

# Le damos permisos adecuados a los archivos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exponemos el puerto 80 (Render lo mapea automaticamente al puerto que asigna)
EXPOSE 80

# Apache se inicia automaticamente con la imagen base
# No necesitamos CMD adicional

FROM php:8.2-apache

# 1. Habilitar mod_rewrite para Apache (soporte de .htaccess y enrutamiento)
RUN a2enmod rewrite

# 2. Instalar dependencias del sistema y extensiones de PHP necesarias
RUN apt-get update && apt-get install -y \
    libssl-dev \
    ca-certificates \
    && docker-php-ext-install pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. Evitar advertencias de FQDN en los logs de Apache
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# 4. Copiar los archivos del proyecto
COPY . /var/www/html/

# 5. Aplicar permisos adecuados para el servidor web
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 6. (Opcional) Si usas el entrypoint para cambiar el puerto de Apache a $PORT de Render
# COPY docker-entrypoint.sh /usr/local/bin/
# RUN chmod +x /usr/local/bin/docker-entrypoint.sh
# CMD ["docker-entrypoint.sh"]

EXPOSE 80
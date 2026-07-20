FROM php:8.2-apache

# Actualizar repositorios e instalar certificados SSL junto con el driver PDO MySQL
RUN apt-get update && apt-get install -y ca-certificates \
    && docker-php-ext-install pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copiar todos los archivos del proyecto al directorio público de Apache
COPY . /var/www/html/

# Configurar los permisos correctos para que Apache pueda leer los archivos
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 80 nativo de Apache (Render lo mapeará automáticamente)
EXPOSE 80
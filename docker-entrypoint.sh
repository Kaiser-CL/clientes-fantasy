#!/bin/bash
# Render asigna un puerto dinamico via la variable de entorno $PORT
# Este script configura Apache para escuchar en ese puerto antes de arrancar

# Cambia el puerto de escucha de Apache al que Render asigna (default 80 si no hay PORT)
LISTEN_PORT=${PORT:-80}

# Actualizar la configuracion de Apache con el puerto correcto
sed -i "s/Listen 80/Listen $LISTEN_PORT/" /etc/apache2/ports.conf
sed -i "s/:80>/:$LISTEN_PORT>/" /etc/apache2/sites-enabled/000-default.conf

# Arrancar Apache en primer plano
apache2-foreground

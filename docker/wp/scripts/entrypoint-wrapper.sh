#!/bin/bash
set -e

echo "=== Iniciando WordPress con entrypoint wrapper ==="

# Ejecutar el entrypoint original de WordPress
# Esto copia WordPress core y crea wp-config.php
/usr/local/bin/docker-entrypoint.sh php-fpm &

# Esperar un poco para que WordPress se copie
sleep 5

# Dar permisos a scripts
chmod -R 755 /var/www/scripts

# Ejecutar setup automático si está habilitado
if [ "${WORDPRESS_AUTO_SETUP:-true}" = "true" ] && [ "${WP_ALLOW_MULTISITE:-false}" = "false" ]; then
    echo "=== Ejecutando setup automático de WordPress ==="
    bash /var/www/scripts/wp-init.sh &
fi

# Mantener el contenedor corriendo
wait
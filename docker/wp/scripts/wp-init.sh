#!/bin/bash
# Script para inicializar WordPress automáticamente
# Espera a que WordPress esté listo y ejecuta wp-setup.sh

# Esperar a que la base de datos esté disponible
echo "Esperando a que la base de datos esté lista..."
until mysql -h"${WORDPRESS_DB_HOST}" -u"${WORDPRESS_DB_USER}" -p"${WORDPRESS_DB_PASSWORD}" "${WORDPRESS_DB_NAME}" --skip-ssl -e "SELECT 1" &>/dev/null; do
    sleep 5
    echo "Reintentando conexión a la base de datos..."
done

echo "Base de datos disponible."

# Verificar si la configuración ya se realizó
if wp option get auto_setup_completed --allow-root &>/dev/null; then
    echo "La configuración ya fue realizada previamente."
    echo "Para forzar la reconfiguración, ejecuta: wp option delete auto_setup_completed --allow-root"
else
    echo "Ejecutando configuración inicial..."
    bash /var/www/scripts/wp-setup.sh
fi

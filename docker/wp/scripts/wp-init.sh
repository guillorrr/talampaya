#!/bin/bash
# Script para inicializar WordPress automáticamente
# Espera a que WordPress esté listo y ejecuta wp-setup.sh

# Esperar a que WordPress esté disponible
echo "Esperando a que WordPress esté disponible..."
until $(wp core is-installed --allow-root); do
    sleep 5
    echo "Esperando a que la base de datos esté lista..."
done

echo "WordPress está listo."

# Verificar si la configuración ya se realizó
if wp option get auto_setup_completed --allow-root &>/dev/null; then
    echo "La configuración ya fue realizada previamente."
    echo "Para forzar la reconfiguración, ejecuta: wp option delete auto_setup_completed --allow-root"
else
    echo "Ejecutando configuración inicial..."
    /var/www/scripts/wp-setup.sh
fi

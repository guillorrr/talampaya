#!/bin/bash
# Script para inicializar WordPress automáticamente
# Espera a que WordPress esté listo y ejecuta wp-setup.sh

# Esperar a que WordPress esté disponible
echo "Esperando a que WordPress esté disponible..."
until $(wp core is-installed --allow-root); do
    sleep 5
    echo "Esperando a que la base de datos esté lista..."
done

echo "WordPress está listo. Ejecutando configuración..."
/var/www/scripts/wp-setup.sh

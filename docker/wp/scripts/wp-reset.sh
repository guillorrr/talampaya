#!/bin/bash
# Script para deshacer la configuración base
# Borra las páginas creadas, los usuarios y restablece la configuración por defecto

echo "Iniciando eliminación de la configuración base..."

# 1. Eliminar páginas creadas por el script de configuración
echo "Eliminando páginas..."
# Home
HOME_ID=$(wp post list --post_type=page --name=inicio --field=ID --allow-root)
if [ ! -z "$HOME_ID" ]; then
    wp post delete $HOME_ID --force --allow-root
    echo "Página Home eliminada"
fi

# Contact
CONTACT_ID=$(wp post list --post_type=page --name=contacto --field=ID --allow-root)
if [ ! -z "$CONTACT_ID" ]; then
    wp post delete $CONTACT_ID --force --allow-root
    echo "Página Contact eliminada"
fi

# About
ABOUT_ID=$(wp post list --post_type=page --name=nosotros --field=ID --allow-root)
if [ ! -z "$ABOUT_ID" ]; then
    wp post delete $ABOUT_ID --force --allow-root
    echo "Página About eliminada"
fi

# Blog
BLOG_ID=$(wp post list --post_type=page --name=blog --field=ID --allow-root)
if [ ! -z "$BLOG_ID" ]; then
    wp post delete $BLOG_ID --force --allow-root
    echo "Página Blog eliminada"
fi

# 2. Restablecer la configuración por defecto
echo "Restableciendo configuración por defecto..."
wp option update show_on_front posts --allow-root
wp option delete page_on_front --allow-root
wp option delete page_for_posts --allow-root
wp option update blog_public 1 --allow-root
wp rewrite structure '/%year%/%monthnum%/%day%/%postname%/' --allow-root
wp option delete category_base category --allow-root
wp option delete tag_base tag --allow-root
wp language core install en_US --allow-root
wp site switch-language en_US --allow-root
wp option update timezone_string 'UTC' --allow-root
wp rewrite flush --allow-root
echo "Configuración general restaurada a valores predeterminados"

# 3. Eliminar usuarios creados por el script de configuración
echo "Eliminando usuarios..."
# Administrator
if wp user get administrator --field=ID --allow-root &>/dev/null; then
    wp user delete administrator --reassign=1 --allow-root
    echo "Usuario administrator eliminado"
fi

# Editor
if wp user get editor --field=ID --allow-root &>/dev/null; then
    wp user delete editor --reassign=1 --allow-root
    echo "Usuario editor eliminado"
fi

# Author
if wp user get author --field=ID --allow-root &>/dev/null; then
    wp user delete author --reassign=1 --allow-root
    echo "Usuario author eliminado"
fi

# Collaborator
if wp user get collaborator --field=ID --allow-root &>/dev/null; then
    wp user delete collaborator --reassign=1 --allow-root
    echo "Usuario collaborator eliminado"
fi

# Subscriber
if wp user get subscriber --field=ID --allow-root &>/dev/null; then
    wp user delete subscriber --reassign=1 --allow-root
    echo "Usuario subscriber eliminado"
fi

# 4. Eliminar la opción que marca la configuración como completada
wp option delete auto_setup_completed --allow-root

echo "Reset completado con éxito."

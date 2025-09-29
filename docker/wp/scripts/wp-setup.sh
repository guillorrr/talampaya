#!/bin/bash
# Script para configurar WordPress automáticamente
# Crea páginas y usuarios básicos

# Verificar si ya se realizó la configuración
if wp option get auto_setup_completed --allow-root &>/dev/null; then
    echo "La configuración ya fue realizada previamente."
    exit 0
fi

echo "Iniciando configuración de WordPress..."

# 1. Crear páginas
echo "Creando páginas..."

# Home
HOME_ID=$(wp post create --post_type=page --post_title="Home" --post_name="home" --post_content="Bienvenido a la página principal" --post_status=publish --porcelain --allow-root)
echo "Página Home creada con ID $HOME_ID"

# Contact
CONTACT_ID=$(wp post create --post_type=page --post_title="Contact" --post_name="contact" --post_content="Página de contacto" --post_status=publish --porcelain --allow-root)
echo "Página Contact creada con ID $CONTACT_ID"

# About
ABOUT_ID=$(wp post create --post_type=page --post_title="About" --post_name="about" --post_content="Acerca de nosotros" --post_status=publish --porcelain --allow-root)
echo "Página About creada con ID $ABOUT_ID"

# Blog
BLOG_ID=$(wp post create --post_type=page --post_title="Blog" --post_name="blog" --post_content="Nuestro blog" --post_status=publish --porcelain --allow-root)
echo "Página Blog creada con ID $BLOG_ID"

# 2. Configurar página de inicio y página de entradas
echo "Configurando página de inicio y entradas..."
wp option update show_on_front page --allow-root
wp option update page_on_front $HOME_ID --allow-root
wp option update page_for_posts $BLOG_ID --allow-root

# 3. Obtener la contraseña desde las variables de entorno
ADMIN_PASS=${WORDPRESS_ADMIN_PASS:-password}

# 4. Crear usuarios con diferentes roles
echo "Creando usuarios..."
DOMAIN=$(wp option get siteurl --allow-root | sed -e 's/https\?:\/\///' | sed -e 's/\/.*//' | sed -e 's/:.*//')

# Administrator
if ! wp user get administrator --field=ID --allow-root &>/dev/null; then
    wp user create administrator administrator@$DOMAIN --role=administrator --user_pass=$ADMIN_PASS --allow-root
    echo "Usuario administrator creado"
fi

# Editor
if ! wp user get editor --field=ID --allow-root &>/dev/null; then
    wp user create editor editor@$DOMAIN --role=editor --user_pass=$ADMIN_PASS --allow-root
    echo "Usuario editor creado"
fi

# Author
if ! wp user get author --field=ID --allow-root &>/dev/null; then
    wp user create author author@$DOMAIN --role=author --user_pass=$ADMIN_PASS --allow-root
    echo "Usuario author creado"
fi

# Collaborator (contributor en WordPress)
if ! wp user get collaborator --field=ID --allow-root &>/dev/null; then
    wp user create collaborator collaborator@$DOMAIN --role=contributor --user_pass=$ADMIN_PASS --allow-root
    echo "Usuario collaborator creado"
fi

# Subscriber
if ! wp user get subscriber --field=ID --allow-root &>/dev/null; then
    wp user create subscriber subscriber@$DOMAIN --role=subscriber --user_pass=$ADMIN_PASS --allow-root
    echo "Usuario subscriber creado"
fi

# Marcar la configuración como completada
wp option add auto_setup_completed yes --allow-root
echo "Configuración completada con éxito."

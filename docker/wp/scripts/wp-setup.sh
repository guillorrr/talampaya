#!/bin/bash
# Script para configurar WordPress automáticamente
# Crea páginas y usuarios básicos

# Verificar si ya se realizó la configuración
if wp option get auto_setup_completed --allow-root &>/dev/null; then
    echo "La configuración ya fue realizada previamente."
    exit 0
fi

echo "Iniciando configuración de WordPress..."

# 1. Configuraciones generales
echo "Configurando opciones generales..."

# Desactivar la indexación de buscadores
wp option update blog_public 0 --allow-root
echo "Indexación de buscadores desactivada"

# Configurar estructura de permalink a "post name"
wp rewrite structure '/%postname%/' --allow-root
echo "Estructura de permalink establecida a post name"

# Configurar categoría base a "seccion"
wp rewrite category-base seccion --allow-root
echo "Base de categorías establecida a 'seccion'"

# Configurar tag base a "tema"
wp rewrite tag-base tema --allow-root
echo "Base de etiquetas establecida a 'tema'"

# Configurar idioma a español de España
wp language core install es_ES --allow-root
wp site switch-language es_ES --allow-root
echo "Idioma establecido a español de España (es_ES)"

# 2. Crear páginas
echo "Creando páginas..."

# Home
HOME_ID=$(wp post create --post_type=page --post_title="Inicio" --post_name="inicio" --post_content="Bienvenido a la página principal" --post_status=publish --porcelain --allow-root)
echo "Página Inicio creada con ID $HOME_ID"

# Contact
CONTACT_ID=$(wp post create --post_type=page --post_title="Contacto" --post_name="contacto" --post_content="Página de contacto" --post_status=publish --porcelain --allow-root)
echo "Página Contacto creada con ID $CONTACT_ID"

# About
ABOUT_ID=$(wp post create --post_type=page --post_title="Nosotros" --post_name="nosotros" --post_content="Acerca de nosotros" --post_status=publish --porcelain --allow-root)
echo "Página Nosotros creada con ID $ABOUT_ID"

# Blog
BLOG_ID=$(wp post create --post_type=page --post_title="Blog" --post_name="blog" --post_content="Nuestro blog" --post_status=publish --porcelain --allow-root)
echo "Página Blog creada con ID $BLOG_ID"

# 3. Configurar página de inicio y página de entradas
echo "Configurando página de inicio y entradas..."
wp option update show_on_front page --allow-root
wp option update page_on_front $HOME_ID --allow-root
wp option update page_for_posts $BLOG_ID --allow-root

# 4. Obtener la contraseña desde las variables de entorno
ADMIN_PASS=${WORDPRESS_ADMIN_PASS:-password}

# 5. Crear usuarios con diferentes roles
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

# 6. Actualizar la configuración de tiempo y zona horaria
wp option update timezone_string 'Europe/Madrid' --allow-root
echo "Zona horaria configurada a Europe/Madrid"

# 7. Aplicar cambios de rewrite rules
wp rewrite flush --allow-root
echo "Reglas de reescritura actualizadas"

# Marcar la configuración como completada
wp option add auto_setup_completed yes --allow-root
echo "Configuración completada con éxito."

#!/bin/bash
# Script para crear contenido de demostración en WordPress
# Genera posts y términos aleatorios usando wp post generate y wp term generate

echo "Generando contenido de demostración..."

# Verificar si ya hay contenido demo creado
if wp option get demo_content_created --allow-root &>/dev/null; then
    echo "El contenido de demostración ya fue creado."
    echo "Para regenerar, primero elimina el contenido existente con wp-demo-clean.sh"
    exit 0
fi

## 1. Crear categorías de demostración
echo "Creando categorías de demostración..."
wp term generate category --count=15 --format=ids --max_depth=2 --allow-root | xargs -d ' ' -I % wp term update category % --slug="demo-category-%" --allow-root
DEMO_CATEGORIES_BY_SLUG=$(wp term list category --field=term_id --format=ids --search="demo-category-" --allow-root)

## 2. Crear etiquetas de demostración
echo "Creando etiquetas de demostración..."
wp term generate post_tag --format=ids --count=10 --allow-root | xargs -d ' ' -I % wp term update post_tag % --slug="demo-tag-%" --allow-root
DEMO_TAGS_BY_SLUG=$(wp term list post_tag --field=slug --search="demo-tag-" --allow-root)

## 3. Generar posts de demostración con contenido aleatorio
echo "Creando posts de demostración..."

curl -sL 'https://baconipsum.com/api/?type=all-meat&paras=20&start-with-lorem=1' | \
sed 's/^\[\(.*\)\]$/\1/' | awk -F'"' '{for(i=2;i<=NF;i+=2) print $i}' | while read -r paragraph; do
    title=$(echo "$paragraph" | awk -F'[,.]' '{print $1}')
    content=$(echo "$paragraph" | sed "s/^$title[,.]\s*//")
    slug="demo-$(echo "$title" | tr '[:upper:]' '[:lower:]' | tr -cd 'a-z0-9-' | tr ' ' '-')"
    wp post create --post_title="$title" --post_content="$content" --post_name="$slug" --post_status=publish --post_author=1 --allow-root
done

# 4. Modificar los posts generados para incluir el prefijo demo en el slug y asignar términos
RECENT_POSTS=$(wp post list --post_type=post --post_status=publish --posts_per_page=30 --orderby=date --order=DESC --field=ID --format=ids --allow-root)
POST_COUNT=1

for post_id in $RECENT_POSTS; do
    # Obtener el título del post
    POST_TITLE=$(wp post get $post_id --field=post_title --allow-root)

    POST_COUNT=$((POST_COUNT + 1))

    # Asignar categoría aleatoria
    RANDOM_CATEGORIES=$(echo $DEMO_CATEGORIES_BY_SLUG | tr ' ' '\n' | sort -R | head -n 1)

    # Seleccionar 1-3 etiquetas aleatorias
    NUM_TAGS=$((RANDOM % 3 + 1))
    RANDOM_TAGS=$(echo $DEMO_TAGS_BY_SLUG | tr ' ' '\n' | sort -R | head -n $NUM_TAGS | tr '\n' ',')
    RANDOM_TAGS=${RANDOM_TAGS%,}  # Eliminar la última coma

    # Actualizar el post
    wp post update $post_id --post_category=$RANDOM_CATEGORIES --tags_input="$RANDOM_TAGS" --allow-root

    echo "Post actualizado: $POST_TITLE (ID: $post_id)"
done

# Marcar que el contenido demo ha sido creado
wp option add demo_content_created yes --allow-root

echo "Contenido de demostración creado con éxito."
echo "Se han generado 30 posts, 10 categorías y 10 etiquetas."
echo "Todos los elementos tienen prefijo 'demo-' en el slug para facilitar su identificación."
echo "Para eliminar este contenido, ejecuta el script wp-demo-clean.sh"

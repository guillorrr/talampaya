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

# 1. Crear categorías de demostración
echo "Creando categorías de demostración..."
wp term generate category --count=10 --format=ids --max_depth=2 --prefix="Demo Cat" --allow-root

# Asignar slug personalizado con prefijo "demo-" a las categorías recién creadas
DEMO_CATEGORIES=$(wp term list category --field=term_id --format=ids --search="Demo Cat" --allow-root)
for cat_id in $DEMO_CATEGORIES; do
    # Obtener el nombre de la categoría
    CAT_NAME=$(wp term get category $cat_id --field=name --allow-root)
    # Crear un slug con prefijo demo-
    SLUG="demo-$(echo $CAT_NAME | iconv -t ascii//TRANSLIT | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-zA-Z0-9]+/-/g' | sed -E 's/^-+|-+$//g')"
    # Actualizar el slug
    wp term update category $cat_id --slug="$SLUG" --allow-root
    echo "Categoría actualizada: $CAT_NAME con slug: $SLUG"
done

# 2. Crear etiquetas de demostración
echo "Creando etiquetas de demostración..."
wp term generate post_tag --count=10 --format=ids --prefix="Demo Tag" --allow-root

# Asignar slug personalizado con prefijo "demo-" a las etiquetas recién creadas
DEMO_TAGS=$(wp term list post_tag --field=term_id --format=ids --search="Demo Tag" --allow-root)
for tag_id in $DEMO_TAGS; do
    # Obtener el nombre de la etiqueta
    TAG_NAME=$(wp term get post_tag $tag_id --field=name --allow-root)
    # Crear un slug con prefijo demo-
    SLUG="demo-$(echo $TAG_NAME | iconv -t ascii//TRANSLIT | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-zA-Z0-9]+/-/g' | sed -E 's/^-+|-+$//g')"
    # Actualizar el slug
    wp term update post_tag $tag_id --slug="$SLUG" --allow-root
    echo "Etiqueta actualizada: $TAG_NAME con slug: $SLUG"
done

# 3. Generar posts de demostración con contenido aleatorio
echo "Creando posts de demostración..."
wp post generate --count=30 --post_type=post --post_status=publish --post_author=1 --post_date=2023-01-01 --allow-root

# 4. Modificar los posts generados para incluir el prefijo demo en el slug y asignar términos
RECENT_POSTS=$(wp post list --post_type=post --post_status=publish --posts_per_page=30 --orderby=date --order=DESC --field=ID --format=ids --allow-root)
POST_COUNT=1

for post_id in $RECENT_POSTS; do
    # Obtener el título del post
    POST_TITLE=$(wp post get $post_id --field=post_title --allow-root)

    # Crear un nuevo slug con prefijo demo-
    SLUG="demo-post-$POST_COUNT"
    POST_COUNT=$((POST_COUNT + 1))

    # Asignar categoría aleatoria
    RANDOM_CAT=$(echo $DEMO_CATEGORIES | tr ' ' '\n' | sort -R | head -n 1)

    # Seleccionar 1-3 etiquetas aleatorias
    NUM_TAGS=$((RANDOM % 3 + 1))
    RANDOM_TAGS=$(echo $DEMO_TAGS | tr ' ' '\n' | sort -R | head -n $NUM_TAGS | tr '\n' ',')
    RANDOM_TAGS=${RANDOM_TAGS%,}  # Eliminar la última coma

    # Actualizar el post
    wp post update $post_id --post_name="$SLUG" --post_category="$RANDOM_CAT" --tags_input="$RANDOM_TAGS" --allow-root

    echo "Post actualizado: $POST_TITLE (ID: $post_id) con slug: $SLUG"
done

# Marcar que el contenido demo ha sido creado
wp option add demo_content_created yes --allow-root

echo "Contenido de demostración creado con éxito."
echo "Se han generado 30 posts, 10 categorías y 10 etiquetas."
echo "Todos los elementos tienen prefijo 'demo-' en el slug para facilitar su identificación."
echo "Para eliminar este contenido, ejecuta el script wp-demo-clean.sh"

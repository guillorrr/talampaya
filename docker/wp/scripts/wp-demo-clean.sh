#!/bin/bash
# Script para eliminar el contenido de demostración en WordPress
# Elimina todos los posts y términos con prefijo demo-

echo "Eliminando contenido de demostración..."

## 1. Eliminar todos los posts de demostración (con slug que comienza con demo-)
echo "Eliminando posts de demostración..."
DEMO_POSTS=$(wp post list --post_status=any --post_type=post --field=ID --format=ids --posts_per_page=-1 --post_name='demo*' --allow-root)
if [ ! -z "$DEMO_POSTS" ]; then
    echo "Posts de demostración encontrados: $DEMO_POSTS"
    wp post delete $DEMO_POSTS --force --allow-root
    echo "Posts de demostración eliminados"
else
    echo "No se encontraron posts de demostración"
fi

# Verificar también por el slug
echo "Eliminando categorias con prefijo 'demo-' en el slug..."
DEMO_CATEGORIES_BY_SLUG=$(wp term list category --field=term_id --format=ids --search="demo-" --allow-root)
if [ ! -z "$DEMO_CATEGORIES_BY_SLUG" ]; then
    wp term delete category $DEMO_CATEGORIES_BY_SLUG --allow-root
    echo "Categorías de demostración con prefijo 'demo-' eliminadas"
fi

## Verificar también por el slug
echo "Eliminando etiquetas con prefijo 'demo-' en el slug..."
DEMO_TAGS_BY_SLUG=$(wp term list post_tag --field=term_id --format=ids --search="demo-" --allow-root)
if [ ! -z "$DEMO_TAGS_BY_SLUG" ]; then
    wp term delete post_tag $DEMO_TAGS_BY_SLUG --allow-root
    echo "Etiquetas de demostración con prefijo 'demo-' eliminadas"
fi

## 4. Eliminar la opción que marca que el contenido demo fue creado
wp option delete demo_content_created --allow-root

echo "Limpieza de contenido de demostración completada con éxito."

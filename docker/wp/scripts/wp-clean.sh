#!/bin/bash
# Script para limpiar WordPress completamente
# Elimina todos los posts, páginas y usuarios de ejemplo

echo "Limpiando WordPress..."

# 1. Eliminar todos los posts de ejemplo
echo "Eliminando posts de ejemplo..."
wp post delete $(wp post list --post_type=post --format=ids --allow-root) --force --allow-root

# 2. Eliminar todas las páginas (excepto las que queremos conservar)
echo "Eliminando páginas de ejemplo..."
wp post delete $(wp post list --post_type=page --format=ids --allow-root) --force --allow-root

# 3. Eliminar comentarios
echo "Eliminando comentarios..."
wp comment delete $(wp comment list --format=ids --allow-root) --force --allow-root

# 4. Eliminar usuarios excepto el administrador principal
echo "Eliminando usuarios de ejemplo..."
# Obtener el ID del usuario actual (admin)
ADMIN_ID=$(wp user get 1 --field=ID --allow-root)

# Eliminar otros usuarios (exceptuando el admin principal)
OTHER_USERS=$(wp user list --exclude=$ADMIN_ID --field=ID --allow-root)
if [ ! -z "$OTHER_USERS" ]; then
    wp user delete $OTHER_USERS --reassign=$ADMIN_ID --allow-root
fi

# 5. Eliminar taxonomías de ejemplo
echo "Eliminando categorías y etiquetas de ejemplo..."
# Categorías (excepto Uncategorized)
UNCATEGORIZED_ID=$(wp term list category --slug=uncategorized --field=term_id --allow-root)
wp term delete category $(wp term list category --exclude=$UNCATEGORIZED_ID --field=term_id --allow-root) --allow-root

# Etiquetas
wp term delete post_tag $(wp term list post_tag --field=term_id --allow-root) --allow-root

# 6. Eliminar la opción que marca la configuración como completada
wp option delete auto_setup_completed --allow-root

echo "Limpieza completada con éxito."

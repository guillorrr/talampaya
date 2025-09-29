# Talampaya - WordPress Development

## Configuración Automática

Este proyecto incluye un sistema de configuración automática que se ejecuta durante la primera instalación de WordPress. Esta configuración se realiza a través de scripts bash que utilizan WP-CLI dentro del contenedor WordPress principal.

### Scripts disponibles

Los scripts se encuentran en `docker/wp/scripts/`:

1. **wp-setup.sh**: Realiza la configuración inicial
   - Configura ajustes generales:
     - Deshabilita la indexación por buscadores
     - Establece la estructura de permalink a "post name" (/%postname%/)
     - Cambia la base de categorías a "seccion"
     - Cambia la base de etiquetas a "tema"
     - Configura el idioma a español de España (es_ES)
     - Establece la zona horaria a Europe/Madrid
   - Crea las páginas: Inicio, Contacto, Nosotros y Blog (en español)
   - Configura Inicio como página de inicio y Blog como página de entradas
   - Crea usuarios con diferentes roles (administrator, editor, author, etc.)
   - Activa automáticamente el tema con el mismo nombre que la variable APP_NAME (si existe)

2. **wp-reset.sh**: Deshace la configuración realizada por wp-setup.sh
   - Elimina las páginas creadas
   - Restablece la configuración de página de inicio y entradas a los valores predeterminados
   - Elimina los usuarios creados

3. **wp-clean.sh**: Limpieza completa de WordPress
   - Elimina todos los posts, páginas y comentarios
   - Elimina todos los usuarios excepto el administrador principal
   - Elimina categorías y etiquetas de ejemplo

4. **wp-init.sh**: Script de inicialización automática
   - Espera a que WordPress esté disponible
   - Ejecuta wp-setup.sh automáticamente

### Contraseña de usuarios

Todos los usuarios se crean con la contraseña definida en la variable `WORDPRESS_ADMIN_PASS` del archivo `.env`.

## Ejecución de scripts

### Ejecución automática

La configuración se ejecuta automáticamente cuando se inicia el proyecto:

```bash
# Inicia todos los servicios, incluida la configuración automática
npm run start

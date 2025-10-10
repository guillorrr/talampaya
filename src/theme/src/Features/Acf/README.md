# Integración con Advanced Custom Fields (ACF)

Este directorio contiene clases para la integración con el plugin Advanced Custom Fields (ACF), 
proporcionando una estructura orientada a objetos que facilita el uso y extensión de ACF en proyectos
basados en WordPress y Timber.

## Estructura

- **Acf**: Clase principal que gestiona el registro de bloques ACF y configuración básica.
- **Block/BlockRenderer**: Sistema extensible para renderizar bloques ACF con Timber.
- **Json/JsonExporter**: Utilidades para exportar campos ACF a archivos JSON.
- **Tables/TableJsonGenerator**: Soporte para el plugin ACF Custom Database Tables.

## Características principales

### Registro automático de bloques ACF

Los bloques ACF se registran automáticamente desde sus archivos JSON ubicados en `ACF_BLOCKS_PATH`.
Para que un bloque sea registrado correctamente, debe cumplir estas convenciones:

- Cada bloque debe estar en su propio directorio
- El directorio y el archivo JSON deben tener el mismo nombre base
- El archivo JSON debe llamarse `nombre-bloque-block.json`

### Renderizado flexible de bloques

El sistema utiliza la clase `BlockRenderer` para renderizar bloques ACF con Timber. Esta clase implementa un patrón de diseño que permite extender la funcionalidad de los bloques sin modificar el código base del renderizador.

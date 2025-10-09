# Integraciones de Plugins

Este directorio contiene las clases de integración para diversos plugins de WordPress. Cada clase
implementa la interfaz `PluginInterface` o extiende la clase `AbstractPlugin` para proporcionar
una integración estándar con el sistema de plugins de Talampaya.

## Estructura

Cada plugin integrado es una clase que:

1. Verifica si el plugin de WordPress correspondiente está instalado y activo
2. Añade funcionalidades específicas relacionadas con ese plugin (filtros, hooks, etc.)
3. Define las dependencias de plugins requeridos para TGM Plugin Activation

## Plugins disponibles

- **ACF**: Integración con Advanced Custom Fields
- **WPML**: Integración con el plugin de multilenguaje WPML
- **(Otros)**: Añade aquí tus propios plugins integrados

## ¿Cómo funciona?

Las clases de este directorio son cargadas automáticamente por el `PluginManager` y solo se inicializan
si el método `shouldLoad()` devuelve `true`, lo que típicamente significa que el plugin de WordPress 
correspondiente está activo.

## Creación de nuevas integraciones

Para crear una nueva integración para un plugin de WordPress:

1. Crea una nueva clase en este directorio que extienda `AbstractPlugin`
2. Implementa los métodos requeridos (`initialize`, `shouldLoad`, etc.)
3. El `PluginManager` detectará automáticamente tu nueva clase

Consulta el archivo README.md en la carpeta principal del sistema de plugins para más detalles.

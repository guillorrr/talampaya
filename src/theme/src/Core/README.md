# Sistema de Extensiones de Talampaya

Este documento explica el sistema de extensiones modulares de Talampaya que permite agregar funcionalidades al tema sin modificar las clases principales.

## Estructura

El sistema de extensiones está dividido en dos partes principales:

### 1. Extensiones de Contexto

Permiten agregar variables al contexto global de Timber que estará disponible en todas las plantillas Twig.

Estructura:
- `src/Core/ContextExtender/` - Directorio principal
- `ContextExtenderInterface.php` - Interfaz que todas las extensiones deben implementar
- `ContextManager.php` - Gestor central que registra y aplica todas las extensiones
- `Custom/` - Directorio para extensiones personalizadas

### 2. Extensiones de Twig

Permiten agregar filtros, funciones y extensiones al entorno Twig.

Estructura:
- `src/Core/TwigExtender/` - Directorio principal
- `TwigExtenderInterface.php` - Interfaz que todas las extensiones deben implementar
- `TwigManager.php` - Gestor central que registra y aplica todas las extensiones
- `Custom/` - Directorio para extensiones personalizadas

## Cómo crear extensiones personalizadas

### 1. Crear una extensión de contexto

Crea un archivo en `src/Core/ContextExtender/Custom/` con una clase que implemente `ContextExtenderInterface`:

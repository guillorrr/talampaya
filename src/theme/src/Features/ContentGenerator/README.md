# Sistema de Generación de Contenido

Este sistema proporciona una arquitectura modular y escalable para generar diferentes tipos de contenido en WordPress de forma automática o programada, permitiendo crear contenido inicial o actualizar contenido existente.

## Tabla de Contenidos

1. [Características Principales](#características-principales)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Componentes Principales](#componentes-principales)
4. [Flujo de Trabajo](#flujo-de-trabajo)
5. [Tipos de Procesadores](#tipos-de-procesadores)
6. [Ejemplos de Uso](#ejemplos-de-uso)
7. [Extensión del Sistema](#extensión-del-sistema)
8. [Diagrama de Flujo](#diagrama-de-flujo)

## Características Principales

- **Modular**: Diseñado con patrón de fábrica para crear diferentes tipos de generadores de contenido
- **Escalable**: Permite añadir nuevos tipos de procesadores sin modificar el código base
- **Flexible**: Soporta múltiples tipos de contenido (páginas, CPT, taxonomías) y formatos (HTML, Gutenberg blocks, ACF)
- **Programable**: Control sobre cuándo y cómo se genera o actualiza el contenido
- **Priorizado**: Establece el orden de ejecución para diferentes tipos de generadores
- **Rastreable**: Registra qué contenido ya ha sido generado para evitar duplicaciones

## Arquitectura del Sistema

El sistema utiliza varios patrones de diseño:

- **Patrón Fábrica**: La clase `ContentGeneratorFactory` crea diferentes tipos de generadores
- **Patrón Estrategia**: Los procesadores de contenido (`ContentProcessorInterface`) encapsulan diferentes algoritmos para generar contenido
- **Patrón Adaptador**: Permite que diferentes fuentes de contenido sean procesadas de manera uniforme
- **Patrón Compuesto**: El `ContentGeneratorManager` coordina múltiples generadores con prioridades específicas

## Componentes Principales

### ContentGeneratorInterface
Define el contrato básico que cualquier generador debe implementar. Métodos principales:
- `generate()`: Inicia el proceso de generación
- `getOptionKey()`: Obtiene la clave de opción única para seguimiento

### AbstractContentGenerator
Proporciona la implementación base que las clases concretas pueden extender:
- Gestión del estado (si el contenido ya ha sido generado)
- Registro de resultados en la base de datos
- Flujo común de verificación antes de generar

### ContentTypeGenerator
El generador genérico y modular para cualquier tipo de post:
- Soporta múltiples tipos de post (páginas, CPT, etc.)
- Acepta diferentes procesadores de contenido
- Maneja creación o actualización de contenido
- Soporta metadatos y taxonomías

### ContentGeneratorFactory
Fábrica para crear diferentes tipos de generadores:
- `createContentGenerator()`: Crea un generador genérico
- `createHtmlContentGenerator()`: Especializado en contenido HTML
- `createBlocksContentGenerator()`: Especializado en bloques Gutenberg

### ContentProcessorInterface
Define la interfaz para todos los procesadores de contenido:
- `process()`: Transforma el contenido crudo en formato adecuado para WordPress

### ContentGeneratorManager
Coordina la ejecución de múltiples generadores:
- Registro de generadores con prioridades
- Ejecución ordenada por prioridad
- Hooks para integrarse con eventos de WordPress

## Flujo de Trabajo

1. **Configuración**: Se definen los datos de contenido y se selecciona el tipo de generador
2. **Inicialización**: Se crea el generador usando la fábrica
3. **Registro**: Se registra el generador en el manager con su prioridad
4. **Activación**: El contenido se genera cuando:
   - El tema se activa (`after_switch_theme`)
   - Se ejecuta manualmente (`forceRegenerateAll()`)
5. **Procesamiento**: Cada generador:
   - Verifica si el contenido ya existe
   - Procesa los datos según la estrategia definida
   - Crea o actualiza el contenido
   - Registra el resultado para evitar duplicaciones

## Tipos de Procesadores

### HtmlContentProcessor
Procesa contenido desde archivos HTML:
- Lee archivos HTML desde rutas específicas
- Convierte el contenido en formato adecuado para posts

### BlocksContentProcessor
Procesa definiciones de bloques Gutenberg:
- Transforma arrays de definiciones en sintaxis de bloques
- Soporta bloques core y personalizados
- Integración con ACF si está disponible

## Ejemplos de Uso

### Ejemplo 1: Crear páginas legales desde archivos HTML

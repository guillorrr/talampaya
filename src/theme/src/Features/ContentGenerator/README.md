# Sistema de Generación de Contenido por Defecto

Este sistema proporciona una arquitectura modular y escalable para generar contenido por defecto cuando se activa un tema de WordPress o cuando se ejecuta una función específica.

## Características

- **Modular**: Cada tipo de contenido tiene su propio generador especializado
- **Escalable**: Fácil de extender para nuevos tipos de contenido
- **Configurable**: Controla cuándo se genera el contenido (activación del tema o manualmente)
- **Priorizado**: Establece el orden en que se generan diferentes tipos de contenido

## Estructura del Sistema

El sistema está compuesto por las siguientes clases:

- `AbstractContentGenerator`: Clase base abstracta que define la estructura común
- `PageContentGenerator`: Generador para páginas con bloques de Gutenberg/ACF
- `CustomPostTypeGenerator`: Generador para tipos de post personalizados
- `LegalContentGenerator`: Generador para contenido legal desde archivos HTML
- `ContentGeneratorFactory`: Fábrica para crear instancias de generadores
- `ContentGeneratorManager`: Administrador que coordina todos los generadores

## Uso Básico

### 1. Registrar generadores

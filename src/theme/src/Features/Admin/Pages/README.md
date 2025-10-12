# Sistema de Páginas Personalizadas
# Páginas de Administración

Este directorio contiene las clases para crear y gestionar páginas personalizadas en el panel de administración de WordPress.

## Estructura

Las páginas de administración se implementan como clases que extienden de `AbstractPageSetting` o siguen su mismo patrón. Esta estructura garantiza que las traducciones se carguen correctamente antes de que las páginas intenten utilizarlas.

## Cómo crear una nueva página de administración

1. Crea una nueva clase PHP en este directorio que siga el patrón de las existentes.

2. La clase debe implementar como mínimo:
   - Un constructor que llame a los hooks necesarios
   - Un método `registerPage` que añada la página al gestor

### Ejemplo básico
Esta característica proporciona una forma modular y escalable de agregar páginas personalizadas al panel de administración de WordPress.

## Características principales

- Sistema modular para crear diferentes tipos de páginas de administración
- Soporte integrado para páginas con campos ACF
- Soporte para páginas con contenido HTML personalizado
- Soporte para páginas renderizadas con plantillas Twig
- Diseñado para ser extensible por temas y plugins que utilicen este proyecto como upstream

## Tipos de páginas disponibles

1. **AcfPage**: Páginas con campos de Advanced Custom Fields
2. **HtmlPage**: Páginas con contenido HTML personalizado
3. **TwigPage**: Páginas que utilizan plantillas Twig para renderizar su contenido

## Cómo agregar una nueva página personalizada

### Ejemplo: Agregar una página ACF

# Sistema de Endpoints API

Este directorio contiene las clases que conforman el sistema de endpoints de la API REST de WordPress.

## Estructura

- **EndpointsManager**: Gestor centralizado que registra y mantiene todos los endpoints.
- **EndpointInterface**: Interfaz que deben implementar todos los endpoints.
- **AbstractEndpoint**: Clase base que proporciona funcionalidad común para endpoints.
- **GeolocationEndpoint**: Endpoint para obtener datos de geolocalización.
- **Custom/**: Directorio para endpoints personalizados.

## Uso del sistema

### Registrar nuevos endpoints

Para crear un nuevo endpoint:

1. Crea una nueva clase en el directorio `Custom/` que extienda `AbstractEndpoint`.
2. Implementa el método `register()` para definir las rutas y callbacks.
3. Define la constante `ROUTE` para establecer la ruta base.

Ejemplo:

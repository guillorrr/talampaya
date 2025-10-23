# ContentGeneratorManager - Documentación Técnica

## Índice

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Componentes Principales](#componentes-principales)
4. [Flujo de Ejecución](#flujo-de-ejecución)
5. [Análisis de Código](#análisis-de-código)
6. [Ejemplos de Uso](#ejemplos-de-uso)
7. [Mejoras Sugeridas](#mejoras-sugeridas)

---

## Resumen Ejecutivo

El **ContentGeneratorManager** es el orquestador principal del sistema de generación de contenido demo del tema Talampaya. Su propósito es coordinar la ejecución de múltiples generadores de contenido en un orden específico para garantizar que las dependencias entre contenidos se manejen correctamente.

### Caso de Uso Principal

Imagina que necesitas pre-cargar contenido demo cuando el tema se activa. El contenido incluye:

1. **Taxonomías** (categorías, etiquetas personalizadas) - Sin dependencias
2. **Páginas base** (Home, About, Contact) - Pueden requerir taxonomías
3. **Custom Post Types** (Projects, Banners) - Requieren taxonomías y páginas
4. **Banners** que referencian proyectos específicos - Requieren que los proyectos ya existan

El ContentGeneratorManager resuelve este problema mediante un sistema de **prioridades** que ejecuta los generadores en el orden correcto.

---

## Arquitectura del Sistema

### Diagrama de Clases

```
┌─────────────────────────────────────┐
│   ContentGeneratorInterface         │
│  ┌──────────────────────────────┐   │
│  │ + generate(force: bool)      │   │
│  │ + getOptionKey(): string     │   │
│  └──────────────────────────────┘   │
└─────────────────────────────────────┘
                  △
                  │ implements
                  │
┌─────────────────────────────────────┐
│   AbstractContentGenerator          │
│  ┌──────────────────────────────┐   │
│  │ # option_key: string         │   │
│  │ # isAlreadyGenerated()       │   │
│  │ # markAsGenerated()          │   │
│  │ + generate(force: bool)      │   │
│  │ # generateContent(): bool    │   │ ← Abstract method
│  └──────────────────────────────┘   │
└─────────────────────────────────────┘
                  △
                  │ extends
                  │
        ┌─────────┴─────────┬─────────────────┐
        │                   │                 │
┌───────────────┐  ┌─────────────────┐  ┌────────────────┐
│ ProjectPost   │  │ LegalPages      │  │ Default        │
│ Generator     │  │ Generator       │  │ Generator      │
└───────────────┘  └─────────────────┘  └────────────────┘
        │                   │
        │ uses              │ uses
        ▼                   ▼
┌─────────────────────────────────────┐
│   ContentTypeGenerator              │
│  ┌──────────────────────────────┐   │
│  │ - post_type: string          │   │
│  │ - content_data: array        │   │
│  │ - content_processors: array  │   │
│  │ # generateContent(): bool    │   │
│  └──────────────────────────────┘   │
└─────────────────────────────────────┘
        △
        │ created by
        │
┌─────────────────────────────────────┐
│   ContentGeneratorFactory           │
│  ┌──────────────────────────────┐   │
│  │ + createContentGenerator()   │   │
│  │ + createHtmlContentGen()     │   │
│  │ + createBlocksContentGen()   │   │
│  └──────────────────────────────┘   │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│   ContentGeneratorManager           │
│  ┌──────────────────────────────┐   │
│  │ - generators: array          │   │ ← Indexed by priority
│  │ - generatorClasses: array    │   │ ← NOT USED
│  │ + register(gen, priority)    │   │
│  │ + generateAllContent(force)  │   │
│  │ + runGenerators()            │   │
│  │ + initGenerators()           │   │
│  └──────────────────────────────┘   │
└─────────────────────────────────────┘
```

---

## Componentes Principales

### 1. ContentGeneratorManager

**Ubicación:** `/src/theme/src/Features/ContentGenerator/ContentGeneratorManager.php`

**Responsabilidades:**

-   Registrar generadores de contenido
-   Ejecutarlos en orden de prioridad
-   Coordinar la ejecución automática en activación de tema
-   Permitir regeneración manual del contenido

**Propiedades principales:**

```php
protected array $generators = [];          // Generadores organizados por prioridad
protected bool $run_on_theme_activation;   // Si ejecutar en activación del tema
protected bool $auto_register_generators;  // Si auto-descubrir generadores
private bool $initialized = false;         // Evita doble inicialización
```

**Métodos públicos clave:**

| Método                          | Descripción                                  | Cuándo usar                                     |
| ------------------------------- | -------------------------------------------- | ----------------------------------------------- |
| `register(generator, priority)` | Registra un generador manualmente            | Cuando necesitas control fino sobre prioridades |
| `generateAllContent(force)`     | Ejecuta todos los generadores registrados    | Para regeneración programática                  |
| `runGenerators()`               | Wrapper para activación de tema              | Llamado automáticamente por WordPress           |
| `regenerateContent(force)`      | Re-ejecuta generadores después de activación | Para regenerar contenido manualmente            |
| `forceRegenerateAll()`          | Fuerza regeneración ignorando estado         | Útil para desarrollo/debugging                  |

---

### 2. AbstractContentGenerator

**Ubicación:** `/src/theme/src/Features/ContentGenerator/AbstractContentGenerator.php`

**Responsabilidades:**

-   Proporcionar estructura base para todos los generadores
-   Manejar el estado de generación (evitar duplicaciones)
-   Registrar en WordPress options si el contenido ya fue creado

**Flujo de generación:**

```php
public function generate(bool $force = false): void
{
    if ($this->isAlreadyGenerated($force)) {
        return; // Ya existe y no forzamos regeneración
    }

    $success = $this->generateContent(); // ← Método abstracto implementado por hijos
    $this->markAsGenerated($success);    // Guardar estado en DB
}
```

**Clave de seguimiento:**
Cada generador tiene un `option_key` único (ej: `"projects_content_generated"`) que se guarda en `wp_options` para rastrear si ya ejecutó.

---

### 3. ContentTypeGenerator

**Ubicación:** `/src/theme/src/Features/ContentGenerator/ContentTypeGenerator.php`

**Responsabilidades:**

-   Generador genérico que puede crear cualquier tipo de post
-   Soporta diferentes procesadores de contenido (HTML, Blocks, Raw)
-   Maneja metadatos y taxonomías

**Estructura de datos:**

```php
$content_data = [
	'slug-del-post' => [
		'title' => 'Título del Post',
		'content' => '...contenido...',
		'content_type' => 'html|blocks|raw', // Tipo de procesador
		'status' => 'publish',
		'update' => true, // Si actualizar si ya existe
		'meta' => [
			'meta_key' => 'meta_value',
		],
		'taxonomies' => [
			'category' => ['term1', 'term2'],
		],
	],
];
```

**Procesadores de contenido:**

| Tipo     | Descripción                               | Uso                              |
| -------- | ----------------------------------------- | -------------------------------- |
| `raw`    | Sin procesamiento, contenido directo      | Contenido ya formateado          |
| `html`   | Procesa archivos HTML externos            | Páginas legales desde archivos   |
| `blocks` | Procesa definiciones de bloques Gutenberg | Posts con bloques personalizados |

---

### 4. ContentGeneratorFactory

**Ubicación:** `/src/theme/src/Features/ContentGenerator/ContentGeneratorFactory.php`

**Responsabilidades:**

-   Factory pattern para crear diferentes tipos de generadores
-   Simplificar la creación de generadores especializados

**Métodos estáticos:**

```php
// Generador genérico
ContentGeneratorFactory::createContentGenerator(
	$option_key,
	$post_type,
	$content_data,
	$content_processors
);

// Generador especializado en HTML
ContentGeneratorFactory::createHtmlContentGenerator(
	$option_key,
	$post_type,
	$content_data,
	$base_path
);

// Generador especializado en bloques
ContentGeneratorFactory::createBlocksContentGenerator($option_key, $post_type, $content_data);
```

---

## Flujo de Ejecución

### Diagrama de Secuencia - Activación del Tema

```
Usuario                WordPress           TalampayaStarter        ContentGeneratorManager    Generadores
  │                        │                       │                           │                    │
  │  Activa tema          │                       │                           │                    │
  ├──────────────────────>│                       │                           │                    │
  │                        │                       │                           │                    │
  │                        │  after_setup_theme    │                           │                    │
  │                        ├──────────────────────>│                           │                    │
  │                        │                       │  new ContentGeneratorManager(true, false)      │
  │                        │                       ├──────────────────────────>│                    │
  │                        │                       │                           │                    │
  │                        │                       │  Hook: after_setup_theme (priority 800)        │
  │                        │                       │                           │  initGenerators()  │
  │                        │                       │                           ├───────────────────>│
  │                        │                       │                           │                    │
  │                        │  after_switch_theme   │                           │                    │
  │                        ├──────────────────────────────────────────────────>│                    │
  │                        │                       │                           │  runGenerators()   │
  │                        │                       │                           ├───────────────────>│
  │                        │                       │                           │                    │
  │                        │                       │                           │ initGenerators()   │
  │                        │                       │                           │ (double check)     │
  │                        │                       │                           │                    │
  │                        │                       │                           │ generateAllContent()│
  │                        │                       │                           ├───────────────────>│
  │                        │                       │                           │                    │
  │                        │                       │                           │  Ordena por prioridad
  │                        │                       │                           │  ksort($generators)
  │                        │                       │                           │                    │
  │                        │                       │                           │  PRIORITY 5        │
  │                        │                       │                           │  (Taxonomies)      │
  │                        │                       │                           │  generate()        │
  │                        │                       │                           ├───────────────────>│
  │                        │                       │                           │<───────────────────┤
  │                        │                       │                           │                    │
  │                        │                       │                           │  PRIORITY 10       │
  │                        │                       │                           │  (PostTypes)       │
  │                        │                       │                           │  generate()        │
  │                        │                       │                           ├───────────────────>│
  │                        │                       │                           │<───────────────────┤
  │                        │                       │                           │                    │
  │                        │                       │                           │  PRIORITY 15       │
  │                        │                       │                           │  (Others)          │
  │                        │                       │                           │  generate()        │
  │                        │                       │                           ├───────────────────>│
  │                        │                       │                           │<───────────────────┤
  │                        │<──────────────────────────────────────────────────┤                    │
  │<───────────────────────┤                       │                           │                    │
```

---

### Flujo Detallado - Generación de Contenido

```
┌─────────────────────────────────────────────────────────────────┐
│                    INICIO: generateAllContent()                 │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │ ¿Hay generadores│
                    │  registrados?   │
                    └────────┬────────┘
                             │
                   NO ◄──────┼──────► SI
                   │         │
                   ▼         ▼
           ┌─────────┐   ┌──────────────────┐
           │  ERROR  │   │ Ordenar por      │
           │  LOG    │   │ prioridad        │
           └─────────┘   │ ksort($generators)│
                         └────────┬──────────┘
                                  │
                                  ▼
                    ┌──────────────────────────┐
                    │ Iterar por grupos de     │
                    │ prioridad (5, 10, 15...) │
                    └────────┬─────────────────┘
                             │
                ┌────────────┴────────────┐
                │                         │
                ▼                         ▼
    ┌───────────────────────┐  ┌──────────────────────┐
    │ PRIORITY 5            │  │ PRIORITY 10          │
    │ (Taxonomies)          │  │ (Post Types)         │
    │                       │  │                      │
    │ foreach generator:    │  │ foreach generator:   │
    │   generator.generate()│  │   generator.generate()│
    └───────────┬───────────┘  └──────────┬───────────┘
                │                         │
                └────────────┬────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │ Generator.generate(force)    │
              └──────────────┬───────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │ ¿Ya fue generado?            │
              │ isAlreadyGenerated(force)    │
              └──────────────┬───────────────┘
                             │
                   SI ◄──────┼──────► NO
                   │         │
                   ▼         ▼
           ┌─────────┐   ┌──────────────────┐
           │ RETURN  │   │ generateContent()│ ← Implementación específica
           └─────────┘   └────────┬─────────┘
                                  │
                                  ▼
                    ┌──────────────────────────┐
                    │ Procesar content_data:   │
                    │                          │
                    │ foreach item:            │
                    │   - Verificar si existe  │
                    │   - Crear/actualizar post│
                    │   - Procesar contenido   │
                    │   - Guardar metadata     │
                    │   - Asignar taxonomías   │
                    └────────┬─────────────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │ markAsGenerated()│
                    │ (Guardar en DB)  │
                    └──────────────────┘
```

---

### Flujo de Inicialización de Generadores

```
┌─────────────────────────────────────┐
│       initGenerators()              │
└────────────┬────────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│ ¿auto_register_generators = true? │
└────────────┬───────────────────────┘
             │
    NO ◄─────┼─────► SI
    │        │
    ▼        ▼
┌─────────────────────┐  ┌─────────────────────────┐
│ initContent         │  │ registerCustom          │
│ GeneratorsWithPrior │  │ Generators()            │
│                     │  │                         │
│ Manual priority:    │  │ Auto-discovery:         │
│ - Taxonomy → 5      │  │ - Escanear directorio   │
│ - PostType → 10     │  │ - Registrar todos con   │
│ - Others   → 15     │  │   prioridad 10          │
└─────────────────────┘  └─────────────────────────┘
             │                       │
             └───────────┬───────────┘
                         │
                         ▼
        ┌────────────────────────────────┐
        │ do_action(                     │
        │   'talampaya_register_content_│
        │    generators', $this          │
        │ )                              │
        │                                │
        │ Permite extensión externa      │
        └────────────────────────────────┘
```

---

## Análisis de Código

### Arquitectura y Diseño

#### 1. Separación de Responsabilidades

El código utiliza métodos privados especializados para mantener el principio de responsabilidad única (SRP):

```php
private function getGeneratorsPath(): string           // Obtiene ruta configurada
private function getGeneratorFiles(): array            // Obtiene archivos PHP
private function shouldSkipFile(): bool                // Valida archivos a ignorar
private function buildFullyQualifiedClassName(): string // Construye namespace completo
private function isValidGenerator(): bool              // Valida herencia correcta
private function scanGeneratorClasses(): array         // Orquesta todo el escaneo
```

Los métodos públicos delegan en `scanGeneratorClasses()`, evitando duplicación:

```php
public function getAvailableGenerators(): array
{
    return $this->scanGeneratorClasses();
}

protected function registerCustomGenerators(): void
{
    $availableGenerators = $this->scanGeneratorClasses();
    foreach ($availableGenerators as $fullyQualifiedClassName => $shortName) {
        // Instanciar y registrar
    }
}
```

**Ventajas:** Mayor mantenibilidad, código testeable, cumple principio DRY

---

#### 2. Asignación de Prioridades Eficiente

La asignación de prioridades se realiza en un solo recorrido mediante un método dedicado:

```php
public function initContentGeneratorsWithPriority(): void
{
    $availableGenerators = $this->getAvailableGenerators();

    foreach ($availableGenerators as $className => $shortName) {
        $priority = $this->determinePriority($shortName);
        $this->registerGeneratorByClassName($className, $priority);
    }
}

private function determinePriority(string $shortName): int
{
    if (strpos($shortName, 'Taxonomy') !== false) return 5;  // Taxonomías primero
    if (strpos($shortName, 'PostType') !== false) return 10; // Post types
    return 15; // Otros
}
```

**Ventajas:** Lógica encapsulada, eficiente O(n), fácil de extender

---

#### 3. Guard Clause para Idempotencia

Se utiliza un flag para garantizar que la inicialización sea idempotente:

```php
private bool $initialized = false;

public function initGenerators(): void
{
    if ($this->initialized) {
        return; // Ya inicializado, no hacer nada
    }

    // ... lógica de inicialización ...

    $this->initialized = true;
}
```

**Ventajas:** Previene re-registros, comportamiento predecible, seguro para múltiples llamadas

---

### Patrones de Diseño

1. **Sistema de Prioridades:**

    - Array asociativo con prioridades como keys
    - Ordenamiento con `ksort()` antes de ejecutar
    - Permite ejecución ordenada respetando dependencias

2. **Patrón Factory:**

    - `ContentGeneratorFactory` centraliza la creación de instancias
    - Simplifica código cliente
    - Facilita testing y mantenimiento

3. **Verificación de Estado:**

    - `isAlreadyGenerated()` consulta `wp_options`
    - Evita duplicaciones mediante `option_key` único
    - Permite forzar regeneración con parámetro `$force`

4. **Extensibilidad vía Hooks:**
    - `do_action('talampaya_register_content_generators', $this)`
    - Permite que plugins/temas registren generadores personalizados
    - Mantiene el código cerrado a modificación, abierto a extensión (OCP)

---

## Ejemplos de Uso

### Ejemplo 1: Generador Simple con Prioridad Manual

```php
use App\Features\ContentGenerator\AbstractContentGenerator;

class BannerTaxonomyGenerator extends AbstractContentGenerator
{
	public function __construct()
	{
		parent::__construct('banner_taxonomy_generated');
	}

	protected function generateContent(): bool
	{
		// 1. Crear taxonomía vacía (solo estructura)
		$term_data = [
			'hero-banner' => 'Hero Banner',
			'sidebar-banner' => 'Sidebar Banner',
		];

		foreach ($term_data as $slug => $name) {
			wp_insert_term($name, 'banner_category', ['slug' => $slug]);
		}

		return true;
	}
}
```

**Registro en TalampayaStarter:**

```php
$this->contentGeneratorManager = new ContentGeneratorManager(true, false);

// Registrar con prioridad 5 (antes de posts)
add_action(
	'talampaya_register_content_generators',
	function ($manager) {
		$manager->register(new BannerTaxonomyGenerator(), 5);
	},
	10
);
```

---

### Ejemplo 2: Generador con Dependencias (Banners → Projects)

```php
class BannerPostGenerator extends AbstractContentGenerator
{
	public function __construct()
	{
		parent::__construct('banner_posts_generated');
	}

	protected function generateContent(): bool
	{
		// PASO 1: Crear banners vacíos (solo título y slug)
		$banner_ids = [];

		$initial_banners = [
			'home-hero' => 'Banner Principal Home',
			'about-hero' => 'Banner Página About',
		];

		foreach ($initial_banners as $slug => $title) {
			$banner_id = wp_insert_post([
				'post_title' => $title,
				'post_name' => $slug,
				'post_type' => 'banner',
				'post_status' => 'publish',
			]);

			$banner_ids[$slug] = $banner_id;
		}

		// PASO 2: Esperar a que otros generadores creen proyectos
		// (esto pasaría en otro generador con prioridad mayor)

		// PASO 3: Actualizar banners con referencias
		// (esto se haría en un generador con prioridad 15)
		return true;
	}
}

class BannerContentUpdater extends AbstractContentGenerator
{
	public function __construct()
	{
		parent::__construct('banner_content_updated');
	}

	protected function generateContent(): bool
	{
		// Obtener IDs de proyectos ya creados
		$project = get_page_by_path('proyecto-principal', OBJECT, 'project_post');

		if (!$project) {
			error_log('BannerContentUpdater: Proyecto no encontrado');
			return false;
		}

		// Actualizar banner con referencia al proyecto
		$banner = get_page_by_path('home-hero', OBJECT, 'banner');

		if ($banner) {
			update_post_meta($banner->ID, 'referenced_project', $project->ID);
			update_post_meta($banner->ID, 'banner_link', get_permalink($project->ID));

			// Actualizar contenido del banner
			wp_update_post([
				'ID' => $banner->ID,
				'post_content' => 'Descubre nuestro proyecto destacado',
			]);
		}

		return true;
	}
}
```

**Registro con orden correcto:**

```php
add_action(
	'talampaya_register_content_generators',
	function ($manager) {
		$manager->register(new BannerTaxonomyGenerator(), 5); // Taxonomías primero
		$manager->register(new ProjectPostGenerator(), 10); // Proyectos
		$manager->register(new BannerPostGenerator(), 10); // Banners vacíos
		$manager->register(new BannerContentUpdater(), 15); // Rellenar banners
	},
	10
);
```

---

### Ejemplo 3: Regeneración Manual desde Admin

```php
// En un admin page o settings
add_action('admin_post_regenerate_demo_content', function () {
	global $talampaya_starter;

	// Acceder al manager
	$manager = $talampaya_starter->getContentGeneratorManager();

	// Forzar regeneración de todo
	$manager->forceRegenerateAll();

	wp_redirect(admin_url('admin.php?page=demo-content&regenerated=true'));
	exit();
});
```

---

### Ejemplo 4: Usar ContentGeneratorFactory para HTML

```php
use App\Features\ContentGenerator\ContentGeneratorFactory;

class LegalPagesGenerator extends AbstractContentGenerator
{
	protected ?ContentTypeGenerator $internalGenerator = null;

	public function __construct()
	{
		parent::__construct('legal_pages_generated');
	}

	protected function generateContent(): bool
	{
		$legal_pages = [
			'aviso-legal' => [
				'title' => 'Aviso Legal',
				'update' => true,
				'meta' => ['_show_in_footer' => true],
			],
			'privacidad' => [
				'title' => 'Política de Privacidad',
				'update' => true,
			],
		];

		// Crear generador HTML usando factory
		$this->internalGenerator = ContentGeneratorFactory::createHtmlContentGenerator(
			$this->getOptionKey(),
			'page',
			$legal_pages,
			'/src/Features/DefaultContent/html-content/'
		);

		return $this->internalGenerator->generateContent();
	}
}
```

---

## Posibles Mejoras Futuras

### Prioridad ALTA

1. **Mejorar sistema de prioridades**
    - Agregar método `getPriority()` en AbstractContentGenerator
    - Eliminar dependencia de nombres de clase
    - Permitir que cada generador declare su prioridad

### Prioridad MEDIA

2. **Agregar validación de dependencias**
    - Permitir que generadores declaren dependencias
    - Verificar orden antes de ejecutar

```php
abstract class AbstractContentGenerator
{
	public function getDependencies(): array
	{
		return []; // Override to declare dependencies
	}
}

class BannerContentUpdater extends AbstractContentGenerator
{
	public function getDependencies(): array
	{
		return [ProjectPostGenerator::class];
	}
}
```

3. **Agregar modo "dry-run"**
    - Permitir simular generación sin crear contenido
    - Útil para debugging

```php
$manager->generateAllContent($force = false, $dryRun = true);
```

### Prioridad BAJA

4. **Mejorar logging**

    - Usar WP_CLI::log() cuando esté disponible
    - Agregar niveles de log (debug, info, error)

5. **Agregar callbacks de progreso**
    - Permitir hooks después de cada generador
    - Útil para progress bars en WP-CLI o admin

```php
do_action('talampaya_content_generator_progress', $current, $total, $generator_name);
```

6. **Unit tests**
    - Agregar tests para ContentGeneratorManager
    - Mockear generadores para testing

---

## Configuración Actual en TalampayaStarter

**Ubicación:** `/src/theme/src/TalampayaStarter.php:59`

```php
// Inicializar ContentGeneratorManager con auto-registro desactivado
$this->contentGeneratorManager = new ContentGeneratorManager(
	true, // $run_on_theme_activation = true
	false // $auto_register_generators = false
);
```

**Qué significa:**

-   ✅ Los generadores se ejecutarán al activar el tema
-   ❌ NO se auto-descubrirán generadores del directorio
-   ℹ️ Se usará `initContentGeneratorsWithPriority()` para asignar prioridades basadas en nombres

**Para cambiar a auto-registro:**

```php
$this->contentGeneratorManager = new ContentGeneratorManager(true, true);
```

Esto activaría `registerCustomGenerators()` que registra todos los generadores con prioridad 10 por defecto.

---

## Referencias

-   **ContentGeneratorManager:** `/src/theme/src/Features/ContentGenerator/ContentGeneratorManager.php`
-   **AbstractContentGenerator:** `/src/theme/src/Features/ContentGenerator/AbstractContentGenerator.php`
-   **ContentTypeGenerator:** `/src/theme/src/Features/ContentGenerator/ContentTypeGenerator.php`
-   **ContentGeneratorFactory:** `/src/theme/src/Features/ContentGenerator/ContentGeneratorFactory.php`
-   **Generadores existentes:** `/src/theme/src/Features/ContentGenerator/Generators/`

---

## Glosario

| Término               | Definición                                                                |
| --------------------- | ------------------------------------------------------------------------- |
| **Generator**         | Clase que extiende AbstractContentGenerator y genera contenido específico |
| **Priority**          | Número que determina orden de ejecución (menor = primero)                 |
| **option_key**        | Clave única en wp_options para rastrear si el generador ya ejecutó        |
| **force**             | Parámetro booleano para forzar regeneración ignorando estado              |
| **content_processor** | Función callable que transforma contenido crudo en formato WordPress      |

---

**Última actualización:** 2025-10-22
**Versión del tema:** Talampaya v1.0

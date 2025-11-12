# Architecture

Complete guide to Talampaya's architecture, design patterns, and code organization.

## Table of Contents

- [Architecture](#architecture)
  - [Table of Contents](#table-of-contents)
  - [Theme Structure \& Entry Points](#theme-structure--entry-points)
    - [Initialization Chain](#initialization-chain)
  - [Directory Organization](#directory-organization)
  - [Key Architectural Patterns](#key-architectural-patterns)
    - [Auto-Discovery System](#auto-discovery-system)
    - [Manager Pattern](#manager-pattern)
    - [Data Flow](#data-flow)
  - [Custom Post Types \& Taxonomies](#custom-post-types--taxonomies)
    - [Example: ProjectPostType](#example-projectposttype)
    - [Example: EpicTaxonomy](#example-epictaxonomy)
    - [Adding New Post Types/Taxonomies](#adding-new-post-typestaxonomies)
  - [Naming Conventions](#naming-conventions)
    - [PHP Classes](#php-classes)
    - [Files](#files)
    - [Namespaces](#namespaces)
  - [Code Standards](#code-standards)
    - [PHP](#php)
    - [JavaScript](#javascript)
    - [CSS](#css)
  - [Key Files Reference](#key-files-reference)

## Theme Structure & Entry Points

The theme follows a structured initialization chain to ensure proper loading order and dependency management.

### Initialization Chain

1. **`/src/theme/functions.php`** - Theme entry point
   - Loads Composer autoloader
   - Calls `bootstrap.php`
   - Instantiates `TalampayaStarter` (main theme class)

2. **`/src/theme/src/bootstrap.php`** - Initialization sequence
   - Configures Timber
   - Registers managers (Assets, Plugins, Register, Context, Twig, ContentGenerator)
   - Loads custom post types, taxonomies, menus, sidebars
   - Includes ACF fields, hooks, and filters

3. **`/src/theme/src/TalampayaStarter.php`** - Main orchestrator
   - Extends `Timber\Site`
   - Manages context data for templates
   - Extends Twig environment
   - Handles content generation

## Directory Organization

```
/src/theme/
├── functions.php              # Entry point
├── src/                       # PHP application code (PSR-4: App\)
│   ├── bootstrap.php         # Initialization
│   ├── TalampayaStarter.php  # Main class
│   ├── Core/                 # Infrastructure
│   │   ├── Setup/            # WordPress setup
│   │   ├── Plugins/          # Plugin management
│   │   ├── ContextExtender/  # Data providers
│   │   └── TwigExtender/     # Template extensions
│   ├── Features/             # Feature modules
│   │   ├── Acf/              # ACF integration & blocks
│   │   ├── Admin/            # Admin customization
│   │   └── ContentGenerator/ # Demo content
│   ├── Register/             # CPT/Taxonomy registration
│   │   ├── PostType/
│   │   ├── Taxonomy/
│   │   ├── Menu/
│   │   └── Sidebar/
│   ├── Inc/                  # Models, helpers, services
│   │   ├── Models/           # Post models (extends Timber\Post)
│   │   ├── Helpers/          # WordPress-specific utilities
│   │   └── Utils/            # General utilities
│   └── Hooks/                # WordPress hooks/filters
├── views/                    # Twig templates
│   ├── layouts/
│   ├── pages/
│   ├── blocks/               # ACF block templates
│   └── components/
├── assets/
│   ├── styles/               # SCSS source files
│   ├── scripts/              # JavaScript files
│   ├── fonts/
│   └── images/
├── blocks/                   # ACF block JSON definitions
└── acf-json/                 # ACF field group exports
```

## Key Architectural Patterns

### Auto-Discovery System

Multiple systems use `FileUtils::talampaya_directory_iterator()` to automatically discover and register components:

- **Post types** in `Register/PostType/` (extend `AbstractPostType`)
- **Taxonomies** in `Register/Taxonomy/` (extend `AbstractTaxonomy`)
- **Plugins** in `Core/Plugins/Integration/` (implement `PluginInterface`)
- **Context extenders** in `Core/ContextExtender/Custom/`
- **Twig extenders** in `Core/TwigExtender/Custom/`
- **Content generators** in `Features/ContentGenerator/Generators/`

**Rules**:
- Files starting with `_` are skipped
- Class names must match file names (PSR-4)
- Classes must extend/implement the correct base class/interface

### Manager Pattern

Core functionality is organized through manager classes:

| Manager | Responsibility | Location |
|---------|---------------|----------|
| **PluginManager** | Third-party plugin integration (ACF, WPML, Yoast) | `Core/Plugins/PluginManager.php` |
| **RegisterManager** | Auto-registers CPTs, taxonomies, menus, sidebars | `Core/Setup/RegisterManager.php` |
| **TwigManager** | Manages Twig extensions (filters, functions) | `Core/TwigExtender/TwigManager.php` |
| **ContextManager** | Manages data passed to templates | `Core/ContextExtender/ContextManager.php` |
| **ContentGeneratorManager** | Orchestrates demo content generation | `Features/ContentGenerator/ContentGeneratorManager.php` |
| **AssetsManager** | Handles CSS/JS enqueuing | `Core/Setup/AssetsManager.php` |

### Data Flow

```
WordPress Request
  → Template File (index.php, page.php, etc.)
  → DefaultController::get_blog_context()
  → Timber::context() [base context]
  → TalampayaStarter::addToContext()
  → ContextManager::extendContext() [chains through extenders]
  → TalampayaStarter::addToTwig()
  → TwigManager::extendTwig() [adds filters/functions]
  → Timber::render('template.twig', $context)
  → HTML Output
```

## Custom Post Types & Taxonomies

### Example: ProjectPostType

**Location**: `src/Register/PostType/ProjectPostType.php`

**Configuration**:
- Slug: `project_post`
- URL: `/project/`
- Taxonomies: `epic_taxonomy`
- REST API enabled
- Icon: `dashicons-admin-appearance`

### Example: EpicTaxonomy

**Location**: `src/Register/Taxonomy/EpicTaxonomy.php`

**Configuration**:
- Slug: `epic_taxonomy`
- Attached to: `project_post`
- Non-hierarchical

### Adding New Post Types/Taxonomies

1. Create class in `/src/theme/src/Register/PostType/` or `/src/theme/src/Register/Taxonomy/`
2. Extend `AbstractPostType` or `AbstractTaxonomy`
3. Implement `configure()` method returning WordPress args
4. Auto-discovered and registered by `RegisterManager`

See [COMMON-TASKS.md](COMMON-TASKS.md#adding-a-new-post-type) for detailed steps.

## Naming Conventions

### PHP Classes

| Type | Convention | Example |
|------|-----------|---------|
| Abstract classes | `Abstract*` prefix | `AbstractPostType` |
| Interfaces | `*Interface` suffix | `PluginInterface` |
| Managers | `*Manager` suffix | `PluginManager` |
| Helpers | `*Helper` suffix | `PostHelper` |
| Utils | `*Utils` suffix | `FileUtils` |

### Files

- **Class files**: PascalCase matching class name (PSR-4)
- **Skip discovery**: Prefix with `_` (e.g., `_example.php`)

### Namespaces

- **Root**: `App\`
- **PSR-4 autoload**: `"App\\": "wp-content/themes/talampaya/src/"`

## Code Standards

### PHP

- PHP 8.0+ features (strict typing, return types)
- Follow WordPress Coding Standards where applicable
- Use type hints and return types
- Prefer composition over inheritance

### JavaScript

- ES6+ syntax (transpiled by Babel)
- ESLint for linting
- Prettier for formatting

### CSS

- SCSS syntax
- Stylelint for linting
- BEM methodology for class names (where applicable)

## Key Files Reference

| File | Purpose |
|------|---------|
| `/src/theme/functions.php` | Theme entry point |
| `/src/theme/src/bootstrap.php` | Initialization sequence |
| `/src/theme/src/TalampayaStarter.php` | Main theme orchestrator |
| `/composer.json` | PHP dependencies + PSR-4 autoload |
| `/src/theme/phpunit.xml` | PHP test configuration |

---

For related documentation:
- [COMMON-TASKS.md](COMMON-TASKS.md) - Step-by-step implementation guides
- [TIMBER-TWIG.md](TIMBER-TWIG.md) - Templating system details
- [CONTENT-GENERATOR.md](CONTENT-GENERATOR.md) - Content generation system
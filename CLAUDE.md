# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Talampaya is a professional WordPress theme built with modern PHP and a sophisticated build system. It uses Timber/Twig templating, Docker for development, Gulp for asset compilation, and follows a modular, namespace-based architecture with PSR-4 autoloading.

## Essential Development Commands

### Starting Development
```bash
# Start full Docker environment
npm start  # or docker compose up

# Start development build with hot reload
npm run dev  # Runs Gulp watchers + Browser Sync

# Production build (creates dist/talampaya.zip)
npm run build
```

### WordPress Management
```bash
# Initialize WordPress (first-time setup)
npm run wp:setup

# Reset WordPress to fresh state
npm run wp:reset

# Generate demo content
npm run wp:demo:create

# Delete demo content
npm run wp:demo:delete

# Backup database
npm run wp:backup

# WP-CLI access
docker compose exec wp wp [command]
```

### Code Quality
```bash
# JavaScript linting
npm run lint:js
npm run lint:fix

# Format all files (JS, PHP, Twig, CSS)
npm run prettier:write
npm run prettier:check

# Run PHP tests
npm test  # or composer test
```

### Docker Operations
```bash
# Rebuild specific service
npm run docker:rebuild:node

# Update PHP dependencies
npm run docker:composer:update

# Fix file permissions
npm run docker:permissions

# Delete posts by type
npm run wp:delete-posts --post_type=page

# Delete taxonomy terms
npm run wp:delete-terms --taxonomy=category
```

### Pattern Lab
```bash
# Start Pattern Lab design system
npm run patternlab
```

## Architecture Overview

### Theme Structure & Entry Points

The theme follows a structured initialization chain:

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

### Directory Organization

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

### Key Architectural Patterns

#### Auto-Discovery System
Multiple systems use `FileUtils::talampaya_directory_iterator()` to automatically discover and register components:
- Post types in `Register/PostType/` (extend `AbstractPostType`)
- Taxonomies in `Register/Taxonomy/` (extend `AbstractTaxonomy`)
- Plugins in `Core/Plugins/Integration/` (implement `PluginInterface`)
- Context extenders in `Core/ContextExtender/Custom/`
- Twig extenders in `Core/TwigExtender/Custom/`
- Content generators in `Features/ContentGenerator/Generators/`

Files starting with `_` are skipped. Class names must match file names (PSR-4).

#### Manager Pattern
Core functionality organized through manager classes:
- **PluginManager**: Third-party plugin integration (ACF, WPML, Yoast)
- **RegisterManager**: Auto-registers CPTs, taxonomies, menus, sidebars
- **TwigManager**: Manages Twig extensions (filters, functions)
- **ContextManager**: Manages data passed to templates
- **ContentGeneratorManager**: Orchestrates demo content generation
- **AssetsManager**: Handles CSS/JS enqueuing

#### Data Flow
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

### Custom Post Types & Taxonomies

**ProjectPostType** (src/Register/PostType/ProjectPostType.php):
- Slug: `project_post`
- URL: `/project/`
- Taxonomies: `epic_taxonomy`
- REST API enabled
- Icon: `dashicons-admin-appearance`

**EpicTaxonomy** (src/Register/Taxonomy/EpicTaxonomy.php):
- Slug: `epic_taxonomy`
- Attached to: `project_post`
- Non-hierarchical

To add new post types/taxonomies, extend `AbstractPostType`/`AbstractTaxonomy` and implement `configure()` method. They will be auto-discovered.

### ACF Integration

**Block System**:
- Block JSON definitions in `/blocks/`
- Block templates in `/views/blocks/`
- Rendered via `BlockRenderer` with Twig
- Context modifiers in `Features/Acf/Blocks/Modifiers/` for dynamic data injection

**Field Groups**:
- Auto-synced JSON exports in `/acf-json/`
- Field definitions in `Features/Acf/Fields/`
- Organized by post type/template
- WPML integration for translation

### Content Generator System

Automatically generates demo content on theme activation (controlled in `TalampayaStarter`):
- Generators in `Features/ContentGenerator/Generators/`
- Extend `AbstractContentGenerator`
- Priority-based execution (taxonomies first, then posts)
- Can be manually triggered or disabled

Example: `ProjectPostGenerator` creates sample project posts with ACF fields.

### Timber/Twig Templating

**Context Data**:
- Access via `{{ variable }}` in Twig
- Extended through `ContextExtenderInterface` implementations
- Built-in: `PathsContext` (asset URLs)

**Custom Twig Extensions**:
- Filters/functions added via `TwigExtenderInterface`
- Auto-discovered in `Core/TwigExtender/Custom/`
- Register with `TwigManager::registerExtender()`

**Template Paths** (configured in `TalampayaStarter::addLocations()`):
- `@atoms` → `views/atoms`
- `@molecules` → `views/molecules`
- `@organisms` → `views/organisms`
- `@templates` → `views/templates`
- `@pages` → `views/pages`
- `@layouts` → `views/layouts`
- `@blocks` → `views/blocks`

## Build System

### Gulp Build Pipeline

**Development** (`npm run dev`):
1. Copy theme files (with name replacement)
2. Copy fonts/images
3. Compile SCSS → CSS (with sourcemaps)
4. Bundle JavaScript via Webpack
5. Copy plugins, languages, ACF JSON
6. Copy Pattern Lab files and mockups
7. Start Browser Sync proxy server
8. Watch for changes (polling-based for Docker compatibility)

**Production** (`npm run build`):
- Same as dev + minification
- No sourcemaps
- Outputs to `/dist/` as ZIP file

**Entry Points** (defined in `webpack.config.js`):
- `main.js`: Pattern Lab scripts
- `scripts.js`: Theme frontend
- `backend.js`: Admin area

### Asset Organization

**SCSS**:
- Frontend: `assets/styles/main.scss` → `style.css`
- Admin: `assets/styles/backend-*.scss` → `backend-styles.css`

**JavaScript**:
- Bundled via Webpack with Babel transpilation
- ES6+ supported
- Separate bundles for frontend/admin

### Environment Variables

Required in `.env` file:
- `APP_NAME`: Theme name (default: talampaya)
- `DOMAIN`: Development domain (e.g., talampaya.local)
- `PROTOCOL`: http or https
- `NODE_ENV`: development or production
- Database credentials
- Plugin licenses (ACF Pro, WPML)

## Docker Environment

### Services

- **db**: MariaDB database
- **wp**: PHP 8.4-FPM + WordPress + WP-CLI + Xdebug
- **nginx**: Reverse proxy with SSL (mkcert)
- **node**: Gulp build system
- **composer**: PHP dependency management
- **phpmyadmin**: Database GUI (port 8082)
- **mailhog**: Email testing (port 8025)
- **patternlab**: Design system
- **cache**: Redis for object caching

### Volumes

- `/build`: Live WordPress installation (mounted from host)
- `db-data`: Persistent database storage

### Network

- Custom network: `talampaya-network`
- Services communicate via service names (e.g., `wp`, `db`, `nginx`)

### WordPress Auto-Setup

On first run, WordPress is automatically configured via:
- `/docker/wp/scripts/wp-init.sh`
- `/docker/wp/scripts/wp-setup.sh`

Controlled by `WORDPRESS_AUTO_SETUP=true` in `.env`.

## Testing

### PHP Tests

**Configuration**: `/src/theme/phpunit.xml`

**Framework**: PHPUnit with WordBless (WordPress testing library)

**Run tests**:
```bash
npm test
# or
composer test
# or
docker compose exec wp vendor/bin/phpunit -c wp-content/themes/talampaya
```

Tests located in `/src/theme/tests/` (prefix: `test-*.php`).

### JavaScript Tests

**Framework**: Jest

**Configuration**: `/jest.config.js`

**Run tests**:
```bash
npm run test
```

## Pattern Lab Integration

Pattern Lab provides a living design system using Atomic Design methodology.

**Structure**:
- `/patternlab/source/_patterns/` - Pattern definitions
  - `atoms/`: Basic elements
  - `molecules/`: Component combinations
  - `organisms/`: Complex sections
  - `templates/`: Page layouts
  - `pages/`: Example pages
- `/patternlab/source/_data/` - JSON mockup data

**Integration with Theme**:
- Gulp copies Pattern Lab Twig files → `/src/theme/views/`
- Gulp transforms JSON data → `/src/theme/src/Mockups/`
- Webpack bundles Pattern Lab JavaScript
- Shared component library between design and implementation

**Running Pattern Lab**:
```bash
npm run patternlab  # Starts on port 4000
```

## Important Conventions

### Naming Conventions

**PHP Classes**:
- Abstract classes: `Abstract*` prefix (e.g., `AbstractPostType`)
- Interfaces: `*Interface` suffix (e.g., `PluginInterface`)
- Managers: `*Manager` suffix (e.g., `PluginManager`)
- Helpers: `*Helper` suffix (e.g., `PostHelper`)
- Utils: `*Utils` suffix (e.g., `FileUtils`)

**Files**:
- Class files: PascalCase matching class name (PSR-4)
- Skip discovery: Prefix with `_` (e.g., `_example.php`)

**Namespaces**:
- Root: `App\`
- PSR-4 autoload: `"App\\": "wp-content/themes/talampaya/src/"`

### Code Standards

**PHP**:
- PHP 8.0+ features (strict typing, return types)
- Follow WordPress Coding Standards where applicable
- Use type hints and return types
- Prefer composition over inheritance

**JavaScript**:
- ES6+ syntax (transpiled by Babel)
- ESLint for linting
- Prettier for formatting

**CSS**:
- SCSS syntax
- Stylelint for linting
- BEM methodology for class names (where applicable)

### Git Workflow

**Branches**:
- Main branch: `master` (for production)
- Development branch: `develop` (default)
- Feature branches: `feature/*`
- Hotfixes: `hotfix/*`

**Commit Convention**:
- Uses Conventional Commits (enforced by commitlint)
- Format: `type(scope): message`
- Types: `feat`, `fix`, `refactor`, `chore`, `docs`, `test`

**Pre-commit Hooks** (via Husky + lint-staged):
- Runs Prettier on staged files
- Runs ESLint on JS/TS files
- Runs Stylelint on SCSS files
- Checks commit message format

## Third-Party Integrations

### Required Plugins (managed via Composer)

- **Timber** (^2.1): Twig templating engine
- **Advanced Custom Fields Pro** (6.6.0): Field management
- **Yoast SEO**: SEO optimization
- **WPML**: Multilingual support (requires license in `.env`)
  - `sitepress-multilingual-cms`
  - `wpml-string-translation`
  - `wp-seo-multilingual`
  - `acfml`
  - `wpml-import`
  - `wpml-all-import`

### Plugin Integration System

Custom plugins integrate via `PluginInterface`:
```php
interface PluginInterface {
    public function getName(): string;
    public function shouldLoad(): bool;  // Check if WP plugin active
    public function initialize(): void;
    public function getRequiredPlugins(): array;
}
```

Plugins auto-discovered in `Core/Plugins/Integration/`.

## Common Development Tasks

### Adding a New Post Type

1. Create class in `/src/theme/src/Register/PostType/`
2. Extend `AbstractPostType`
3. Implement `configure()` method returning WordPress args
4. Auto-discovered and registered by `RegisterManager`

Example in: `Register/PostType/ProjectPostType.php`

### Adding a New Feature

1. Create directory in `/src/theme/src/Features/YourFeature/`
2. Implement feature logic
3. Register in `bootstrap.php` or use auto-discovery
4. Follow namespace convention: `App\Features\YourFeature`

### Adding Twig Extensions

1. Create class in `/src/theme/src/Core/TwigExtender/Custom/`
2. Implement `TwigExtenderInterface`
3. Add filters via `$twig->addFilter(new TwigFilter(...))`
4. Auto-discovered and loaded by `TwigManager`

### Adding Context Data

1. Create class in `/src/theme/src/Core/ContextExtender/Custom/`
2. Implement `ContextExtenderInterface`
3. Return array of key-value pairs in `extendContext()`
4. Auto-discovered and merged into template context

### Creating ACF Blocks

1. Define block JSON in `/src/theme/blocks/block-name.json`
2. Create Twig template in `/src/theme/views/blocks/block-name.twig`
3. (Optional) Add context modifier in `/src/theme/src/Features/Acf/Blocks/Modifiers/`
4. Block automatically registered via `BlockRenderer`

### Generating Demo Content

1. Create generator in `/src/theme/src/Features/ContentGenerator/Generators/`
2. Extend `AbstractContentGenerator`
3. Implement `generate()` method
4. Set priority in constructor (5=taxonomies, 10=posts, 15=other)
5. Register in `ContentGeneratorManager` (in `TalampayaStarter`)

Example in: `Features/ContentGenerator/Generators/ProjectPostGenerator.php`

## Troubleshooting

### Build Issues

**Gulp not watching files**:
- Check Docker volume mounts in `docker-compose.yml`
- Ensure Node container is running: `docker compose ps`
- Restart Node service: `docker compose restart node`

**Missing dependencies**:
```bash
# PHP dependencies
npm run docker:composer:update

# Node dependencies
rm -rf node_modules package-lock.json && npm install
```

**Permission errors**:
```bash
npm run docker:permissions
```

### Database Issues

**Reset WordPress**:
```bash
npm run wp:reset
```

**Access database**:
- PhpMyAdmin: http://localhost:8082
- Or via CLI: `docker compose exec db mysql -u root -ppassword talampaya`

### Xdebug Configuration

Xdebug 3.4.2 installed in `wp` container.

**IDE Settings**:
- Port: 9003
- IDE key: PHPSTORM
- Path mappings: `/var/www/html` → `/path/to/talampaya/build`

### Clear Caches

**WordPress object cache**:
```bash
docker compose exec wp wp cache flush --allow-root
```

**Browser Sync cache**: Restart Gulp (`npm run dev`)

**Redis cache**: Restart cache service
```bash
docker compose restart cache
```

## Key Files Reference

| File | Purpose |
|------|---------|
| `/src/theme/functions.php` | Theme entry point |
| `/src/theme/src/bootstrap.php` | Initialization sequence |
| `/src/theme/src/TalampayaStarter.php` | Main theme orchestrator |
| `/gulpfile.js` | Build system configuration (795 lines) |
| `/webpack.config.js` | JavaScript bundling |
| `/docker-compose.yml` | Container orchestration |
| `/composer.json` | PHP dependencies + PSR-4 autoload |
| `/package.json` | Node dependencies + npm scripts |
| `/.env` | Environment variables (copy from `.env.example`) |
| `/src/theme/phpunit.xml` | PHP test configuration |

## Resources

- **Timber Documentation**: https://timber.github.io/docs/
- **Twig Documentation**: https://twig.symfony.com/doc/
- **ACF Documentation**: https://www.advancedcustomfields.com/resources/
- **Pattern Lab**: https://patternlab.io/
- **WordPress Codex**: https://codex.wordpress.org/

# Talampaya

> Professional WordPress theme boilerplate with modern architecture, Timber/Twig templating, ACF Pro, WPML multilingual support, and Docker development environment.

## Quick Start

```bash
# Clone and setup
git clone https://github.com/yourusername/talampaya.git
cd talampaya
cp .env.example .env

# Edit .env with your configuration

# Start development environment
npm start        # Start Docker services
npm run dev      # Start build with hot reload
```

Your site will be available at the domain configured in `.env` (default: https://talampaya.local)

## Key Features

- **Modern PHP Architecture**: PSR-4 autoloading, strict typing, namespace-based modular structure
- **Timber/Twig Templating**: Clean separation of logic and presentation
- **ACF Pro Integration**: Custom blocks, field groups, advanced custom fields
- **WPML Ready**: Full multilingual support with translation management
- **Docker Development**: Complete containerized environment (WordPress, PHP 8.4, MariaDB, Redis, Nginx)
- **Build System**: Gulp + Webpack with hot reload, SCSS compilation, JS bundling
- **Pattern Lab**: Living design system based on Atomic Design methodology
- **Auto-Discovery**: Automatic registration of post types, taxonomies, plugins, and extensions
- **Demo Content**: Automated content generation system with dependency management
- **Testing**: PHPUnit + Jest configured and ready

## Tech Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| WordPress | Latest | CMS Core |
| PHP | 8.4 | Application logic |
| Timber | ^2.1 | Twig templating for WordPress |
| ACF Pro | 6.6.0 | Advanced custom fields |
| WPML | Latest | Multilingual support |
| Docker | Latest | Development environment |
| Node.js | Latest | Build system |
| Gulp | 5.x | Task runner |
| Webpack | 5.x | JavaScript bundler |
| Pattern Lab | Latest | Design system |

## Documentation

Talampaya uses a modular documentation structure. All technical documentation is located in the `/docs` directory.

### Core Documentation

| Document | Description |
|----------|-------------|
| [DEVELOPMENT.md](docs/DEVELOPMENT.md) | Development workflow, commands, and daily usage |
| [ARCHITECTURE.md](docs/ARCHITECTURE.md) | Complete theme architecture and design patterns |
| [DOCKER.md](docs/DOCKER.md) | Docker environment setup and services |
| [BUILD-SYSTEM.md](docs/BUILD-SYSTEM.md) | Gulp, Webpack, and asset compilation |

### Feature Documentation

| Document | Description |
|----------|-------------|
| [TIMBER-TWIG.md](docs/TIMBER-TWIG.md) | Templating system, context, and Twig extensions |
| [ACF-BLOCKS.md](docs/ACF-BLOCKS.md) | ACF blocks system and custom fields |
| [CONTENT-GENERATOR.md](docs/CONTENT-GENERATOR.md) | Demo content generation system |
| [PATTERN-LAB.md](docs/PATTERN-LAB.md) | Design system and component library |

### Reference Documentation

| Document | Description |
|----------|-------------|
| [COMMON-TASKS.md](docs/COMMON-TASKS.md) | Step-by-step guides for common development tasks |
| [TESTING.md](docs/TESTING.md) | Running and writing tests |
| [THIRD-PARTY.md](docs/THIRD-PARTY.md) | Third-party plugin integrations |
| [TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) | Common issues and solutions |
| [CONTRIBUTING.md](docs/CONTRIBUTING.md) | Code conventions and contribution guidelines |

## Project Structure

```
talampaya/
├── README.md                # This file
├── CLAUDE.md                # AI assistant guidelines
├── .env                     # Environment config (APP_NAME for fork detection)
├── docs/                    # Detailed documentation
├── docker/                  # Docker configuration
├── src/theme/               # WordPress theme source
│   ├── functions.php        # Theme entry point
│   ├── src/                 # PHP application code (PSR-4: App\)
│   │   ├── Core/            # Infrastructure (managers, plugins, extensions)
│   │   ├── Features/        # Feature modules (ACF, Admin, ContentGenerator)
│   │   ├── Register/        # Auto-registered components (CPT, taxonomies)
│   │   └── Inc/             # Models, helpers, utilities
│   ├── views/               # Twig templates
│   ├── assets/              # Source SCSS, JS, images
│   ├── blocks/              # ACF block definitions (JSON)
│   └── tests/               # PHP unit tests
├── patternlab/              # Pattern Lab design system
├── build/                   # Compiled WordPress installation
└── dist/                    # Production build output
```

## Essential Commands

```bash
# Development
npm start                    # Start Docker services
npm run dev                  # Start development build with hot reload
npm run build                # Create production build (dist/talampaya.zip)

# WordPress
npm run wp:setup             # Initialize WordPress (first-time)
npm run wp:reset             # Reset to fresh state
npm run wp:demo:create       # Generate demo content

# Code Quality
npm run lint:js              # Lint JavaScript
npm run prettier:write       # Format all files
npm test                     # Run PHP tests

# Docker
npm run docker:rebuild:node  # Rebuild Node service
npm run docker:permissions   # Fix file permissions
```

For complete command reference, see [DEVELOPMENT.md](docs/DEVELOPMENT.md)

## Fork Setup

If you're forking Talampaya for a new project:

1. **Update environment**:
   ```bash
   # Edit .env
   APP_NAME=your-project-name   # Change this to identify your fork
   DOMAIN=your-project.local
   ```

2. **Update theme metadata**:
   ```bash
   # Edit src/theme/style.css
   Theme Name: Your Project Name
   ```

3. **Add upstream remote** (to sync with Talampaya updates):
   ```bash
   git remote add upstream https://github.com/guillorrr/talampaya.git
   git fetch upstream
   ```

4. **Sync with upstream** (periodically):
   ```bash
   git fetch upstream
   git merge upstream/master
   ```

See [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md#syncing-with-upstream) for detailed upstream synchronization workflow.

## Requirements

- Docker Desktop or Docker Engine + Docker Compose
- Node.js (for local build tools)
- mkcert (for local SSL certificates)

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- IE11+ (with polyfills)

## License

MIT License - See LICENSE file for details

## Support

- **Documentation**: Check `/docs` directory
- **Issues**: Report bugs and feature requests via GitHub Issues
- **Discussions**: Use GitHub Discussions for questions

## Credits

Built with:
- [Timber](https://timber.github.io/docs/) - Twig templating for WordPress
- [Advanced Custom Fields](https://www.advancedcustomfields.com/) - Custom field management
- [Pattern Lab](https://patternlab.io/) - Design system methodology
- [WPML](https://wpml.org/) - Multilingual plugin

---

**Version**: 1.0.0
**Last Updated**: 2025-01-12

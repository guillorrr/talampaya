# Development

Complete guide to development workflow, commands, and daily usage of Talampaya.

## Table of Contents

- [Development](#development)
  - [Table of Contents](#table-of-contents)
  - [Essential Development Commands](#essential-development-commands)
    - [Starting Development](#starting-development)
    - [WordPress Management](#wordpress-management)
    - [Code Quality](#code-quality)
    - [Docker Operations](#docker-operations)
    - [Pattern Lab](#pattern-lab)
  - [Development Workflow](#development-workflow)
    - [First-Time Setup](#first-time-setup)
    - [Daily Workflow](#daily-workflow)
  - [Git Workflow](#git-workflow)
    - [Branches](#branches)
    - [Commit Convention](#commit-convention)
    - [Pre-commit Hooks](#pre-commit-hooks)
  - [WP-CLI Usage](#wp-cli-usage)
  - [Debugging](#debugging)
    - [Xdebug Configuration](#xdebug-configuration)
    - [WordPress Debug Mode](#wordpress-debug-mode)

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
npm run patternlab  # Starts on port 4000
```

## Development Workflow

### First-Time Setup

1. **Clone and configure**:
   ```bash
   git clone https://github.com/yourusername/talampaya.git
   cd talampaya
   cp .env.example .env
   ```

2. **Edit `.env` file** with your configuration:
   ```env
   APP_NAME=talampaya
   DOMAIN=talampaya.local
   PROTOCOL=https
   NODE_ENV=development

   # Database
   DB_NAME=talampaya
   DB_USER=root
   DB_PASSWORD=password

   # WordPress
   WORDPRESS_ADMIN_USER=admin
   WORDPRESS_ADMIN_PASS=admin
   WORDPRESS_ADMIN_EMAIL=admin@talampaya.local

   # Plugin licenses
   ACF_PRO_KEY=your-acf-pro-license-key
   WPML_USER_ID=your-wpml-user-id
   WPML_KEY=your-wpml-subscription-key
   ```

3. **Start Docker environment**:
   ```bash
   npm start
   ```

4. **Initialize WordPress** (automatic on first run):
   ```bash
   npm run wp:setup
   ```

5. **Start development build**:
   ```bash
   npm run dev
   ```

6. **Access your site**:
   - Frontend: https://talampaya.local
   - Admin: https://talampaya.local/wp-admin
   - PhpMyAdmin: http://localhost:8082
   - Mailhog: http://localhost:8025

### Daily Workflow

1. **Start services** (if not running):
   ```bash
   npm start
   ```

2. **Start development build**:
   ```bash
   npm run dev
   ```

3. **Make your changes** - files will auto-reload via Browser Sync

4. **Run code quality checks**:
   ```bash
   npm run lint:js
   npm run prettier:check
   npm test
   ```

5. **Commit changes** (follows conventional commits):
   ```bash
   git add .
   git commit -m "feat: add new feature"
   ```

6. **Stop services** (when done):
   ```bash
   docker compose down
   ```

## Git Workflow

### Branches

- **Main branch**: `master` (for production)
- **Development branch**: `develop` (default)
- **Feature branches**: `feature/feature-name`
- **Hotfixes**: `hotfix/issue-description`

### Commit Convention

Uses **Conventional Commits** (enforced by commitlint):

**Format**: `type(scope): message`

**Types**:
- `feat` - New feature
- `fix` - Bug fix
- `refactor` - Code refactoring
- `chore` - Maintenance tasks
- `docs` - Documentation changes
- `test` - Test additions/modifications
- `style` - Code style changes (formatting, etc.)
- `perf` - Performance improvements

**Examples**:
```bash
git commit -m "feat(acf): add hero block with background image"
git commit -m "fix(twig): resolve context data issue in footer"
git commit -m "docs: update architecture documentation"
git commit -m "refactor(helpers): simplify PostHelper methods"
```

### Pre-commit Hooks

Husky + lint-staged automatically run on commit:

- Prettier on staged files
- ESLint on JS/TS files
- Stylelint on SCSS files
- Commit message validation

To bypass hooks (not recommended):
```bash
git commit --no-verify
```

## WP-CLI Usage

Access WP-CLI inside the WordPress container:

```bash
# Basic syntax
docker compose exec wp wp [command]

# Examples
docker compose exec wp wp plugin list
docker compose exec wp wp user list
docker compose exec wp wp post list --post_type=page
docker compose exec wp wp cache flush
docker compose exec wp wp rewrite flush
docker compose exec wp wp search-replace 'oldurl.com' 'newurl.com'
```

Common WP-CLI commands:

```bash
# Plugin management
docker compose exec wp wp plugin activate acf
docker compose exec wp wp plugin deactivate acf

# User management
docker compose exec wp wp user create john john@example.com --role=editor

# Database
docker compose exec wp wp db export
docker compose exec wp wp db import backup.sql

# Transients
docker compose exec wp wp transient delete --all
```

## Debugging

### Xdebug Configuration

Xdebug 3.4.2 is installed in the `wp` container.

**IDE Settings** (PHPStorm/VS Code):
- Port: `9003`
- IDE key: `PHPSTORM`
- Path mappings: `/var/www/html` → `/path/to/talampaya/build`

**Activate Xdebug**:
```bash
# Set in .env
XDEBUG_MODE=develop,debug

# Restart container
docker compose restart wp
```

**Browser Extension**:
Install Xdebug Helper for Chrome/Firefox and set IDE key to `PHPSTORM`.

### WordPress Debug Mode

Enable in `.env`:
```env
WP_DEBUG=true
WP_DEBUG_LOG=true
WP_DEBUG_DISPLAY=false
```

Debug log location: `/build/wp-content/debug.log`

View logs:
```bash
docker compose exec wp tail -f /var/www/html/wp-content/debug.log
```

---

For related documentation:
- [DOCKER.md](DOCKER.md) - Docker environment details
- [BUILD-SYSTEM.md](BUILD-SYSTEM.md) - Build pipeline configuration
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Common issues and solutions
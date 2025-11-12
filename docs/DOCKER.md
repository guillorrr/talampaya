# Docker Environment

Complete guide to the Docker-based development environment for Talampaya.

## Table of Contents

- [Docker Environment](#docker-environment)
  - [Table of Contents](#table-of-contents)
  - [Overview](#overview)
  - [Services](#services)
  - [Volumes](#volumes)
  - [Network](#network)
  - [WordPress Auto-Setup](#wordpress-auto-setup)
    - [Setup Scripts](#setup-scripts)
  - [Service Details](#service-details)
    - [Database (MariaDB)](#database-mariadb)
    - [WordPress (PHP-FPM)](#wordpress-php-fpm)
    - [Nginx](#nginx)
    - [Node](#node)
    - [PhpMyAdmin](#phpmyadmin)
    - [Mailhog](#mailhog)
    - [Redis](#redis)
  - [Common Operations](#common-operations)
    - [Starting Services](#starting-services)
    - [Stopping Services](#stopping-services)
    - [Rebuilding Services](#rebuilding-services)
    - [Viewing Logs](#viewing-logs)
    - [Accessing Containers](#accessing-containers)
  - [Troubleshooting](#troubleshooting)

## Overview

Talampaya uses Docker Compose to provide a complete, isolated development environment with all necessary services.

**Benefits**:
- Consistent environment across all developers
- No need to install PHP, MySQL, or WordPress locally
- Easy service management
- Isolated from system PHP/MySQL installations
- Reproducible builds

## Services

| Service | Technology | Port(s) | Purpose |
|---------|-----------|---------|---------|
| **db** | MariaDB 10.11 | 3306 | Database |
| **wp** | PHP 8.4-FPM + WordPress | - | WordPress core + theme |
| **nginx** | Nginx + mkcert SSL | 80, 443 | Web server with HTTPS |
| **node** | Node.js (latest) | - | Gulp build system |
| **composer** | Composer 2 | - | PHP dependency management |
| **phpmyadmin** | phpMyAdmin | 8082 | Database GUI |
| **mailhog** | Mailhog | 8025, 1025 | Email testing |
| **patternlab** | Node.js | 4000 | Pattern Lab design system |
| **cache** | Redis 7 | 6379 | Object cache |

## Volumes

| Volume | Purpose | Persistence |
|--------|---------|-------------|
| `db-data` | Database storage | Persistent (survives container removal) |
| `./build` | Live WordPress installation | Host-mounted (editable from host) |
| `./src/theme` | Theme source files | Host-mounted |
| `./docker` | Docker configuration | Host-mounted |

## Network

**Custom network**: `talampaya-network`

Services communicate via service names:
- Database: `db:3306`
- WordPress: `wp:9000`
- Nginx: `nginx:80` / `nginx:443`
- Redis: `cache:6379`

## WordPress Auto-Setup

On first run, WordPress is automatically configured if `WORDPRESS_AUTO_SETUP=true` in `.env`.

**Process**:
1. `wp` container starts
2. Waits for database availability
3. Runs `wp-init.sh` script
4. Calls `wp-setup.sh` to configure WordPress
5. Activates theme and creates initial content

### Setup Scripts

**Location**: `/docker/wp/scripts/`

| Script | Purpose |
|--------|---------|
| `wp-init.sh` | Initialization wrapper (waits for DB, runs setup) |
| `wp-setup.sh` | Configures WordPress, creates pages, users |
| `wp-reset.sh` | Undoes wp-setup (removes pages, users) |
| `wp-clean.sh` | Complete cleanup (all posts, users, terms) |

**Manual execution**:
```bash
# Setup
npm run wp:setup

# Reset
npm run wp:reset
```

## Service Details

### Database (MariaDB)

**Image**: `mariadb:10.11`

**Configuration** (via `.env`):
```env
DB_NAME=talampaya
DB_USER=root
DB_PASSWORD=password
DB_HOST=db
```

**Access**:
```bash
# Via PhpMyAdmin
http://localhost:8082

# Via CLI
docker compose exec db mysql -u root -p
```

**Backup database**:
```bash
npm run wp:backup
# or
docker compose exec wp wp db export backup-$(date +%Y%m%d).sql
```

### WordPress (PHP-FPM)

**Image**: Custom (based on `wordpress:php8.4-fpm`)

**Includes**:
- PHP 8.4 with extensions (gd, mysqli, zip, intl, etc.)
- WP-CLI
- Xdebug 3.4.2
- Composer

**Environment** (`.env`):
```env
WORDPRESS_DB_HOST=db
WORDPRESS_DB_NAME=talampaya
WORDPRESS_DB_USER=root
WORDPRESS_DB_PASSWORD=password
WORDPRESS_ADMIN_USER=admin
WORDPRESS_ADMIN_PASS=admin
WORDPRESS_ADMIN_EMAIL=admin@talampaya.local
```

**Execute WP-CLI**:
```bash
docker compose exec wp wp plugin list
```

### Nginx

**Image**: `nginx:alpine`

**Features**:
- SSL with mkcert (trusted certificates)
- Reverse proxy to PHP-FPM
- Gzip compression
- Static file serving

**Configuration**: `/docker/nginx/default.conf`

**Access**:
- HTTP: http://talampaya.local
- HTTPS: https://talampaya.local

### Node

**Image**: `node:latest`

**Purpose**:
- Runs Gulp build system
- Watches files for changes
- Compiles SCSS and JavaScript

**Runs**: `npm run dev` (automatically via docker-compose)

### PhpMyAdmin

**Image**: `phpmyadmin/phpmyadmin`

**Access**: http://localhost:8082

**Credentials**:
- Server: `db`
- Username: `root`
- Password: (from `.env` `DB_PASSWORD`)

### Mailhog

**Image**: `mailhog/mailhog`

**Purpose**: Catches all outgoing emails from WordPress

**Access**:
- Web UI: http://localhost:8025
- SMTP: `mailhog:1025`

**WordPress configuration** (automatic):
```php
define('SMTP_HOST', 'mailhog');
define('SMTP_PORT', 1025);
```

### Redis

**Image**: `redis:7-alpine`

**Purpose**: Object cache for WordPress

**Configuration** (if using Redis Object Cache plugin):
```php
define('WP_REDIS_HOST', 'cache');
define('WP_REDIS_PORT', 6379);
```

## Common Operations

### Starting Services

```bash
# Start all services
npm start
# or
docker compose up -d

# Start specific service
docker compose up -d wp
```

### Stopping Services

```bash
# Stop all services
docker compose down

# Stop specific service
docker compose stop wp
```

### Rebuilding Services

```bash
# Rebuild all services
docker compose up -d --build

# Rebuild specific service
docker compose up -d --build wp
# or
npm run docker:rebuild:node
```

### Viewing Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f wp
docker compose logs -f nginx
docker compose logs -f node
```

### Accessing Containers

```bash
# WordPress container
docker compose exec wp bash

# Database container
docker compose exec db bash

# Node container
docker compose exec node sh
```

## Troubleshooting

**Services won't start**:
```bash
# Check status
docker compose ps

# Check logs
docker compose logs

# Rebuild
docker compose down
docker compose up -d --build
```

**Permission errors**:
```bash
npm run docker:permissions
```

**Database connection errors**:
```bash
# Verify database is running
docker compose ps db

# Check database logs
docker compose logs db

# Restart database
docker compose restart db
```

**Port conflicts**:
If ports 80, 443, 3306, 8082, 8025 are already in use, edit `docker-compose.yml` to use different ports.

---

For related documentation:
- [DEVELOPMENT.md](DEVELOPMENT.md) - Development workflow
- [BUILD-SYSTEM.md](BUILD-SYSTEM.md) - Build system
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Detailed troubleshooting
# Troubleshooting

Common issues and solutions for Talampaya development.

## Table of Contents

- [Troubleshooting](#troubleshooting)
  - [Table of Contents](#table-of-contents)
  - [Build Issues](#build-issues)
    - [Gulp Not Watching Files](#gulp-not-watching-files)
    - [Missing Dependencies](#missing-dependencies)
    - [Permission Errors](#permission-errors)
    - [Webpack Build Errors](#webpack-build-errors)
  - [Database Issues](#database-issues)
    - [Database Connection Errors](#database-connection-errors)
    - [WordPress Installation Fails](#wordpress-installation-fails)
    - [Lost Database Data](#lost-database-data)
  - [Docker Issues](#docker-issues)
    - [Services Won't Start](#services-wont-start)
    - [Port Conflicts](#port-conflicts)
    - [Container Exits Immediately](#container-exits-immediately)
  - [WordPress Issues](#wordpress-issues)
    - [White Screen of Death (WSOD)](#white-screen-of-death-wsod)
    - [Fatal Error: Class Not Found](#fatal-error-class-not-found)
    - [ACF Blocks Not Appearing](#acf-blocks-not-appearing)
  - [Xdebug Issues](#xdebug-issues)
    - [Xdebug Not Connecting](#xdebug-not-connecting)
  - [Clear Caches](#clear-caches)
    - [WordPress Object Cache](#wordpress-object-cache)
    - [Browser Sync Cache](#browser-sync-cache)
    - [Redis Cache](#redis-cache)
    - [Timber Cache](#timber-cache)
  - [Performance Issues](#performance-issues)
    - [Slow Page Load](#slow-page-load)
    - [Slow Docker Performance (Mac)](#slow-docker-performance-mac)
  - [WPML Issues](#wpml-issues)
    - [Missing Translations](#missing-translations)
    - [Language Switcher Not Working](#language-switcher-not-working)

## Build Issues

### Gulp Not Watching Files

**Symptoms**: Changes to SCSS/JS/Twig files not triggering rebuild

**Solutions**:

1. **Check Docker volume mounts**:
   ```bash
   docker compose ps
   docker compose logs node
   ```

2. **Ensure Node container is running**:
   ```bash
   docker compose ps node
   # Should show "Up"
   ```

3. **Restart Node service**:
   ```bash
   docker compose restart node
   ```

4. **Check gulpfile.js watch configuration**:
   - Ensure polling is enabled for Docker compatibility
   - Check file paths in watch tasks

5. **Clear build directory and rebuild**:
   ```bash
   rm -rf build/wp-content/themes/talampaya
   npm run dev
   ```

### Missing Dependencies

**Symptoms**: `Module not found`, `Class not found`, etc.

**Solutions**:

**PHP dependencies**:
```bash
npm run docker:composer:update
# or
docker compose exec wp composer install
```

**Node dependencies**:
```bash
rm -rf node_modules package-lock.json
npm install
```

**WordPress plugins** (if using Composer for plugins):
```bash
docker compose exec wp composer update
```

### Permission Errors

**Symptoms**: `EACCES: permission denied`, `unable to create directory`

**Solutions**:

1. **Fix file permissions** (fastest):
   ```bash
   npm run docker:permissions
   ```

2. **Manual fix**:
   ```bash
   sudo chown -R $(whoami):$(whoami) build/
   sudo chmod -R 755 build/
   ```

3. **Docker user mapping** (edit `docker-compose.yml`):
   ```yaml
   wp:
     user: "${UID}:${GID}"
   ```

   Then in `.env`:
   ```env
   UID=1000
   GID=1000
   ```

### Webpack Build Errors

**Symptoms**: `ERROR in ./src/...`, syntax errors in JavaScript

**Solutions**:

1. **Check Babel configuration** (`.babelrc` or `babel.config.js`)

2. **Clear Webpack cache**:
   ```bash
   rm -rf node_modules/.cache
   npm run dev
   ```

3. **Check for syntax errors** in JS files:
   ```bash
   npm run lint:js
   ```

4. **Update Webpack dependencies**:
   ```bash
   npm update webpack webpack-cli babel-loader
   ```

## Database Issues

### Database Connection Errors

**Symptoms**: `Error establishing database connection`

**Solutions**:

1. **Verify database service is running**:
   ```bash
   docker compose ps db
   # Should show "Up"
   ```

2. **Check database logs**:
   ```bash
   docker compose logs db
   ```

3. **Verify database credentials** in `.env`:
   ```env
   DB_NAME=talampaya
   DB_USER=root
   DB_PASSWORD=password
   DB_HOST=db
   ```

4. **Restart database**:
   ```bash
   docker compose restart db
   ```

5. **Wait for database to be ready**:
   ```bash
   docker compose exec db mysql -u root -ppassword -e "SHOW DATABASES;"
   ```

### WordPress Installation Fails

**Symptoms**: WordPress setup doesn't complete, blank page

**Solutions**:

1. **Run setup manually**:
   ```bash
   npm run wp:setup
   ```

2. **Check WP-CLI errors**:
   ```bash
   docker compose exec wp wp core is-installed
   ```

3. **Reset and reinstall**:
   ```bash
   npm run wp:reset
   npm run wp:setup
   ```

4. **Check `wp-config.php`**:
   - Verify database constants
   - Check file permissions

### Lost Database Data

**Symptoms**: All posts/pages/settings disappeared after restart

**Solutions**:

1. **Check if volume still exists**:
   ```bash
   docker volume ls | grep talampaya
   ```

2. **Restore from backup**:
   ```bash
   npm run wp:backup  # If you have a backup
   docker compose exec wp wp db import backup.sql
   ```

3. **Prevent future data loss**:
   - Always use named volumes in `docker-compose.yml`
   - Regularly backup database:
     ```bash
     npm run wp:backup
     ```

## Docker Issues

### Services Won't Start

**Symptoms**: `docker compose up` fails, containers exit

**Solutions**:

1. **Check Docker status**:
   ```bash
   docker compose ps
   docker compose logs
   ```

2. **Rebuild services**:
   ```bash
   docker compose down
   docker compose up -d --build
   ```

3. **Check for port conflicts** (see below)

4. **Remove and recreate**:
   ```bash
   docker compose down -v  # WARNING: Removes volumes
   docker compose up -d
   ```

### Port Conflicts

**Symptoms**: `Bind for 0.0.0.0:80 failed: port is already allocated`

**Solutions**:

1. **Find what's using the port**:
   ```bash
   sudo lsof -i :80
   sudo lsof -i :443
   sudo lsof -i :3306
   ```

2. **Stop conflicting service**:
   ```bash
   sudo systemctl stop apache2
   sudo systemctl stop mysql
   ```

3. **Change ports** in `docker-compose.yml`:
   ```yaml
   nginx:
     ports:
       - "8080:80"
       - "8443:443"
   ```

### Container Exits Immediately

**Symptoms**: Container starts then immediately exits

**Solutions**:

1. **Check container logs**:
   ```bash
   docker compose logs [service-name]
   ```

2. **Common issues**:
   - Missing environment variables in `.env`
   - Syntax errors in `docker-compose.yml`
   - Missing or corrupted Dockerfile

3. **Rebuild from scratch**:
   ```bash
   docker compose down
   docker compose build --no-cache
   docker compose up -d
   ```

## WordPress Issues

### White Screen of Death (WSOD)

**Symptoms**: Blank white page, no error message

**Solutions**:

1. **Enable debugging** in `.env`:
   ```env
   WP_DEBUG=true
   WP_DEBUG_LOG=true
   WP_DEBUG_DISPLAY=true
   ```

2. **Check error log**:
   ```bash
   docker compose exec wp tail -f /var/www/html/wp-content/debug.log
   ```

3. **Common causes**:
   - PHP fatal error
   - Memory limit exceeded
   - Plugin conflict

4. **Increase PHP memory**:
   ```php
   // In wp-config.php
   define('WP_MEMORY_LIMIT', '256M');
   ```

### Fatal Error: Class Not Found

**Symptoms**: `Fatal error: Class 'App\...' not found`

**Solutions**:

1. **Regenerate autoloader**:
   ```bash
   docker compose exec wp composer dump-autoload
   ```

2. **Check namespace** matches directory structure (PSR-4)

3. **Verify class file** exists and is named correctly

4. **Check `composer.json` autoload section**:
   ```json
   {
     "autoload": {
       "psr-4": {
         "App\\": "wp-content/themes/talampaya/src/"
       }
     }
   }
   ```

### ACF Blocks Not Appearing

**Symptoms**: Custom blocks don't show in Gutenberg editor

**Solutions**:

1. **Verify block JSON** exists in `/src/theme/blocks/`

2. **Check block registration** in ACF settings

3. **Sync ACF field groups**:
   - Go to WP Admin → Custom Fields → Tools → Sync
   - Import from `/acf-json/`

4. **Clear Gutenberg cache**:
   ```bash
   docker compose exec wp wp cache flush
   ```

5. **Verify BlockRenderer** is properly configured

## Xdebug Issues

### Xdebug Not Connecting

**Symptoms**: Breakpoints not hit, no debugging session

**Solutions**:

1. **Verify Xdebug is enabled** in `.env`:
   ```env
   XDEBUG_MODE=develop,debug
   ```

2. **Restart WordPress container**:
   ```bash
   docker compose restart wp
   ```

3. **Check IDE configuration**:
   - Port: 9003
   - IDE key: PHPSTORM
   - Path mappings: `/var/www/html` → `/path/to/talampaya/build`

4. **Test Xdebug**:
   ```bash
   docker compose exec wp php -v
   # Should show "with Xdebug v3.4.2"
   ```

5. **Check Xdebug log**:
   ```bash
   docker compose exec wp cat /tmp/xdebug.log
   ```

## Clear Caches

### WordPress Object Cache

```bash
docker compose exec wp wp cache flush --allow-root
```

### Browser Sync Cache

```bash
# Restart Gulp
npm run dev
```

### Redis Cache

```bash
docker compose restart cache
```

### Timber Cache

```bash
docker compose exec wp wp transient delete --all --allow-root
```

## Performance Issues

### Slow Page Load

**Causes**:
- Too many database queries
- Unoptimized images
- No caching
- Large CSS/JS files

**Solutions**:

1. **Enable Query Monitor plugin** to identify slow queries

2. **Optimize images**:
   ```bash
   npm run build  # Includes image optimization
   ```

3. **Enable Redis object cache**:
   - Install Redis Object Cache plugin
   - Configure in `wp-config.php`:
     ```php
     define('WP_REDIS_HOST', 'cache');
     define('WP_REDIS_PORT', 6379);
     ```

4. **Minify assets** (production build):
   ```bash
   npm run build
   ```

### Slow Docker Performance (Mac)

**Cause**: Docker volume mounting is slow on macOS

**Solutions**:

1. **Use Docker Desktop with VirtioFS** (latest versions)

2. **Use `:cached` or `:delegated` flags** in `docker-compose.yml`:
   ```yaml
   volumes:
     - ./build:/var/www/html:cached
   ```

3. **Exclude node_modules** from volume mounts

## WPML Issues

### Missing Translations

**Solutions**:

1. **Scan theme for strings**:
   - WPML → Theme and Plugin Localization
   - Scan theme files

2. **Check string registration**:
   ```php
   do_action('wpml_register_single_string', 'theme', 'string_name', 'String Value');
   ```

3. **Verify language is active**:
   - WPML → Languages
   - Check language status

### Language Switcher Not Working

**Solutions**:

1. **Check WPML configuration**:
   - WPML → Languages → Language switcher

2. **Verify template code**:
   ```php
   do_action('wpml_add_language_selector');
   ```

3. **Clear WPML cache**:
   ```bash
   docker compose exec wp wp cache flush
   ```

---

For related documentation:
- [DEVELOPMENT.md](DEVELOPMENT.md) - Development workflow
- [DOCKER.md](DOCKER.md) - Docker environment
- [BUILD-SYSTEM.md](BUILD-SYSTEM.md) - Build system
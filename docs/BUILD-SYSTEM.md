# Build System

Complete guide to the Gulp + Webpack build pipeline, asset compilation, and deployment.

## Table of Contents

- [Build System](#build-system)
  - [Table of Contents](#table-of-contents)
  - [Overview](#overview)
  - [Gulp Build Pipeline](#gulp-build-pipeline)
    - [Development Mode (`npm run dev`)](#development-mode-npm-run-dev)
    - [Production Mode (`npm run build`)](#production-mode-npm-run-build)
  - [Webpack Configuration](#webpack-configuration)
    - [Entry Points](#entry-points)
    - [Loaders](#loaders)
  - [Asset Organization](#asset-organization)
    - [SCSS](#scss)
    - [JavaScript](#javascript)
    - [Images \& Fonts](#images--fonts)
  - [Environment Variables](#environment-variables)
  - [Browser Sync](#browser-sync)
  - [File Watching](#file-watching)
  - [Build Output](#build-output)
  - [Customization](#customization)

## Overview

Talampaya uses a sophisticated build system combining:
- **Gulp 5.x** - Task runner and orchestration
- **Webpack 5.x** - JavaScript bundling and transpilation
- **Babel** - ES6+ transpilation
- **Sass** - SCSS compilation
- **PostCSS** - CSS optimization and autoprefixing
- **Browser Sync** - Live reload and proxy server

## Gulp Build Pipeline

### Development Mode (`npm run dev`)

1. **Copy theme files** (with name replacement)
2. **Copy fonts/images**
3. **Compile SCSS → CSS** (with sourcemaps)
4. **Bundle JavaScript via Webpack**
5. **Copy plugins, languages, ACF JSON**
6. **Copy Pattern Lab files and mockups**
7. **Start Browser Sync proxy server**
8. **Watch for changes** (polling-based for Docker compatibility)

**Output**: `/build/wp-content/themes/talampaya/`

### Production Mode (`npm run build`)

Same as dev mode with additional optimizations:
- **Minification** of CSS and JavaScript
- **No sourcemaps**
- **Image optimization**
- **Output to ZIP file**: `/dist/talampaya.zip`

## Webpack Configuration

**Location**: `/webpack.config.js`

### Entry Points

| Entry | Source | Output | Purpose |
|-------|--------|--------|---------|
| `main.js` | `src/theme/assets/scripts/main.js` | `main.js` | Pattern Lab scripts |
| `scripts.js` | `src/theme/assets/scripts/scripts.js` | `scripts.js` | Theme frontend |
| `backend.js` | `src/theme/assets/scripts/backend.js` | `backend.js` | Admin area |

### Loaders

- **Babel Loader** - Transpiles ES6+ to ES5
  - Preset: `@babel/preset-env`
  - Supports async/await, arrow functions, classes, etc.

**Example webpack.config.js**:
```javascript
module.exports = {
  entry: {
    main: './src/theme/assets/scripts/main.js',
    scripts: './src/theme/assets/scripts/scripts.js',
    backend: './src/theme/assets/scripts/backend.js'
  },
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname, 'build/wp-content/themes/talampaya/assets/scripts')
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env']
          }
        }
      }
    ]
  }
};
```

## Asset Organization

### SCSS

**Source**: `/src/theme/assets/styles/`

**Compilation**:
- Frontend: `main.scss` → `style.css`
- Admin: `backend-*.scss` → `backend-styles.css`

**Features**:
- Sass/SCSS syntax
- Autoprefixer (via PostCSS)
- CSS minification in production
- Sourcemaps in development

**Example structure**:
```
assets/styles/
├── main.scss              # Frontend entry point
├── backend-main.scss      # Admin entry point
├── _variables.scss        # Variables
├── _mixins.scss           # Mixins
├── base/                  # Base styles
├── components/            # Component styles
├── layouts/               # Layout styles
└── utilities/             # Utility classes
```

### JavaScript

**Source**: `/src/theme/assets/scripts/`

**Bundling**:
- Webpack bundles all imports
- Babel transpilation to ES5
- Tree shaking in production
- Code splitting supported

**Example structure**:
```
assets/scripts/
├── scripts.js             # Frontend entry
├── backend.js             # Admin entry
├── main.js                # Pattern Lab entry
├── modules/               # ES6 modules
├── components/            # Component scripts
└── utils/                 # Utility functions
```

### Images & Fonts

**Images**:
- Source: `/src/theme/assets/images/`
- Output: `/build/.../assets/images/`
- Optimization in production

**Fonts**:
- Source: `/src/theme/assets/fonts/`
- Output: `/build/.../assets/fonts/`
- Direct copy (no processing)

## Environment Variables

Required in `.env` file:

```env
# Application
APP_NAME=talampaya              # Theme name (used for file replacement)
DOMAIN=talampaya.local          # Development domain
PROTOCOL=https                  # http or https
NODE_ENV=development            # development or production

# Build
BROWSERSYNC_PROXY=nginx         # Browser Sync proxy target
BROWSERSYNC_PORT=3000           # Browser Sync port
```

## Browser Sync

Browser Sync provides:
- **Live reload** on file changes
- **CSS injection** without full reload
- **Synchronized browsing** across devices
- **Network access** for mobile testing

**Configuration** (in `gulpfile.js`):
```javascript
browserSync.init({
  proxy: 'nginx',  // Docker service name
  port: 3000,
  open: false,
  notify: false,
  files: [
    'build/wp-content/themes/talampaya/**/*.css',
    'build/wp-content/themes/talampaya/**/*.js',
    'build/wp-content/themes/talampaya/**/*.twig'
  ]
});
```

**Access**:
- Local: http://localhost:3000
- Network: http://192.168.x.x:3000 (for mobile testing)

## File Watching

Gulp watches these files for changes:

| Files | Action |
|-------|--------|
| `src/theme/assets/styles/**/*.scss` | Recompile SCSS |
| `src/theme/assets/scripts/**/*.js` | Rebuild with Webpack |
| `src/theme/views/**/*.twig` | Copy to build |
| `src/theme/src/**/*.php` | Copy to build |
| `src/theme/blocks/**/*.json` | Copy to build |
| `src/theme/acf-json/**/*.json` | Copy to build |

**Polling mode**: Used for Docker compatibility (monitors file changes across mounted volumes).

## Build Output

### Development Build

**Location**: `/build/wp-content/themes/talampaya/`

**Characteristics**:
- Unminified assets
- Sourcemaps included
- Fast compilation
- Larger file sizes

### Production Build

**Location**: `/dist/talampaya.zip`

**Characteristics**:
- Minified CSS and JS
- No sourcemaps
- Optimized images
- Smaller file sizes
- Ready for deployment

**Contents of ZIP**:
```
talampaya.zip
└── talampaya/
    ├── functions.php
    ├── style.css
    ├── src/
    ├── views/
    ├── assets/
    ├── blocks/
    ├── acf-json/
    ├── languages/
    └── vendor/
```

## Customization

### Adding a New SCSS Entry Point

1. **Create file**: `src/theme/assets/styles/new-entry.scss`
2. **Add Gulp task** in `gulpfile.js`:
   ```javascript
   function compileNewEntry() {
     return gulp.src('src/theme/assets/styles/new-entry.scss')
       .pipe(sass())
       .pipe(gulp.dest('build/wp-content/themes/talampaya/assets/styles/'));
   }
   ```
3. **Add to watch**:
   ```javascript
   gulp.watch('src/theme/assets/styles/new-entry.scss', compileNewEntry);
   ```

### Adding a New Webpack Entry

1. **Update `webpack.config.js`**:
   ```javascript
   module.exports = {
     entry: {
       // ... existing entries
       'new-bundle': './src/theme/assets/scripts/new-bundle.js'
     }
   };
   ```

2. **Enqueue in WordPress**:
   ```php
   wp_enqueue_script(
     'new-bundle',
     get_template_directory_uri() . '/assets/scripts/new-bundle.js',
     [],
     '1.0.0',
     true
   );
   ```

---

For related documentation:
- [DEVELOPMENT.md](DEVELOPMENT.md) - Development workflow
- [DOCKER.md](DOCKER.md) - Docker environment
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md#build-issues) - Build troubleshooting
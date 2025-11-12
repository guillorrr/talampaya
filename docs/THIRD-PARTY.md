# Third-Party Integrations

Complete guide to third-party plugins and services integrated in Talampaya.

## Table of Contents

- [Third-Party Integrations](#third-party-integrations)
  - [Table of Contents](#table-of-contents)
  - [Overview](#overview)
  - [Required Plugins](#required-plugins)
    - [Timber](#timber)
    - [Advanced Custom Fields Pro](#advanced-custom-fields-pro)
    - [Yoast SEO](#yoast-seo)
    - [WPML](#wpml)
  - [Plugin Integration System](#plugin-integration-system)
    - [Plugin Interface](#plugin-interface)
    - [Creating a Plugin Integration](#creating-a-plugin-integration)
  - [License Management](#license-management)
  - [Plugin Configuration](#plugin-configuration)
    - [ACF Pro Configuration](#acf-pro-configuration)
    - [WPML Configuration](#wpml-configuration)
  - [Auto-Discovery](#auto-discovery)

## Overview

Talampaya integrates several third-party plugins to provide enterprise-level functionality. All plugins are managed via **Composer** and follow a **plugin integration pattern** for centralized management.

## Required Plugins

### Timber

**Version**: ^2.1
**Purpose**: Twig templating engine for WordPress

**Installation** (via Composer):
```json
{
  "require": {
    "timber/timber": "^2.1"
  }
}
```

**Documentation**: https://timber.github.io/docs/

**Key Features**:
- Clean separation of logic and presentation
- Twig template syntax
- Powerful template inheritance
- Built-in caching

**Usage**:
```php
$context = Timber::context();
$context['posts'] = Timber::get_posts();
Timber::render('pages/archive.twig', $context);
```

See [TIMBER-TWIG.md](TIMBER-TWIG.md) for complete documentation.

### Advanced Custom Fields Pro

**Version**: 6.6.0
**Purpose**: Advanced custom field management and Gutenberg blocks

**Installation** (via Composer):
```json
{
  "repositories": [
    {
      "type": "composer",
      "url": "https://connect.advancedcustomfields.com"
    }
  ],
  "require": {
    "wpengine/advanced-custom-fields-pro": "6.6.0"
  }
}
```

**License**: Required (add to `.env`)
```env
ACF_PRO_KEY=your-license-key-here
```

**Documentation**: https://www.advancedcustomfields.com/resources/

**Key Features**:
- Custom field groups
- Gutenberg block creation
- Flexible content fields
- Repeater fields
- Relationship fields
- Gallery fields

**Usage**:
```php
// Get field value
$value = get_field('field_name');

// Get field in Twig
{{ fields.field_name }}
```

See [ACF-BLOCKS.md](ACF-BLOCKS.md) for complete documentation.

### Yoast SEO

**Version**: Latest
**Purpose**: SEO optimization

**Installation** (via Composer):
```json
{
  "require": {
    "yoast/wordpress-seo": "*"
  }
}
```

**Documentation**: https://yoast.com/wordpress/plugins/seo/

**Key Features**:
- Title and meta description templates
- XML sitemaps
- Breadcrumbs
- Schema.org structured data
- Readability analysis

**Integration**:
```php
// In templates
<?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<p id="breadcrumbs">','</p>');
} ?>
```

### WPML

**Version**: Latest
**Purpose**: Multilingual support

**Installation** (via Composer):
```json
{
  "repositories": [
    {
      "type": "composer",
      "url": "https://wpml.org/repositories/wpml-pro/"
    }
  ],
  "require": {
    "wpml/sitepress-multilingual-cms": "*",
    "wpml/wpml-string-translation": "*",
    "wpml/wp-seo-multilingual": "*",
    "wpml/acfml": "*",
    "wpml/wpml-import": "*",
    "wpml/wpml-all-import": "*"
  }
}
```

**License**: Required (add to `.env`)
```env
WPML_USER_ID=your-user-id
WPML_KEY=your-subscription-key
```

**Documentation**: https://wpml.org/documentation/

**Key Features**:
- Multiple language management
- Translation management
- Multilingual content types
- Language switcher
- SEO for multilingual sites
- ACF field translation

**Components**:
- `sitepress-multilingual-cms` - Core WPML
- `wpml-string-translation` - String translation
- `wp-seo-multilingual` - Yoast SEO integration
- `acfml` - ACF integration
- `wpml-import` - Import/export
- `wpml-all-import` - WP All Import integration

**Configuration** (in `wp-config.php`):
```php
define('ICL_LANGUAGE_CODE', 'en'); // Default language
```

## Plugin Integration System

Talampaya uses a **plugin integration pattern** to manage third-party plugins.

### Plugin Interface

**Location**: `/src/Core/Plugins/PluginInterface.php`

```php
<?php

namespace App\Core\Plugins;

interface PluginInterface
{
    /**
     * Get plugin name
     */
    public function getName(): string;

    /**
     * Check if WordPress plugin is active
     */
    public function shouldLoad(): bool;

    /**
     * Initialize plugin integration
     */
    public function initialize(): void;

    /**
     * Get required WordPress plugins
     */
    public function getRequiredPlugins(): array;
}
```

### Creating a Plugin Integration

**Location**: `/src/Core/Plugins/Integration/YourPluginIntegration.php`

**Example**:
```php
<?php

namespace App\Core\Plugins\Integration;

use App\Core\Plugins\PluginInterface;

class YourPluginIntegration implements PluginInterface
{
    public function getName(): string
    {
        return 'Your Plugin';
    }

    public function shouldLoad(): bool
    {
        // Check if WordPress plugin is active
        return class_exists('YourPlugin\Main');
    }

    public function initialize(): void
    {
        // Add hooks, filters, configuration
        add_action('init', [$this, 'configure']);
    }

    public function getRequiredPlugins(): array
    {
        return ['your-plugin/your-plugin.php'];
    }

    public function configure(): void
    {
        // Plugin-specific configuration
    }
}
```

**Auto-discovered by PluginManager** in `/src/Core/Plugins/Integration/`

## License Management

**Location**: `.env` file

**Required licenses**:
```env
# ACF Pro
ACF_PRO_KEY=your-acf-pro-license-key

# WPML
WPML_USER_ID=your-wpml-user-id
WPML_KEY=your-wpml-subscription-key
```

**Composer authentication** (automatically configured):
```json
{
  "config": {
    "allow-plugins": {
      "composer/installers": true
    }
  },
  "extra": {
    "installer-paths": {
      "build/wp-content/plugins/{$name}/": ["type:wordpress-plugin"],
      "build/wp-content/themes/{$name}/": ["type:wordpress-theme"]
    }
  }
}
```

## Plugin Configuration

### ACF Pro Configuration

**Activate license**:
```php
// In wp-config.php (auto-configured via .env)
define('ACF_PRO_LICENSE', getenv('ACF_PRO_KEY'));
```

**Hide ACF admin menu** (production):
```php
add_filter('acf/settings/show_admin', '__return_false');
```

**Set JSON save point**:
```php
add_filter('acf/settings/save_json', function() {
    return get_stylesheet_directory() . '/acf-json';
});
```

**Set JSON load point**:
```php
add_filter('acf/settings/load_json', function($paths) {
    $paths[] = get_stylesheet_directory() . '/acf-json';
    return $paths;
});
```

### WPML Configuration

**Activate license**:
```php
// In wp-config.php (auto-configured via .env)
define('WPML_USER_ID', getenv('WPML_USER_ID'));
define('WPML_KEY', getenv('WPML_KEY'));
```

**Configure languages** (admin):
1. WPML → Languages → Language pairs
2. Add languages
3. Configure URL format

**String translation**:
```php
// Register translatable string
do_action('wpml_register_single_string', 'theme', 'button_text', 'Click Me');

// Get translated string
$translated = apply_filters('wpml_translate_single_string', 'Click Me', 'theme', 'button_text');
```

**In Twig**:
```twig
{{ function('icl_t', 'theme', 'button_text', 'Click Me') }}
```

## Auto-Discovery

**How it works**:

1. `PluginManager` scans `/src/Core/Plugins/Integration/`
2. Finds all classes implementing `PluginInterface`
3. Checks `shouldLoad()` for each plugin
4. Calls `initialize()` if plugin is active

**Benefits**:
- No manual registration
- Centralized plugin management
- Easy to add new integrations
- Conditional loading based on active plugins

**Example**: `/src/Core/Plugins/PluginManager.php`
```php
public function loadPlugins(): void
{
    $plugins = $this->discoverPlugins();

    foreach ($plugins as $plugin) {
        if ($plugin->shouldLoad()) {
            $plugin->initialize();
        }
    }
}
```

---

For related documentation:
- [ACF-BLOCKS.md](ACF-BLOCKS.md) - ACF block system
- [ARCHITECTURE.md](ARCHITECTURE.md#manager-pattern) - Manager pattern details
- [DEVELOPMENT.md](DEVELOPMENT.md) - Development workflow
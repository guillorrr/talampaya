# Application Layer

Complete guide to the Application Layer patterns in Talampaya: Controllers, Services, Models, Traits, and Helpers.

## Table of Contents

- [Application Layer](#application-layer)
  - [Table of Contents](#table-of-contents)
  - [Overview](#overview)
  - [Controllers](#controllers)
    - [What are Controllers?](#what-are-controllers)
    - [Controller Responsibilities](#controller-responsibilities)
    - [DefaultController Example](#defaultcontroller-example)
    - [Creating Custom Controllers](#creating-custom-controllers)
    - [Best Practices for Controllers](#best-practices-for-controllers)
  - [Services](#services)
    - [What are Services?](#what-are-services)
    - [Service Responsibilities](#service-responsibilities)
    - [AbstractImportService Example](#abstractimportservice-example)
    - [Creating Custom Services](#creating-custom-services)
    - [Best Practices for Services](#best-practices-for-services)
  - [Models](#models)
    - [What are Models?](#what-are-models)
    - [AbstractPost Pattern](#abstractpost-pattern)
    - [ProjectPost Example](#projectpost-example)
    - [Creating Custom Models](#creating-custom-models)
    - [Best Practices for Models](#best-practices-for-models)
  - [Traits](#traits)
    - [What are Traits?](#what-are-traits)
    - [ImportDataTrait Example](#importdatatrait-example)
    - [CsvProcessorTrait Example](#csvprocessortrait-example)
    - [Creating Custom Traits](#creating-custom-traits)
    - [Traits vs Inheritance](#traits-vs-inheritance)
    - [Best Practices for Traits](#best-practices-for-traits)
  - [Helpers](#helpers)
    - [What are Helpers?](#what-are-helpers)
    - [Existing Helpers](#existing-helpers)
    - [AcfHelper Example](#acfhelper-example)
    - [Creating Custom Helpers](#creating-custom-helpers)
    - [Helpers vs Services](#helpers-vs-services)
    - [Best Practices for Helpers](#best-practices-for-helpers)
  - [Template Files](#template-files)
    - [Keep Template Files Simple](#keep-template-files-simple)
    - [Single Post Templates](#single-post-templates)
    - [Archive Templates](#archive-templates)
    - [Custom Post Type Templates](#custom-post-type-templates)
  - [Decision Matrix](#decision-matrix)
  - [Integration Patterns](#integration-patterns)
  - [Complete Examples](#complete-examples)
    - [Example 1: Product Feature](#example-1-product-feature)
    - [Example 2: Import System](#example-2-import-system)

## Overview

The Application Layer in Talampaya separates concerns using five key patterns:

| Pattern | Purpose | Location | Type |
|---------|---------|----------|------|
| **Controllers** | Prepare context for templates | `/Inc/Controllers/` | Static/Stateless |
| **Services** | Handle business logic | `/Inc/Services/` | Stateful instances |
| **Models** | Extend Timber\Post with custom methods | `/Inc/Models/` | Extends Timber\Post |
| **Traits** | Reusable code across classes | `/Inc/Traits/` | PHP Traits |
| **Helpers** | Static utility functions | `/Inc/Helpers/` | Static only |

**Key principle**: WordPress template files (`single.php`, `archive.php`, etc.) should be **minimal** and only act as entry points. All logic belongs in the Application Layer.

## Controllers

### What are Controllers?

Controllers prepare the context data that will be passed to Twig templates. They sit between WordPress template files and Twig views.

**Location**: `/src/theme/src/Inc/Controllers/`

**Key concept**: Controllers transform WordPress data into structured context arrays for templates.

### Controller Responsibilities

Controllers should:
- Fetch data from WordPress (posts, terms, options)
- Transform data into template-friendly structures
- Load mock/demo data (if applicable)
- Merge different data sources
- Return context arrays

Controllers should NOT:
- Handle form submissions
- Process imports/exports
- Contain heavy business logic
- Directly interact with external APIs (use Services for this)

### DefaultController Example

**Location**: `src/Inc/Controllers/DefaultController.php:1`

The `DefaultController` provides context methods for standard WordPress templates:

```php
class DefaultController
{
    // Front page context
    public static function get_front_page_context($context = []): array
    {
        $data = self::load_json_data("data", []);

        // Get featured posts
        $featured_posts = Timber::get_posts([
            'posts_per_page' => 4,
        ])->to_array();

        if (!empty($featured_posts)) {
            $data['hero'] = array_shift($featured_posts);
            $data['touts'] = $featured_posts;
        }

        return array_merge($context, $data);
    }

    // Single post context
    public static function get_single_context($context = []): array
    {
        $data = self::load_json_data("article", []);

        $data['title'] = get_the_title();
        $data['content'] = apply_filters('the_content', get_post_field('post_content', get_the_ID()));

        // Get related posts
        $categories = wp_get_post_categories(get_the_ID());
        $data['related_posts'] = get_posts([
            'category__in' => $categories,
            'post__not_in' => [get_the_ID()],
            'numberposts' => 3,
        ]);

        return array_merge($context, $data);
    }
}
```

### Creating Custom Controllers

**When to create a controller**:
- Custom post type needs specific context preparation
- Complex data aggregation for a template
- Reusable context logic across multiple templates

**Steps**:

1. **Create controller** in `/src/theme/src/Inc/Controllers/`:

```php
<?php

namespace App\Inc\Controllers;

use Timber\Timber;

class ProductController
{
    /**
     * Get context for single product
     */
    public static function get_single_product_context(array $context = []): array
    {
        $post = $context['post'] ?? Timber::get_post();

        $data = [
            'product' => $post,
            'price' => get_field('price', $post->ID),
            'sku' => get_field('sku', $post->ID),
            'stock' => get_field('stock', $post->ID),
            'gallery' => get_field('gallery', $post->ID),
        ];

        // Get related products
        $categories = wp_get_post_categories($post->ID);
        $related = Timber::get_posts([
            'post_type' => 'product',
            'category__in' => $categories,
            'post__not_in' => [$post->ID],
            'posts_per_page' => 4,
        ]);

        $data['related_products'] = $related;

        return array_merge($context, $data);
    }

    /**
     * Get context for product archive
     */
    public static function get_archive_product_context(array $context = []): array
    {
        $data = [
            'title' => post_type_archive_title('', false),
            'description' => get_the_archive_description(),
        ];

        // Get filter options
        $data['categories'] = Timber::get_terms([
            'taxonomy' => 'product_category',
            'hide_empty' => true,
        ]);

        return array_merge($context, $data);
    }
}
```

2. **Use in template file** (`single-product.php`):

```php
<?php
use Timber\Timber;
use App\Inc\Controllers\ProductController;

$context = Timber::context();
$templates = ['@pages/single-product.twig', '@pages/single.twig'];

Timber::render($templates, ProductController::get_single_product_context($context));
```

### Best Practices for Controllers

1. **Static methods**: Use static methods for stateless controllers
2. **Naming convention**: `get_{template_type}_context()`
3. **Accept base context**: Always accept `$context` parameter and merge
4. **Return arrays**: Always return associative arrays
5. **Type hints**: Use type hints for parameters and return types
6. **Documentation**: Document what data is added to context
7. **Keep it simple**: Heavy logic belongs in Services or Models

## Services

### What are Services?

Services handle complex business logic that doesn't fit in Controllers or Models. They perform operations, transformations, and integrations.

**Location**: `/src/theme/src/Inc/Services/`

**Key concept**: Services encapsulate reusable business logic.

### Service Responsibilities

Services should:
- Process imports/exports
- Handle external API integrations
- Perform complex calculations
- Orchestrate multi-step operations
- Validate and transform data
- Handle file uploads/processing

Services should NOT:
- Prepare template context (use Controllers)
- Render HTML directly
- Contain presentation logic

### AbstractImportService Example

**Location**: `src/Inc/Services/AbstractImportService.php:1`

Base class for import services:

```php
abstract class AbstractImportService
{
    use CsvProcessorTrait;

    /**
     * Get model class for this service
     */
    abstract public function getModelClass(): string;

    /**
     * Process specific data for this import type
     */
    abstract public function processSpecificData(array $data): array;

    /**
     * Process CSV row data
     */
    public function processData(array $row): array
    {
        $post_type = $row['post_type'] ?? $this->getPostType();

        return [
            'post_type' => $post_type,
            'custom_id' => $row['custom_id'] ?? '',
            'title' => StringUtils::talampaya_make_phrase_ucfirst($row['title'] ?? ''),
            'status' => ($row['status'] ?? '1') === '1' ? 'publish' : 'draft',
            'slug' => $row['slug'] ?? '',
            'content' => $row['content'] ?? '',
        ];
    }

    /**
     * Create or update item
     */
    public function createOrUpdate(array $data, AbstractPost $modelInstance): ?Post
    {
        $custom_id = sanitize_text_field($data['custom_id']);

        $item = $modelInstance->findByCustomId($custom_id, $data['post_type']);

        if ($item) {
            $item->updateFromData($data);
        } else {
            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($data['title']),
                'post_type' => $data['post_type'],
                'post_status' => $data['status'],
            ]);

            $item = Timber::get_post($post_id);
            $item->updateCustomFields($data);
        }

        return $item;
    }
}
```

### Creating Custom Services

**When to create a service**:
- Import/export functionality
- External API integration
- Complex data processing
- Multi-step operations
- Validation logic
- File processing

**Steps**:

```php
<?php

namespace App\Inc\Services;

use WP_Error;

/**
 * Service for product synchronization with external API
 */
class ProductSyncService
{
    private string $api_url;
    private string $api_key;

    public function __construct()
    {
        $this->api_url = get_option('product_api_url');
        $this->api_key = get_option('product_api_key');
    }

    public static function getInstance(): self
    {
        return new static();
    }

    /**
     * Sync all products from external API
     */
    public function syncAllProducts()
    {
        $products = $this->fetchProductsFromApi();

        if (is_wp_error($products)) {
            return $products;
        }

        $synced = [];
        $errors = [];

        foreach ($products as $product_data) {
            $result = $this->syncProduct($product_data);

            if (is_wp_error($result)) {
                $errors[] = $result->get_error_message();
            } else {
                $synced[] = $result;
            }
        }

        return [
            'synced' => $synced,
            'errors' => $errors,
            'total' => count($products),
        ];
    }

    private function fetchProductsFromApi()
    {
        $response = wp_remote_get($this->api_url . '/products', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data['products'] ?? [];
    }
}
```

### Best Practices for Services

1. **Single responsibility**: Each service handles one domain
2. **Static factory method**: Provide `getInstance()` method
3. **Return types**: Use strict return types
4. **Error handling**: Return `WP_Error` for failures
5. **Logging**: Log operations for debugging
6. **Testable**: Write services to be easily unit tested
7. **Dependency injection**: Accept dependencies via constructor

## Models

### What are Models?

Models extend `Timber\Post` to add custom methods and functionality for specific post types. They encapsulate post-specific logic and data access.

**Location**: `/src/theme/src/Inc/Models/`

**Key concept**: Models represent your custom post types with additional business methods.

### AbstractPost Pattern

**Location**: `src/Inc/Models/AbstractPost.php:1`

Base class for all custom post models:

```php
abstract class AbstractPost extends \Timber\Post
{
    use ImportDataTrait;

    /**
     * Get instance of model
     */
    public static function getInstance(): self
    {
        return new static();
    }

    /**
     * Get post type based on class name
     */
    public static function getPostType(): string
    {
        $class_name = (new \ReflectionClass(static::class))->getShortName();
        return Str::snake($class_name);
    }

    /**
     * Get custom ID (must be implemented by child classes)
     */
    abstract public function custom_id(): ?string;

    /**
     * Update post from data array
     */
    public function updateFromData(array $data): bool
    {
        $post_update_data = ['ID' => $this->ID];

        if (!empty($data['title'])) {
            $post_update_data['post_title'] = sanitize_text_field($data['title']);
        }
        if (!empty($data['content'])) {
            $post_update_data['post_content'] = wp_kses_post($data['content']);
        }

        if (count($post_update_data) > 1) {
            $result = wp_update_post($post_update_data, true);
            if (is_wp_error($result)) {
                return false;
            }
        }

        $this->updateCustomFields($data);

        return true;
    }

    /**
     * Update custom fields
     */
    public function updateCustomFields(array $data): bool
    {
        $exclude_keys = ['post_type', 'title', 'status', 'content', 'slug'];

        foreach ($data as $key => $value) {
            if (!empty($value) && !in_array($key, $exclude_keys)) {
                $post_type = $data['post_type'] ?? static::getPostType();
                $field_key = "field_post_type_{$post_type}_{$key}";

                update_field($field_key, $value, $this->ID);
            }
        }

        return true;
    }

    /**
     * Find post by custom ID
     */
    public function findByCustomId(string $custom_id, string $post_type = null): ?self
    {
        $post_type = $post_type ?? static::getPostType();

        $args = [
            'post_type' => $post_type,
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => "post_type_{$post_type}_custom_id",
                    'value' => sanitize_text_field($custom_id),
                    'compare' => '=',
                ],
            ],
            'posts_per_page' => 1,
        ];

        $posts = Timber::get_posts($args);
        return !empty($posts) ? $posts[0] : null;
    }
}
```

### ProjectPost Example

**Location**: `src/Inc/Models/ProjectPost.php:1`

```php
class ProjectPost extends AbstractPost
{
    /**
     * Get custom ID
     */
    public function custom_id(): ?string
    {
        return $this->meta('post_type_project_post_custom_id');
    }

    /**
     * Get project subtitle
     */
    public function subtitle(): ?string
    {
        return get_field('subtitle', $this->ID);
    }

    /**
     * Get project category
     */
    public function project_category()
    {
        $terms = wp_get_post_terms($this->ID, 'epic_taxonomy');
        return !empty($terms) ? $terms[0] : null;
    }

    /**
     * Get main image
     */
    public function main_image()
    {
        return get_field('image', $this->ID);
    }

    /**
     * Check if project is featured
     */
    public function is_featured(): bool
    {
        return (bool) get_field('featured', $this->ID);
    }

    /**
     * Update from data array (overridden to handle project-specific fields)
     */
    public function updateFromData(array $data): bool
    {
        // Call parent method
        $result = parent::updateFromData($data);

        // Process project-specific fields
        $this->processProjectSpecificFields($data);

        return $result;
    }

    protected function processProjectSpecificFields(array $data): bool
    {
        $specific_fields = [
            'subtitle' => 'field_post_type_project_post_subtitle',
            'category' => 'field_post_type_project_post_category',
            'image_main_url' => 'field_post_type_project_post_image_main_url',
        ];

        foreach ($specific_fields as $key => $field_key) {
            if (!empty($data[$key])) {
                update_field($field_key, $data[$key], $this->ID);
            }
        }

        return true;
    }
}
```

### Creating Custom Models

**Steps**:

1. **Create model** in `/src/theme/src/Inc/Models/`:

```php
<?php

namespace App\Inc\Models;

use Timber\Timber;

class Product extends AbstractPost
{
    /**
     * Get custom ID
     */
    public function custom_id(): ?string
    {
        return $this->meta('post_type_product_custom_id');
    }

    /**
     * Get price
     */
    public function getPrice(): float
    {
        return (float) get_field('price', $this->ID);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPrice(): string
    {
        return '$' . number_format($this->getPrice(), 2);
    }

    /**
     * Check if on sale
     */
    public function isOnSale(): bool
    {
        return (bool) get_field('on_sale', $this->ID);
    }

    /**
     * Get sale price
     */
    public function getSalePrice(): ?float
    {
        if (!$this->isOnSale()) {
            return null;
        }

        return (float) get_field('sale_price', $this->ID);
    }

    /**
     * Get stock status
     */
    public function getStockStatus(): string
    {
        $stock = (int) get_field('stock', $this->ID);

        if ($stock === 0) {
            return 'out_of_stock';
        } elseif ($stock < 5) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
```

2. **Use in controller**:

```php
use App\Inc\Models\Product;

$context['products'] = Timber::get_posts([
    'post_type' => 'product',
    'posts_per_page' => 10,
], Product::class); // Use custom model
```

3. **Use in templates**:

```twig
{% for product in products %}
  <h3>{{ product.title }}</h3>
  <p>Price: {{ product.formatted_price }}</p>
  {% if product.is_on_sale %}
    <p>Sale: ${{ product.sale_price }}</p>
  {% endif %}
  <p>Status: {{ product.stock_status }}</p>
{% endfor %}
```

### Best Practices for Models

1. **Extend AbstractPost**: Always extend the base class
2. **Implement custom_id()**: Required by AbstractPost
3. **Getter methods**: Use `get*()` or `is*()` naming
4. **Computed properties**: Methods for derived values
5. **Type hints**: Always specify return types
6. **Business logic only**: No presentation logic
7. **Cache when possible**: Cache expensive operations

## Traits

### What are Traits?

Traits provide reusable code that can be used across multiple classes without inheritance. They solve the single-inheritance limitation in PHP.

**Location**: `/src/theme/src/Inc/Traits/`

**Key concept**: Traits allow horizontal code reuse across unrelated classes.

### ImportDataTrait Example

**Location**: `src/Inc/Traits/ImportDataTrait.php:1`

Provides SEO data update functionality:

```php
trait ImportDataTrait
{
    /**
     * Update SEO data for post or term
     */
    public function updateSeoData(array $data): bool
    {
        if (empty($data['seo_title']) &&
            empty($data['seo_description']) &&
            empty($data['keyphrase'])) {
            return true;
        }

        $keyphrase = sanitize_text_field($data['keyphrase'] ?? '');
        $seo_title = sanitize_text_field($data['seo_title'] ?? '');
        $seo_description = sanitize_text_field($data['seo_description'] ?? '');

        // Determine if working with post or taxonomy
        $is_taxonomy = isset($data['term_id']) && isset($data['taxonomy']);

        if ($is_taxonomy) {
            $term_id = $data['term_id'];

            if (!empty($keyphrase)) {
                update_term_meta($term_id, '_yoast_wpseo_focuskw', $keyphrase);
            }
            if (!empty($seo_title)) {
                update_term_meta($term_id, '_yoast_wpseo_title', $seo_title);
            }
            if (!empty($seo_description)) {
                update_term_meta($term_id, '_yoast_wpseo_metadesc', $seo_description);
            }
        } else {
            // Working with posts
            $post_id = $this->ID;

            if (!empty($keyphrase)) {
                update_post_meta($post_id, '_yoast_wpseo_focuskw', $keyphrase);
            }
            if (!empty($seo_title)) {
                update_post_meta($post_id, '_yoast_wpseo_title', $seo_title);
            }
            if (!empty($seo_description)) {
                update_post_meta($post_id, '_yoast_wpseo_metadesc', $seo_description);
            }
        }

        return true;
    }
}
```

### CsvProcessorTrait Example

**Location**: `src/Inc/Traits/CsvProcessorTrait.php:1`

Provides CSV processing functionality:

```php
trait CsvProcessorTrait
{
    /**
     * Process CSV file
     */
    public function processCsv(
        $file_path,
        callable $row_processor,
        ?array $expected_headers = null,
        int $start_line = 1,
        int $line_count = PHP_INT_MAX
    ): array {
        if (!file_exists($file_path)) {
            return [
                'success' => 0,
                'errors' => 0,
                'message' => "File not found: " . $file_path,
                'updated_items' => [],
            ];
        }

        $success_count = 0;
        $error_count = 0;
        $updated_items = [];

        if (($handle = fopen($file_path, 'r')) !== false) {
            $headers = fgetcsv($handle, 20000, ',');

            // Validate headers
            if ($expected_headers && $headers !== $expected_headers) {
                fclose($handle);
                return [
                    'success' => 0,
                    'errors' => 1,
                    'message' => 'Invalid headers',
                    'updated_items' => [],
                ];
            }

            $line_number = 1;
            while (($row = fgetcsv($handle, 20000, ',')) !== false) {
                if ($line_number < $start_line) {
                    $line_number++;
                    continue;
                }

                if ($line_number >= $start_line + $line_count) {
                    break;
                }

                // Combine headers with row data
                $row_data = array_combine($headers, $row);

                // Process row
                $result = $row_processor($row_data);

                if ($result === true) {
                    $success_count++;
                } else {
                    $error_count++;
                }

                $line_number++;
            }

            fclose($handle);
        }

        return [
            'success' => $success_count,
            'errors' => $error_count,
            'message' => "Processed {$success_count} items, {$error_count} errors",
            'updated_items' => $updated_items,
        ];
    }
}
```

### Creating Custom Traits

**When to create a trait**:
- Functionality used across multiple unrelated classes
- Avoid code duplication
- Add optional behavior to classes
- Implement cross-cutting concerns (logging, validation)

**Steps**:

```php
<?php

namespace App\Inc\Traits;

/**
 * Adds audit logging functionality
 */
trait AuditLogTrait
{
    /**
     * Log an action
     */
    protected function logAction(string $action, array $data = []): void
    {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'action' => $action,
            'class' => get_class($this),
            'data' => $data,
        ];

        error_log('[AUDIT] ' . json_encode($log_entry));

        // Optionally save to database
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'audit_log',
            $log_entry,
            ['%s', '%d', '%s', '%s', '%s']
        );
    }

    /**
     * Get audit history
     */
    public function getAuditHistory(int $limit = 50): array
    {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}audit_log
             WHERE class = %s
             ORDER BY timestamp DESC
             LIMIT %d",
            get_class($this),
            $limit
        ), ARRAY_A);
    }
}
```

**Usage**:

```php
class Product extends AbstractPost
{
    use AuditLogTrait;

    public function updatePrice(float $new_price): void
    {
        $old_price = $this->getPrice();

        update_field('price', $new_price, $this->ID);

        $this->logAction('price_updated', [
            'old_price' => $old_price,
            'new_price' => $new_price,
        ]);
    }
}
```

### Traits vs Inheritance

| Aspect | Traits | Inheritance |
|--------|--------|-------------|
| **Relationship** | Horizontal reuse | Vertical (is-a) |
| **Multiple usage** | Multiple traits per class | Single parent |
| **Purpose** | Add functionality | Specialize behavior |
| **When to use** | Cross-cutting concerns | Related classes |
| **Example** | Logging, Validation | AbstractPost → ProductPost |

### Best Practices for Traits

1. **Single purpose**: One trait = one concern
2. **Protected methods**: Make trait methods protected when possible
3. **No state**: Avoid properties in traits
4. **Document dependencies**: Note required methods/properties
5. **Naming**: Use `*Trait` suffix
6. **Conflict resolution**: Handle method name conflicts explicitly

## Helpers

### What are Helpers?

Helpers are classes containing **static utility methods** for specific domains. They provide reusable functions that don't fit in Models or Services.

**Location**: `/src/theme/src/Inc/Helpers/`

**Key concept**: Helpers are stateless, static-only utility classes.

### Existing Helpers

Talampaya includes 15 built-in helpers:

| Helper | Purpose | Example Methods |
|--------|---------|-----------------|
| **AcfHelper** | ACF field utilities | `talampaya_create_acf_group_fields()`, `talampaya_replace_keys_from_acf_register_fields()` |
| **PostHelper** | Post operations | `talampaya_create_post()`, `talampaya_get_all_postmeta_for_post_type()` |
| **TermHelper** | Taxonomy/term utilities | `talampaya_create_category()`, `talampaya_get_term_by_slug()` |
| **AttachmentsHelper** | Media handling | `talampaya_get_attachment_id_from_url()`, `talampaya_upload_image()` |
| **AcfHelper** | ACF operations | Field group creation, key replacement |
| **AuthorHelper** | Author management | `talampaya_get_or_create_author()` |
| **BlocksHelper** | Gutenberg blocks | Block registration utilities |
| **ContentCreationHelper** | Content generation | Create posts with metadata |
| **ContentTypeHelper** | Post type utilities | Check post type, get registered types |
| **CustomHelper** | Custom utilities | Project-specific helpers |
| **JsonHelper** | JSON operations | Parse, validate JSON |
| **LanguageHelper** | i18n/WPML utilities | Translation helpers |
| **LinksHelper** | URL utilities | Generate links, sanitize URLs |
| **OptionsHelper** | WordPress options | Get/set theme options |
| **RegisterHelper** | Registration utilities | Register custom elements |
| **WordpressHelper** | WP core utilities | Common WP operations |

### AcfHelper Example

**Location**: `src/Inc/Helpers/AcfHelper.php:1`

```php
class AcfHelper
{
    /**
     * Replace keys from ACF field groups to avoid conflicts
     */
    public static function talampaya_replace_keys_from_acf_register_fields(
        array $array,
        string $key = '',
        string $type = 'block',
        bool $is_subfield = false
    ): array {
        if (isset($array['key'])) {
            $array['key'] = "group_" . $type . "_" . $key . "_" . $array['key'];
        }

        if (isset($array['fields']) && is_array($array['fields'])) {
            foreach ($array['fields'] as &$field) {
                if (!$is_subfield && isset($field['key'])) {
                    $field['key'] = "field_" . $type . "_" . $key . "_" . $field['key'];
                }

                if (!$is_subfield && isset($field['name'])) {
                    $field['name'] = $type . "_" . $key . "_" . $field['name'];
                }

                // Process subfields
                if (isset($field['sub_fields']) && is_array($field['sub_fields'])) {
                    $fake_array['fields'] = $field['sub_fields'];
                    $new_subfields = self::talampaya_replace_keys_from_acf_register_fields(
                        $fake_array,
                        $key,
                        $type,
                        true
                    );
                    $field['sub_fields'] = $new_subfields['fields'];
                }
            }
        }

        return $array;
    }

    /**
     * Create ACF field group fields
     */
    public static function talampaya_create_acf_group_fields(array $fields): array
    {
        $result = [];

        foreach ($fields as $field_config) {
            $name = $field_config[0];
            $type = $field_config[1] ?? 'text';
            $wrapper_width = $field_config[2] ?? null;
            $label = $field_config[3] ?? null;
            $required = $field_config[4] ?? 0;
            $additional_args = $field_config[5] ?? [];

            $result[] = self::talampaya_create_acf_single_field(
                $name,
                $type,
                $wrapper_width,
                $label,
                $required,
                $additional_args
            );
        }

        return $result;
    }

    /**
     * Set image on ACF custom field
     */
    public static function talampaya_set_image_on_custom_field(
        int $post_id,
        string $image_url,
        string $field_key,
        string $alt_text = ''
    ): void {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_sideload_image($image_url, $post_id, '', 'id');

        if (!is_wp_error($attachment_id)) {
            update_field($field_key, $attachment_id, $post_id);

            if ($alt_text) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
            }
        }
    }
}
```

### Creating Custom Helpers

**When to create a helper**:
- Stateless utility functions
- Domain-specific operations (dates, strings, URLs)
- WordPress API wrappers
- Reusable formatting functions

**Steps**:

```php
<?php

namespace App\Inc\Helpers;

/**
 * Date and time utilities
 */
class DateHelper
{
    /**
     * Format date for display
     */
    public static function formatDate(string $date, string $format = 'F j, Y'): string
    {
        return date_i18n($format, strtotime($date));
    }

    /**
     * Get relative time (e.g., "2 hours ago")
     */
    public static function getRelativeTime(string $date): string
    {
        return human_time_diff(strtotime($date), current_time('timestamp')) . ' ago';
    }

    /**
     * Check if date is in the past
     */
    public static function isPast(string $date): bool
    {
        return strtotime($date) < current_time('timestamp');
    }

    /**
     * Get date range string
     */
    public static function getDateRange(string $start, string $end): string
    {
        $start_formatted = self::formatDate($start, 'M j');
        $end_formatted = self::formatDate($end, 'M j, Y');

        return "{$start_formatted} - {$end_formatted}";
    }

    /**
     * Convert timezone
     */
    public static function convertTimezone(
        string $date,
        string $from_tz,
        string $to_tz
    ): string {
        $dt = new \DateTime($date, new \DateTimeZone($from_tz));
        $dt->setTimezone(new \DateTimeZone($to_tz));

        return $dt->format('Y-m-d H:i:s');
    }
}
```

**Usage**:

```php
// In controller
$context['event_date'] = DateHelper::formatDate($event->post_date);
$context['relative_time'] = DateHelper::getRelativeTime($event->post_date);
$context['is_past_event'] = DateHelper::isPast($event->post_date);
```

```twig
{# In template #}
<time>{{ event_date }}</time>
<span>{{ relative_time }}</span>
{% if is_past_event %}
  <span class="badge">Past Event</span>
{% endif %}
```

### Helpers vs Services

| Aspect | Helpers | Services |
|--------|---------|----------|
| **Methods** | Static only | Instance methods |
| **State** | Stateless | Can be stateful |
| **Purpose** | Utilities | Business logic |
| **Dependencies** | Minimal | Can have dependencies |
| **Example** | Format date, parse URL | Import CSV, sync API |
| **Instantiation** | Never | Via `new` or `getInstance()` |

### Best Practices for Helpers

1. **Static only**: All methods must be static
2. **No state**: Never use properties
3. **Pure functions**: Same input = same output
4. **Naming**: Use `*Helper` suffix
5. **Domain focus**: One helper per domain
6. **Documentation**: Document all parameters and return values
7. **Prefix**: Use `talampaya_` prefix for global functions

## Template Files

### Keep Template Files Simple

WordPress template files (`single.php`, `archive.php`, `single-{post-type}.php`, `archive-{post-type}.php`) should be **minimal**.

**IMPORTANT**: Template files are NOT controllers. They should only:
1. Get base context from Timber
2. Call a Controller method
3. Specify which Twig template to render
4. Pass context to Timber::render()

### Single Post Templates

**Example**: `single.php:1`

```php
<?php
use Timber\Timber;
use App\Inc\Controllers\DefaultController;

$context = Timber::context();
$post = $context['post'];
$templates = ['@pages/single-' . $post->post_type . '.twig', '@pages/single.twig'];

if (post_password_required($post->ID)) {
    $templates = '@pages/single-password.twig';
}

$controller = new DefaultController();

Timber::render($templates, $controller->get_single_context($context));
```

### Archive Templates

**Example**: `archive.php:1`

```php
<?php
use Timber\Timber;

$templates = ['@pages/archive.twig', '@pages/index.twig'];

$title = 'Archive';
if (is_post_type_archive()) {
    $title = post_type_archive_title('', false);
    array_unshift($templates, '@pages/archive-' . get_post_type() . '.twig');
}

$context = Timber::context(['title' => $title]);

Timber::render($templates, $context);
```

### Custom Post Type Templates

**Good example** (`single-product.php`):

```php
<?php
use Timber\Timber;
use App\Inc\Controllers\ProductController;

$context = Timber::context();
$templates = ['@pages/single-product.twig', '@pages/single.twig'];

Timber::render($templates, ProductController::get_single_product_context($context));
```

**Bad example** (too much logic):

```php
<?php
// DON'T DO THIS - logic belongs in Controller
use Timber\Timber;

$context = Timber::context();

// Don't put this logic here
$context['featured'] = get_field('featured', $post->ID);
$context['related'] = Timber::get_posts([
    'post_type' => 'product',
    'category__in' => wp_get_post_categories($post->ID),
]);
$context['price_range'] = [/* complex query */];

Timber::render('@pages/single-product.twig', $context);
```

## Decision Matrix

Use this matrix to decide which pattern to use:

| Scenario | Use |
|----------|-----|
| Prepare data for template | **Controller** |
| Import/export CSV | **Service** |
| Custom methods for post type | **Model** |
| Reusable functionality across classes | **Trait** |
| Static utility function | **Helper** |
| External API integration | **Service** |
| Format date/string | **Helper** |
| Query WordPress data | **Controller** or **Model** |
| File upload processing | **Service** |
| SEO metadata | **Trait** (if reusable) or **Model** method |
| Complex calculations | **Service** or **Model** method |
| Generate permalink | **Helper** |
| Validate form data | **Service** |
| Transform data structure | **Controller** or **Helper** |

## Integration Patterns

**How patterns work together**:

```
WordPress Request
  ↓
Template File (single-product.php)
  ↓
ProductController::get_single_product_context()
  ├─ Uses Product Model (extends AbstractPost)
  │  ├─ Uses ImportDataTrait
  │  └─ Uses AuditLogTrait
  ├─ Calls ProductSyncService (if needed)
  │  └─ Uses CsvProcessorTrait
  ├─ Uses DateHelper::formatDate()
  ├─ Uses AcfHelper::get_field_value()
  └─ Returns context array
  ↓
Timber::render('single-product.twig', $context)
  ↓
HTML Output
```

**Example flow for import**:

```
Admin Action
  ↓
ProductImportService::import()
  ├─ Uses CsvProcessorTrait::processCsv()
  ├─ For each row:
  │  ├─ Processes data
  │  ├─ Finds or creates Product Model
  │  ├─ Product uses ImportDataTrait::updateSeoData()
  │  ├─ Uses AcfHelper::set_image()
  │  └─ Uses PostHelper::create_post()
  └─ Returns result array
```

## Complete Examples

### Example 1: Product Feature

Full implementation of a product feature using all patterns:

**Model**: `/Inc/Models/Product.php`

```php
<?php

namespace App\Inc\Models;

use App\Inc\Traits\AuditLogTrait;

class Product extends AbstractPost
{
    use AuditLogTrait;

    public function custom_id(): ?string
    {
        return $this->meta('post_type_product_custom_id');
    }

    public function getPrice(): float
    {
        return (float) get_field('price', $this->ID);
    }

    public function getSku(): string
    {
        return get_field('sku', $this->ID) ?? '';
    }

    public function isOnSale(): bool
    {
        return (bool) get_field('on_sale', $this->ID);
    }

    public function updatePrice(float $new_price): bool
    {
        $old_price = $this->getPrice();

        $result = update_field('price', $new_price, $this->ID);

        if ($result) {
            $this->logAction('price_updated', [
                'old_price' => $old_price,
                'new_price' => $new_price,
            ]);
        }

        return $result;
    }
}
```

**Service**: `/Inc/Services/ProductImportService.php`

```php
<?php

namespace App\Inc\Services;

use App\Inc\Models\Product;
use App\Inc\Traits\CsvProcessorTrait;
use App\Inc\Helpers\AcfHelper;

class ProductImportService
{
    use CsvProcessorTrait;

    public function import(string $file_path): array
    {
        return $this->processCsv($file_path, function($row) {
            return $this->processRow($row);
        }, ['sku', 'title', 'price', 'stock']);
    }

    private function processRow(array $row): bool
    {
        $product = $this->findBySku($row['sku']);

        if (!$product) {
            $product = $this->createProduct($row);
        }

        if ($product) {
            $product->updateFromData([
                'title' => $row['title'],
                'price' => $row['price'],
                'stock' => $row['stock'],
            ]);

            return true;
        }

        return false;
    }

    private function findBySku(string $sku): ?Product
    {
        $product = new Product();
        return $product->findByCustomId($sku, 'product');
    }

    private function createProduct(array $row): ?Product
    {
        $post_id = wp_insert_post([
            'post_title' => $row['title'],
            'post_type' => 'product',
            'post_status' => 'publish',
        ]);

        if ($post_id) {
            update_field('field_post_type_product_custom_id', $row['sku'], $post_id);
            return new Product($post_id);
        }

        return null;
    }
}
```

**Controller**: `/Inc/Controllers/ProductController.php`

```php
<?php

namespace App\Inc\Controllers;

use Timber\Timber;
use App\Inc\Models\Product;
use App\Inc\Helpers\DateHelper;

class ProductController
{
    public static function get_single_product_context(array $context = []): array
    {
        $post = $context['post'] ?? Timber::get_post();

        $product = new Product($post->ID);

        $data = [
            'product' => $product,
            'price' => $product->getPrice(),
            'formatted_price' => $product->getFormattedPrice(),
            'sku' => $product->getSku(),
            'stock_status' => $product->getStockStatus(),
            'is_on_sale' => $product->isOnSale(),
        ];

        // Get related products
        $data['related_products'] = self::getRelatedProducts($product);

        return array_merge($context, $data);
    }

    private static function getRelatedProducts(Product $product): array
    {
        $categories = wp_get_post_categories($product->ID);

        return Timber::get_posts([
            'post_type' => 'product',
            'category__in' => $categories,
            'post__not_in' => [$product->ID],
            'posts_per_page' => 4,
        ], Product::class)->to_array();
    }
}
```

**Template**: `single-product.php`

```php
<?php
use Timber\Timber;
use App\Inc\Controllers\ProductController;

$context = Timber::context();
$templates = ['@pages/single-product.twig', '@pages/single.twig'];

Timber::render($templates, ProductController::get_single_product_context($context));
```

**Twig**: `views/pages/single-product.twig`

```twig
{% extends "@layouts/base.twig" %}

{% block content %}
  <article class="product">
    <h1>{{ product.title }}</h1>

    <div class="product__info">
      <p class="product__price">
        {% if is_on_sale %}
          <del>{{ formatted_price }}</del>
          <ins>{{ product.sale_price }}</ins>
        {% else %}
          {{ formatted_price }}
        {% endif %}
      </p>

      <p class="product__sku">SKU: {{ sku }}</p>
      <p class="product__stock">Status: {{ stock_status }}</p>
    </div>

    <div class="product__content">
      {{ product.content }}
    </div>

    {% if related_products %}
      <section class="related-products">
        <h2>Related Products</h2>
        <div class="products-grid">
          {% for related in related_products %}
            <div class="product-card">
              <h3>{{ related.title }}</h3>
              <p>{{ related.formatted_price }}</p>
            </div>
          {% endfor %}
        </div>
      </section>
    {% endif %}
  </article>
{% endblock %}
```

### Example 2: Import System

Complete import system implementation:

**Trait**: `/Inc/Traits/CsvProcessorTrait.php` (already exists)

**Service**: `/Inc/Services/AbstractImportService.php` (already exists)

**Concrete Service**: `/Inc/Services/ProductImportService.php` (from Example 1)

**Helper**: Create specialized helper for import validation:

```php
<?php

namespace App\Inc\Helpers;

class ImportHelper
{
    /**
     * Validate CSV headers
     */
    public static function validateHeaders(array $actual, array $expected): bool
    {
        return $actual === $expected;
    }

    /**
     * Sanitize import row
     */
    public static function sanitizeRow(array $row): array
    {
        return array_map('sanitize_text_field', $row);
    }

    /**
     * Validate required fields
     */
    public static function validateRequired(array $row, array $required): array
    {
        $errors = [];

        foreach ($required as $field) {
            if (empty($row[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        return $errors;
    }
}
```

**Usage**:

```php
// In admin page or AJAX handler
$service = new ProductImportService();
$result = $service->import($_FILES['csv_file']['tmp_name']);

if ($result['success'] > 0) {
    echo "Imported {$result['success']} products";
}
if ($result['errors'] > 0) {
    echo "Errors: {$result['errors']}";
}
```

---

For related documentation:
- [ARCHITECTURE.md](ARCHITECTURE.md) - Overall architecture
- [TIMBER-TWIG.md](TIMBER-TWIG.md) - Templating system
- [COMMON-TASKS.md](COMMON-TASKS.md) - Implementation guides
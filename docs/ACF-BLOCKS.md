# ACF Blocks

Complete guide to the ACF (Advanced Custom Fields) integration and custom blocks system in Talampaya.

## Table of Contents

- [ACF Blocks](#acf-blocks)
  - [Table of Contents](#table-of-contents)
  - [Overview](#overview)
  - [Block System Architecture](#block-system-architecture)
  - [Creating ACF Blocks](#creating-acf-blocks)
    - [1. Define Block JSON](#1-define-block-json)
    - [2. Create Twig Template](#2-create-twig-template)
    - [3. (Optional) Add Context Modifier](#3-optional-add-context-modifier)
  - [Block Context Modifiers](#block-context-modifiers)
    - [Example: Hero Block Modifier](#example-hero-block-modifier)
  - [Field Groups](#field-groups)
    - [JSON Synchronization](#json-synchronization)
    - [Organizing Field Groups](#organizing-field-groups)
  - [WPML Integration](#wpml-integration)
  - [Block Rendering](#block-rendering)
  - [Best Practices](#best-practices)
  - [Common Patterns](#common-patterns)
    - [Repeater Fields](#repeater-fields)
    - [Relationship Fields](#relationship-fields)
    - [Flexible Content](#flexible-content)

## Overview

Talampaya uses **ACF Pro 6.6.0** for:
- Custom field management
- Gutenberg block creation
- Field group organization
- WPML translation integration

All blocks are rendered using **Twig templates** via the `BlockRenderer` system.

## Block System Architecture

```
/src/theme/blocks/                    # ACF Block directories
    └── hero/                         # Each block has its own directory
        ├── hero-block.json           # Block definition
        ├── hero-block.php            # ACF fields registration
        └── hero-block.twig           # Block template

/src/theme/src/Features/Acf/Blocks/
    ├── BlockRenderer.php             # Main rendering class
    └── Modifiers/                    # Context modification classes
        └── HeroBlockModifier.php

/src/theme/acf-json/                  # ACF field group exports (auto-synced)
    └── group_hero_block.json
```

## Creating ACF Blocks

Each block is a self-contained directory with three files: JSON definition, PHP field registration, and Twig template.

### 1. Create Block Directory

**Location**: `/src/theme/blocks/{block-name}/`

Create a new directory for your block inside `/src/theme/blocks/`.

### 2. Define Block JSON

**Location**: `/src/theme/blocks/{block-name}/{block-name}-block.json`

**Example** (`/src/theme/blocks/hero/hero-block.json`):
```json
{
  "name": "hero",
  "title": "Hero Section",
  "description": "Hero section with background image and CTA",
  "category": "theme",
  "icon": "cover-image",
  "keywords": ["hero", "banner", "header"],
  "acf": {
    "mode": "preview",
    "renderCallback": "App\\Features\\Acf\\Blocks\\BlockRenderer::render"
  },
  "supports": {
    "align": ["wide", "full"],
    "anchor": true,
    "customClassName": true,
    "jsx": true
  },
  "example": {
    "attributes": {
      "mode": "preview",
      "data": {
        "hero_title": "Welcome to Talampaya",
        "hero_subtitle": "Modern WordPress theme"
      }
    }
  }
}
```

**Key properties**:
- `name` - Block identifier (must match template name)
- `title` - Display name in editor
- `category` - Block category (`theme`, `common`, `formatting`, etc.)
- `renderCallback` - Always use `BlockRenderer::render`
- `supports` - Gutenberg editor features

### 3. Register ACF Fields (PHP)

**Location**: `/src/theme/blocks/{block-name}/{block-name}-block.php`

This file programmatically registers ACF field groups for the block.

**Example** (`/src/theme/blocks/hero/hero-block.php`):
```php
<?php

use Illuminate\Support\Str;
use App\Inc\Helpers\AcfHelper;

function add_acf_block_hero(): void
{
    $key = "hero";
    $key_underscore = Str::snake($key);
    $key_dash = str_replace("_", "-", $key_underscore);
    $title = Str::title(str_replace("_", " ", $key_underscore));
    $block_title = __($title, "talampaya");

    $fields = [
        ["hero_title"],
        ["hero_subtitle"],
        ["hero_background_image", "image", 100, null, 0, ["return_format" => "array"]],
        ["hero_cta", "link"],
    ];

    $groups = [[$block_title, AcfHelper::talampaya_create_acf_group_fields($fields), 1]];

    foreach ($groups as $group) {
        $field_group = [
            "key" => Str::snake($group[0]),
            "title" => __($group[0], "talampaya"),
            "fields" => $group[1],
            "location" => [
                [
                    [
                        "param" => "block",
                        "operator" => "==",
                        "value" => "acf/" . $key_dash,
                    ],
                ],
            ],
            "show_in_rest" => true,
            "menu_order" => $group[2],
        ];

        acf_add_local_field_group(
            AcfHelper::talampaya_replace_keys_from_acf_register_fields($field_group, $key_underscore)
        );
    }
}
add_action("acf/init", "add_acf_block_hero", 10);
```

### 4. Create Twig Template

**Location**: `/src/theme/blocks/{block-name}/{block-name}-block.twig`

**Example** (`/src/theme/blocks/hero/hero-block.twig`):
```twig
{# Block: Hero Section #}
<section class="hero {{ block.classes }}" id="{{ block.anchor }}">
  {% if fields.hero_background_image %}
    <img src="{{ fields.hero_background_image.url }}"
         alt="{{ fields.hero_background_image.alt }}"
         class="hero__background">
  {% endif %}

  <div class="hero__content">
    {% if fields.hero_title %}
      <h1 class="hero__title">{{ fields.hero_title }}</h1>
    {% endif %}

    {% if fields.hero_subtitle %}
      <p class="hero__subtitle">{{ fields.hero_subtitle }}</p>
    {% endif %}

    {% if fields.hero_cta %}
      <a href="{{ fields.hero_cta.url }}"
         class="hero__cta"
         target="{{ fields.hero_cta.target }}">
        {{ fields.hero_cta.title }}
      </a>
    {% endif %}
  </div>
</section>
```

**Available context**:
- `fields` - ACF field values
- `block` - Block meta (id, name, classes, anchor, etc.)
- `is_preview` - Boolean, true if in editor preview

### 5. (Optional) Add Context Modifier

**Location**: `/src/theme/src/Features/Acf/Blocks/Modifiers/{BlockName}Modifier.php`

Context modifiers allow you to:
- Process field data before rendering
- Add computed values
- Fetch related data
- Transform data structures

**Example** (`HeroBlockModifier.php`):
```php
<?php

namespace App\Features\Acf\Blocks\Modifiers;

class HeroBlockModifier
{
    public static function modify(array $context): array
    {
        // Add computed class based on background
        $context['has_background'] = !empty($context['fields']['hero_background_image']);

        // Process CTA link
        if (!empty($context['fields']['hero_cta'])) {
            $cta = $context['fields']['hero_cta'];
            $context['cta_external'] = $cta['target'] === '_blank';
        }

        return $context;
    }
}
```

**Register modifier** in `BlockRenderer.php`:
```php
private array $modifiers = [
    'hero' => HeroBlockModifier::class,
];
```

## Block Context Modifiers

Modifiers transform data before it reaches the Twig template.

### Example: Hero Block Modifier

```php
<?php

namespace App\Features\Acf\Blocks\Modifiers;

use Timber\Timber;

class HeroBlockModifier
{
    public static function modify(array $context): array
    {
        $fields = $context['fields'];

        // Fetch related post if ID is stored
        if (!empty($fields['featured_post_id'])) {
            $context['featured_post'] = Timber::get_post($fields['featured_post_id']);
        }

        // Process background settings
        if (!empty($fields['background_type'])) {
            $context['background_class'] = 'hero--' . $fields['background_type'];
        }

        // Add default values
        $context['fields']['hero_title'] = $fields['hero_title'] ?? get_bloginfo('name');

        return $context;
    }
}
```

## Field Groups

### JSON Synchronization

ACF field groups are auto-synced to `/src/theme/acf-json/`.

**Benefits**:
- Version control field definitions
- Easy deployment across environments
- Prevent database-only field definitions

**Workflow**:
1. Create/edit field group in WordPress admin
2. Field group is auto-exported to `/acf-json/`
3. Commit JSON file to git
4. On deployment, ACF auto-imports from JSON

### Organizing Field Groups

**Naming convention**:
```
group_{post_type}_{purpose}.json
group_hero_block.json
group_project_post_fields.json
group_page_settings.json
```

**Location rules**:
- Blocks: `Post Type == Block`
- Post types: `Post Type == {post_type}`
- Templates: `Page Template == {template}`
- General: Multiple conditions

## WPML Integration

**Plugin**: ACFML (ACF Multilingual)

**Translation workflow**:
1. Define which fields are translatable in WPML settings
2. Translate content via WPML Translation Management
3. ACF fields sync across languages automatically

**Field configuration**:
- Text fields: Translatable
- Relationship fields: Copy (IDs work across languages)
- Image fields: Translatable (different images per language)

**Example**: Translating block content
```php
// In block template
{% if function('icl_get_current_language') == 'es' %}
  <p>{{ fields.spanish_text }}</p>
{% else %}
  <p>{{ fields.english_text }}</p>
{% endif %}
```

## Block Rendering

**How it works**:

1. User adds block in Gutenberg editor
2. ACF renders field form
3. On save/preview, `BlockRenderer::render()` is called
4. BlockRenderer:
   - Loads block data
   - Applies context modifier (if exists)
   - Renders Twig template
   - Outputs HTML

**BlockRenderer code** (`/src/Features/Acf/Blocks/BlockRenderer.php`):
```php
public static function render(
    array $attributes,
    string $content = "",
    bool $is_preview = false,
    int $post_id = 0,
    ?WP_Block $wp_block = null
): void {
    // Extract block slug from name (removes 'acf/' prefix)
    $slug = str_replace("acf/", "", $attributes["name"]);

    $context = Timber::context();
    $context["attributes"] = $attributes;
    $context["fields"] = get_fields();
    $context["is_preview"] = $is_preview;

    // Apply registered context modifiers
    foreach (self::$contextModifiers as $modifier) {
        $context = $modifier($context, $attributes, $content, $is_preview, $post_id, $wp_block, $slug);
    }

    // Render template from block directory
    Timber::render("blocks/" . $slug . "/" . $slug . "-block.twig", $context);
}
```

## Best Practices

1. **Use consistent naming**:
   - Block directory: `/src/theme/blocks/hero/`
   - Block name (in JSON): `hero`
   - JSON file: `hero-block.json`
   - PHP file: `hero-block.php`
   - Twig template: `hero-block.twig`
   - Modifier (optional): `HeroBlockModifier.php`

2. **Always provide example data** in block JSON for editor preview

3. **Use context modifiers** for complex data processing (keep Twig templates clean)

4. **Leverage Timber** for fetching posts/terms in modifiers:
   ```php
   $context['posts'] = Timber::get_posts(['post_type' => 'project']);
   ```

5. **Validate field data** in modifiers before rendering:
   ```php
   if (empty($fields['required_field'])) {
       return $context; // Skip rendering
   }
   ```

6. **Use ACF field groups** for reusable field sets

## Common Patterns

### Repeater Fields

**Twig template**:
```twig
{% if fields.items %}
  <ul class="item-list">
    {% for item in fields.items %}
      <li>{{ item.title }}</li>
    {% endfor %}
  </ul>
{% endif %}
```

### Relationship Fields

**Modifier**:
```php
if (!empty($fields['related_posts'])) {
    $context['related'] = Timber::get_posts($fields['related_posts']);
}
```

**Twig**:
```twig
{% for post in related %}
  <h3>{{ post.title }}</h3>
{% endfor %}
```

### Flexible Content

**Twig**:
```twig
{% if fields.content_blocks %}
  {% for block in fields.content_blocks %}
    {% if block.acf_fc_layout == 'text_block' %}
      <p>{{ block.text }}</p>
    {% elseif block.acf_fc_layout == 'image_block' %}
      <img src="{{ block.image.url }}" alt="{{ block.image.alt }}">
    {% endif %}
  {% endfor %}
{% endif %}
```

---

For related documentation:
- [TIMBER-TWIG.md](TIMBER-TWIG.md) - Twig templating
- [COMMON-TASKS.md](COMMON-TASKS.md#creating-acf-blocks) - Step-by-step block creation
- [THIRD-PARTY.md](THIRD-PARTY.md#acf-pro) - ACF Pro integration details
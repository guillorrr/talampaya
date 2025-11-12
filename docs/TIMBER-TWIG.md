# Timber & Twig

Complete guide to the Timber/Twig templating system used in Talampaya.

## Table of Contents

- [Timber \& Twig](#timber--twig)
  - [Table of Contents](#table-of-contents)
  - [Overview](#overview)
  - [Template Structure](#template-structure)
    - [Directory Organization](#directory-organization)
  - [Context Data](#context-data)
    - [Base Context](#base-context)
    - [Extending Context](#extending-context)
  - [Custom Twig Extensions](#custom-twig-extensions)
    - [Creating a Twig Extension](#creating-a-twig-extension)
  - [Template Paths (Namespaces)](#template-paths-namespaces)
  - [Common Template Patterns](#common-template-patterns)
    - [Base Layout](#base-layout)
    - [Page Template](#page-template)
    - [Component Inclusion](#component-inclusion)
  - [Twig Filters \& Functions](#twig-filters--functions)
    - [Built-in Timber Filters](#built-in-timber-filters)
    - [Custom Filters](#custom-filters)
  - [Working with Posts](#working-with-posts)
    - [Single Post](#single-post)
    - [Post Loop](#post-loop)
    - [Custom Query](#custom-query)
  - [Working with Menus](#working-with-menus)
  - [Working with Images](#working-with-images)
  - [Best Practices](#best-practices)

## Overview

**Timber** brings Twig templating to WordPress, enabling:
- Clean separation of logic (PHP) and presentation (Twig)
- Reusable template components
- Powerful template inheritance
- Improved readability and maintainability

**Version**: Timber ^2.1

**Documentation**: https://timber.github.io/docs/

## Template Structure

### Directory Organization

Talampaya integrates PatternLab for design system components and uses views/ for WordPress-specific templates:

```
/src/theme/views/
├── layouts/          # Base templates (overall structure)
│   └── base.twig     # Main layout extending PatternLab templates
├── pages/            # WordPress page templates
│   ├── 404.twig      # Compose pages using PatternLab & WP-specific components
│   ├── archive.twig
│   ├── single.twig
│   └── front-page.twig
├── components/       # WordPress-specific components
│   └── post-meta.twig    # WP logic not suitable for PatternLab
├── includes/         # Third-party scripts/snippets
    ├── facebook-pixel.twig
    └── google-analytics.twig

/src/theme/blocks/    # ACF Gutenberg blocks (not in views/)
└── hero/
    ├── hero-block.json
    ├── hero-block.php
    └── hero-block.twig
```

**PatternLab Integration**:
- PatternLab components (atoms, molecules, organisms, templates) are integrated via namespaces
- Views templates include/extend PatternLab components
- `components/` contains only WordPress-specific logic not present in PatternLab

**Logic**:
1. **PatternLab**: Design system components (atoms, molecules, organisms)
2. **views/pages**: WordPress pages that compose PatternLab components
3. **views/components**: WordPress-specific components (post meta, pagination, etc.)
4. **views/layouts**: Base layouts extending PatternLab templates
5. **views/includes**: Third-party tracking/analytics scripts
6. **blocks/**: ACF blocks with self-contained JSON, PHP, and Twig files

## Context Data

### Base Context

Every Timber template has access to the base context:

```twig
{{ site.name }}              {# Site name #}
{{ site.url }}               {# Site URL #}
{{ site.description }}       {# Site description #}
{{ request.uri }}            {# Current URL path #}
```

### Extending Context

Context is extended through **ContextExtenderInterface** implementations.

**How it works**:
1. `Timber::context()` provides base WordPress context
2. `TalampayaStarter::addToContext()` is called
3. `ContextManager::extendContext()` chains through all registered extenders
4. Final merged context is passed to template

**Example: PathsContext** (`/src/Core/ContextExtender/Defaults/PathsContext.php`):
```php
<?php

namespace App\Core\ContextExtender\Defaults;

use App\Core\ContextExtender\ContextExtenderInterface;

class PathsContext implements ContextExtenderInterface
{
    public function extendContext(array $context): array
    {
        $context['paths'] = [
            'theme' => get_template_directory_uri(),
            'assets' => get_template_directory_uri() . '/assets',
            'images' => get_template_directory_uri() . '/assets/images',
            'styles' => get_template_directory_uri() . '/assets/styles',
            'scripts' => get_template_directory_uri() . '/assets/scripts',
        ];

        return $context;
    }
}
```

**Usage in Twig**:
```twig
<img src="{{ paths.images }}/logo.png" alt="Logo">
<link rel="stylesheet" href="{{ paths.styles }}/style.css">
```

**Creating a custom context extender**:

1. Create class in `/src/Core/ContextExtender/Custom/`
2. Implement `ContextExtenderInterface`
3. Return array with new context data
4. Auto-discovered and loaded by `ContextManager`

See [COMMON-TASKS.md](COMMON-TASKS.md#adding-context-data) for details.

## Custom Twig Extensions

Add custom filters, functions, and tests to Twig.

### Creating a Twig Extension

**Location**: `/src/Core/TwigExtender/Custom/MyExtension.php`

**Example**:
```php
<?php

namespace App\Core\TwigExtender\Custom;

use App\Core\TwigExtender\TwigExtenderInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class MyExtension implements TwigExtenderInterface
{
    public function extendTwig(\Twig\Environment $twig): \Twig\Environment
    {
        // Add custom filter
        $twig->addFilter(new TwigFilter('shorten', function (string $text, int $length = 100) {
            return substr($text, 0, $length) . '...';
        }));

        // Add custom function
        $twig->addFunction(new TwigFunction('get_copyright_year', function () {
            return date('Y');
        }));

        return $twig;
    }
}
```

**Usage in Twig**:
```twig
{{ post.content|shorten(150) }}
<p>&copy; {{ get_copyright_year() }} My Company</p>
```

## Template Paths (Namespaces)

Configured in `TalampayaStarter::addLocations()`:

| Namespace | Path | Usage | Notes |
|-----------|------|-------|-------|
| `@atoms` | `views/atoms` | `{% include '@atoms/button.twig' %}` | PatternLab |
| `@molecules` | `views/molecules` | `{% include '@molecules/card.twig' %}` | PatternLab |
| `@organisms` | `views/organisms` | `{% include '@organisms/header.twig' %}` | PatternLab |
| `@templates` | `views/templates` | `{% extends '@templates/base.twig' %}` | PatternLab |
| `@macros` | `views/macros` | `{% import '@macros/forms.twig' %}` | Twig macros |
| `@layouts` | `views/layouts` | `{% extends '@layouts/base.twig' %}` | WordPress |
| `@pages` | `views/pages` | `{% extends '@pages/home.twig' %}` | WordPress |
| `@components` | `views/components` | `{% include '@components/post-meta.twig' %}` | WordPress |
| `@blocks` | `blocks/` | - | ACF blocks (auto-rendered) |

**Example usage**:
```twig
{# WordPress page template #}
{% extends '@layouts/base.twig' %}

{% block content %}
  {# PatternLab components #}
  {% include '@organisms/header.twig' with { title: post.title } %}

  {# WordPress-specific component #}
  {% include '@components/post-meta.twig' %}

  {# PatternLab molecules #}
  {% include '@molecules/card.twig' with { content: post.content } %}
{% endblock %}
```

**Note**: ACF blocks in `@blocks` namespace are rendered automatically by `BlockRenderer`. You don't typically include them manually in templates - they're added via Gutenberg editor.

## Common Template Patterns

### Base Layout

**File**: `views/layouts/base.twig`

```twig
<!DOCTYPE html>
<html {{ site.language_attributes }}>
<head>
  <meta charset="{{ site.charset }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  {{ function('wp_head') }}
</head>
<body class="{{ body_class }}">
  {% include '@organisms/header.twig' %}

  <main class="main">
    {% block content %}
      {# Page content here #}
    {% endblock %}
  </main>

  {% include '@organisms/footer.twig' %}
  {{ function('wp_footer') }}
</body>
</html>
```

### Page Template

**File**: `views/pages/about.twig`

```twig
{% extends '@layouts/base.twig' %}

{% block content %}
  <article class="page">
    <h1>{{ post.title }}</h1>
    <div class="content">
      {{ post.content }}
    </div>
  </article>
{% endblock %}
```

### Component Inclusion

```twig
{# Simple include #}
{% include '@components/nav.twig' %}

{# Include with parameters #}
{% include '@components/button.twig' with {
  text: 'Click Me',
  url: '/contact',
  style: 'primary'
} %}
```

## Twig Filters & Functions

### Built-in Timber Filters

```twig
{# Image resize #}
<img src="{{ post.thumbnail.src|resize(300, 200) }}" alt="{{ post.thumbnail.alt }}">

{# WordPress functions #}
{{ post.content|wpautop }}
{{ text|wp_trim_words(50) }}

{# Date formatting #}
{{ post.date|date('F j, Y') }}
```

### Custom Filters

**Available in Talampaya** (example):
```twig
{# Shorten text #}
{{ post.excerpt|shorten(150) }}

{# Format price #}
{{ product.price|format_price }}
```

## Working with Posts

### Single Post

**Controller** (in WordPress template file):
```php
$context = Timber::context();
$context['post'] = Timber::get_post();
Timber::render('pages/single.twig', $context);
```

**Template**:
```twig
<article class="post">
  <h1>{{ post.title }}</h1>
  <time datetime="{{ post.date|date('c') }}">{{ post.date }}</time>
  <div class="content">
    {{ post.content }}
  </div>
</article>
```

### Post Loop

```twig
{% for post in posts %}
  <article>
    <h2><a href="{{ post.link }}">{{ post.title }}</a></h2>
    {{ post.excerpt }}
  </article>
{% endfor %}
```

### Custom Query

**Controller**:
```php
$context['recent_posts'] = Timber::get_posts([
    'post_type' => 'post',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC',
]);
```

**Template**:
```twig
{% for post in recent_posts %}
  <h3>{{ post.title }}</h3>
{% endfor %}
```

## Working with Menus

**Controller**:
```php
$context['menu'] = Timber::get_menu('primary');
```

**Template**:
```twig
<nav>
  <ul>
    {% for item in menu.items %}
      <li class="{{ item.classes|join(' ') }}">
        <a href="{{ item.link }}">{{ item.title }}</a>
        {% if item.children %}
          <ul>
            {% for child in item.children %}
              <li><a href="{{ child.link }}">{{ child.title }}</a></li>
            {% endfor %}
          </ul>
        {% endif %}
      </li>
    {% endfor %}
  </ul>
</nav>
```

## Working with Images

```twig
{# Thumbnail #}
<img src="{{ post.thumbnail.src }}" alt="{{ post.thumbnail.alt }}">

{# Specific size #}
<img src="{{ post.thumbnail.src('medium') }}" alt="{{ post.thumbnail.alt }}">

{# Resize on-the-fly #}
<img src="{{ post.thumbnail.src|resize(600, 400) }}" alt="{{ post.thumbnail.alt }}">

{# Responsive images #}
<img src="{{ post.thumbnail.src }}"
     srcset="{{ post.thumbnail.srcset }}"
     sizes="(max-width: 600px) 100vw, 600px"
     alt="{{ post.thumbnail.alt }}">

{# ACF image field #}
{% if fields.hero_image %}
  <img src="{{ fields.hero_image.url }}" alt="{{ fields.hero_image.alt }}">
{% endif %}
```

## Best Practices

1. **Keep logic in PHP, presentation in Twig**:
   - Process data in controllers or context extenders
   - Use Twig for display only

2. **Use template inheritance**:
   ```twig
   {% extends '@layouts/base.twig' %}
   ```

3. **Create reusable components**:
   ```twig
   {% include '@components/card.twig' with { post: post } %}
   ```

4. **Leverage Timber's caching**:
   ```php
   $posts = Timber::get_posts($args, false, 'Timber\Post', 3600); // Cache 1 hour
   ```

5. **Use named blocks** for flexibility:
   ```twig
   {% block header %}{% endblock %}
   {% block content %}{% endblock %}
   {% block footer %}{% endblock %}
   ```

6. **Validate data** before output:
   ```twig
   {% if post.thumbnail %}
     <img src="{{ post.thumbnail.src }}" alt="{{ post.thumbnail.alt }}">
   {% endif %}
   ```

---

For related documentation:
- [ARCHITECTURE.md](ARCHITECTURE.md) - Context and data flow
- [ACF-BLOCKS.md](ACF-BLOCKS.md) - ACF block rendering with Twig
- [COMMON-TASKS.md](COMMON-TASKS.md) - Step-by-step Twig implementation guides
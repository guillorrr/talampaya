# Pattern Lab

Complete guide to the Pattern Lab design system integration in Talampaya.

## Table of Contents

- [Pattern Lab](#pattern-lab)
  - [Table of Contents](#table-of-contents)
  - [Overview](#overview)
  - [Running Pattern Lab](#running-pattern-lab)
  - [Structure](#structure)
  - [Integration with Theme](#integration-with-theme)
    - [Twig Templates](#twig-templates)
    - [JSON Mockup Data](#json-mockup-data)
    - [JavaScript](#javascript)
  - [Creating Patterns](#creating-patterns)
    - [Atoms](#atoms)
    - [Molecules](#molecules)
    - [Organisms](#organisms)
    - [Templates](#templates)
    - [Pages](#pages)
  - [Data Files](#data-files)
  - [Workflow](#workflow)
  - [Best Practices](#best-practices)

## Overview

Pattern Lab provides a **living design system** for Talampaya based on **Atomic Design** methodology.

**Benefits**:
- Component-driven development
- Living style guide
- Design-development alignment
- Reusable UI components
- Documentation for designers and developers

**Methodology**: Atomic Design
- **Atoms**: Basic building blocks (buttons, inputs, labels)
- **Molecules**: Simple component combinations (search form, card header)
- **Organisms**: Complex UI sections (header, footer, hero)
- **Templates**: Page layouts
- **Pages**: Specific instances with real content

**Pattern Lab version**: Latest
**Documentation**: https://patternlab.io/

## Running Pattern Lab

```bash
# Start Pattern Lab server
npm run patternlab

# Access at http://localhost:4000
```

**Features**:
- Live preview of all components
- Responsive viewport testing
- Pattern search and navigation
- Code viewer
- Annotation support

## Structure

```
/patternlab/
├── source/
│   ├── _patterns/              # Pattern definitions
│   │   ├── 00-atoms/
│   │   ├── 01-molecules/
│   │   ├── 02-organisms/
│   │   ├── 03-templates/
│   │   └── 04-pages/
│   ├── _data/                  # JSON mockup data
│   │   ├── data.json           # Global data
│   │   ├── listitems.json      # List items
│   │   └── posts.json          # Post data
│   ├── _meta/                  # Metadata and headers
│   ├── css/                    # Pattern Lab specific CSS
│   ├── images/                 # Pattern Lab images
│   └── js/                     # Pattern Lab specific JS
└── public/                     # Generated static site
```

## Integration with Theme

### Twig Templates

**Gulp copies Pattern Lab Twig files** → `/src/theme/views/`

**Workflow**:
1. Create pattern in `/patternlab/source/_patterns/`
2. Gulp watch detects change
3. File is copied to `/src/theme/views/`
4. Available in WordPress templates

**Example**:
```
/patternlab/source/_patterns/01-molecules/card.twig
  ↓ (Gulp copy)
/src/theme/views/molecules/card.twig
  ↓ (Used in WordPress)
{% include '@molecules/card.twig' with { post: post } %}
```

### JSON Mockup Data

**Gulp transforms JSON data** → `/src/theme/src/Mockups/`

**Purpose**:
- Provide realistic data for Pattern Lab previews
- Reusable in WordPress theme for demos

**Example**:
```json
// /patternlab/source/_data/posts.json
{
  "posts": [
    {
      "title": "Example Post",
      "excerpt": "This is an excerpt",
      "date": "2025-01-12",
      "author": "John Doe"
    }
  ]
}
```

**Accessed in Twig**:
```twig
{% for post in posts %}
  <h3>{{ post.title }}</h3>
{% endfor %}
```

### JavaScript

**Webpack bundles Pattern Lab JavaScript**:

**Entry point**: `/src/theme/assets/scripts/main.js`

**Output**: Used in Pattern Lab and theme

## Creating Patterns

### Atoms

**Location**: `/patternlab/source/_patterns/00-atoms/`

**Example**: Button atom (`button.twig`)
```twig
<button class="btn btn--{{ variant|default('primary') }}">
  {{ text|default('Click Me') }}
</button>
```

**JSON data** (`button.json`):
```json
{
  "text": "Submit",
  "variant": "primary"
}
```

### Molecules

**Location**: `/patternlab/source/_patterns/01-molecules/`

**Example**: Card molecule (`card.twig`)
```twig
<div class="card">
  {% if image %}
    <img src="{{ image.url }}" alt="{{ image.alt }}" class="card__image">
  {% endif %}
  <div class="card__content">
    <h3 class="card__title">{{ title }}</h3>
    <p class="card__excerpt">{{ excerpt }}</p>
    {% include '@atoms/button.twig' with { text: 'Read More', variant: 'secondary' } %}
  </div>
</div>
```

### Organisms

**Location**: `/patternlab/source/_patterns/02-organisms/`

**Example**: Header organism (`header.twig`)
```twig
<header class="header">
  <div class="header__logo">
    {% include '@atoms/logo.twig' %}
  </div>
  <nav class="header__nav">
    {% include '@molecules/nav.twig' with { menu: menu } %}
  </nav>
</header>
```

### Templates

**Location**: `/patternlab/source/_patterns/03-templates/`

**Example**: Blog template (`blog-template.twig`)
```twig
{% include '@organisms/header.twig' %}

<main class="main">
  <div class="container">
    {% block content %}{% endblock %}
  </div>
</main>

{% include '@organisms/footer.twig' %}
```

### Pages

**Location**: `/patternlab/source/_patterns/04-pages/`

**Example**: Blog page (`blog-page.twig`)
```twig
{% extends '@templates/blog-template.twig' %}

{% block content %}
  <h1>{{ page.title }}</h1>

  {% for post in posts %}
    {% include '@molecules/card.twig' with {
      title: post.title,
      excerpt: post.excerpt,
      image: post.image
    } %}
  {% endfor %}
{% endblock %}
```

## Data Files

**Global data** (`/patternlab/source/_data/data.json`):
```json
{
  "site": {
    "name": "Talampaya",
    "url": "https://talampaya.local",
    "description": "Professional WordPress Theme"
  },
  "colors": {
    "primary": "#007bff",
    "secondary": "#6c757d"
  }
}
```

**Pattern-specific data** (`pattern-name.json`):
```json
{
  "title": "Example Title",
  "content": "Example content"
}
```

**List items** (`listitems.json`):
```json
{
  "one": "Item 1",
  "two": "Item 2",
  "three": "Item 3"
}
```

**Usage in Twig**:
```twig
{{ site.name }}
{{ colors.primary }}
{{ listitems.one }}
```

## Workflow

### Design-to-Development Flow

1. **Design Phase**:
   - Create components in Pattern Lab
   - Use mockup data
   - Refine UI without WordPress

2. **Integration Phase**:
   - Gulp auto-copies to theme
   - Replace mockup data with WordPress data
   - Test in WordPress context

3. **Maintenance Phase**:
   - Update patterns in Pattern Lab
   - Changes propagate to WordPress
   - Design system stays in sync

### Developer Workflow

```bash
# Terminal 1: Start WordPress
npm start
npm run dev

# Terminal 2: Start Pattern Lab
npm run patternlab

# Both systems watch for changes
# Pattern Lab: http://localhost:4000
# WordPress: https://talampaya.local
```

## Best Practices

1. **Start with atoms, build up**:
   - Create smallest components first
   - Compose molecules from atoms
   - Build organisms from molecules

2. **Use meaningful names**:
   ```
   ✓ button.twig
   ✓ search-form.twig
   ✓ site-header.twig

   ✗ component1.twig
   ✗ thing.twig
   ```

3. **Provide realistic data**:
   ```json
   {
     "title": "10 Tips for Better WordPress Development",
     "excerpt": "Learn how to improve your WordPress workflow..."
   }
   ```

4. **Document patterns** with annotations:
   ```twig
   {#
     Card Component

     Displays a post with image, title, excerpt, and CTA.

     Parameters:
     - title (string): Card title
     - excerpt (string): Short description
     - image (object): Image with url and alt
   #}
   ```

5. **Keep patterns DRY**:
   ```twig
   {# Reuse atoms in molecules #}
   {% include '@atoms/button.twig' %}

   {# Reuse molecules in organisms #}
   {% include '@molecules/card.twig' %}
   ```

6. **Test responsive behavior** in Pattern Lab's viewport resizer

7. **Version control patterns** - commit Pattern Lab changes along with theme changes

---

For related documentation:
- [TIMBER-TWIG.md](TIMBER-TWIG.md) - Twig templating details
- [BUILD-SYSTEM.md](BUILD-SYSTEM.md) - How Gulp integrates Pattern Lab
- [ARCHITECTURE.md](ARCHITECTURE.md) - Theme architecture
# CLAUDE.md

Guidelines for Claude Code when working with the Talampaya WordPress theme project.

## Project Context Detection

**CRITICAL**: Before making ANY changes to code or documentation, determine if this is the original Talampaya project or a fork.

### How to Detect

1. **Read `.env` file** in the project root
2. **Check the `APP_NAME` variable**:
   - `APP_NAME=talampaya` → Original Talampaya project
   - `APP_NAME=anything-else` → Fork of Talampaya (custom project)

### Rules Based on Project Type

| Action | Original Project | Fork Project |
|--------|------------------|--------------|
| Modify core architecture files | ✅ Allowed | ❌ **AVOID** - may break upstream sync |
| Modify documentation files | ✅ Allowed | ⚠️ **ASK USER FIRST** |
| Add new features | ✅ Allowed | ✅ Allowed |
| Add custom post types/taxonomies | ✅ Allowed | ✅ Allowed |
| Modify `CLAUDE.md`, `README.md` | ✅ Allowed | ❌ **DO NOT MODIFY** - keep for upstream |
| Add fork-specific docs | N/A | ✅ Use `docs/FORK-*.md` naming |

**Core architecture files** (avoid in forks):
- `/src/theme/src/Core/**/*.php`
- `/gulpfile.js`
- `/webpack.config.js`
- `/docker-compose.yml`
- `/composer.json` (unless adding dependencies)

**Always safe to modify** (in forks):
- `/src/theme/src/Register/PostType/**` (custom post types)
- `/src/theme/src/Register/Taxonomy/**` (taxonomies)
- `/src/theme/views/**` (templates)
- `/src/theme/assets/**` (styles, scripts)
- `/src/theme/blocks/**` (ACF blocks)
- `/src/theme/src/Features/**` (custom features)

---

## Documentation Structure

**All technical documentation is in `/docs`**. DO NOT repeat information from `/docs` in this file.

### Documentation Reference

Before answering technical questions, consult the appropriate documentation:

| Topic | Document | Path |
|-------|----------|------|
| Architecture & Patterns | ARCHITECTURE.md | `/docs/ARCHITECTURE.md` |
| Application Layer (Controllers, Services, Models, Traits, Helpers) | APPLICATION-LAYER.md | `/docs/APPLICATION-LAYER.md` |
| Development Workflow | DEVELOPMENT.md | `/docs/DEVELOPMENT.md` |
| Build System | BUILD-SYSTEM.md | `/docs/BUILD-SYSTEM.md` |
| Docker Environment | DOCKER.md | `/docs/DOCKER.md` |
| ACF Blocks | ACF-BLOCKS.md | `/docs/ACF-BLOCKS.md` |
| Timber/Twig Templating | TIMBER-TWIG.md | `/docs/TIMBER-TWIG.md` |
| Testing | TESTING.md | `/docs/TESTING.md` |
| Pattern Lab | PATTERN-LAB.md | `/docs/PATTERN-LAB.md` |
| Troubleshooting | TROUBLESHOOTING.md | `/docs/TROUBLESHOOTING.md` |
| Third-Party Plugins | THIRD-PARTY.md | `/docs/THIRD-PARTY.md` |
| Common Tasks | COMMON-TASKS.md | `/docs/COMMON-TASKS.md` |
| Content Generator | CONTENT-GENERATOR.md | `/docs/CONTENT-GENERATOR.md` |
| Contributing | CONTRIBUTING.md | `/docs/CONTRIBUTING.md` |

**When user asks about a topic**:
1. Check if documentation exists
2. Read the relevant documentation file
3. Provide answer based on documentation
4. Reference the documentation file path in response

---

## Working Rules for Claude

### Response Format

1. **Be concise and direct**:
   - No unnecessary pleasantries
   - Get straight to the solution
   - Use bullet points when listing steps

2. **Always provide file paths**:
   - Use format: `file_path:line_number`
   - Example: "Add this to `src/Core/Setup/AssetsManager.php:45`"

3. **Code references**:
   ```
   ✅ Good:
   "The PluginManager auto-discovers plugins in src/Core/Plugins/Integration/:127"

   ❌ Bad:
   "The PluginManager discovers plugins automatically"
   ```

4. **No emojis** unless explicitly requested by user

### Before Making Changes

**ALWAYS check project type first** (original vs fork) by reading `APP_NAME` from `.env`.

**For forks**:
- ⚠️ **ASK USER** before modifying core files
- ✅ Proceed with custom features, post types, blocks

**For original project**:
- ✅ Proceed with all changes
- Document architectural changes in `/docs`

### Code Standards

**Follow these conventions** (see `docs/CONTRIBUTING.md` for full details):

**PHP**:
- Use strict types: `declare(strict_types=1);`
- Type all parameters and return values
- PSR-4 autoloading (namespace must match directory)
- Abstract classes: `Abstract*` prefix
- Interfaces: `*Interface` suffix
- Managers: `*Manager` suffix
- Helpers: `*Helper` suffix (static methods only)
- Services: Instantiated classes (not static)

**Custom Post Types** (CRITICAL):
- Slug MUST use `*_post` suffix: `vendor_post`, `project_post`
- Class name: `*Post` suffix: `VendorPost`, `ProjectPost`
- Max 20 characters for slug (WordPress limit)

**ACF Custom Fields** (CRITICAL):
- Meta key prefix: `post_type_{cpt_slug}_*`
- Example: `post_type_vendor_post_email`, `post_type_project_post_client`
- Models MUST implement `getMetaPrefix()` static method
- Use `AcfHelper::talampaya_replace_keys_from_acf_register_fields()` for field registration

**File naming**:
- PHP classes: `PascalCase.php` (match class name)
- Templates: `kebab-case.twig`
- Skip auto-discovery: `_filename.php` prefix

**Git commits**:
- Format: `type(scope): message`
- Types: `feat`, `fix`, `refactor`, `chore`, `docs`, `test`
- Example: `feat(acf): add testimonial block`

### PatternLab and Twig Templates Architecture (CRITICAL)

**IMPORTANT**: This project uses PatternLab for UI components. The build system copies PatternLab patterns to the WordPress theme.

**PatternLab components** (edit ONLY in `/patternlab/source/_patterns/`):
- `atoms/` - Basic UI elements (buttons, inputs, labels)
- `molecules/` - Combinations of atoms (cards, forms, navigation, lists)
- `organisms/` - Complex UI sections (headers, dashboards, sections, global)
- `templates/` - Page-level layouts
- `pages/` - Full page examples (for PatternLab preview)
- `macros/` - Reusable Twig macros

**Component Organization Rules (CRITICAL)**:

1. **Use Bootstrap 5 classes first** - Always use Bootstrap utility classes (`d-flex`, `gap-3`, `py-4`, `rounded`, `shadow`, etc.) before creating custom CSS. Only create custom styles for things Bootstrap doesn't cover.

2. **Organize by functional type, NOT by business context**:
   ```
   ✅ CORRECT:
   molecules/cards/category-card.twig
   molecules/cards/vendor-card.twig
   molecules/forms/search-hero.twig
   organisms/sections/category-slider.twig

   ❌ WRONG:
   molecules/marketplace/category-card.twig  <- NO context subfolders!
   organisms/marketplace/vendor-slider.twig  <- NO context subfolders!
   ```

3. **Co-locate component files** - Each component should have its files together:
   ```
   molecules/cards/
   ├── category-card.twig      # Required: Template
   ├── category-card.json      # Required: Example data
   ├── category-card.scss      # Optional: Custom styles (only if needed)
   ├── category-card.js        # Optional: JavaScript (only if needed)
   └── category-card.md        # Optional: Documentation
   ```

4. **Existing functional subdirectories**:
   - `molecules/cards/` - Card components
   - `molecules/forms/` - Form components
   - `molecules/navigation/` - Navigation elements
   - `molecules/lists/` - List items
   - `organisms/sections/` - Page sections
   - `organisms/global/` - Header, footer, site-wide
   - `organisms/dashboard/` - Dashboard components
   - `pages/vendors/` - Vendor-related pages

**Twig Syntax Rules (CRITICAL)**:

**NEVER use Twig control structures inside HTML attributes**. This causes linter errors: "A tag must consist of attributes or expressions. Twig Tags are not allowed"

```twig
❌ WRONG - Twig tags inside HTML attribute:
<a href="{{ base_url }}?page={{ page }}{% if filter %}&filter={{ filter }}{% endif %}">

✅ CORRECT - Build URL in variable first:
{% set url_params = 'page=' ~ page %}
{% if filter %}
    {% set url_params = url_params ~ '&filter=' ~ filter %}
{% endif %}
<a href="{{ base_url }}?{{ url_params }}">

✅ ALSO CORRECT - Use ternary operator for simple cases:
<a href="{{ base_url }}?page={{ page }}{{ filter ? '&filter=' ~ filter : '' }}">

✅ ALSO CORRECT - Use Twig's url_encode filter with merge:
{% set params = {page: page} %}
{% if filter %}{% set params = params|merge({filter: filter}) %}{% endif %}
<a href="{{ base_url }}?{{ params|url_encode }}">
```

**Rule**: All dynamic attribute values must be pure Twig expressions (`{{ }}`), never control structures (`{% %}`).

**WordPress-specific templates** (edit in `/src/theme/views/`):
- `layouts/` - Base Twig layouts with WordPress blocks (head, footer)
- `pages/` - Page templates that use WordPress/Timber functions. Generally is a copy of PatternLab pages with WP functions added.
- `components/` - WordPress-specific components (menus, comments)
- `includes/` - Partials with WordPress-specific code (analytics, pixels)

**Build flow**:
1. PatternLab patterns are in `/patternlab/source/_patterns/`
2. Gulp copies them to `/src/theme/views/atoms|molecules|organisms|templates/`
3. Final build goes to `/build/wp-content/themes/{theme}/views/`

**DO NOT manually create/edit files in**:
- `/src/theme/views/atoms/` - comes from PatternLab
- `/src/theme/views/molecules/` - comes from PatternLab
- `/src/theme/views/organisms/` - comes from PatternLab
- `/src/theme/views/templates/` - comes from PatternLab
- `/src/theme/views/macros/` - comes from PatternLab

These directories are overwritten on each build. Always edit the source in PatternLab.

**When to use WordPress-specific templates**:
- When you need Timber/WordPress functions that don't work in PatternLab
- For page-level templates that wire up PatternLab components
- Example: `/src/theme/views/pages/dashboard/admin/vendors.twig` uses PatternLab organisms

### Styles Architecture (CRITICAL)

**IMPORTANT**: All frontend styles MUST be created in PatternLab, NOT in the theme directory.

**PatternLab styles** (edit ONLY in `/patternlab/source/css/`):
- `scss/generic/_variables.scss` - Variables, colors, typography, spacing
- `scss/base/*.scss` - Base element styles (headings, text, forms, links)
- `scss/objects/*.scss` - Component styles (marketplace, vendor, buttons, etc.)
- `style.scss` - Main import file

**Style file structure**:

There are TWO valid locations for component styles:

**1. Co-located styles (PREFERRED for new components)**:
```
patternlab/source/_patterns/molecules/cards/
├── category-card.twig
├── category-card.json
└── category-card.scss    <- Styles specific to this component
```

**2. Centralized styles (for shared/base styles)**:
```
patternlab/source/css/
├── style.scss                    # Main entry - imports all partials
├── scss/
│   ├── vendor/                   # Bootstrap and third-party
│   ├── generic/                  # Variables and mixins
│   │   └── _variables.scss       # ALL variables go here
│   ├── base/                     # Base element styles
│   └── objects/                  # Shared component styles
│       ├── _marketplace.scss           # Dashboard/admin marketplace
│       ├── _marketplace-vendor.scss    # Vendor components
│       └── _marketplace-components.scss # JS components (toast, loading)
```

**Style priority**:
1. Use Bootstrap 5 utility classes first (d-flex, gap-3, py-4, etc.)
2. Use Bootstrap components (card, btn, badge, etc.)
3. Only then create custom styles for what Bootstrap doesn't cover

**DO NOT create styles in**:
- `/src/theme/assets/styles/` - ONLY for WordPress admin/backend specific styles
- Centralized files for component-specific styles (use co-located `.scss` instead)

**When to use theme styles** (`/src/theme/assets/styles/`):
- WordPress admin dashboard customizations
- Backend-only styles that modify wp-admin
- Styles that should NOT be in PatternLab preview

**Build flow for styles**:
1. SCSS files in `/patternlab/source/css/` are compiled
2. Co-located `.scss` files are processed by PatternLab
3. Compiled CSS is copied to theme during build

### What You Can Touch

**✅ Always safe to create/modify**:
- Custom post types in `/src/theme/src/Register/PostType/`
- Taxonomies in `/src/theme/src/Register/Taxonomy/`
- ACF blocks in `/src/theme/blocks/{block-name}/` (JSON, PHP, Twig in same directory)
- PatternLab patterns in `/patternlab/source/_patterns/` (atoms, molecules, organisms, templates)
- PatternLab styles in `/patternlab/source/css/scss/` (ALL frontend styles)
- WordPress page templates in `/src/theme/views/pages/`
- WordPress layouts in `/src/theme/views/layouts/`
- WordPress components in `/src/theme/views/components/`
- WordPress includes in `/src/theme/views/includes/`
- Twig extensions in `/src/theme/src/Core/TwigExtender/Custom/`
- Context extenders in `/src/theme/src/Core/ContextExtender/Custom/`
- Content generators in `/src/theme/src/Features/ContentGenerator/Generators/`
- Custom features in `/src/theme/src/Features/`
- Scripts in `/src/theme/assets/scripts/`
- Models in `/src/theme/src/Inc/Models/`
- Helpers in `/src/theme/src/Inc/Helpers/`
- Backend-only styles in `/src/theme/assets/styles/` (ONLY for wp-admin customizations)

**⚠️ Ask before modifying** (in forks):
- `/src/theme/src/Core/**` (core architecture)
- `/gulpfile.js` (build system)
- `/webpack.config.js` (webpack config)
- `/docker-compose.yml` (Docker setup)
- `/composer.json` (dependencies - OK to add, ASK before removing)
- `/package.json` (dependencies - OK to add, ASK before removing)
- `CLAUDE.md`, `README.md` (root documentation)

**❌ Never modify without explicit user request**:
- `.env` file (contains sensitive data)
- `/vendor/` (Composer dependencies)
- `/node_modules/` (NPM dependencies)
- `/build/` (compiled output)
- `/dist/` (production build)

### Auto-Discovery System

**Important**: Multiple systems auto-discover and register components. When creating new files:

**Auto-discovered**:
- Post types extending `AbstractPostType` in `/src/theme/src/Register/PostType/`
- Taxonomies extending `AbstractTaxonomy` in `/src/theme/src/Register/Taxonomy/`
- Plugin integrations implementing `PluginInterface` in `/src/theme/src/Core/Plugins/Integration/`
- Context extenders implementing `ContextExtenderInterface` in `/src/theme/src/Core/ContextExtender/Custom/`
- Twig extenders implementing `TwigExtenderInterface` in `/src/theme/src/Core/TwigExtender/Custom/`
- Content generators extending `AbstractContentGenerator` in `/src/theme/src/Features/ContentGenerator/Generators/`

**Skip auto-discovery**: Prefix filename with `_` (e.g., `_example.php`)

**After creating auto-discovered files**: Inform user that WordPress permalinks should be flushed:
```bash
docker compose exec wp wp rewrite flush
```

### Workflow Steps

**For new features**:
1. Check project type (original vs fork)
2. Read relevant documentation from `/docs`
3. Create necessary files (post types, blocks, templates, etc.)
4. Test the implementation
5. Inform user of next steps (flush permalinks, clear cache, etc.)

**For troubleshooting**:
1. Check `/docs/TROUBLESHOOTING.md` first
2. Review error logs location (inform user how to access)
3. Provide specific solution with file paths
4. Reference documentation for prevention

**For questions**:
1. Determine topic category
2. Read corresponding `/docs/*.md` file
3. Provide answer based on documentation
4. Include reference: "See `docs/FILE.md` for more details"

---

## Common Scenarios

### User asks: "How do I add a new post type?"

**Response**:
1. Read `/docs/COMMON-TASKS.md#adding-a-new-post-type`
2. Provide step-by-step from documentation
3. Create the file if requested
4. Remind user to flush permalinks

### User asks: "How does the build system work?"

**Response**:
1. Read `/docs/BUILD-SYSTEM.md`
2. Explain based on documentation
3. Reference specific sections
4. Provide command examples

### User asks: "My changes aren't showing up"

**Response**:
1. Check `/docs/TROUBLESHOOTING.md`
2. Ask clarifying questions (file changes? build running? cache cleared?)
3. Provide specific troubleshooting steps
4. Reference documentation sections

### User says: "Add a testimonial block"

**Response**:
1. Check project type (fork check)
2. Read `/docs/ACF-BLOCKS.md#creating-acf-blocks`
3. Create block directory `/src/theme/blocks/testimonial/`
4. Create block JSON in `/src/theme/blocks/testimonial/testimonial-block.json`
5. Create PHP file in `/src/theme/blocks/testimonial/testimonial-block.php` (ACF fields)
6. Create Twig template in `/src/theme/blocks/testimonial/testimonial-block.twig`
7. Block is auto-registered by ACF
8. Mention field group will auto-export to `/src/theme/acf-json/`

---

## Key Reminders

1. **Project type awareness**: Always check `APP_NAME` in `.env` before core modifications
2. **Documentation first**: Check `/docs` before answering or implementing
3. **File paths in responses**: Always include `file_path:line_number`
4. **Auto-discovery**: Inform user when files are auto-registered
5. **No repetition**: Don't duplicate documentation in this file
6. **Concise**: Direct, technical, actionable responses
7. **Code standards**: Follow conventions from `/docs/CONTRIBUTING.md`
8. **Git commits**: Use conventional commit format
9. **Fork safety**: Protect upstream compatibility in forks
10. **Testing**: Remind user to test changes and run quality checks

---

**Documentation version**: 2.4
**Last updated**: 2025-12-01

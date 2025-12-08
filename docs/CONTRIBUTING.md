# Contributing Guide

Guidelines and conventions for contributing to Talampaya.

## Table of Contents

- [Contributing Guide](#contributing-guide)
    - [Table of Contents](#table-of-contents)
    - [Code Style \& Standards](#code-style--standards)
        - [PHP](#php)
        - [JavaScript](#javascript)
        - [CSS/SCSS](#cssscss)
    - [Naming Conventions](#naming-conventions)
        - [PHP Classes](#php-classes)
        - [Files](#files)
        - [Variables](#variables)
        - [Custom Post Types](#custom-post-types)
        - [ACF Custom Fields](#acf-custom-fields)
    - [Class Architecture](#class-architecture)
        - [Helper Classes (Static)](#helper-classes-static)
        - [Service Classes (Instantiated)](#service-classes-instantiated)
        - [When to Use Static Methods](#when-to-use-static-methods)
    - [Git Workflow](#git-workflow)
        - [Branching Strategy](#branching-strategy)
        - [Commit Convention](#commit-convention)
        - [Pre-commit Hooks](#pre-commit-hooks)
    - [Code Quality](#code-quality)
        - [Linting](#linting)
        - [Formatting](#formatting)
        - [Testing](#testing)
    - [Documentation](#documentation)
    - [Pull Request Process](#pull-request-process)
    - [Syncing with Upstream](#syncing-with-upstream)
        - [Initial Setup](#initial-setup)
        - [Regular Sync Workflow](#regular-sync-workflow)
        - [Selective Updates](#selective-updates)
        - [Handling Conflicts](#handling-conflicts)
        - [Best Practices for Fork Maintenance](#best-practices-for-fork-maintenance)
        - [Automation](#automation)
    - [Best Practices](#best-practices)

## Code Style & Standards

### PHP

**Requirements**:
- PHP 8.0+ features (strict typing, return types, named arguments)
- Follow WordPress Coding Standards where applicable
- Use type hints and return types
- Prefer composition over inheritance

**Example**:
```php
<?php

declare(strict_types=1);

namespace App\Helpers;

class PostHelper
{
    /**
     * Get post excerpt with word limit
     *
     * @param string $content Post content
     * @param int $word_limit Maximum words
     * @return string Truncated excerpt
     */
    public static function getExcerpt(string $content, int $word_limit = 55): string
    {
        $words = explode(' ', strip_tags($content));

        if (count($words) <= $word_limit) {
            return $content;
        }

        $excerpt = implode(' ', array_slice($words, 0, $word_limit));
        return $excerpt . '...';
    }
}
```

**Key points**:
- Use `declare(strict_types=1)` at the top of files
- Type all parameters and return values
- Document methods with PHPDoc
- Use meaningful variable names
- Keep methods focused and small

### JavaScript

**Requirements**:
- ES6+ syntax (transpiled by Babel)
- Use `const` and `let`, avoid `var`
- Use arrow functions where appropriate
- ESLint for linting
- Prettier for formatting

**Example**:
```javascript
/**
 * Format price with currency symbol
 * @param {number} price - Price value
 * @param {string} currency - Currency symbol
 * @returns {string} Formatted price
 */
const formatPrice = (price, currency = '$') => {
        return `${currency}${price.toFixed(2)}`;
    };

export default formatPrice;
```

### CSS/SCSS

**Requirements**:
- SCSS syntax
- BEM methodology for class names (where applicable)
- Stylelint for linting
- Mobile-first responsive design

**Example**:
```scss
.card {
    padding: 1rem;
    border: 1px solid #ccc;

    &__title {
        font-size: 1.5rem;
        font-weight: bold;
    }

    &__content {
        margin-top: 1rem;
    }

    &--featured {
        border-color: #007bff;
    }

    @media (min-width: 768px) {
        padding: 2rem;
    }
}
```

## Naming Conventions

### PHP Classes

| Type | Convention | Example |
|------|-----------|---------|
| Abstract classes | `Abstract*` prefix | `AbstractPostType`, `AbstractTaxonomy` |
| Interfaces | `*Interface` suffix | `PluginInterface`, `ContextExtenderInterface` |
| Managers | `*Manager` suffix | `PluginManager`, `TwigManager` |
| Helpers | `*Helper` suffix | `PostHelper`, `ImageHelper` |
| Utils | `*Utils` suffix | `FileUtils`, `StringUtils` |
| Taxonomies | `*Taxonomy` suffix | `ProductSeriesTaxonomy`, `EpicTaxonomy` |
| Models | Singular noun | `Product`, `Project` |

### Files

- **Class files**: PascalCase matching class name (PSR-4)
    - `PostHelper.php`
    - `ProductPostType.php`

- **Skip auto-discovery**: Prefix with `_`
    - `_example.php`
    - `_template.php`

- **Templates**: kebab-case
    - `single-product.twig`
    - `archive-post.twig`

### Variables

- **PHP**: camelCase
  ```php
  $postTitle = 'Example';
  $isPublished = true;
  ```

- **JavaScript**: camelCase
  ```javascript
  const postTitle = 'Example';
  const isPublished = true;
  ```

- **CSS/SCSS**: kebab-case
  ```scss
  $primary-color: #007bff;
  $font-size-base: 16px;
  ```

### Custom Post Types

**IMPORTANT**: All Custom Post Types MUST follow these naming conventions:

**Slug naming**:
- Must use `*_post` suffix
- Maximum length: 20 characters (WordPress limit)
- Examples: `product_post`, `sector_post`, `project_post`
- For compound names, abbreviate if needed: `product_cat_post` (not `product_category_post`)

**Class naming**:
- Must use `*Post` suffix (NOT `*PostType`)
- PascalCase format
- Examples: `ProductPost`, `SectorPost`, `ProjectPost`, `ProductCategoryPost`

**File naming**:
- Class file: Match class name with `.php` extension
    - `ProductPost.php`
    - `SectorPost.php`
    - `ProductCategoryPost.php`

- Controller (single): `single-{cpt_slug}.php`
    - `single-product_post.php`
    - `single-sector_post.php`
    - `single-product_cat_post.php`

- Controller (archive): `archive-{cpt_slug}.php`
    - `archive-product_post.php`
    - `archive-sector_post.php`

- View files: `{template}-{cpt_slug}.twig`
    - `single-product_post.twig`
    - `archive-product_post.twig`

**Model naming**:
- Singular noun without `_post` suffix
- PascalCase format
- Examples: `Product`, `Sector`, `Project`, `ProductCategory`

**Complete example**:

```php
// File: src/Register/PostType/ProductPostType.php
class ProductPostType extends AbstractPostType
{
    protected string $slug = "product_post"; // 12 characters ✅
}

// File: src/Inc/Models/ProductPost.php
class ProductPost extends AbstractPost
{
    public function custom_id(): ?string
    {
        return $this->meta("post_type_product_post_custom_id");
    }
}

// File: single-product_post.php (controller)
$product = Timber::get_post(get_the_ID(), Product::class);
$templates = ["@pages/single-product_post.twig"];
Timber::render($templates, $context);
```

**Naming reference table**:

| CPT Slug | Class Name | Model Name | Controller | View |
|----------|------------|------------|------------|------|
| `product_post` | `ProductPost` | `Product` | `single-product_post.php` | `single-product_post.twig` |
| `sector_post` | `SectorPost` | `Sector` | `single-sector_post.php` | `single-sector_post.twig` |
| `product_cat_post` | `ProductCategoryPost` | `ProductCategory` | `single-product_cat_post.php` | `single-product_cat_post.twig` |

### ACF Custom Fields

**IMPORTANT**: All ACF custom fields for Custom Post Types MUST follow these naming conventions:

**Meta key prefix**:
- Format: `post_type_{cpt_slug}_*`
- Examples:
  - `post_type_product_post_price`
  - `post_type_vendor_post_email`
  - `post_type_project_post_client_name`

**Why this convention?**:
- Avoids conflicts between different post types
- Clear identification of field ownership
- Consistent across all custom post types
- Enables automatic field name transformation via `AcfHelper`

**Model implementation**:

Each model MUST implement a static `getMetaPrefix()` method:

```php
<?php

declare(strict_types=1);

namespace App\Inc\Models;

use Timber\Post;

class VendorPost extends Post
{
    /**
     * Returns the meta key prefix for this post type
     */
    public static function getMetaPrefix(): string
    {
        return "post_type_vendor_post_";
    }

    /**
     * Example getter using the prefix
     */
    public function getEmail(): string
    {
        return $this->meta(self::getMetaPrefix() . "email") ?: "";
    }

    public function getPhone(): string
    {
        return $this->meta(self::getMetaPrefix() . "phone") ?: "";
    }
}
```

**ACF field registration**:

When registering ACF fields, use the `AcfHelper::talampaya_replace_keys_from_acf_register_fields()` function which automatically adds the prefix:

```php
<?php
// File: src/Features/Acf/Fields/Vendor/VendorFields.php

use App\Inc\Helpers\AcfHelper;

class VendorFields
{
    private string $post_type = "vendor_post";

    private function registerContactGroup(): void
    {
        // Field names WITHOUT prefix - helper adds it automatically
        $fields = [
            ["email", "email", 50, __("Email", "flavor"), 1],
            ["phone", "text", 50, __("Teléfono", "flavor"), 0],
            ["whatsapp", "text", 50, __("WhatsApp", "flavor"), 0],
        ];

        // Helper transforms "email" → "post_type_vendor_post_email"
        acf_add_local_field_group(
            AcfHelper::talampaya_replace_keys_from_acf_register_fields(
                $this->post_type,
                "group_vendor_post_contact",
                __("Contacto", "flavor"),
                $fields,
                [["param" => "post_type", "operator" => "==", "value" => $this->post_type]],
                20
            )
        );
    }
}
```

**Field naming reference table**:

| Field Name (in code) | Meta Key (in database) | Access in Model |
|---------------------|------------------------|-----------------|
| `email` | `post_type_vendor_post_email` | `$this->meta(self::getMetaPrefix() . "email")` |
| `phone` | `post_type_vendor_post_phone` | `$this->meta(self::getMetaPrefix() . "phone")` |
| `social_instagram` | `post_type_vendor_post_social_instagram` | `$this->meta(self::getMetaPrefix() . "social_instagram")` |

**Best practices**:
1. Always use `getMetaPrefix()` when accessing meta in models
2. Never hardcode meta keys like `"vendor_email"` - always use the prefix
3. Group related fields with sub-prefixes: `social_facebook`, `social_instagram`
4. Use the ACF helper for field registration to ensure consistent naming

## Class Architecture

### Helper Classes (Static)

**When to use**:
- Pure functions without internal state
- Utility methods that don't require `$this`
- Functions that can be used without instantiation

**Characteristics**:
- All methods are `static`
- No instance properties (`$this` never used)
- Stateless

**Example**:
```php
<?php

namespace App\Helpers;

class JsonHelper
{
    public static function decode(string $json): array
    {
        return json_decode($json, true) ?? [];
    }

    public static function encode(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

// Usage
$data = JsonHelper::decode($jsonString);
```

### Service Classes (Instantiated)

**When to use**:
- Classes with internal state
- Dependency injection needed
- Configuration or context required

**Characteristics**:
- Instantiated with `new Service()`
- Can have instance properties
- May accept dependencies in constructor

**Example**:
```php
<?php

namespace App\Services;

class NewsletterService
{
    private Mailer $mailer;
    private array $config;

    public function __construct(Mailer $mailer, array $config = [])
    {
        $this->mailer = $mailer;
        $this->config = $config;
    }

    public function send(string $email): void
    {
        $this->mailer->send($email, $this->config['template']);
    }
}

// Usage
$service = new NewsletterService($mailer, $config);
$service->send('user@example.com');
```

### When to Use Static Methods

**Use static** when:
- Method doesn't depend on instance properties
- Function is a pure utility (same input = same output)
- No need to maintain state between calls

**Avoid static** when:
- Method depends on instance properties
- Class will be extended and methods overridden
- Need to manage configuration or state

**Comparison**:
```php
// ✓ Good - Static helper
JsonHelper::decode($json);

// ✗ Bad - Unnecessary instantiation
$helper = new JsonHelper();
$helper->decode($json);

// ✓ Good - Service with state
$newsletter = new NewsletterService($mailer);
$newsletter->send($email);

// ✗ Bad - Static service with state
NewsletterService::send($email); // Where does $mailer come from?
```

## Git Workflow

### Branching Strategy

**Main branches**:
- `master` - Production-ready code
- `develop` - Integration branch for features

**Supporting branches**:
- `feature/*` - New features
    - Example: `feature/product-catalog`

- `hotfix/*` - Emergency fixes for production
    - Example: `hotfix/cart-bug`

**Workflow**:
1. Create feature branch from `develop`:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/my-feature
   ```

2. Work on feature and commit

3. Push and create pull request to `develop`

4. After review and merge, delete feature branch

### Commit Convention

**Format**: `type(scope): message`

**Types**:
- `feat` - New feature
- `fix` - Bug fix
- `refactor` - Code refactoring (no functional changes)
- `chore` - Maintenance tasks (dependencies, config)
- `docs` - Documentation changes
- `test` - Test additions/modifications
- `style` - Code style changes (formatting, missing semicolons)
- `perf` - Performance improvements

**Scope** (optional): Component affected
- `acf`, `twig`, `docker`, `build`, etc.

**Examples**:
```bash
feat(acf): add testimonial block
fix(twig): resolve context data issue in footer
refactor(helpers): simplify PostHelper methods
docs(readme): update installation instructions
chore(deps): update Timber to v2.1.5
test(models): add Product model tests
```

**Rules**:
- Use imperative mood ("add" not "added")
- Lowercase (except proper nouns)
- No period at the end
- Max 72 characters

### Pre-commit Hooks

Husky + lint-staged run automatically on commit:

**Runs**:
- **Prettier** on staged files (auto-format)
- **ESLint** on JS/TS files
- **Stylelint** on SCSS files
- **Commitlint** on commit message

**Bypass** (not recommended):
```bash
git commit --no-verify
```

**Fix issues before commit**:
```bash
npm run prettier:write
npm run lint:fix
```

## Code Quality

### Linting

**JavaScript**:
```bash
npm run lint:js
npm run lint:fix  # Auto-fix
```

**PHP**:
```bash
composer phpcs  # PHP CodeSniffer
composer phpcbf  # Auto-fix
```

### Formatting

**All files**:
```bash
npm run prettier:check  # Check formatting
npm run prettier:write  # Auto-format
```

### Testing

**PHP**:
```bash
npm test  # or composer test
```

**JavaScript**:
```bash
npm run test:js
```

See [TESTING.md](TESTING.md) for detailed testing guide.

## Documentation

**Document**:
- Public methods (PHPDoc)
- Complex logic (inline comments)
- Architecture decisions (docs/)
- API endpoints
- Configuration options

**Example PHPDoc**:
```php
/**
 * Get posts by taxonomy term
 *
 * @param string $taxonomy Taxonomy name
 * @param string|int $term Term slug or ID
 * @param int $limit Posts per page
 * @return array<Post> Array of Post objects
 * @throws \InvalidArgumentException If taxonomy doesn't exist
 */
public static function getPostsByTerm(
    string $taxonomy,
    string|int $term,
    int $limit = 10
): array {
    // ...
}
```

## Pull Request Process

1. **Update your branch** with latest `develop`:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout feature/my-feature
   git merge develop
   ```

2. **Ensure all checks pass**:
    - Linting (ESLint, Stylelint, PHPCS)
    - Tests (PHPUnit, Jest)
    - Build succeeds

3. **Create pull request**:
    - Descriptive title
    - Clear description of changes
    - Reference related issues
    - Screenshots (if UI changes)

4. **Address review feedback**

5. **Squash commits** (if requested)

6. **Merge** (after approval)

## Syncing with Upstream

If you forked Talampaya for a custom project and want to receive updates from the original repository:

### Initial Setup

1. **Add upstream remote** (one-time setup):
   ```bash
   git remote add upstream https://github.com/yourusername/talampaya.git
   git fetch upstream
   ```

2. **Verify remotes**:
   ```bash
   git remote -v
   # Should show:
   # origin    https://github.com/your-fork/your-project.git (fetch)
   # origin    https://github.com/your-fork/your-project.git (push)
   # upstream  https://github.com/yourusername/talampaya.git (fetch)
   # upstream  https://github.com/yourusername/talampaya.git (push)
   ```

### Regular Sync Workflow

**Option 1: Merge upstream changes** (recommended):

1. **Fetch latest changes**:
   ```bash
   git fetch upstream
   ```

2. **Checkout your main branch**:
   ```bash
   git checkout develop  # or master, depending on your setup
   ```

3. **Merge upstream changes**:
   ```bash
   git merge upstream/master
   ```

4. **Resolve conflicts** (if any):
    - Review conflicting files
    - Manually merge changes
    - Test thoroughly
    - Commit resolved conflicts

5. **Push to your fork**:
   ```bash
   git push origin develop
   ```

**Option 2: Rebase onto upstream** (clean history):

```bash
git fetch upstream
git checkout develop
git rebase upstream/master
git push origin develop --force-with-lease
```

**Warning**: Only use rebase if you haven't shared your branch with others.

### Selective Updates

To cherry-pick specific commits from upstream:

```bash
# Find the commit hash from upstream
git log upstream/master

# Cherry-pick specific commit
git cherry-pick <commit-hash>
```

### Handling Conflicts

**Common conflict areas in forks**:
- Core architecture files (`/src/theme/src/Core/**`)
- Build configuration (`gulpfile.js`, `webpack.config.js`)
- Documentation (`README.md`, `CLAUDE.md`)

**Resolution strategy**:
1. Accept upstream changes for core files (unless you have critical customizations)
2. Keep your changes for project-specific files
3. Manually merge documentation updates

**Example conflict resolution**:
```bash
# After merge conflict
git status  # See conflicting files

# For each file, choose:
git checkout --ours file.php    # Keep your version
git checkout --theirs file.php  # Accept upstream version
# Or manually edit and resolve

git add file.php
git commit -m "chore: resolve merge conflicts with upstream"
```

### Best Practices for Fork Maintenance

1. **Sync regularly**: Weekly or monthly to avoid large conflict batches
2. **Test after sync**: Run full test suite after merging upstream
3. **Document custom changes**: Use `docs/FORK-*.md` for fork-specific docs
4. **Avoid modifying core**: Stick to custom features to ease upstream merging
5. **Track upstream**: Monitor Talampaya releases and changelogs

### Automation

Create a script to automate syncing (`scripts/sync-upstream.sh`):
```bash
#!/bin/bash
echo "Fetching upstream changes..."
git fetch upstream

echo "Merging upstream/master into develop..."
git checkout develop
git merge upstream/master

if [ $? -eq 0 ]; then
  echo "Sync successful! Running tests..."
  npm test
else
  echo "Merge conflicts detected. Please resolve manually."
  exit 1
fi
```

Make executable:
```bash
chmod +x scripts/sync-upstream.sh
./scripts/sync-upstream.sh
```

---

## Best Practices

1. **Separation of concerns**:
    - Keep business logic in PHP
    - Use Twig for presentation only

2. **DRY (Don't Repeat Yourself)**:
    - Extract reusable functions to helpers
    - Create reusable Twig components

3. **Single Responsibility**:
    - Each class/method should have one clear purpose
    - Small, focused functions

4. **Type safety**:
    - Always use type hints and return types
    - Enable `strict_types`

5. **Error handling**:
    - Use try/catch for expected errors
    - Validate input data
    - Provide meaningful error messages

6. **Performance**:
    - Avoid N+1 queries (use `Timber::get_posts()` batch loading)
    - Cache expensive operations
    - Optimize images and assets

7. **Security**:
    - Escape output in templates: `{{ variable|e }}`
    - Sanitize input: `sanitize_text_field()`
    - Validate data before use
    - Use nonces for forms

8. **Accessibility**:
    - Semantic HTML
    - ARIA labels where needed
    - Keyboard navigation
    - Color contrast

---

**Thank you for contributing to Talampaya!**

For questions, open an issue or discussion on GitHub.

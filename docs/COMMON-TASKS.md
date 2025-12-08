# Common Tasks

Step-by-step guides for common development tasks in Talampaya.

## Table of Contents

- [Common Tasks](#common-tasks)
    - [Table of Contents](#table-of-contents)
    - [Cleaning Scaffolding (For Forks)](#cleaning-scaffolding-for-forks)
    - [Adding a New Post Type](#adding-a-new-post-type)
    - [Adding a New Taxonomy](#adding-a-new-taxonomy)
    - [Creating ACF Blocks](#creating-acf-blocks)
    - [Adding Twig Extensions](#adding-twig-extensions)
    - [Adding Context Data](#adding-context-data)
    - [Adding a New Feature Module](#adding-a-new-feature-module)
    - [Generating Demo Content](#generating-demo-content)
    - [Creating a Custom Model](#creating-a-custom-model)
    - [Adding a Menu Location](#adding-a-menu-location)
    - [Adding a Sidebar](#adding-a-sidebar)
    - [Customizing Admin](#customizing-admin)

## Cleaning Scaffolding (For Forks)

When creating a new WordPress theme from a Talampaya fork, you'll want to remove the example/scaffolding content from PatternLab while keeping the project structure intact.

**Use case**: Start a fresh theme without example atoms, molecules, organisms, templates, or pages from PatternLab.

### Running the Script

```bash
# Interactive mode (recommended)
npm run clean:scaffolding

# With options
npm run clean:scaffolding -- --help      # Show help
npm run clean:scaffolding -- --dry-run   # Preview changes without making them
npm run clean:scaffolding -- --yes       # Non-interactive mode (delete all)
npm run clean:scaffolding -- --verbose   # Show detailed output
```

### What the Script Does

1. **Processes PatternLab patterns** in `/patternlab/source/_patterns/`:
   - `atoms/` - Basic building blocks (buttons, forms, images, etc.)
   - `molecules/` - Component combinations (navigation, blocks, etc.)
   - `organisms/` - Complex sections (header, footer, etc.)
   - `templates/` - Page layouts
   - `pages/` - Example pages with demo data
   - `macros/` - Twig macros

2. **Processes SCSS styles** in `/patternlab/source/css/scss/`:
   - `objects/` - Component-specific styles
   - `base/` - Element base styles

3. **Updates theme views** in `/src/theme/views/pages/`:
   - Replaces PatternLab includes (`@templates/*`) with minimal local templates
   - Templates become self-contained without PatternLab dependencies

### Interactive Options

For each file/directory, you can choose:
- `[e]` **Eliminar** - Delete the file/directory
- `[m]` **Mantener** - Keep the file/directory
- `[v]` **Ver** - View file contents before deciding
- `[s]` **Saltar** - Skip all similar items
- `[q]` **Salir** - Exit and show summary

### Modified Files Detection

The script detects if files have been modified from the original (via git). Modified files are marked with `[modificado]` to help you decide whether to keep customizations or delete.

### Minimal Templates

When updating `views/pages/*.twig`, the script replaces PatternLab includes with minimal, self-contained templates that:
- Extend `layouts/base.twig`
- Include basic header/footer structure
- Support WordPress functions (`wp_nav_menu`, `dynamic_sidebar`, etc.)
- Are ready for customization

### Post-Cleanup Steps

After running the script:

```bash
# 1. Review changes
git diff

# 2. If satisfied, commit
git add -A
git commit -m "chore: clean scaffolding for new project"

# 3. Rebuild assets
npm run build

# 4. Test in browser
npm run start
```

### What's Preserved

The script keeps:
- Directory structure (empty directories for your new patterns)
- PatternLab configuration (`patternlab-config.json`, `alter-twig.php`)
- SCSS variables and mixins (`generic/_variables.scss`, `generic/_mixins.scss`)
- Core theme files (`src/theme/src/Core/`, etc.)

## Adding a New Post Type

**Time**: 5 minutes

**Steps**:

1. **Create class** in `/src/theme/src/Register/PostType/`:
   ```php
   <?php

   namespace App\Register\PostType;

   use App\Register\PostType\AbstractPostType;

   class ProductPostType extends AbstractPostType
   {
       protected function configure(): array
       {
           return [
               'labels' => [
                   'name' => 'Products',
                   'singular_name' => 'Product',
                   'add_new_item' => 'Add New Product',
                   'edit_item' => 'Edit Product',
               ],
               'public' => true,
               'has_archive' => true,
               'show_in_rest' => true,
               'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
               'menu_icon' => 'dashicons-cart',
               'rewrite' => ['slug' => 'products'],
               'taxonomies' => ['product_category'],
           ];
       }

       public function getSlug(): string
       {
           return 'product';
       }
   }
   ```

2. **Save file** - Auto-discovered and registered by `RegisterManager`

3. **Flush rewrite rules**:
   ```bash
   docker compose exec wp wp rewrite flush
   ```

4. **Verify** in WordPress admin → Products menu should appear

**Next steps**:
- Add ACF field group for product fields (see note below about multiple field groups)
- Create Model for the post type (recommended)
- Register Model in Timber classmap
- Create Controller for context preparation (recommended)
- Create custom template files (keep them minimal)

### Creating a Model for the Post Type

Models extend `Timber\Post` to add custom methods and functionality. They should follow the `*Post` naming convention.

**IMPORTANT**: Model file names MUST end with `Post` (e.g., `ProductPost.php`, `ProductCategoryPost.php`).

1. **Create model** in `/src/theme/src/Inc/Models/ProductPost.php`:

```php
<?php

namespace App\Inc\Models;

class ProductPost extends AbstractPost
{
    /**
     * Get custom ID
     */
    public function custom_id(): ?string
    {
        return $this->meta('post_type_product_post_custom_id');
    }

    /**
     * Get post title
     */
    public function title(): string
    {
        return $this->post_title;
    }

    /**
     * Get main image
     */
    public function image(): ?array
    {
        return $this->meta("post_type_product_post_main_image");
    }

    /**
     * Get product tag
     */
    public function tag(): ?string
    {
        return $this->meta("post_type_product_post_tag");
    }

    /**
     * Get description
     */
    public function description(): ?string
    {
        return $this->meta("post_type_product_post_description");
    }

    /**
     * Get card data (only data, no presentation properties)
     * Controllers add presentation properties (type, btn, classes, etc.)
     */
    public function getCardData(): array
    {
        $image = $this->image();

        return [
            "image" => $image ? $image["url"] : null,
            "tag" => $this->tag(),
            "title" => $this->title(),
            "description" => $this->description(),
            "url" => $this->link(),
        ];
    }
}
```

2. **Register in Timber classmap** in `/src/theme/src/TalampayaStarter.php`:

Add filter hooks in constructor:
```php
add_filter("timber/post/classmap", [$this, "extendPostClassmap"]);
add_filter("timber/term/classmap", [$this, "extendTermClassmap"]);
```

Add method for post classmap:
```php
public function extendPostClassmap(array $classmap): array
{
    $custom_classmap = [
        "product_post" => \App\Inc\Models\ProductPost::class,
        // Add more post types here
    ];

    return array_merge($classmap, $custom_classmap);
}
```

Add method for term classmap (if needed):
```php
public function extendTermClassmap(array $classmap): array
{
    $custom_classmap = [
        "product_series" => \App\Inc\Models\ProductSeries::class,
        // Add more taxonomies here
    ];

    return array_merge($classmap, $custom_classmap);
}
```

**Benefits of Timber classmap**:
- Timber automatically uses your custom models when fetching posts
- `Timber::get_post()` returns instance of your model class
- All custom methods available without explicit class specification
- Example: `$product = Timber::get_post($id); $product->image();`

See [APPLICATION-LAYER.md#timber-classmap-integration](APPLICATION-LAYER.md#timber-classmap-integration) for complete details.

**Important: Avoiding ACF Field Name Conflicts**

When creating **multiple field groups** for the same post type, ensure each group uses a unique key to prevent field name conflicts:

```php
foreach ($groups as $group) {
    $group_key = Str::snake($group[0]);           // e.g., "main", "products_related"
    $unique_key = $this->post_type . "_" . $group_key;  // e.g., "product_cat_post_main"

    $field_group = [
        "key" => $group_key,
        "title" => __($group[0], "talampaya"),
        "fields" => $group[1],
        // ... rest of configuration
    ];

    acf_add_local_field_group(
        AcfHelper::talampaya_replace_keys_from_acf_register_fields(
            $field_group,
            $unique_key,  // Use unique key, not just post_type
            "post_type"
        )
    );
}
```

**Why this matters**: If you have fields with the same name (e.g., "items", "title", "tag") in different field groups, using only `$this->post_type` as the key will cause conflicts. The helper function generates field names like:
- Without unique key: `post_type_product_cat_post_items` (conflicts!)
- With unique key: `post_type_product_cat_post_products_related_items` (unique ✓)

See [TROUBLESHOOTING.md](TROUBLESHOOTING.md#acf-field-name-conflicts) for more details.

**Creating template files** (optional - only if you need custom logic):

WordPress template files should be **minimal** and only act as entry points. All logic belongs in Controllers.

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
$context = Timber::context();
$context['featured'] = get_field('featured', $post->ID);
$context['related'] = Timber::get_posts(['post_type' => 'product'...]);
// ... more logic
Timber::render('@pages/single-product.twig', $context);
```

See [APPLICATION-LAYER.md](APPLICATION-LAYER.md) for complete guide on Controllers, Services, Models, Traits, and Helpers.

## Adding a New Taxonomy

**Time**: 5 minutes

**Steps**:

1. **Create class** in `/src/theme/src/Register/Taxonomy/`:
   ```php
   <?php

   namespace App\Register\Taxonomy;

   use App\Register\Taxonomy\AbstractTaxonomy;

   class ProductCategoryTaxonomy extends AbstractTaxonomy
   {
       protected function configure(): array
       {
           return [
               'labels' => [
                   'name' => 'Product Categories',
                   'singular_name' => 'Product Category',
                   'search_items' => 'Search Categories',
                   'all_items' => 'All Categories',
               ],
               'hierarchical' => true,
               'show_in_rest' => true,
               'show_admin_column' => true,
               'rewrite' => ['slug' => 'product-category'],
           ];
       }

       public function getSlug(): string
       {
           return 'product_category';
       }

       public function getPostTypes(): array
       {
           return ['product'];
       }
   }
   ```

2. **Save file** - Auto-discovered and registered

3. **Flush rewrite rules**:
   ```bash
   docker compose exec wp wp rewrite flush
   ```

4. **Verify** in WordPress admin → Product Categories should appear

## Creating ACF Blocks

**Time**: 15 minutes

Each ACF block is a self-contained directory with 3 files: JSON, PHP, and Twig.

**Steps**:

1. **Create block directory**:
   ```bash
   mkdir /src/theme/blocks/testimonial
   ```

2. **Create block JSON** in `/src/theme/blocks/testimonial/testimonial-block.json`:
   ```json
   {
     "name": "acf/testimonial",
     "title": "Testimonial",
     "description": "Display a customer testimonial",
     "category": "theme",
     "icon": "format-quote",
     "keywords": ["testimonial", "quote", "review"],
     "acf": {
       "mode": "preview",
       "renderCallback": "App\\Features\\Acf\\Blocks\\BlockRenderer::render"
     },
     "supports": {
       "align": ["wide", "full"],
       "anchor": true
     },
     "example": {
       "attributes": {
         "mode": "preview",
         "data": {
           "testimonial_text": "Great product!",
           "testimonial_author": "John Doe",
           "testimonial_rating": "5"
         }
       }
     }
   }
   ```

3. **Create PHP file** in `/src/theme/blocks/testimonial/testimonial-block.php`:
   ```php
   <?php

   use Illuminate\Support\Str;
   use App\Inc\Helpers\AcfHelper;

   function add_acf_block_testimonial(): void
   {
       $key = "testimonial";
       $key_underscore = Str::snake($key);
       $key_dash = str_replace("_", "-", $key_underscore);
       $title = Str::title(str_replace("_", " ", $key_underscore));
       $block_title = __($title, "talampaya");

       $fields = [
           ["testimonial_text", "textarea"],
           ["testimonial_author"],
           ["testimonial_rating", "number", 100, null, 0, ["min" => 1, "max" => 5]],
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
   add_action("acf/init", "add_acf_block_testimonial", 10);
   ```

4. **Create Twig template** in `/src/theme/blocks/testimonial/testimonial-block.twig`:
   ```twig
   {# Block: Testimonial #}
   <div class="testimonial {{ block.classes }}">
     {% if fields.testimonial_text %}
       <blockquote class="testimonial__quote">
         {{ fields.testimonial_text }}
       </blockquote>
     {% endif %}

     {% if fields.testimonial_author %}
       <cite class="testimonial__author">
         — {{ fields.testimonial_author }}
       </cite>
     {% endif %}

     {% if fields.testimonial_rating %}
       <div class="testimonial__rating">
         {% for i in 1..fields.testimonial_rating %}
           ★
         {% endfor %}
       </div>
     {% endif %}
   </div>
   ```

5. **Block is auto-registered** - ACF automatically discovers and registers the block from the JSON file.

6. **(Optional) Create context modifier** in `/src/theme/src/Features/Acf/Blocks/Modifiers/TestimonialBlockModifier.php`:
   ```php
   <?php

   namespace App\Features\Acf\Blocks\Modifiers;

   class TestimonialBlockModifier
   {
       public static function modify(array $context): array
       {
           // Add computed values
           $context['rating_percentage'] = ($context['fields']['testimonial_rating'] ?? 0) * 20;

           return $context;
       }
   }
   ```

7. **Register modifier** using `BlockRenderer::registerContextModifier()`:
   ```php
   BlockRenderer::registerContextModifier('testimonial', [TestimonialBlockModifier::class, 'modify']);
   ```

8. **Test** in Gutenberg editor - block should appear in "Theme" category

**Note**: ACF fields are defined programmatically in the PHP file (step 3). Field groups will also auto-export to `/src/theme/acf-json/` if you create/edit them in WordPress admin.

See [ACF-BLOCKS.md](ACF-BLOCKS.md) for detailed documentation.

## Adding Twig Extensions

**Time**: 10 minutes

**Steps**:

1. **Create class** in `/src/theme/src/Core/TwigExtender/Custom/`:
   ```php
   <?php

   namespace App\Core\TwigExtender\Custom;

   use App\Core\TwigExtender\TwigExtenderInterface;
   use Twig\TwigFilter;
   use Twig\TwigFunction;

   class MyTwigExtension implements TwigExtenderInterface
   {
       public function extendTwig(\Twig\Environment $twig): \Twig\Environment
       {
           // Add custom filter
           $twig->addFilter(new TwigFilter('format_price', function ($price) {
               return '$' . number_format($price, 2);
           }));

           // Add custom function
           $twig->addFunction(new TwigFunction('get_site_logo', function () {
               return get_custom_logo();
           }));

           return $twig;
       }
   }
   ```

2. **Save file** - Auto-discovered and loaded by `TwigManager`

3. **Use in templates**:
   ```twig
   {# Use filter #}
   {{ product.price|format_price }}

   {# Use function #}
   {{ get_site_logo() }}
   ```

See [TIMBER-TWIG.md](TIMBER-TWIG.md#custom-twig-extensions) for more examples.

## Adding Context Data

**Time**: 10 minutes

**Steps**:

1. **Create class** in `/src/theme/src/Core/ContextExtender/Custom/`:
   ```php
   <?php

   namespace App\Core\ContextExtender\Custom;

   use App\Core\ContextExtender\ContextExtenderInterface;

   class SiteInfoContext implements ContextExtenderInterface
   {
       public function extendContext(array $context): array
       {
           $context['site_info'] = [
               'phone' => get_option('site_phone'),
               'email' => get_option('site_email'),
               'address' => get_option('site_address'),
               'social' => [
                   'facebook' => get_option('facebook_url'),
                   'twitter' => get_option('twitter_url'),
               ],
           ];

           return $context;
       }
   }
   ```

2. **Save file** - Auto-discovered and merged into context

3. **Use in templates**:
   ```twig
   <a href="tel:{{ site_info.phone }}">{{ site_info.phone }}</a>
   <a href="mailto:{{ site_info.email }}">{{ site_info.email }}</a>
   <a href="{{ site_info.social.facebook }}">Facebook</a>
   ```

See [TIMBER-TWIG.md](TIMBER-TWIG.md#extending-context) for more details.

## Adding a New Feature Module

**Time**: 30 minutes

**Steps**:

1. **Create directory** in `/src/theme/src/Features/YourFeature/`

2. **Create main class**:
   ```php
   <?php

   namespace App\Features\YourFeature;

   class YourFeature
   {
       public function __construct()
       {
           $this->init();
       }

       private function init(): void
       {
           add_action('init', [$this, 'register']);
           add_filter('the_content', [$this, 'modifyContent']);
       }

       public function register(): void
       {
           // Feature initialization
       }

       public function modifyContent(string $content): string
       {
           // Content modification
           return $content;
       }
   }
   ```

3. **Register in bootstrap.php**:
   ```php
   use App\Features\YourFeature\YourFeature;

   new YourFeature();
   ```

4. **Add tests** in `/src/theme/tests/test-your-feature.php`

## Generating Demo Content

**Time**: 20 minutes

**Steps**:

1. **Create generator** in `/src/theme/src/Features/ContentGenerator/Generators/`:
   ```php
   <?php

   namespace App\Features\ContentGenerator\Generators;

   use App\Features\ContentGenerator\AbstractContentGenerator;

   class ProductGenerator extends AbstractContentGenerator
   {
       public function __construct()
       {
           parent::__construct('products_generated');
       }

       public function getPriority(): int
       {
           return 10; // Default priority
       }

       protected function generateContent(): bool
       {
           $products = [
               'product-1' => [
                   'title' => 'Product 1',
                   'content' => 'Description...',
                   'price' => 99.99,
               ],
               // More products...
           ];

           foreach ($products as $slug => $data) {
               $post_id = wp_insert_post([
                   'post_title' => $data['title'],
                   'post_name' => $slug,
                   'post_type' => 'product',
                   'post_content' => $data['content'],
                   'post_status' => 'publish',
               ]);

               if ($post_id) {
                   update_post_meta($post_id, 'price', $data['price']);
               }
           }

           return true;
       }
   }
   ```

2. **Generator is auto-discovered** and will run on theme activation

3. **Manually trigger** (if needed):
   ```php
   $manager->forceRegenerateAll();
   ```

See [CONTENT-GENERATOR.md](CONTENT-GENERATOR.md) for complete documentation.

## Creating a Custom Model

**Time**: 15 minutes

**Steps**:

1. **Create model** in `/src/theme/src/Inc/Models/`:
   ```php
   <?php

   namespace App\Inc\Models;

   use Timber\Post;

   class Product extends Post
   {
       public function getPrice(): float
       {
           return (float) get_field('price', $this->ID);
       }

       public function getFormattedPrice(): string
       {
           return '$' . number_format($this->getPrice(), 2);
       }

       public function isOnSale(): bool
       {
           return (bool) get_field('on_sale', $this->ID);
       }

       public function getSalePrice(): ?float
       {
           if (!$this->isOnSale()) {
               return null;
           }

           return (float) get_field('sale_price', $this->ID);
       }
   }
   ```

2. **Use in controller**:
   ```php
   use App\Inc\Models\Product;

   $context = Timber::context();
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
       <p>Sale: {{ product.sale_price }}</p>
     {% endif %}
   {% endfor %}
   ```

## Adding a Menu Location

**Time**: 5 minutes

**Steps**:

1. **Create class** in `/src/theme/src/Register/Menu/`:
   ```php
   <?php

   namespace App\Register\Menu;

   use App\Register\Menu\AbstractMenu;

   class FooterMenu extends AbstractMenu
   {
       protected function configure(): array
       {
           return [
               'footer-menu' => __('Footer Menu', 'talampaya'),
           ];
       }
   }
   ```

2. **Save file** - Auto-registered

3. **Assign menu** in WordPress admin → Appearance → Menus

4. **Display in template**:
   ```php
   // In controller
   $context['footer_menu'] = Timber::get_menu('footer-menu');
   ```

   ```twig
   {# In template #}
   {% if footer_menu %}
     <nav class="footer-nav">
       {% for item in footer_menu.items %}
         <a href="{{ item.link }}">{{ item.title }}</a>
       {% endfor %}
     </nav>
   {% endif %}
   ```

## Adding a Sidebar

**Time**: 5 minutes

**Steps**:

1. **Create class** in `/src/theme/src/Register/Sidebar/`:
   ```php
   <?php

   namespace App\Register\Sidebar;

   use App\Register\Sidebar\AbstractSidebar;

   class ShopSidebar extends AbstractSidebar
   {
       protected function configure(): array
       {
           return [
               'name' => __('Shop Sidebar', 'talampaya'),
               'id' => 'shop-sidebar',
               'description' => __('Widgets for shop pages', 'talampaya'),
               'before_widget' => '<div class="widget %2$s">',
               'after_widget' => '</div>',
               'before_title' => '<h3 class="widget__title">',
               'after_title' => '</h3>',
           ];
       }
   }
   ```

2. **Save file** - Auto-registered

3. **Add widgets** in WordPress admin → Appearance → Widgets

4. **Display in template**:
   ```twig
   {% if function('is_active_sidebar', 'shop-sidebar') %}
     <aside class="sidebar">
       {{ function('dynamic_sidebar', 'shop-sidebar') }}
     </aside>
   {% endif %}
   ```

## Customizing Admin

**Time**: Variable

**Common customizations**:

**Hide admin menu items**:
```php
// In /src/Features/Admin/
add_action('admin_menu', function() {
    remove_menu_page('edit-comments.php');
});
```

**Custom admin columns**:
```php
add_filter('manage_product_posts_columns', function($columns) {
    $columns['price'] = 'Price';
    return $columns;
});

add_action('manage_product_posts_custom_column', function($column, $post_id) {
    if ($column === 'price') {
        echo get_field('price', $post_id);
    }
}, 10, 2);
```

**Custom dashboard widget**:
```php
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'custom_widget',
        'Custom Widget',
        function() {
            echo '<p>Widget content</p>';
        }
    );
});
```

---

For related documentation:
- [ARCHITECTURE.md](ARCHITECTURE.md) - Architecture details
- [APPLICATION-LAYER.md](APPLICATION-LAYER.md) - Controllers, Services, Models, Traits, and Helpers
- [ACF-BLOCKS.md](ACF-BLOCKS.md) - ACF block system
- [TIMBER-TWIG.md](TIMBER-TWIG.md) - Templating system
- [CONTENT-GENERATOR.md](CONTENT-GENERATOR.md) - Content generation

# ACF Fields for Custom Post Types

Complete guide for creating and managing ACF (Advanced Custom Fields) field groups for custom post types in Talampaya.

## Table of Contents

- [Overview](#overview)
- [File Structure and Naming Conventions](#file-structure-and-naming-conventions)
- [Creating ACF Fields for a Custom Post Type](#creating-acf-fields-for-a-custom-post-type)
- [Featured Image Sync with Trait](#featured-image-sync-with-trait)
- [Examples](#examples)
- [Best Practices](#best-practices)

---

## Overview

ACF field groups for custom post types are organized as **classes** that follow **PSR-4 autoloading** standards. Each custom post type has its own directory containing field registration classes.

**Key principles**:
- One class per post type
- PascalCase file naming
- Namespace matches directory structure
- Optional featured image sync via Trait

---

## File Structure and Naming Conventions

### Directory Structure

```
/src/theme/src/Features/Acf/Fields/
├── Product/
│   └── ProductPostFields.php
├── ProductCategoryPost/
│   └── ProductCategoryPostFields.php
├── ProjectPost/
│   └── ProjectPostFields.php
├── SuccessStoryPost/
│   └── SuccessStoryPostFields.php
├── TestimonialPost/
│   └── TestimonialPostFields.php
└── Templates/
    └── template-layouts.php
```

### Naming Convention

**Pattern**: `{PostTypeName}Fields.php`

| Post Type Slug | Directory | Class File |
|---------------|-----------|------------|
| `product_post` | `Product/` | `ProductPostFields.php` |
| `product_cat_post` | `ProductCategoryPost/` | `ProductCategoryPostFields.php` |
| `success_story_post` | `SuccessStoryPost/` | `SuccessStoryPostFields.php` |
| `testimonial_post` | `TestimonialPost/` | `TestimonialPostFields.php` |
| `project_post` | `ProjectPost/` | `ProjectPostFields.php` |

**Rules**:
- Files use **PascalCase** (e.g., `ProductPostFields.php`)
- Classes use **PascalCase** matching the filename
- Namespaces follow directory structure: `App\Features\Acf\Fields\{DirectoryName}`

---

## Creating ACF Fields for a Custom Post Type

### Step 1: Create Directory and File

Create a directory and file following the naming convention:

```
/src/theme/src/Features/Acf/Fields/MyPostType/MyPostTypeFields.php
```

### Step 2: Basic Class Structure

```php
<?php
// Post Type Key: my_post_type

declare(strict_types=1);

namespace App\Features\Acf\Fields\MyPostType;

use Illuminate\Support\Str;
use App\Inc\Helpers\AcfHelper;

class MyPostTypeFields
{
	private string $post_type = "my_post_type";

	public function __construct()
	{
		add_action("acf/init", [$this, "registerFields"], 10);
	}

	public function registerFields(): void
	{
		$block_title = __("My Post Type Fields", "talampaya");

		$fields = [
			["title", "text", 100, __("Title", "talampaya"), 1],
			["description", "textarea", 100, __("Description", "talampaya"), 0],
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
							"param" => "post_type",
							"operator" => "==",
							"value" => $this->post_type,
						],
					],
				],
				"show_in_rest" => true,
				"menu_order" => $group[2],
			];

			acf_add_local_field_group(
				AcfHelper::talampaya_replace_keys_from_acf_register_fields(
					$field_group,
					$this->post_type,
					"post_type"
				)
			);
		}
	}
}

new MyPostTypeFields();
```

### Step 3: Define Fields

Use the `AcfHelper::talampaya_create_acf_group_fields()` method to define fields.

**Field definition format**:
```php
[
	'field_name',           // Required: Field name
	'field_type',           // Optional: text, textarea, wysiwyg, image, etc. (default: 'text')
	'wrapper_width',        // Optional: Width percentage (default: null = full width)
	'label',                // Optional: Field label (default: auto-generated from field name)
	'required',             // Optional: 1 = required, 0 = optional (default: 0)
	'additional_args'       // Optional: Array of additional ACF arguments (default: [])
]
```

**Common field types**:
- `text` - Single line text
- `textarea` - Multi-line text
- `wysiwyg` - Rich text editor
- `image` - Image upload
- `date_picker` - Date selection
- `relationship` - Link to other posts
- `repeater` - Repeating group of fields
- `select` - Dropdown select

### Step 4: Instantiate the Class

At the end of the file, instantiate the class:

```php
new MyPostTypeFields();
```

---

## Featured Image Sync with Trait

The **`FeaturedImageSyncTrait`** automatically syncs an ACF image field to the WordPress featured image when a post is saved.

### When to Use

Use this trait when:
- Your CPT has an `image` ACF field
- You want that image to automatically become the post's featured image
- You want to avoid writing custom `save_post` hooks

### How to Use

**Step 1**: Add the trait to your class:

```php
use App\Features\Acf\Traits\FeaturedImageSyncTrait;

class MyPostTypeFields
{
	use FeaturedImageSyncTrait;

	private string $post_type = "my_post_type";

	public function __construct()
	{
		// Register featured image sync
		$this->registerFeaturedImageSync($this->post_type, "image");

		// Register ACF fields
		add_action("acf/init", [$this, "registerFields"], 10);
	}

	// ... rest of the class
}
```

**Step 2**: Ensure your field definition includes an `image` field:

```php
$fields = [
	["image", "image", 100, __("Image", "talampaya"), 0, $image_args],
	// ... other fields
];
```

**Parameters**:
- `$postType` - The post type slug (e.g., `"product_post"`)
- `$fieldName` - The raw field name **without** the `post_type_` prefix (e.g., `"image"`)

### How It Works

The trait:
1. Registers a `save_post` hook for the specified post type
2. Constructs the full ACF field name: `post_type_{slug}_{fieldName}`
3. Calls `AcfHelper::talampaya_save_custom_thumbnail_as_featured_image()`
4. Updates the WordPress featured image with the ACF image field value

---

## Examples

### Example 1: Simple Fields (No Image Sync)

**ProjectPostFields.php** - Date picker fields only:

```php
<?php

declare(strict_types=1);

namespace App\Features\Acf\Fields\ProjectPost;

use Illuminate\Support\Str;
use App\Inc\Helpers\AcfHelper;

class ProjectPostFields
{
	private string $post_type = "project_post";

	public function __construct()
	{
		add_action("acf/init", [$this, "registerFields"], 10);
	}

	public function registerFields(): void
	{
		$block_title = __("Project Fields", "talampaya");

		$date_args = [
			"display_format" => "Y-m-d",
			"return_format" => "Y-m-d",
			"first_day" => 1,
		];

		$fields = [
			["start_date", "date_picker", 50, __("Start Date", "talampaya"), 0, $date_args],
			["end_date", "date_picker", 50, __("End Date", "talampaya"), 0, $date_args],
		];

		// ... field group registration
	}
}

new ProjectPostFields();
```

### Example 2: Fields with Featured Image Sync

**ProductPostFields.php** - Image field synced to featured image:

```php
<?php

declare(strict_types=1);

namespace App\Features\Acf\Fields\Product;

use Illuminate\Support\Str;
use App\Inc\Helpers\AcfHelper;
use App\Features\Acf\Traits\FeaturedImageSyncTrait;

class ProductPostFields
{
	use FeaturedImageSyncTrait;

	private string $post_type = "product_post";

	public function __construct()
	{
		// Enable featured image sync
		$this->registerFeaturedImageSync($this->post_type, "image");

		// Register ACF fields
		add_action("acf/init", [$this, "registerFields"], 10);
	}

	public function registerFields(): void
	{
		$block_title = __("Product Fields", "talampaya");

		$image_args = [
			"return_format" => "array",
			"preview_size" => "medium",
			"library" => "all",
		];

		$relationship_args = [
			"post_type" => ["product_cat_post"],
			"filters" => ["search"],
			"return_format" => "id",
			"max" => 1,
			"ui" => 1,
		];

		$fields = [
			["image", "image", 100, __("Image", "talampaya"), 0, $image_args],
			["product_category", "relationship", 100, __("Category", "talampaya"), 1, $relationship_args],
		];

		// ... field group registration
	}
}

new ProductPostFields();
```

### Example 3: Complex Fields with Relationships

**SuccessStoryPostFields.php** - Multiple relationships and WYSIWYG:

```php
<?php

declare(strict_types=1);

namespace App\Features\Acf\Fields\SuccessStoryPost;

use Illuminate\Support\Str;
use App\Inc\Helpers\AcfHelper;
use App\Features\Acf\Traits\FeaturedImageSyncTrait;

class SuccessStoryPostFields
{
	use FeaturedImageSyncTrait;

	private string $post_type = "success_story_post";

	public function __construct()
	{
		$this->registerFeaturedImageSync($this->post_type, "image");
		add_action("acf/init", [$this, "registerFields"], 10);
	}

	public function registerFields(): void
	{
		$block_title = __("Success Story Fields", "talampaya");

		$image_args = [
			"return_format" => "array",
			"preview_size" => "medium",
			"library" => "all",
		];

		$products_args = [
			"post_type" => ["product_post"],
			"filters" => ["search"],
			"return_format" => "id",
			"ui" => 1,
		];

		$testimonial_args = [
			"post_type" => ["testimonial_post"],
			"filters" => ["search"],
			"return_format" => "id",
			"max" => 1,
			"ui" => 1,
		];

		$fields = [
			["description", "wysiwyg", 100, __("Description", "talampaya"), 0],
			["image", "image", 100, __("Image", "talampaya"), 0, $image_args],
			["typology", "text", 50, __("Typology", "talampaya"), 0],
			["client", "text", 50, __("Client", "talampaya"), 0],
			["city", "text", 50, __("City", "talampaya"), 0],
			["products", "relationship", 100, __("Related Products", "talampaya"), 0, $products_args],
			["testimonial", "relationship", 100, __("Testimonial", "talampaya"), 0, $testimonial_args],
		];

		// ... field group registration
	}
}

new SuccessStoryPostFields();
```

---

## Best Practices

### 1. Naming Conventions

- **File names**: PascalCase matching the class name (`ProductPostFields.php`)
- **Class names**: PascalCase with `Fields` suffix (`ProductPostFields`)
- **Namespaces**: Follow directory structure (`App\Features\Acf\Fields\Product`)

### 2. Field Organization

- Group related fields together
- Use clear, descriptive field names
- Add translations for all labels using `__()`
- Use appropriate wrapper widths for side-by-side fields (50%, 33%, etc.)

### 3. Required Fields

- Mark truly required fields with `1` in the required parameter
- Consider UX: too many required fields can be frustrating

### 4. Image Fields

- Always use `"return_format" => "array"` for image fields
- Set appropriate `preview_size` (thumbnail, medium, large)
- Use featured image sync trait when the image should be the post's main image

### 5. Relationship Fields

- Specify `post_type` array to limit selectable posts
- Enable `filters` for searchability in admin
- Use `"return_format" => "id"` for better performance
- Set `max` when only one related item should be selected
- Enable `ui => 1` for better admin experience

### 6. Additional Arguments

Pass field-specific arguments via the `additional_args` parameter:

```php
$date_args = [
	"display_format" => "d/m/Y",
	"return_format" => "Y-m-d",
	"first_day" => 1,
];
["event_date", "date_picker", 100, __("Date", "talampaya"), 1, $date_args]
```

### 7. Field Export

After creating fields in the WordPress admin:
1. Fields auto-export to `/src/theme/acf-json/`
2. Commit the JSON files to git
3. On deployment, ACF auto-imports from JSON

---

## Related Documentation

- [ACF-BLOCKS.md](ACF-BLOCKS.md) - Creating ACF blocks
- [COMMON-TASKS.md](COMMON-TASKS.md) - Common development tasks
- [ARCHITECTURE.md](ARCHITECTURE.md) - Overall project architecture
- [CONTRIBUTING.md](CONTRIBUTING.md) - Code standards and conventions

---

**Last updated**: 2025-01-16

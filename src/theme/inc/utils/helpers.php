<?php

use Illuminate\Support\Str;

// -----------------------------------------------------------------------------
// Define Constants
// -----------------------------------------------------------------------------
define("WOOCOMMERCE_IS_ACTIVE", class_exists("WooCommerce"));

// -----------------------------------------------------------------------------
// String to Slug
// -----------------------------------------------------------------------------

if (!function_exists("talampaya_string_to_slug")):
	function talampaya_string_to_slug($str)
	{
		$str = strtolower(trim($str));
		$str = preg_replace("/[^a-z0-9-]/", "_", $str);
		$str = preg_replace("/-+/", "_", $str);
		return $str;
	}
endif;

// -----------------------------------------------------------------------------
// Theme Name
// -----------------------------------------------------------------------------

if (!function_exists("talampaya_theme_name")):
	function talampaya_theme_name()
	{
		$talampaya_theme = wp_get_theme();
		return $talampaya_theme->get("Name");
	}
endif;

// -----------------------------------------------------------------------------
// Parent Theme Name
// -----------------------------------------------------------------------------

if (!function_exists("talampaya_parent_theme_name")):
	function talampaya_parent_theme_name()
	{
		$theme = wp_get_theme();
		if ($theme->parent()):
			$theme_name = $theme->parent()->get("Name");
		else:
			$theme_name = $theme->get("Name");
		endif;

		return $theme_name;
	}
endif;

// -----------------------------------------------------------------------------
// Theme Slug
// -----------------------------------------------------------------------------

if (!function_exists("talampaya_theme_slug")):
	function talampaya_theme_slug()
	{
		$talampaya_theme = wp_get_theme();
		return talampaya_string_to_slug($talampaya_theme->get("Name"));
	}
endif;

// -----------------------------------------------------------------------------
// Theme Author
// -----------------------------------------------------------------------------

if (!function_exists("talampaya_theme_author")):
	function talampaya_theme_author()
	{
		$talampaya_theme = wp_get_theme();
		return $talampaya_theme->get("Author");
	}
endif;

// -----------------------------------------------------------------------------
// Theme Description
// -----------------------------------------------------------------------------

if (!function_exists("talampaya_theme_description")):
	function talampaya_theme_description()
	{
		$talampaya_theme = wp_get_theme();
		return $talampaya_theme->get("Description");
	}
endif;

// -----------------------------------------------------------------------------
// Theme Version
// -----------------------------------------------------------------------------

if (!function_exists("talampaya_theme_version")):
	function talampaya_theme_version()
	{
		$talampaya_theme = wp_get_theme();
		return $talampaya_theme->get("Version");
	}
endif;

// -----------------------------------------------------------------------------
// Convert hex to rgb
// -----------------------------------------------------------------------------

function talampaya_hex2rgb($hex)
{
	$hex = str_replace("#", "", $hex);

	if (strlen($hex) == 3) {
		$r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
		$g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
		$b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
	} else {
		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));
	}
	$rgb = [$r, $g, $b];
	return implode(",", $rgb); // returns the rgb values separated by commas
	//return $rgb; // returns an array with the rgb values
}

// -----------------------------------------------------------------------------
// Page ID
// -----------------------------------------------------------------------------

function talampaya_page_id()
{
	$page_id = "";
	if (is_single() || is_page()) {
		$page_id = get_the_ID();
	} elseif (WOOCOMMERCE_IS_ACTIVE && is_shop()) {
		$page_id = wc_get_page_id("shop");
	} else {
		$page_id = get_option("page_for_posts");
	}
	return $page_id;
}

/**
 * Compress custom styles
 */
function talampaya_compress_styles($minify)
{
	$minify = preg_replace("/\/\*((?!\*\/).)*\*\//", "", $minify); // negative look ahead
	$minify = preg_replace("/\s{2,}/", " ", $minify);
	$minify = preg_replace("/\s*([:;{}])\s*/", '$1', $minify);
	$minify = preg_replace("/;}/", "}", $minify);

	return $minify;
}

// -----------------------------------------------------------------------------
// Return array of values from custom post meta for custom post type
// -----------------------------------------------------------------------------
function talampaya_get_all_postmeta_for_post_type($key, $post_type, array $excludes = null)
{
	$args = [
		"post_type" => $post_type,
		"post_status" => "publish",
		"posts_per_page" => -1,
		"fields" => "ids",
	];

	if (!empty($excludes)) {
		$args["meta_query"] = [
			"relation" => "AND",
		];
		foreach ($excludes as $exclude) {
			$args["meta_query"][] = [
				"key" => $key,
				"value" => $exclude,
				"compare" => "!=",
			];
		}
	}

	$posts = get_posts($args);
	$values = [];
	foreach ($posts as $post) {
		$values[] = get_post_meta($post, $key, true);
	}

	return $values;
}

// -----------------------------------------------------------------------------
// Return array of terms for a custom taxonomy
// -----------------------------------------------------------------------------
function talampaya_get_terms_for_taxonomy($taxonomy, $fields = "all", $hide_empty = false): array
{
	$terms = get_terms([
		"taxonomy" => $taxonomy,
		"hide_empty" => $hide_empty,
		"fields" => $fields,
	]);

	$terms = is_array($terms) ? $terms : (array) $terms;

	return $terms;
}

// -----------------------------------------------------------------------------
// Labeling new registration custom post types
// -----------------------------------------------------------------------------
function talampaya_compile_post_type_labels(
	$singular = "Post",
	$plural = "Posts",
	string $gender = null
): array {
	$p_lower = strtolower($plural);
	$s_lower = strtolower($singular);

	$new = $gender === "f" ? "Nueva" : "Nuevo";
	$all = $gender === "f" ? "Todas las" : "Todos los";

	return [
		"name" => __($plural, "talampaya"),
		"singular_name" => __($singular, "talampaya"),
		"add_new_item" => __("$new $singular", "talampaya"),
		"edit_item" => __("Editar $singular", "talampaya"),
		"view_item" => __("Ver $singular", "talampaya"),
		"view_items" => __("Ver $plural", "talampaya"),
		"search_items" => __("Buscar $plural", "talampaya"),
		"not_found" => __("No $p_lower found", "talampaya"),
		"not_found_in_trash" => __("No $p_lower found in Trash", "talampaya"),
		"parent_item_colon" => __("Parent $singular", "talampaya"),
		"all_items" => __("$all $plural", "talampaya"),
		"archives" => __("$singular Archives", "talampaya"),
		"attributes" => __("$singular Attributes", "talampaya"),
		"insert_into_item" => __("Insert into $s_lower", "talampaya"),
		"uploaded_to_this_item" => __("Uploaded to this $s_lower", "talampaya"),
	];
}

// -----------------------------------------------------------------------------
// Labeling new registration custom post types
// -----------------------------------------------------------------------------
function talampaya_compile_taxonomy_labels(
	$singular = "Post",
	$plural = "Posts",
	string $gender = null
): array {
	$p_lower = strtolower($plural);
	$s_lower = strtolower($singular);

	$new = $gender === "f" ? "Nueva" : "Nuevo";
	$all = $gender === "f" ? "Todas las" : "Todos los";

	return [
		"name" => __($plural, "talampaya"),
		"singular_name" => __($singular, "talampaya"),
		"add_new_item" => __("$new $singular", "talampaya"),
		"edit_item" => __("Editar $singular", "talampaya"),
		"search_items" => __("Buscar $plural", "talampaya"),
		"parent_item_colon" => __("Parent $singular", "talampaya"),
		"all_items" => __("$all $plural", "talampaya"),
		"parent_item" => __("Parent $singular", "talampaya"),
		"update_item" => __("Actualizar $singular", "talampaya"),
		"new_item_name" => __("$new nombre de $singular", "talampaya"),
		"menu_name" => __($plural, "talampaya"),
	];
}

// -----------------------------------------------------------------------------
// Update Post Type Capabilities
// -----------------------------------------------------------------------------
function talampaya_compile_post_type_capabilities($singular = "post", $plural = "posts")
{
	return [
		"edit_post" => "edit_$singular",
		"read_post" => "read_$singular",
		"delete_post" => "delete_$singular",
		"edit_posts" => "edit_$plural",
		"edit_others_posts" => "edit_others_$plural",
		"publish_posts" => "publish_$plural",
		"read_private_posts" => "read_private_$plural",
		"read" => "read",
		"delete_posts" => "delete_$plural",
		"delete_private_posts" => "delete_private_$plural",
		"delete_published_posts" => "delete_published_$plural",
		"delete_others_posts" => "delete_others_$plural",
		"edit_private_posts" => "edit_private_$plural",
		"edit_published_posts" => "edit_published_$plural",
		"create_posts" => "create_$plural",
	];
}

// -----------------------------------------------------------------------------
// Get text color type
// -----------------------------------------------------------------------------
function get_text_color_type($hexcolor)
{
	// If a leading # is provided, remove it
	if (substr($hexcolor, 0, 1) === "#") {
		$hexcolor = substr($hexcolor, 1);
	}

	// If a three-character hexcode, make six-character
	if (strlen($hexcolor) === 3) {
		$hexArray = str_split($hexcolor);

		$hexcolor = join(
			"",
			array_map(function ($hex) {
				return $hex . $hex;
			}, $hexArray)
		);
	}

	// Convert to RGB value
	$r = intval(substr($hexcolor, 0, 2), 16);
	$g = intval(substr($hexcolor, 2, 2), 16);
	$b = intval(substr($hexcolor, 4, 2), 16);

	// Get YIQ ratio
	$yiq = ($r * 299 + $g * 587 + $b * 114) / 1000;

	// Check contrast
	$yiq >= 128 ? ($text_color = "dark") : ($text_color = "light");

	return $text_color;
}

// -----------------------------------------------------------------------------
// Directory Iterator Group By Folder
// -----------------------------------------------------------------------------
if (!function_exists("talampaya_directory_iterator_group_by_folder")):
	function talampaya_directory_iterator_group_by_folder(
		$path = "/inc/register",
		$extension = "php"
	) {
		$data = [];
		foreach ($directories = new DirectoryIterator($path) as $directory) {
			if ($directory->isDir() && !$directory->isDot()) {
				$explode_directories = explode("/", $directory->getPathname());
				$last_directory = end($explode_directories);

				$data[$last_directory] = [];
				foreach ($files = new DirectoryIterator($directory->getPathname()) as $file) {
					if ($file->isFile() && $file->getExtension() === $extension) {
						$data[$last_directory] = array_merge(
							$data[$last_directory],
							require_once $file->getPathname()
						);
					}
				}
			}
		}
		return $data;
	}
endif;

// -----------------------------------------------------------------------------
// Directory Iterator
// -----------------------------------------------------------------------------
if (!function_exists("talampaya_directory_iterator")):
	function talampaya_directory_iterator(
		$path = "/inc/filters",
		$extension = "php",
		$prefix_exclude = "_",
		$exclude_files = []
	) {
		$data = [];
		foreach ($files = new DirectoryIterator($path) as $file) {
			if ($file->isFile() && $file->getExtension() === $extension) {
				$filenameWithoutExtension = pathinfo($file->getFilename(), PATHINFO_FILENAME);
				if (
					!in_array($filenameWithoutExtension, $exclude_files) &&
					!str_starts_with($filenameWithoutExtension, $prefix_exclude)
				) {
					$data[] = $file->getPathname();
				}
			}
		}
		return $data;
	}
endif;

// -----------------------------------------------------------------------------
// Replace keys from ACF register fields
// -----------------------------------------------------------------------------
if (!function_exists("talampaya_replace_keys_from_acf_register_fields")):
	function talampaya_replace_keys_from_acf_register_fields($array, $prefix)
	{
		foreach ($array as $key => &$value) {
			if (is_array($value)) {
				$value = talampaya_replace_keys_from_acf_register_fields($value, $prefix);
			} else {
				if (is_string($value)) {
					if (str_starts_with($value, "field_")) {
						$value = str_replace("field_", "field_" . $prefix . "_", $value);
					} elseif (str_starts_with($value, "group_")) {
						$value = str_replace("group_", "group_" . $prefix . "_", $value);
					}
				}
			}
		}
		return $array;
	}
endif;

// -----------------------------------------------------------------------------
// Filter array by key and value
// -----------------------------------------------------------------------------
if (!function_exists("talampaya_filter_array_by_key_and_value")):
	function talampaya_filter_array_by_key_and_value($field, $value, $arrays)
	{
		foreach ($arrays as $array) {
			if (isset($array[$field]) && $array[$field] == $value) {
				return $array;
			}
		}
		return null;
	}
endif;

// -----------------------------------------------------------------------------
// Get JSON data from URL
// -----------------------------------------------------------------------------
if (!function_exists("talampaya_get_json_data_from_url")):
	function talampaya_get_json_data_from_url($url)
	{
		$response = wp_remote_get($url);
		$body = wp_remote_retrieve_body($response);
		return json_decode($body, true);
	}
endif;

// -----------------------------------------------------------------------------
// Create ACF Field dynamically
// -----------------------------------------------------------------------------
if (!function_exists("talampaya_create_acf_field")):
	function talampaya_create_acf_field(
		string $name,
		string $type = "text",
		int $wrapper_width = null,
		string $label = null,
		int $required = 0,
		array $additional_args = [],
		int $wpml = 0,
		string $prefix = ""
	): array {
		if ($wrapper_width !== null) {
			$wrapper_width = ["wrapper" => ["width" => $wrapper_width . "%"]];
		} else {
			$wrapper_width = [];
		}

		$wpml = ["wpml_cf_preferences" => $wpml];

		$key_name = Str::snake($name);
		$label_name = Str::title(str_replace("_", " ", $name));
		return array_merge(
			[
				"key" => "field_" . $prefix . $key_name,
				"name" => $prefix . $key_name,
				"label" => $label ?? $label_name,
				"type" => $type,
				"required" => $required,
			],
			$wrapper_width,
			$wpml,
			$additional_args
		);
	}
endif;

// -----------------------------------------------------------------------------
// Create ACF Group Fields dynamically
// -----------------------------------------------------------------------------
if (!function_exists("talampaya_create_acf_group_fields")):
	function talampaya_create_acf_group_fields(
		array $fields,
		string $prefix = "",
		int $wpml = 0
	): array {
		$group_fields = [];
		foreach ($fields as $field) {
			$group_fields[] = talampaya_create_acf_field(
				$field[0],
				isset($field[1]) ? $field[1] : "text",
				isset($field[2]) ? $field[2] : null,
				isset($field[3]) ? $field[3] : null,
				isset($field[4]) ? $field[4] : 0,
				isset($field[5]) ? $field[5] : [],
				$wpml,
				$prefix
			);
		}
		return $group_fields;
	}
endif;

// -----------------------------------------------------------------------------
// Update Title with ACF Custom Title
// -----------------------------------------------------------------------------
if (!function_exists("talampaya_update_title_with_acf_custom_title")):
	function talampaya_update_title_with_acf_custom_title($post_id, $post_type, $title_field)
	{
		if (get_post_type($post_id) == $post_type) {
			$custom_post_type_title = get_field($title_field, $post_id);
			if ($custom_post_type_title) {
				$post_args = [
					"ID" => $post_id,
					"post_title" => $custom_post_type_title,
					"post_name" => sanitize_title($custom_post_type_title),
				];
				wp_update_post($post_args);
			}
		}
	}
endif;

// -----------------------------------------------------------------------------
// Save Custom Thumbnail as Featured Image
// -----------------------------------------------------------------------------
if (!function_exists("talampaya_save_custom_thumbnail_as_featured_image")):
	function talampaya_save_custom_thumbnail_as_featured_image(
		$post_id,
		$post_type,
		$thumbnail_field
	) {
		if (get_post_type($post_id) == $post_type) {
			remove_action("save_post", "talampaya_save_custom_thumbnail_as_featured_image");
			$custom_thumbnail_id = get_field($thumbnail_field, $post_id);
			if ($custom_thumbnail_id) {
				set_post_thumbnail($post_id, $custom_thumbnail_id["ID"]);
			} else {
				delete_post_thumbnail($post_id);
			}
			add_action("save_post", "talampaya_save_custom_thumbnail_as_featured_image");
		}
	}
endif;

// -----------------------------------------------------------------------------
// CUSTOM HELPER FUNCTIONS
// -----------------------------------------------------------------------------

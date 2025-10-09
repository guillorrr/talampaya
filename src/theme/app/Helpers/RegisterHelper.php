<?php

namespace Talampaya\App\Helpers;

class RegisterHelper
{
	// -----------------------------------------------------------------------------
	// Labeling new registration custom post types
	// -----------------------------------------------------------------------------
	public static function talampaya_compile_post_type_labels(
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
	public static function talampaya_compile_taxonomy_labels(
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
	public static function talampaya_compile_post_type_capabilities(
		$singular = "post",
		$plural = "posts"
	) {
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
}

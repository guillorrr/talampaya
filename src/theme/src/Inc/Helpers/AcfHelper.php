<?php

namespace App\Inc\Helpers;

use Illuminate\Support\Str;
use App\Inc\Helpers\AttachmentsHelper;

class AcfHelper
{
	// -----------------------------------------------------------------------------
	// Replace keys from ACF register fields
	// -----------------------------------------------------------------------------

	public static function talampaya_replace_keys_from_acf_register_fields(
		$array,
		$key = "",
		$type = "block",
		$is_subfield = false
	) {
		if (isset($array["key"])) {
			$array["key"] = "group_" . $type . "_" . $key . "_" . $array["key"];
		}

		if (isset($array["fields"]) && is_array($array["fields"])) {
			foreach ($array["fields"] as &$field) {
				if (!$is_subfield && isset($field["key"])) {
					$field["key"] = "field_" . $type . "_" . $key . "_" . $field["key"];
				} elseif ($is_subfield && isset($field["key"])) {
					$field["key"] = $key . "_" . $field["key"];
				}

				if (!$is_subfield && isset($field["name"])) {
					$field["name"] = $type . "_" . $key . "_" . $field["name"];
				} elseif ($is_subfield && isset($field["name"])) {
					$field["name"] = $key . "_" . $field["name"];
				}

				if (isset($field["sub_fields"]) && is_array($field["sub_fields"])) {
					$fake_array["fields"] = $field["sub_fields"];
					$new_subfields = self::talampaya_replace_keys_from_acf_register_fields(
						$fake_array,
						$key,
						$type,
						true
					);
					$field["sub_fields"] = $new_subfields["fields"];
				}
			}
		}

		return $array;
	}

	// -----------------------------------------------------------------------------
	// Create ACF Field dynamically
	// -----------------------------------------------------------------------------

	public static function talampaya_create_acf_field(
		string $name,
		string $type = "text",
		int $wrapper_width = null,
		string $label = null,
		int $required = 0,
		array $additional_args = [],
		int $wpml = 0
	): array {
		if ($wrapper_width !== null) {
			$wrapper_width = ["wrapper" => ["width" => $wrapper_width . "%"]];
		} else {
			$wrapper_width = [];
		}

		$wpml = ["wpml_cf_preferences" => $wpml];

		$key_name = Str::snake($name);
		$label_name = Str::title(str_replace("_", " ", $key_name));
		return array_merge(
			[
				"key" => $key_name,
				"name" => $key_name,
				"label" => $label ?? $label_name,
				"type" => $type,
				"required" => $required,
			],
			$wrapper_width,
			$wpml,
			$additional_args
		);
	}

	// -----------------------------------------------------------------------------
	// Create ACF Field Repeater dynamically
	// -----------------------------------------------------------------------------

	public static function talampaya_create_acf_field_repeater(
		string $name,
		array $subfields = [],
		int $wrapper_width = null,
		string $label = null,
		int $required = 0,
		array $additional_args = [],
		int $wpml = 0,
		string $layout = "block"
	): array {
		if ($wrapper_width !== null) {
			$wrapper_width = ["wrapper" => ["width" => $wrapper_width . "%"]];
		} else {
			$wrapper_width = [];
		}

		$wpml = ["wpml_cf_preferences" => $wpml];

		$key_name = Str::snake($name);
		$label_name = Str::title(str_replace("_", " ", $key_name));
		return array_merge(
			[
				"key" => $key_name,
				"name" => $key_name,
				"label" => $label ?? $label_name,
				"type" => "repeater",
				"required" => $required,
				"sub_fields" => $subfields,
				"layout" => $layout ?? "block",
			],
			$wrapper_width,
			$wpml,
			$additional_args
		);
	}

	// -----------------------------------------------------------------------------
	// Create ACF Group Fields dynamically
	// -----------------------------------------------------------------------------

	public static function talampaya_create_acf_group_fields(array $fields, int $wpml = 0): array
	{
		$group_fields = [];
		foreach ($fields as $field) {
			$group_fields[] = self::talampaya_create_acf_field(
				$field[0],
				isset($field[1]) ? $field[1] : "text",
				isset($field[2]) ? $field[2] : null,
				isset($field[3]) ? $field[3] : null,
				isset($field[4]) ? $field[4] : 0,
				isset($field[5]) ? $field[5] : [],
				$wpml
			);
		}
		return $group_fields;
	}

	// -----------------------------------------------------------------------------
	// Update Title with ACF Custom Title
	// -----------------------------------------------------------------------------

	public static function talampaya_update_title_with_acf_custom_title(
		$post_id,
		$post_type,
		$title_field
	) {
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

	// -----------------------------------------------------------------------------
	// Save Custom Thumbnail as Featured Image
	// -----------------------------------------------------------------------------

	public static function talampaya_save_custom_thumbnail_as_featured_image(
		$post_id,
		$post_type = "post",
		$thumbnail_field = "thumbnail"
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

	// -----------------------------------------------------------------------------
	// Save Image on Custom Field
	// -----------------------------------------------------------------------------
	public static function set_image_on_custom_field(
		$post_id,
		$image_url,
		$custom_field,
		$title = null
	): bool {
		if (!empty($image_url)) {
			$filename = basename($image_url);
			$image_id = AttachmentsHelper::get_image_id_by_filename($filename);

			if ($image_id) {
				return update_field($custom_field, $image_id, $post_id);
			} else {
				$post_id = is_int($post_id) ? $post_id : 0;
				$image_id = media_sideload_image($image_url, $post_id, $title, "id");
				if (!is_wp_error($image_id)) {
					return update_field($custom_field, $image_id, $post_id);
				}
			}
		}
		return false;
	}
}

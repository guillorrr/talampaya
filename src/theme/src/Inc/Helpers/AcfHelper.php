<?php

namespace App\Inc\Helpers;

use Illuminate\Support\Str;

class AcfHelper
{
	/**
	 * Replace keys and names from ACF Register Fields
	 * to avoid conflicts when using the same field groups in different contexts
	 * e.g. blocks, options pages, custom post types, etc.
	 * @param array $array The ACF field group array
	 * @param string $key A unique key to prefix the field keys and names
	 * @param string $type The type of context (e.g. block, options, post_type)
	 * @param bool $is_subfield Whether the current field is a subfield
	 * @return array The modified ACF field group array with replaced keys and names
	 *
	 * Usage:
	 *  $fields = [
	 *      'key' => 'group_1',
	 *      'title' => 'My Group',
	 *      'fields' => [
	 *          [
	 *              'key' => 'field_1',
	 *              'name' => 'my_field',
	 *              'type' => 'text',
	 *          ],
	 *         ],
	 *  ];
	 *
	 * $new_fields = AcfHelper::talampaya_replace_keys_from_acf_register_fields($fields, 'unique_key', 'block');
	 *
	 * Result:
	 * [
	 *      'key' => 'group_block_unique_key_group_1',
	 *      'title' => 'My Group',
	 *      'fields' => [
	 *          [
	 *              'key' => 'field_block_unique_key_field_1',
	 *              'name' => 'block_unique_key_my_field',
	 *              'type' => 'text',
	 *          ],
	 *      ],
	 * ]
	 *
	 * Note: This function assumes that the input array follows the ACF field group structure.
	 * It recursively processes subfields if they exist.
	 * Make sure to test the function with your specific ACF field group structure.
	 */
	public static function talampaya_replace_keys_from_acf_register_fields(
		array $array,
		string $key = "",
		string $type = "block",
		bool $is_subfield = false
	): array {
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

	/**
	 * Create a single ACF field.
	 *
	 * @param string $name
	 * @param string $type
	 * @param int|null $wrapper_width
	 * @param string|null $label
	 * @param int $required
	 * @param array $additional_args
	 * @param int $wpml
	 * @return array
	 *
	 * Example usage:
	 * $field = AcfHelper::talampaya_create_acf_field(
	 *      'my_field', 'text', 50, 'My Field', 1, ['placeholder' => 'Enter text'], 1
	 * );
	 *
	 * Result:
	 * [
	 *      'key' => 'my_field',
	 *      'name' => 'my_field',
	 *      'label' => 'My Field',
	 *      'type' => 'text',
	 *      'required' => 1,
	 *      'wrapper' => ['width' => '50%'],
	 *      'wpml_cf_preferences' => 1,
	 *      'placeholder' => 'Enter text',
	 * ]
	 * @see https://www.advancedcustomfields.com/resources/field-settings/
	 * @see https://www.advancedcustomfields.com/resources/wpml-integration/
	 * @note The $wpml parameter sets the WPML translation preference for the field.
	 * 0 = Don't translate, 1 = Copy, 2 = Translate
	 * Default is 0 (Don't translate).
	 */
	public static function talampaya_create_acf_field(
		string $name,
		string $type = "text",
		?int $wrapper_width = null,
		?string $label = null,
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

	/**
	 * Create a repeater ACF field.
	 *
	 * @param string $name
	 * @param array $subfields
	 * @param int|null $wrapper_width
	 * @param string|null $label
	 * @param int $required
	 * @param array $additional_args
	 * @param int $wpml
	 * @param string $layout
	 * @return array
	 *
	 * Example usage:
	 * $subfields = [
	 *    AcfHelper::talampaya_create_acf_field('subfield_1', 'text', 50, 'Subfield 1', 1),
	 *   AcfHelper::talampaya_create_acf_field('subfield_2', 'number', 50, 'Subfield 2', 0),
	 * ];
	 *  $repeater_field = AcfHelper::talampaya_create_acf_field_repeater(
	 *      'my_repeater',
	 *      $subfields,
	 *      100,
	 *      'My Repeater',
	 *      1,
	 *      [],
	 *      1,
	 *      'table'
	 *  );
	 *
	 * Result:
	 *  [
	 *      'key' => 'my_repeater',
	 *      'name' => 'my_repeater',
	 *      'label' => 'My Repeater',
	 *      'type' => 'repeater',
	 *      'required' => 1,
	 *      'sub_fields' => [
	 *          [
	 *              'key' => 'subfield_1',
	 *              'name' => 'subfield_1',
	 *              'label' => 'Subfield 1',
	 *              'type' => 'text',
	 *              'required' => 1,
	 *              'wrapper' => ['width' => '50%'],
	 *         ],
	 *          [
	 *              'key' => 'subfield_2',
	 *              'name' => 'subfield_2',
	 *              'label' => 'Subfield 2',
	 *              'type' => 'number',
	 *              'required' => 0,
	 *              'wrapper' => ['width' => '50%'],
	 *          ],
	 *      ],
	 *      'layout' => 'table',
	 *      'wrapper' => ['width' => '100%'],
	 *      'wpml_cf_preferences' => 1,
	 *  ]
	 *
	 * Note: Adjust the $subfields array as needed to include the desired subfields for the repeater.
	 * The $layout parameter can be 'table', 'block', or 'row' based on your preference.
	 *
	 * @see AcfHelper::talampaya_create_acf_field()
	 * @see https://www.advancedcustomfields.com/resources/field-settings/
	 * @see https://www.advancedcustomfields.com/resources/wpml-integration/
	 * @note The $wpml parameter sets the WPML translation preference for the field.
	 * 0 = Don't translate, 1 = Copy, 2 = Translate
	 * Default is 0 (Don't translate).
	 */
	public static function talampaya_create_acf_field_repeater(
		string $name,
		array $subfields = [],
		?int $wrapper_width = null,
		?string $label = null,
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

	/**
	 * Create multiple ACF fields for a group field.
	 *
	 * @param array $fields
	 * @param int $wpml
	 * @return array
	 *
	 * Each field is defined by an array with the following structure:
	 * [
	 *   0 => 'field_name',        // Required: The name of the field
	 *  1 => 'field_type',        // Optional: The type of the field (default: 'text')
	 *  2 => 'wrapper_width',     // Optional: The width of the field wrapper (default: null)
	 *  3 => 'label',             // Optional: The label of the field (default: null)
	 *  4 => 'required',          // Optional: Whether the field is required (default: 0)
	 *  5 => 'additional_args'    // Optional: Additional arguments for the field (default: [])
	 * ]
	 * Example:
	 * $fields = [
	 *  ['first_name', 'text', 50, 'First Name', 1, []],
	 *  ['last_name', 'text', 50, 'Last Name', 1, []],
	 * ['bio', 'textarea', null, 'Biography', 0, ['rows' => 4]],
	 * ];
	 * $group_fields = AcfHelper::talampaya_create_acf_group_fields($fields, 1);
	 * Result:
	 * [
	 * [
	 *   'key' => 'first_name',
	 *  'name' => 'first_name',
	 *  'label' => 'First Name',
	 *  'type' => 'text',
	 *  'required' => 1,
	 *  'wrapper' => ['width' => '50%'],
	 * ],
	 * [
	 *  'key' => 'last_name',
	 * 'name' => 'last_name',
	 * 'label' => 'Last Name',
	 * 'type' => 'text',
	 * 'required' => 1,
	 * 'wrapper' => ['width' => '50%'],
	 * ],
	 * [
	 * 'key' => 'bio',
	 * 'name' => 'bio',
	 * 'label' => 'Biography',
	 * 'type' => 'textarea',
	 * 'required' => 0,
	 * 'wrapper' => [],
	 * 'rows' => 4,
	 * ],
	 * ]
	 *
	 * @see AcfHelper::talampaya_create_acf_field()
	 * @see https://www.advancedcustomfields.com/resources/field-settings/
	 * @see https://www.advancedcustomfields.com/resources/wpml-integration/
	 * @note The $wpml parameter sets the WPML translation preference for all fields in the group.
	 * 0 = Don't translate, 1 = Copy, 2 = Translate
	 * Default is 0 (Don't translate).
	 * Adjust as needed based on your WPML configuration.
	 */
	public static function talampaya_create_acf_group_fields(array $fields, int $wpml = 0): array
	{
		$group_fields = [];
		foreach ($fields as $field) {
			$group_fields[] = self::talampaya_create_acf_field(
				$field[0],
				$field[1] ?? "text",
				$field[2] ?? null,
				$field[3] ?? null,
				$field[4] ?? 0,
				$field[5] ?? [],
				$wpml
			);
		}
		return $group_fields;
	}

	/**
	 * Normaliza nombres con acrónimos para evitar problemas con snake_case
	 *
	 * Cuando se usa Str::snake() con acrónimos (FAQs, CTA, API, etc.),
	 * Laravel convierte cada letra mayúscula en un segmento separado.
	 * Este método normaliza los acrónimos antes de aplicar snake_case.
	 *
	 * @param string $name Nombre del grupo o campo
	 * @return string Nombre normalizado
	 *
	 * @example
	 * normalizeAcronymsForSnakeCase("FAQs") -> "Faqs" -> snake_case -> "faqs"
	 * normalizeAcronymsForSnakeCase("CTA") -> "Cta" -> snake_case -> "cta"
	 * normalizeAcronymsForSnakeCase("XMLParser") -> "Xmlparser" -> snake_case -> "xmlparser"
	 * normalizeAcronymsForSnakeCase("HubSpot") -> "HubSpot" -> snake_case -> "hub_spot"
	 * normalizeAcronymsForSnakeCase("Banner Info") -> "Banner Info" -> snake_case -> "banner_info"
	 */
	public static function normalizeAcronymsForSnakeCase(string $name): string
	{
		// Detectar secuencias de 2+ mayúsculas consecutivas seguidas opcionalmente de minúsculas
		// Esto captura acrónimos completos: FAQ, FAQs, CTA, API, APIs, XMLParser, etc.
		// No afecta palabras normales en PascalCase: HubSpot, UserInfo, etc.
		return preg_replace_callback(
			"/\b[A-Z]{2,}[a-z]*\b/",
			function ($matches) {
				return ucfirst(strtolower($matches[0]));
			},
			$name
		);
	}

	/**
	 * Update the post_title and slug with a custom ACF field value.
	 *
	 * @param int $post_id The ID of the post being saved.
	 * @param string $post_type The post type to check.
	 * @param string $title_field The ACF field name for the custom title.
	 * @return void
	 * @note This function should be hooked to the 'save_post' action.
	 * @see https://www.advancedcustomfields.com/resources/get_field/
	 * @see https://developer.wordpress.org/reference/functions/wp_update_post/
	 */
	public static function talampaya_update_title_with_acf_custom_title(
		int $post_id,
		string $post_type,
		string $title_field
	): void {
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

	/**
	 * Set the featured image from a custom ACF image field.
	 *
	 * If the custom field is empty, remove the featured image.
	 * @param int $post_id The ID of the post being saved.
	 * @param string $post_type The post_type to check (default: 'post').
	 * @param string $thumbnail_field The ACF field name for the custom thumbnail (default: 'thumbnail').
	 * @return void
	 * @note This function should be hooked to the 'save_post' action.
	 * @see https://www.advancedcustomfields.com/resources/get_field/
	 * @see https://developer.wordpress.org/reference/functions/set_post_thumbnail/
	 * @see https://developer.wordpress.org/reference/functions/delete_post_thumbnail/
	 */
	public static function talampaya_save_custom_thumbnail_as_featured_image(
		int $post_id,
		string $post_type = "post",
		string $thumbnail_field = "thumbnail"
	): void {
		// Skip revisions and autosaves
		if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
			return;
		}

		if (get_post_type($post_id) == $post_type) {
			$custom_thumbnail_id = get_field($thumbnail_field, $post_id);
			if ($custom_thumbnail_id) {
				// Handle both array and scalar return formats
				$image_id = is_array($custom_thumbnail_id)
					? $custom_thumbnail_id["ID"] ?? ($custom_thumbnail_id["id"] ?? null)
					: $custom_thumbnail_id;
				if ($image_id) {
					set_post_thumbnail($post_id, $image_id);
				}
			} else {
				delete_post_thumbnail($post_id);
			}
		}
	}

	/**
	 * Set an image to a custom field, sideloading it if necessary.
	 *
	 * @param int|string $post_id The ID of the post to update.
	 * @param string $image_url The URL of the image to set.
	 * @param string $custom_field The ACF custom field key or name to update.
	 * @param string|null $title Optional. The title to use for the image if sideloaded.
	 * @return bool True on success, false on failure.
	 * @note $post_id can be an integer (post ID) or a string (e.g., 'option' for options page).
	 * @see https://www.advancedcustomfields.com/resources/update_field/
	 * @see https://developer.wordpress.org/reference/functions/media_sideload_image/
	 * @see https://developer.wordpress.org/reference/functions/wp_insert_attachment/
	 */
	public static function talampaya_set_image_on_custom_field(
		int|string $post_id,
		string $image_url,
		string $custom_field,
		?string $title = null
	): bool {
		if (!empty($image_url)) {
			$filename = basename($image_url);
			$image_id = AttachmentsHelper::get_image_id_by_filename($filename);

			if ($image_id) {
				return update_field($custom_field, $image_id, $post_id);
			} else {
				$media_post_id = is_int($post_id) ? $post_id : 0;
				$image_id = media_sideload_image($image_url, $media_post_id, $title, "id");
				if (!is_wp_error($image_id)) {
					return update_field($custom_field, $image_id, $post_id);
				}
			}
		}
		return false;
	}

	/**
	 * Generate post content for ACF blocks.
	 *
	 * @param array $blocks
	 * @return string
	 *
	 * Example usage:
	 * [
	 *      "name" => "sitemap",
	 *      "data" => [
	 *          "field_block_sitemap_title" => "Mapa Web",
	 *          "field_block_sitemap_subtitle" => "Así estructuramos nuestros contenidos",
	 *      ],
	 * ],
	 *
	 * Result:
	 * <!-- wp:acf/sitemap {
	 *      "id":"block_64b8f0ed3e4a7",
	 *      "name":"acf/sitemap",
	 *      "data":{
	 *          "field_block_sitemap_title":"Mapa Web",
	 *          "field_block_sitemap_subtitle":"Así estructuramos nuestros contenidos"
	 *      }
	 * } -->
	 * <!-- /wp:acf/sitemap -->
	 *
	 */
	public static function talampaya_make_content_for_blocks_acf(array $blocks = []): string
	{
		$content = "";

		foreach ($blocks as $index => $block) {
			$block_name = $block["name"];
			$data = $block["data"] ?? [];

			$block_id = "block_" . uniqid();

			$data_json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

			$content .= sprintf(
				'<!-- wp:acf/%s {"id":"%s","name":"acf/%s","data":%s} -->' . PHP_EOL,
				$block_name,
				$block_id,
				$block_name,
				$data_json
			);
			$content .= "<!-- /wp:acf/" . $block_name . " -->" . PHP_EOL;
		}

		return $content;
	}
}

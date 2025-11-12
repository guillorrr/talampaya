<?php

namespace App\Inc\Helpers;

class TermHelper
{
	public static function talampaya_create_category($category_name, $taxonomy = "category")
	{
		$category_id = get_cat_ID($category_name);
		if ($category_id == 0) {
			$new_category = wp_insert_term($category_name, $taxonomy);
			if (!is_wp_error($new_category)) {
				$category_id = $new_category["term_id"];
			}
		}
		return $category_id;
	}

	// -----------------------------------------------------------------------------
	// Return array of terms for a custom taxonomy
	// -----------------------------------------------------------------------------
	public static function talampaya_get_terms_for_taxonomy(
		$taxonomy,
		$fields = "all",
		$hide_empty = false
	): array {
		$terms = get_terms([
			"taxonomy" => $taxonomy,
			"hide_empty" => $hide_empty,
			"fields" => $fields,
		]);

		return is_array($terms) ? $terms : (array) $terms;
	}
}

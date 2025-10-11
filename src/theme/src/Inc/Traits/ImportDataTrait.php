<?php

namespace App\Inc\Traits;

trait ImportDataTrait
{
	public function updateSeoData(array $data): void
	{
		if (empty($data["seo_title"]) && empty($data["seo_description"])) {
			return;
		}

		$keyphrase = sanitize_text_field($data["keyphrase"] ?? "");
		$seo_title = sanitize_text_field($data["seo_title"] ?? "");
		$seo_description = sanitize_text_field($data["seo_description"] ?? "");

		// Determinar si estamos trabajando con un post o una taxonomía
		$is_taxonomy = isset($data["term_id"]) && isset($data["taxonomy"]);

		if ($is_taxonomy) {
			$term_id = $data["term_id"];

			// Update keyphrase if the value is different.
			$current_keyphrase = get_term_meta($term_id, "_yoast_wpseo_focuskw", true);
			if ($current_keyphrase !== $keyphrase) {
				error_log("Updating keyphrase for term ID $term_id: $keyphrase");
				update_term_meta($term_id, "_yoast_wpseo_focuskw", $keyphrase);
			}

			// Update title if the value is different.
			$current_seo_title = get_term_meta($term_id, "_yoast_wpseo_title", true);
			if ($current_seo_title !== $seo_title) {
				error_log("Updating SEO title for term ID $term_id: $seo_title");
				update_term_meta($term_id, "_yoast_wpseo_title", $seo_title);
			}

			// Update description if the value is different.
			$current_seo_description = get_term_meta($term_id, "_yoast_wpseo_metadesc", true);
			if ($current_seo_description !== $seo_description) {
				error_log("Updating SEO description for term ID $term_id: $seo_description");
				update_term_meta($term_id, "_yoast_wpseo_metadesc", $seo_description);
			}
		} else {
			// Trabajando con posts (mantener la compatibilidad existente)
			$post_id = $this->ID;

			// Update keyphrase if the value is different.
			$current_keyphrase = get_post_meta($post_id, "_yoast_wpseo_focuskw", true);
			if ($current_keyphrase !== $keyphrase) {
				update_post_meta($post_id, "_yoast_wpseo_focuskw", $keyphrase);
			}

			// Update title if the value is different.
			$current_seo_title = get_post_meta($post_id, "_yoast_wpseo_title", true);
			if ($current_seo_title !== $seo_title) {
				update_post_meta($post_id, "_yoast_wpseo_title", $seo_title);
			}

			// Update description if the value is different.
			$current_seo_description = get_post_meta($post_id, "_yoast_wpseo_metadesc", true);
			if ($current_seo_description !== $seo_description) {
				update_post_meta($post_id, "_yoast_wpseo_metadesc", $seo_description);
			}
		}
	}
}

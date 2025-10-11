<?php

namespace App\Inc\Traits;

trait ImportDataTrait
{
	/**
	 * Actualiza los datos SEO del post o término
	 *
	 * @param array $data Datos a actualizar
	 * @return bool True si la actualización fue exitosa, false en caso contrario
	 */
	public function updateSeoData(array $data): bool
	{
		if (
			empty($data["seo_title"]) &&
			empty($data["seo_description"]) &&
			empty($data["keyphrase"])
		) {
			return true;
		}

		try {
			$keyphrase = sanitize_text_field($data["keyphrase"] ?? "");
			$seo_title = sanitize_text_field($data["seo_title"] ?? "");
			$seo_description = sanitize_text_field($data["seo_description"] ?? "");

			// Determinar si estamos trabajando con un post o una taxonomía
			$is_taxonomy = isset($data["term_id"]) && isset($data["taxonomy"]);

			if ($is_taxonomy) {
				$term_id = $data["term_id"];
				$class_name = get_class($this);

				// Update keyphrase if the value is different.
				$current_keyphrase = get_term_meta($term_id, "_yoast_wpseo_focuskw", true);
				if (!empty($keyphrase) && $current_keyphrase !== $keyphrase) {
					error_log(
						"{$class_name}::updateSeoData: Actualizando keyphrase para term_id={$term_id}: {$keyphrase}"
					);
					update_term_meta($term_id, "_yoast_wpseo_focuskw", $keyphrase);
				}

				// Update title if the value is different.
				$current_seo_title = get_term_meta($term_id, "_yoast_wpseo_title", true);
				if (!empty($seo_title) && $current_seo_title !== $seo_title) {
					error_log(
						"{$class_name}::updateSeoData: Actualizando SEO title para term_id={$term_id}: {$seo_title}"
					);
					update_term_meta($term_id, "_yoast_wpseo_title", $seo_title);
				}

				// Update description if the value is different.
				$current_seo_description = get_term_meta($term_id, "_yoast_wpseo_metadesc", true);
				if (!empty($seo_description) && $current_seo_description !== $seo_description) {
					error_log(
						"{$class_name}::updateSeoData: Actualizando SEO description para term_id={$term_id}: {$seo_description}"
					);
					update_term_meta($term_id, "_yoast_wpseo_metadesc", $seo_description);
				}
			} else {
				// Trabajando con posts
				$post_id = $this->ID;
				$class_name = get_class($this);

				// Update keyphrase if the value is different.
				$current_keyphrase = get_post_meta($post_id, "_yoast_wpseo_focuskw", true);
				if (!empty($keyphrase) && $current_keyphrase !== $keyphrase) {
					error_log(
						"{$class_name}::updateSeoData: Actualizando keyphrase para post_id={$post_id}: {$keyphrase}"
					);
					update_post_meta($post_id, "_yoast_wpseo_focuskw", $keyphrase);
				}

				// Update title if the value is different.
				$current_seo_title = get_post_meta($post_id, "_yoast_wpseo_title", true);
				if (!empty($seo_title) && $current_seo_title !== $seo_title) {
					error_log(
						"{$class_name}::updateSeoData: Actualizando SEO title para post_id={$post_id}: {$seo_title}"
					);
					update_post_meta($post_id, "_yoast_wpseo_title", $seo_title);
				}

				// Update description if the value is different.
				$current_seo_description = get_post_meta($post_id, "_yoast_wpseo_metadesc", true);
				if (!empty($seo_description) && $current_seo_description !== $seo_description) {
					error_log(
						"{$class_name}::updateSeoData: Actualizando SEO description para post_id={$post_id}: {$seo_description}"
					);
					update_post_meta($post_id, "_yoast_wpseo_metadesc", $seo_description);
				}
			}

			return true;
		} catch (\Exception $e) {
			$class_name = get_class($this);
			error_log(
				"{$class_name}::updateSeoData: Error al actualizar datos SEO para ID=" .
					($is_taxonomy ? $data["term_id"] : $this->ID) .
					", error=" .
					$e->getMessage()
			);
			return false;
		}
	}
}

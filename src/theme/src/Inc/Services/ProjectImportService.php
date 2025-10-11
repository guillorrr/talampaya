<?php

namespace App\Inc\Services;

use App\Inc\Helpers\AcfHelper;
use App\Inc\Helpers\TermHelper;
use App\Inc\Models\AbstractPost;
use App\Inc\Models\ProjectPost;
use Timber\Timber;

/**
 * Servicio para la importación de proyectos
 */
class ProjectImportService extends AbstractImportService
{
	/**
	 * Obtiene la clase del modelo asociado a este servicio
	 *
	 * @return string Nombre completo de la clase del modelo
	 */
	public function getModelClass(): string
	{
		return ProjectPost::class;
	}

	/**
	 * Procesa datos específicos para este tipo de importación
	 *
	 * @param array $data Datos a procesar
	 * @return array Datos procesados
	 */
	public function processSpecificData(array $data): array
	{
		return [
			"category" => $data["category"] ?? "",
			"image" => $data["image_main_url"] ?? "",
			"image_main_title" => $data["image_main_title"] ?? "",
			"image_main_alt" => $data["image_main_alt"] ?? "",
			"subtitle" => $data["subtitle"] ?? "",
			"tags" => $data["tags"] ?? "",
		];
	}

	/**
	 * Procesa los datos de una fila para preparar la importación
	 *
	 * @param array $row Datos de la fila
	 * @return array Datos procesados
	 */
	public function processData(array $row): array
	{
		$baseData = parent::processData($row);
		$specificData = $this->processSpecificData($row);

		return array_merge($baseData, $specificData);
	}

	/**
	 * Crea o actualiza un elemento con procesamiento adicional para este tipo
	 *
	 * @param array $data Datos procesados
	 * @param AbstractPost $modelInstance Instancia del modelo
	 * @return \Timber\Post|null
	 */
	public function createOrUpdate(array $data, AbstractPost $modelInstance): ?\Timber\Post
	{
		$post = parent::createOrUpdate($data, $modelInstance);

		if (!$post) {
			return null;
		}

		if (!empty($data["category"]) && $data["category"] !== "NULL") {
			$category_id = null;

			if (!term_exists($data["category"], "category")) {
				if (method_exists(TermHelper::class, "talampaya_create_category")) {
					$category_id = TermHelper::talampaya_create_category($data["category"]);
				} else {
					$category = wp_insert_term($data["category"], "category");
					if (!is_wp_error($category)) {
						$category_id = $category["term_id"];
					}
				}
			} else {
				$category = get_term_by("name", $data["category"], "category");
				if ($category) {
					$category_id = $category->term_id;
				}
			}

			if ($category_id) {
				wp_set_post_categories($post->ID, [$category_id]);
			}
		}

		if (!empty($data["image"])) {
			AcfHelper::set_image_on_custom_field(
				$post->ID,
				$data["image"],
				"field_post_type_" . $data["post_type"] . "_image",
				$data["image_main_alt"]
			);
		}

		if (!empty($data["tags"]) && $data["tags"] !== "NULL") {
			$tags = explode(";", $data["tags"]);
			$tags = array_map("trim", $tags);
			if (!empty($tags)) {
				wp_set_post_tags($post->ID, $tags, true);
			}
		}

		return $post;
	}
}

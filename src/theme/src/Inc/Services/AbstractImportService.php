<?php

namespace App\Inc\Services;

use App\Inc\Models\AbstractPost;
use App\Utils\StringUtils;
use Illuminate\Support\Str;
use Timber\Post;
use App\Inc\Traits\CsvProcessorTrait;
use Timber\Timber;

/**
 * Clase base abstracta para servicios de importación
 *
 * Esta clase proporciona la estructura básica para implementar
 * servicios de importación para diferentes tipos de contenido.
 */
abstract class AbstractImportService
{
	use CsvProcessorTrait;

	/**
	 * Obtiene una instancia del servicio
	 *
	 * @return static
	 */
	public static function getInstance(): self
	{
		return new static();
	}

	/**
	 * Obtiene el identificador único de un elemento
	 *
	 * @param array $row Fila de datos
	 * @param Post $result Post procesado
	 * @return string
	 */
	protected function getItemId(array $row, $result): string
	{
		return $row["id"] ?? ($row["custom_id"] ?? $result->ID);
	}

	/**
	 * Procesa el resultado de la importación
	 *
	 * @param Post $result Post procesado
	 * @param array $row Fila de datos original
	 * @param array $processed_ids Referencias a IDs ya procesados
	 * @param array $updated_items Referencias a items actualizados
	 * @return void
	 */
	protected function processResult(
		$result,
		array $row,
		array &$processed_ids,
		array &$updated_items
	): void {
		if ($result instanceof Post) {
			$item_id = $this->getItemId($row, $result);
			if (!isset($processed_ids[$item_id])) {
				$processed_ids[$item_id] = true;
				$updated_items[] = [
					"post_id" => $result->ID,
					"title" => method_exists($result, "title")
						? $result->title()
						: $result->post_title,
					"permalink" => get_permalink($result->ID),
				];
			}
		}
	}

	/**
	 * Modelo relacionado con este servicio de importación
	 *
	 * @return string Nombre de la clase del modelo
	 */
	abstract public function getModelClass(): string;

	/**
	 * Obtiene el tipo de post para este servicio
	 *
	 * @return string
	 */
	public function getPostType(): string
	{
		$modelClass = $this->getModelClass();
		if (!class_exists($modelClass)) {
			$serviceClass = (new \ReflectionClass($this))->getShortName();
			$postType = preg_replace('/ImportService$/', "", $serviceClass);
			return Str::snake($postType);
		}

		// Usar el método estático para obtener el tipo de post sin instanciar la clase
		if (method_exists($modelClass, "getPostType")) {
			return $modelClass::getPostType();
		}

		// Fallback por si no existe el método estático
		$ref = new \ReflectionClass($modelClass);
		$className = $ref->getShortName();
		return Str::snake($className);
	}

	/**
	 * Procesa los datos de una fila para prepararlos para la importación
	 *
	 * @param array $row Fila de datos a procesar
	 * @return array Datos procesados listos para importar
	 */
	public function processData(array $row): array
	{
		$post_type = $row["post_type"] ?? $this->getPostType();

		return [
			"post_type" => $post_type,
			"custom_id" => $row["custom_id"] ?? "",
			"title" => StringUtils::talampaya_make_phrase_ucfirst($row["title"] ?? ""),
			"status" => ($row["status"] ?? "1") === "1" ? "publish" : "draft",
			"post_date" => !empty($row["post_date"]) ? date("Y-m-d H:i:s", $row["post_date"]) : "",
			"post_modified" => !empty($row["post_modified"])
				? date("Y-m-d H:i:s", $row["post_modified"])
				: "",
			"slug" => $row["slug"] ?? "",
			"seo_description" => $row["seo_description"] ?? "",
			"content" => $row["content"] ?? "",
		];
	}

	/**
	 * Crea o actualiza un elemento basado en los datos proporcionados
	 *
	 * @param array $data Datos procesados para importar
	 * @param AbstractPost $modelInstance Instancia del modelo
	 * @return Post|null El post creado/actualizado o null si hay error
	 */
	public function createOrUpdate(array $data, AbstractPost $modelInstance): ?Post
	{
		$custom_id = sanitize_text_field($data["custom_id"]);
		if (empty($custom_id)) {
			error_log(static::class . "::createOrUpdate: CustomID vacío");
			return null;
		}

		$post_type = $data["post_type"] ?? $this->getPostType();

		if (!isset($data["custom_id"]) && !empty($custom_id)) {
			$data["custom_id"] = $custom_id;
		}

		error_log(static::class . "::createOrUpdate: Procesando item custom_id=" . $custom_id);

		$item = $modelInstance->findByCustomId($custom_id, $post_type);

		if ($item) {
			error_log(
				static::class .
					"::createOrUpdate: Actualizando item existente custom_id=" .
					$custom_id .
					", post_id=" .
					$item->ID
			);
			$success = $item->updateFromData($data);
			if (!$success) {
				error_log(
					static::class .
						"::createOrUpdate: Error al actualizar item custom_id=" .
						$custom_id
				);
				return null;
			}
		} else {
			error_log(
				static::class . "::createOrUpdate: Creando nuevo item custom_id=" . $custom_id
			);
			$post_args = [
				"post_title" => sanitize_text_field($data["title"]),
				"post_content" => wp_kses_post($data["content"] ?? ""),
				"post_status" => $data["status"],
				"post_type" => $post_type,
			];

			if (!empty($data["slug"])) {
				$post_args["post_name"] = sanitize_title($data["slug"]);
				error_log(
					static::class .
						"::createOrUpdate: Configurando post_name a " .
						$post_args["post_name"]
				);
			}

			if (!empty($data["post_date"])) {
				$post_args["post_date"] = $data["post_date"];
			}
			if (!empty($data["post_modified"])) {
				$post_args["post_modified"] = $data["post_modified"];
			}
			$post_id = wp_insert_post($post_args, true);
			if (is_wp_error($post_id)) {
				error_log(
					static::class .
						"::createOrUpdate: Error al crear item custom_id=" .
						$custom_id .
						", error=" .
						$post_id->get_error_message()
				);
				return null;
			}

			$custom_id_field = "field_post_type_{$post_type}_custom_id";
			update_field($custom_id_field, $custom_id, $post_id);
			error_log(
				static::class .
					"::createOrUpdate: Custom ID guardado para nuevo post, post_id=" .
					$post_id
			);

			$item = Timber::get_post($post_id);

			$custom_fields_updated = $item->updateCustomFields($data);
			if (!$custom_fields_updated) {
				error_log(
					static::class .
						"::createOrUpdate: Advertencia - Algunos campos personalizados no se pudieron actualizar para custom_id=" .
						$custom_id
				);
			}

			if (method_exists($item, "updateSeoData")) {
				$seo_updated = $item->updateSeoData($data);
				if (!$seo_updated) {
					error_log(
						static::class .
							"::createOrUpdate: Advertencia - Datos SEO no se pudieron actualizar para custom_id=" .
							$custom_id
					);
				}
			}
		}

		error_log(
			static::class . "::createOrUpdate: Item procesado con éxito, post_id=" . $item->ID
		);
		return $item;
	}

	/**
	 * Método abstracto para procesar datos específicos de cada tipo de importación
	 *
	 * @param array $data Datos a procesar
	 * @return array Datos procesados
	 */
	abstract public function processSpecificData(array $data): array;
}

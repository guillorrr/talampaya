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

		$model = new $modelClass();
		return $model->default_post_type();
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
	 * @param AbstractPost|null $modelInstance Instancia del modelo (opcional)
	 * @return Post|null El post creado/actualizado o null si hay error
	 */
	public function createOrUpdate(array $data, ?AbstractPost $modelInstance = null): ?Post
	{
		$custom_id = sanitize_text_field($data["custom_id"]);
		if (empty($custom_id)) {
			error_log(static::class . "::createOrUpdate: CustomID vacío");
			return null;
		}

		if ($modelInstance === null) {
			$modelClass = $this->getModelClass();
			if (!class_exists($modelClass)) {
				error_log(
					static::class . "::createOrUpdate: La clase del modelo {$modelClass} no existe"
				);
				return null;
			}
			$modelInstance = new $modelClass();
		}

		$post_type = $data["post_type"] ?? $this->getPostType();

		$item = $modelInstance->findByCustomId($custom_id, $post_type);

		if ($item) {
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
			$item = Timber::get_post($post_id);
			$success = $item->updateFromData($data);
			if (!$success) {
				error_log(
					static::class .
						"::createOrUpdate: Error al actualizar datos de nueva item custom_id=" .
						$custom_id
				);
				return null;
			}
		}

		$this->setFields($item->ID, $data);

		error_log(static::class . "::createOrUpdate: item procesada, post_id=" . $item->ID);
		return $item;
	}

	/**
	 * Establece los campos personalizados de un post
	 *
	 * @param int $post_id ID del post
	 * @param array $data Datos a establecer
	 * @return void
	 */
	public function setFields($post_id, $data): void
	{
		$post_type = $data["post_type"] ?? $this->getPostType();

		$fields = [
			"custom_id" => "field_post_type_{$post_type}_custom_id",
			"post_slug" => "field_post_type_{$post_type}_url_slug",
			"seo_description" => "field_post_type_{$post_type}_seo_description",
		];

		foreach ($fields as $key => $field_key) {
			if (!empty($data[$key]) && $data[$key] !== "NULL") {
				update_field($field_key, $data[$key], $post_id);
			}
		}
	}

	/**
	 * Método abstracto para procesar datos específicos de cada tipo de importación
	 *
	 * @param array $data Datos a procesar
	 * @return array Datos procesados
	 */
	abstract public function processSpecificData(array $data): array;
}

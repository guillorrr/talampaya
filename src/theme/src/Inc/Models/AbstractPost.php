<?php

namespace App\Inc\Models;

use Timber\Timber;
use App\Inc\Traits\ImportDataTrait;
use Illuminate\Support\Str;

/**
 * Clase base abstracta para modelos de Post personalizados
 */
abstract class AbstractPost extends \Timber\Post
{
	use ImportDataTrait;

	/**
	 * Obtiene una instancia del modelo
	 *
	 * @return static
	 */
	public static function getInstance(): self
	{
		return new static();
	}

	/**
	 * Obtiene el tipo de post predeterminado basado en el nombre de la clase
	 * Versión estática que no requiere una instancia
	 *
	 * @return string
	 */
	public static function getPostType(): string
	{
		$class_name = (new \ReflectionClass(static::class))->getShortName();
		return Str::snake($class_name);
	}

	/**
	 * Obtiene el tipo de post predeterminado basado en el nombre de la clase
	 *
	 * @return string
	 */
	public function default_post_type(): string
	{
		return static::getPostType();
	}

	/**
	 * Obtiene el ID personalizado del post
	 * Este método debe ser implementado por las clases hijas
	 *
	 * @return string|null
	 */
	abstract public function custom_id(): ?string;

	/**
	 * Actualiza los datos del post a partir de un array
	 *
	 * @param array $data Datos a actualizar
	 * @return bool True si la actualización fue exitosa, false en caso contrario
	 */
	public function updateFromData(array $data): bool
	{
		try {
			error_log(
				static::class . "::updateFromData: Iniciando actualización para post_id={$this->ID}"
			);

			$post_update_data = [
				"ID" => $this->ID,
			];

			// Solo actualizar campos si tienen un valor
			if (!empty($data["title"])) {
				$post_update_data["post_title"] = sanitize_text_field($data["title"]);
			}
			if (!empty($data["content"])) {
				$post_update_data["post_content"] = wp_kses_post($data["content"]);
			}
			if (!empty($data["slug"])) {
				$post_update_data["post_name"] = sanitize_title($data["slug"]);
			} elseif (!empty($data["post_slug"])) {
				$post_update_data["post_name"] = sanitize_title($data["post_slug"]);
			}
			if (!empty($data["status"])) {
				$post_update_data["post_status"] = $data["status"];
			}
			if (!empty($data["post_date"])) {
				$post_update_data["post_date"] = $data["post_date"];
			}
			if (!empty($data["post_modified"])) {
				$post_update_data["post_modified"] = $data["post_modified"];
			}
			if (!empty($data["menu_order"])) {
				$post_update_data["menu_order"] = $data["menu_order"];
			}

			// Solo ejecutar wp_update_post si hay algo que cambiar
			if (count($post_update_data) > 1) {
				error_log(
					static::class .
						"::updateFromData: Actualizando datos básicos del post para post_id={$this->ID}"
				);
				$result = wp_update_post($post_update_data, true);
				if (is_wp_error($result)) {
					error_log(
						static::class .
							"::updateFromData: Error al actualizar post_id={$this->ID}, error={$result->get_error_message()}"
					);
					return false;
				}

				// Actualizar propiedades del objeto actual con los datos del post actualizado
				$updated_post = get_post($this->ID);
				if ($updated_post) {
					$this->post_title = $updated_post->post_title;
					$this->post_content = $updated_post->post_content;
					$this->post_status = $updated_post->post_status;
					if (isset($updated_post->post_date)) {
						$this->post_date = $updated_post->post_date;
					}
					if (isset($updated_post->post_modified)) {
						$this->post_modified = $updated_post->post_modified;
					}
				}
			}

			// Actualizar campos personalizados
			$custom_fields_success = $this->updateCustomFields($data);
			if (!$custom_fields_success) {
				error_log(
					static::class .
						"::updateFromData: Advertencia - Algunos campos personalizados no se actualizaron para post_id={$this->ID}"
				);
				// Continuamos a pesar de problemas con campos personalizados
			}

			// Actualizar datos SEO si existe el método
			if (method_exists($this, "updateSeoData")) {
				$seo_update_success = $this->updateSeoData($data);
				if (!$seo_update_success) {
					error_log(
						static::class .
							"::updateFromData: Advertencia - Algunos datos SEO no se actualizaron para post_id={$this->ID}"
					);
					// Continuamos a pesar de problemas con datos SEO
				}
			}

			error_log(
				static::class .
					"::updateFromData: Finalizada actualización exitosa para post_id={$this->ID}"
			);
			return true;
		} catch (\Exception $e) {
			error_log(
				static::class .
					"::updateFromData: Error inesperado al actualizar post_id={$this->ID}, error=" .
					$e->getMessage()
			);
			return false;
		}
	}

	/**
	 * Actualiza los campos personalizados del post
	 *
	 * @param array $data Datos a actualizar
	 * @return bool True si la actualización fue exitosa, false en caso contrario
	 */
	public function updateCustomFields(array $data): bool
	{
		$exclude_keys = [
			"post_type",
			"title",
			"status",
			"post_date",
			"post_modified",
			"content",
			"slug",
			"menu_order",
		];

		$success = true;
		$updated_fields_count = 0;

		try {
			foreach ($data as $key => $value) {
				if (!empty($value) && !in_array($key, $exclude_keys)) {
					$post_type = $data["post_type"] ?? null;
					$class_name = (new \ReflectionClass($this))->getShortName();
					if (empty($post_type)) {
						$post_type = Str::snake($class_name);
					}

					// Intentar primero con la clave específica para este tipo de post
					$field_key = "field_post_type_{$post_type}_{$key}";
					$field_exists = function_exists("acf_get_field")
						? acf_get_field($field_key)
						: null;

					if (!$field_exists) {
						// Si no existe, buscar si hay un campo en los campos generados por articles_post-fields.php
						$field_key = "field_post_type_{$post_type}_{$key}";
						error_log(
							static::class .
								"::updateCustomFields: Campo ACF no encontrado: {$field_key} para post_id={$this->ID}"
						);
					}

					if (function_exists("update_field")) {
						$result = update_field($field_key, $value, $this->ID);
						if ($result) {
							$updated_fields_count++;
						} else {
							error_log(
								static::class .
									"::updateCustomFields: No se pudo actualizar el campo {$key} con clave {$field_key} para post_id={$this->ID}"
							);
						}
					} else {
						error_log(
							static::class .
								"::updateCustomFields: La función update_field no está disponible para post_id={$this->ID}"
						);
						$success = false;
					}
				}
			}

			// Si hay campos para actualizar y no se actualizó ninguno, considerar como error
			if (
				$updated_fields_count === 0 &&
				count(array_diff_key($data, array_flip($exclude_keys))) > 0
			) {
				error_log(
					static::class .
						"::updateCustomFields: No se actualizó ningún campo personalizado para post_id={$this->ID}"
				);
				// No fallar solo por esto, podría ser que no hay campos ACF disponibles
				// $success = false;
			}
		} catch (\Exception $e) {
			error_log(
				static::class .
					"::updateCustomFields: Error al actualizar campos personalizados para post_id={$this->ID}, error=" .
					$e->getMessage()
			);
			$success = false;
		}

		return $success;
	}

	/**
	 * Busca un post por su ID personalizado
	 *
	 * @param string $custom_id ID personalizado
	 * @param string|null $post_type Tipo de post
	 * @return static|null
	 */
	public function findByCustomId(string $custom_id, string $post_type = null): ?self
	{
		if (!is_null($post_type)) {
			$post_type = sanitize_text_field($post_type);
		} else {
			$post_type = Str::snake((new \ReflectionClass($this))->getShortName());
		}

		$args = [
			"post_type" => $post_type,
			"post_status" => "any",
			"meta_query" => [
				[
					"key" => "post_type_{$post_type}_custom_id",
					"value" => sanitize_text_field($custom_id),
					"compare" => "=",
				],
			],
			"posts_per_page" => 1,
		];

		$posts = Timber::get_posts($args);
		return !empty($posts) ? $posts[0] : null;
	}
}

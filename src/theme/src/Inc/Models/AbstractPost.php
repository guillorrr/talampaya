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
	 *
	 * @return string
	 */
	public function default_post_type(): string
	{
		$class_name = (new \ReflectionClass($this))->getShortName();
		return Str::snake($class_name);
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
	 * @return bool
	 */
	public function updateFromData(array $data): bool
	{
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
			$result = wp_update_post($post_update_data, true);
			if (is_wp_error($result)) {
				error_log(
					static::class .
						"::updateFromData: Error al actualizar post_id={$this->ID}, error={$result->get_error_message()}"
				);
				return false;
			}
		}

		$this->updateCustomFields($data);

		if (method_exists($this, "updateSeoData")) {
			$this->updateSeoData($data);
		}

		return true;
	}

	/**
	 * Actualiza los campos personalizados del post
	 *
	 * @param array $data Datos a actualizar
	 * @return void
	 */
	public function updateCustomFields(array $data): void
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

		foreach ($data as $key => $value) {
			if (!empty($value) && !in_array($key, $exclude_keys)) {
				$post_type = $data["post_type"] ?? null;
				$class_name = (new \ReflectionClass($this))->getShortName();
				if (empty($post_type)) {
					$post_type = Str::snake($class_name);
				}

				// Intentar primero con la clave específica para este tipo de post
				$field_key = "field_post_type_{$post_type}_{$key}";
				$field_exists = function_exists("acf_get_field") ? acf_get_field($field_key) : null;

				if (!$field_exists) {
					// Si no existe, buscar si hay un campo en los campos generados por articles_post-fields.php
					error_log(
						"Campo ACF no encontrado: {$field_key}, intentando con clave alternativa"
					);
					$field_key = "field_post_type_{$post_type}_{$key}";
				}

				if (function_exists("update_field")) {
					update_field($field_key, $value, $this->ID);
				}
			}
		}
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

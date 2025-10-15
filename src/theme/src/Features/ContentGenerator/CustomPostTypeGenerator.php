<?php

namespace App\Features\ContentGenerator;

/**
 * Generador de contenido para Custom Post Types
 */
class CustomPostTypeGenerator extends AbstractContentGenerator
{
	/**
	 * El tipo de post personalizado
	 * @var string
	 */
	protected string $post_type;

	/**
	 * Datos para los posts a crear
	 * @var array
	 */
	protected array $posts_data;

	/**
	 * Constructor
	 *
	 * @param string $option_key Clave de opción para rastrear si el contenido ya ha sido creado
	 * @param string $post_type El tipo de post personalizado
	 * @param array $posts_data Datos de los posts a crear
	 */
	public function __construct(string $option_key, string $post_type, array $posts_data)
	{
		parent::__construct($option_key);
		$this->post_type = $post_type;
		$this->posts_data = $posts_data;
	}

	/**
	 * Genera Custom Post Types basados en los datos proporcionados
	 *
	 * @return bool Verdadero si la generación fue exitosa, falso en caso contrario
	 */
	protected function generateContent(): bool
	{
		if (empty($this->posts_data)) {
			return false;
		}

		$existing_posts = get_posts([
			"post_type" => $this->post_type,
			"post_status" => "any",
			"numberposts" => -1,
		]);

		$existing_slugs = array_map(function ($post) {
			return $post->post_name;
		}, $existing_posts);

		$success = true;

		foreach ($this->posts_data as $slug => $post_data) {
			// Omitir si el post ya existe
			if (in_array($slug, $existing_slugs, true)) {
				continue;
			}

			$post_args = [
				"post_title" => $post_data["title"] ?? $slug,
				"post_name" => $slug,
				"post_content" => $post_data["content"] ?? "",
				"post_status" => "publish",
				"post_type" => $this->post_type,
			];

			$post_id = wp_insert_post($post_args);

			if (!$post_id || is_wp_error($post_id)) {
				$success = false;
				continue;
			}

			// Si hay metadatos, los añadimos
			if (!empty($post_data["meta"])) {
				foreach ($post_data["meta"] as $meta_key => $meta_value) {
					update_post_meta($post_id, $meta_key, $meta_value);
				}
			}
		}

		return $success;
	}
}

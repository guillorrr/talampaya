<?php

namespace App\Features\ContentGenerator;

/**
 * Generador genérico para cualquier tipo de contenido
 */
class ContentTypeGenerator extends AbstractContentGenerator
{
	/**
	 * El tipo de post
	 * @var string
	 */
	protected string $post_type;

	/**
	 * Datos para los contenidos a crear o actualizar
	 * @var array
	 */
	protected array $content_data;

	/**
	 * Estrategias de procesamiento de contenido
	 * @var array
	 */
	protected array $content_processors;

	/**
	 * Constructor
	 *
	 * @param string $option_key Clave de opción para rastrear si el contenido ya ha sido creado
	 * @param string $post_type Tipo de post
	 * @param array $content_data Datos del contenido a generar
	 * @param array $content_processors Procesadores de contenido opcionales
	 */
	public function __construct(
		string $option_key,
		string $post_type,
		array $content_data,
		array $content_processors = []
	) {
		parent::__construct($option_key);
		$this->post_type = $post_type;
		$this->content_data = $content_data;
		$this->content_processors = $content_processors ?: [
			"default" => function ($content) {
				return $content;
			},
		];
	}

	/**
	 * Registra un procesador de contenido
	 *
	 * @param string $type Tipo de procesador
	 * @param callable $processor Función de procesamiento
	 * @return $this
	 */
	public function registerContentProcessor(string $type, callable $processor): self
	{
		$this->content_processors[$type] = $processor;
		return $this;
	}

	/**
	 * Genera el contenido basado en los datos y procesadores proporcionados
	 *
	 * @return bool Verdadero si la generación fue exitosa, falso en caso contrario
	 */
	protected function generateContent(): bool
	{
		if (empty($this->content_data)) {
			return false;
		}

		$success = true;

		foreach ($this->content_data as $slug => $item_data) {
			// Determinar si es creación o actualización
			$is_update = isset($item_data["update"]) && $item_data["update"] === true;
			$post = get_page_by_path($slug, OBJECT, $this->post_type);

			// Configuración común
			$post_args = [
				"post_title" => $item_data["title"] ?? $slug,
				"post_name" => $slug,
				"post_type" => $this->post_type,
				"post_status" => "publish",
			];

			// Procesamiento de contenido según el tipo
			if (isset($item_data["content"])) {
				$content_type = $item_data["content_type"] ?? "default";
				$processor =
					$this->content_processors[$content_type] ??
					$this->content_processors["default"];
				$post_args["post_content"] = $processor($item_data["content"]);
			}

			if ($is_update && $post) {
				// Actualizar post existente
				$post_args["ID"] = $post->ID;
				$result = wp_update_post($post_args);
			} elseif (!$post) {
				// Crear nuevo post
				$result = wp_insert_post($post_args);
			} else {
				// Post existe y no es una actualización
				continue;
			}

			if (!$result || is_wp_error($result)) {
				$success = false;
				continue;
			}

			$post_id = $result;

			// Procesar metadatos si existen
			if (isset($item_data["meta"]) && is_array($item_data["meta"])) {
				foreach ($item_data["meta"] as $meta_key => $meta_value) {
					update_post_meta($post_id, $meta_key, $meta_value);
				}
			}

			// Procesar taxonomías si existen
			if (isset($item_data["taxonomies"]) && is_array($item_data["taxonomies"])) {
				foreach ($item_data["taxonomies"] as $taxonomy => $terms) {
					wp_set_object_terms($post_id, $terms, $taxonomy);
				}
			}
		}

		return $success;
	}

	/**
	 * Procesador de contenido HTML
	 *
	 * @param string $path Ruta al archivo HTML
	 * @return string Contenido HTML o cadena vacía en caso de error
	 */
	public static function htmlContentProcessor(string $path): string
	{
		$file_path = get_template_directory() . $path;
		if (file_exists($file_path)) {
			$content = file_get_contents($file_path);
			return $content !== false ? $content : "";
		}
		return "";
	}

	/**
	 * Procesador de contenido para bloques Gutenberg
	 *
	 * @param array $blocks Definición de bloques
	 * @return string Contenido de bloques procesado
	 */
	public static function blocksContentProcessor(array $blocks): string
	{
		if (function_exists("\App\Inc\Helpers\AcfHelper::talampaya_make_content_for_blocks_acf")) {
			return \App\Inc\Helpers\AcfHelper::talampaya_make_content_for_blocks_acf($blocks);
		}

		// Implementación básica para bloques
		$content = "";
		foreach ($blocks as $block) {
			$block_name = $block["name"] ?? "";
			if (!empty($block_name)) {
				$attrs = isset($block["attributes"]) ? " " . json_encode($block["attributes"]) : "";
				$inner_content = $block["innerContent"] ?? "";
				$content .= "<!-- wp:" . $block_name . $attrs . " -->";
				if (!empty($inner_content)) {
					$content .= $inner_content;
					$content .= "<!-- /wp:" . $block_name . " -->";
				}
			}
		}
		return $content;
	}
}

<?php

namespace App\Features\Acf;

use App\Utils\FileUtils;
use Timber;
use WP_Block;

/**
 * Clase principal para la integración de Advanced Custom Fields (ACF)
 *
 * Proporciona funcionalidad básica para registrar bloques ACF y renderizarlos con Timber
 */
class Acf
{
	/**
	 * Inicializar la integración con ACF
	 */
	public function __construct()
	{
		if (!class_exists("ACF")) {
			return;
		}

		// Registrar los bloques ACF
		add_action("init", [$this, "registerBlocks"]);
	}

	/**
	 * Registrar bloques ACF desde archivos JSON
	 */
	public function registerBlocks(): void
	{
		if (!defined("ACF_BLOCKS_PATH") || !file_exists(ACF_BLOCKS_PATH)) {
			return;
		}

		$blocks = FileUtils::talampaya_directory_iterator_universal(ACF_BLOCKS_PATH, [
			"extension" => "json",
			"process_subdirs" => true,
			"filter_callback" => function ($file, $path, $directory_name = null) {
				return $directory_name &&
					str_ends_with($file->getFilename(), "-block.json") &&
					$file->getFilename() === "$directory_name-block.json";
			},
		]);

		foreach ($blocks as $block_json) {
			register_block_type($block_json);
		}
	}

	/**
	 * Render callback para bloques ACF
	 * Este método es inmutable y sirve como base para renderizar bloques ACF con Timber
	 *
	 * @param array $attributes Atributos del bloque
	 * @param string $content Contenido del bloque
	 * @param bool $is_preview Si se está visualizando en el editor
	 * @param int $post_id ID del post actual
	 * @param WP_Block|null $wp_block Instancia del bloque
	 */
	public static function renderBlock(
		array $attributes,
		string $content = "",
		bool $is_preview = false,
		int $post_id = 0,
		?WP_Block $wp_block = null
	): void {
		// Crear el slug del bloque a partir del nombre en block.json
		$slug = str_replace("acf/", "", $attributes["name"]);

		$context = Timber::context();

		// Almacenar atributos del bloque
		$context["attributes"] = $attributes;

		// Almacenar valores de campos (del grupo de campos ACF para el bloque)
		$context["fields"] = get_fields();

		// Almacenar si el bloque se está renderizando en el editor o en el frontend
		$context["is_preview"] = $is_preview;

		// Permitir que otros plugins/temas modifiquen el contexto
		$context = apply_filters(
			"acf/block/render/context",
			$context,
			$attributes,
			$content,
			$is_preview,
			$post_id,
			$wp_block,
			$slug
		);

		// Renderizar el bloque
		Timber::render("blocks/" . $slug . "/" . $slug . "-block.twig", $context);
	}
}

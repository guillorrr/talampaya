<?php

namespace App\Features\Acf;

use App\Features\Acf\Block\BlockRenderer;
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

		// Inicializar el sistema de modificadores de contexto para BlockRenderer
		$this->initBlockRenderer();
	}

	/**
	 * Inicializar el sistema BlockRenderer y agregar modificadores de contexto
	 */
	private function initBlockRenderer(): void
	{
		// Ejemplo: registrar un modificador de contexto que agrega metadatos comunes
		BlockRenderer::registerContextModifier("metadata", function (
			$context,
			$attributes,
			$content,
			$is_preview,
			$post_id,
			$wp_block,
			$slug
		) {
			$context["metadata"] = [
				"blockSlug" => $slug,
				"postId" => $post_id,
			];
			return $context;
		});

		// Compatibilidad con el método antiguo - redirigir filtros al nuevo sistema
		add_filter(
			"acf/block/render/context",
			function ($context, $attributes, $content, $is_preview, $post_id, $wp_block, $slug) {
				// Este filtro garantiza compatibilidad con código existente que usaba el filtro anterior
				return $context;
			},
			10,
			7
		);
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
	 * Render callback para bloques ACF (Método heredado para compatibilidad)
	 * Este método ahora delega la renderización a BlockRenderer
	 *
	 * @param array $attributes Atributos del bloque
	 * @param string $content Contenido del bloque
	 * @param bool $is_preview Si se está visualizando en el editor
	 * @param int $post_id ID del post actual
	 * @param WP_Block|null $wp_block Instancia del bloque
	 * @deprecated Usar BlockRenderer::render en su lugar
	 */
	public static function renderBlock(
		array $attributes,
		string $content = "",
		bool $is_preview = false,
		int $post_id = 0,
		?WP_Block $wp_block = null
	): void {
		// Delegar la renderización a BlockRenderer para mantener compatibilidad
		BlockRenderer::render($attributes, $content, $is_preview, $post_id, $wp_block);
	}
}

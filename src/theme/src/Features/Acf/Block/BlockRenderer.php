<?php

namespace App\Features\Acf\Block;

use Timber;
use WP_Block;

/**
 * Clase para renderizar bloques ACF
 *
 * Proporciona métodos para renderizar bloques ACF y extender su funcionalidad
 */
class BlockRenderer
{
	/**
	 * Almacena los modificadores de contexto registrados
	 *
	 * @var callable[]
	 */
	protected static array $contextModifiers = [];

	/**
	 * Registra un modificador de contexto para bloques ACF
	 *
	 * @param string $key Identificador único del modificador
	 * @param callable $callback Función callback que modificará el contexto
	 *     La función debe aceptar estos parámetros:
	 *     - array $context: El contexto actual
	 *     - array $attributes: Los atributos del bloque
	 *     - string $content: Contenido del bloque
	 *     - bool $is_preview: Si se está visualizando en el editor
	 *     - int $post_id: ID del post actual
	 *     - WP_Block|null $wp_block: Instancia del bloque
	 *     - string $slug: Slug del bloque
	 */
	public static function registerContextModifier(string $key, callable $callback): void
	{
		self::$contextModifiers[$key] = $callback;
	}

	/**
	 * Elimina un modificador de contexto registrado
	 *
	 * @param string $key Identificador del modificador a eliminar
	 */
	public static function removeContextModifier(string $key): void
	{
		if (isset(self::$contextModifiers[$key])) {
			unset(self::$contextModifiers[$key]);
		}
	}

	/**
	 * Render callback genérico para bloques ACF
	 * Este método es inmutable y sirve como base para renderizar bloques ACF con Timber
	 *
	 * @param array $attributes Atributos del bloque
	 * @param string $content Contenido del bloque
	 * @param bool $is_preview Si se está visualizando en el editor
	 * @param int $post_id ID del post actual
	 * @param WP_Block|null $wp_block Instancia del bloque
	 */
	public static function render(
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

		// Aplicar modificadores de contexto registrados
		foreach (self::$contextModifiers as $modifier) {
			$context = $modifier(
				$context,
				$attributes,
				$content,
				$is_preview,
				$post_id,
				$wp_block,
				$slug
			);
		}

		// Permitir que otros plugins/temas modifiquen el contexto con filtros de WordPress
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

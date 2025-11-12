<?php

namespace App\Inc\Helpers;

/**
 * Helper para generar diferentes tipos de contenido para WordPress
 */
class ContentTypeHelper
{
	/**
	 * Genera un bloque Gutenberg de párrafo
	 *
	 * @param string $content Contenido del párrafo
	 * @param array $attributes Atributos adicionales para el bloque
	 * @return string Bloque Gutenberg formateado
	 */
	public static function createParagraph(string $content, array $attributes = []): string
	{
		$attrs = "";
		if (!empty($attributes)) {
			$attrs = " " . json_encode($attributes);
		}

		return "<!-- wp:paragraph{$attrs} -->\n<p>{$content}</p>\n<!-- /wp:paragraph -->";
	}

	/**
	 * Genera un bloque Gutenberg de encabezado
	 *
	 * @param string $content Contenido del encabezado
	 * @param int $level Nivel del encabezado (1-6)
	 * @param array $attributes Atributos adicionales para el bloque
	 * @return string Bloque Gutenberg formateado
	 */
	public static function createHeading(
		string $content,
		int $level = 2,
		array $attributes = []
	): string {
		if ($level < 1) {
			$level = 1;
		}
		if ($level > 6) {
			$level = 6;
		}

		$attrs = "";
		if (!empty($attributes)) {
			$attrs = " " . json_encode($attributes);
		}

		return "<!-- wp:heading{$attrs}  {\"level\":{$level}} -->\n<h{$level} class=\"wp-block-heading\">{$content}</h{$level}>\n<!-- /wp:heading -->";
	}

	/**
	 * Genera un bloque Gutenberg de lista
	 *
	 * @param array $items Elementos de la lista
	 * @param string $type Tipo de lista: 'ul' (desordenada) o 'ol' (ordenada)
	 * @param array $attributes Atributos adicionales para el bloque
	 * @return string Bloque Gutenberg formateado
	 */
	public static function createList(
		array $items,
		string $type = "ul",
		array $attributes = []
	): string {
		$attrs = "";
		if (!empty($attributes)) {
			$attrs = " " . json_encode($attributes);
		}

		$list_type = $type === "ol" ? "ordered" : "unordered";

		$list_content = "<{$type}  class=\"wp-block-list\">\n";
		foreach ($items as $item) {
			$list_content .= "\t<!-- wp:list-item --><li>{$item}</li><!-- /wp:list-item -->\n";
		}
		$list_content .= "</{$type}>";

		return "<!-- wp:list {\"type\":\"{$list_type}\"{$attrs}} -->\n{$list_content}\n<!-- /wp:list -->";
	}

	/**
	 * Genera un bloque Gutenberg de cita
	 *
	 * @param string $content Contenido de la cita
	 * @param string $citation Autor de la cita
	 * @param array $attributes Atributos adicionales para el bloque
	 * @return string Bloque Gutenberg formateado
	 */
	public static function createQuote(
		string $content,
		string $citation = "",
		array $attributes = []
	): string {
		$attrs = "";
		if (!empty($attributes)) {
			$attrs = " " . json_encode($attributes);
		}

		$quote = "<blockquote class=\"wp-block-quote\"><p>{$content}</p>";
		if (!empty($citation)) {
			$quote .= "<cite>{$citation}</cite>";
		}
		$quote .= "</blockquote>";

		return "<!-- wp:quote{$attrs} -->\n{$quote}\n<!-- /wp:quote -->";
	}

	/**
	 * Genera un bloque Gutenberg personalizado
	 *
	 * @param string $block_name Nombre del bloque
	 * @param array $attributes Atributos del bloque
	 * @param string $inner_content Contenido interno del bloque
	 * @return string Bloque Gutenberg formateado
	 */
	public static function createCustomBlock(
		string $block_name,
		array $attributes = [],
		string $inner_content = ""
	): string {
		$attrs = "";
		if (!empty($attributes)) {
			$attrs = " " . json_encode($attributes);
		}

		if (empty($inner_content)) {
			return "<!-- wp:{$block_name}{$attrs} /-->";
		}

		return "<!-- wp:{$block_name}{$attrs} -->\n{$inner_content}\n<!-- /wp:{$block_name} -->";
	}

	/**
	 * Genera un separador
	 *
	 * @param array $attributes Atributos adicionales para el bloque
	 * @return string Bloque Gutenberg formateado
	 */
	public static function createSeparator(array $attributes = []): string
	{
		$attrs = "";
		if (!empty($attributes)) {
			$attrs = " " . json_encode($attributes);
		}

		return "<!-- wp:separator{$attrs} -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->";
	}

	/**
	 * Crea un contenedor con bloques interiores
	 *
	 * @param array $inner_blocks Array de bloques internos
	 * @param array $attributes Atributos adicionales para el bloque
	 * @return string Bloque Gutenberg formateado
	 */
	public static function createGroup(array $inner_blocks, array $attributes = []): string
	{
		$attrs = "";
		if (!empty($attributes)) {
			$attrs = " " . json_encode($attributes);
		}

		$content = implode("\n", $inner_blocks);

		return "<!-- wp:group{$attrs} -->\n<div class=\"wp-block-group\">\n{$content}\n</div>\n<!-- /wp:group -->";
	}

	/**
	 * Crea un bloque HTML
	 *
	 * @param string $html Contenido HTML
	 * @return string Bloque Gutenberg formateado
	 */
	public static function createHtmlBlock(string $html): string
	{
		return "<!-- wp:html -->\n{$html}\n<!-- /wp:html -->";
	}

	/**
	 * Compone varios bloques en un solo contenido
	 *
	 * @param array $blocks Array de bloques a combinar
	 * @return string Contenido combinado
	 */
	public static function combineBlocks(array $blocks): string
	{
		return implode("\n\n", $blocks);
	}

	/**
	 * Genera contenido para páginas de texto clasico
	 *
	 * @param string $title Título de la página
	 * @param array $paragraphs Párrafos para el contenido
	 * @param array $sections Secciones adicionales (título => contenido)
	 * @return string Contenido formateado como bloques Gutenberg
	 */
	public static function createClassicPageContent(
		string $title,
		array $paragraphs,
		array $sections = []
	): string {
		$blocks = [];

		// Título principal
		$blocks[] = self::createHeading($title, 1);

		// Párrafos introductorios
		foreach ($paragraphs as $paragraph) {
			$blocks[] = self::createParagraph($paragraph);
		}

		// Secciones adicionales
		foreach ($sections as $section_title => $section_content) {
			$blocks[] = self::createHeading($section_title, 2);

			if (is_array($section_content)) {
				// Si el contenido es un array, crear párrafos
				foreach ($section_content as $paragraph) {
					$blocks[] = self::createParagraph($paragraph);
				}
			} else {
				// Si es una cadena, crear un solo párrafo
				$blocks[] = self::createParagraph($section_content);
			}
		}

		return self::combineBlocks($blocks);
	}

	/**
	 * Convierte contenido HTML en bloques Gutenberg si es posible
	 *
	 * @param string $html Contenido HTML a convertir
	 * @return string Contenido convertido a bloques Gutenberg o encapsulado en bloque HTML
	 */
	public static function htmlToGutenberg(string $html): string
	{
		// Si disponemos de la función parse_blocks, podemos intentar convertir el HTML
		if (function_exists("parse_blocks")) {
			$blocks = parse_blocks($html);

			// Si se pudo parsear como bloques, serializamos
			if (!empty($blocks) && isset($blocks[0]["blockName"])) {
				return serialize_blocks($blocks);
			}
		}

		// Si no se pudo convertir, lo encapsulamos en un bloque HTML
		return self::createHtmlBlock($html);
	}
}

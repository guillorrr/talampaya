<?php

namespace App\Features\ContentGenerator;

/**
 * Generador de contenido para páginas
 */
class PageContentGenerator extends AbstractContentGenerator
{
	/**
	 * Datos de contenido para las páginas
	 * @var array
	 */
	protected array $pages_data;

	/**
	 * Constructor
	 *
	 * @param string $option_key Clave de opción para rastrear si el contenido ya ha sido creado
	 * @param array $pages_data Datos de contenido para páginas
	 */
	public function __construct(string $option_key, array $pages_data)
	{
		parent::__construct($option_key);
		$this->pages_data = $pages_data;
	}

	/**
	 * Genera contenido para páginas basado en los datos proporcionados
	 *
	 * @return bool Verdadero si la generación fue exitosa, falso en caso contrario
	 */
	protected function generateContent(): bool
	{
		if (empty($this->pages_data)) {
			return false;
		}

		$success = true;

		foreach ($this->pages_data as $page_slug => $blocks) {
			$page = get_page_by_path($page_slug);

			if (!$page || $page->post_content !== "") {
				continue;
			}

			$content = $this->generateBlocksContent($blocks);

			$update_result = wp_update_post([
				"ID" => $page->ID,
				"post_content" => $content,
			]);

			if (!$update_result) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Genera el contenido de bloques para la página
	 *
	 * @param array $blocks Datos de los bloques a generar
	 * @return string Contenido HTML generado
	 */
	protected function generateBlocksContent(array $blocks): string
	{
		if (function_exists("\App\Inc\Helpers\AcfHelper::talampaya_make_content_for_blocks_acf")) {
			return \App\Inc\Helpers\AcfHelper::talampaya_make_content_for_blocks_acf($blocks);
		}

		// Implementación básica si no existe el helper
		$content = "";
		foreach ($blocks as $block) {
			$content .= "<!-- wp:acf/" . $block["name"] . " /-->";
		}

		return $content;
	}
}

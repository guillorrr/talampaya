<?php

namespace App\Features\ContentGenerator\Processors;

use App\Features\ContentGenerator\ContentProcessorInterface;

/**
 * Procesador para contenido HTML desde archivos
 */
class HtmlContentProcessor implements ContentProcessorInterface
{
	/**
	 * @var string
	 */
	protected string $base_path;

	/**
	 * Constructor
	 *
	 * @param string $base_path Ruta base para los archivos HTML
	 */
	public function __construct(string $base_path = "/src/Features/DefaultContent/html-content/")
	{
		$this->base_path = $base_path;
	}

	/**
	 * Procesa contenido HTML desde un archivo
	 *
	 * @param mixed $content Ruta al archivo HTML o contenido HTML directo
	 * @return string Contenido HTML procesado
	 */
	public function process(mixed $content): string
	{
		// Si es una ruta relativa, construir ruta completa
		if (is_string($content) && str_starts_with($content, "/")) {
			$file_path = get_template_directory() . $content;
		} elseif (is_string($content) && !preg_match("/<[^>]*>/", $content)) {
			// Si parece un nombre de archivo sin tags HTML
			$file_path = get_template_directory() . $this->base_path . $content;
			if (!file_exists($file_path) && !pathinfo($file_path, PATHINFO_EXTENSION)) {
				$file_path .= ".html";
			}
		} else {
			// Si es contenido HTML directo, devolverlo tal cual
			return $content;
		}

		if (file_exists($file_path)) {
			$html_content = file_get_contents($file_path);
			return $html_content !== false ? $html_content : "";
		}

		return "";
	}
}

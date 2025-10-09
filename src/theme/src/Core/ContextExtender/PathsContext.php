<?php

namespace App\Core\ContextExtender;

/**
 * Clase que agrega las rutas al contexto global de Timber
 */
class PathsContext implements ContextExtenderInterface
{
	/**
	 * Extiende el contexto de Timber
	 *
	 * @param array $context El contexto actual de Timber
	 * @return array El contexto modificado
	 */
	public function extendContext(array $context): array
	{
		$context["paths"] = $this->getPathsForContext();
		return $context;
	}

	/**
	 * Crea un objeto de rutas para el contexto usando las constantes definidas
	 *
	 * @return object Objeto con todas las rutas
	 */
	protected function getPathsForContext(): object
	{
		$paths = [
			"theme_root" => get_theme_root(),
			"template" => defined("THEME_DIR") ? THEME_DIR : get_template_directory(),
			"stylesheet" => get_stylesheet_directory(),
			"template_uri" => defined("THEME_URI") ? THEME_URI : get_template_directory_uri(),
			"stylesheet_uri" => get_stylesheet_directory_uri(),
			"assets" => defined("THEME_ASSETS_URI")
				? THEME_ASSETS_URI
				: get_template_directory_uri() . "/assets",
			"img" => defined("THEME_IMG_URI")
				? THEME_IMG_URI
				: get_template_directory_uri() . "/assets/img",
			"css" => defined("THEME_CSS_URI")
				? THEME_CSS_URI
				: get_template_directory_uri() . "/css",
			"js" => defined("THEME_JS_URI") ? THEME_JS_URI : get_template_directory_uri() . "/js",
		];

		$rel_paths = [
			"rel_template" => wp_make_link_relative($paths["template_uri"]),
			"rel_stylesheet" => wp_make_link_relative($paths["stylesheet_uri"]),
		];

		// Rutas de funciones adicionales para mantener compatibilidad
		$functions_paths = [
			"core" => $paths["template"] . "/core",
			"functions" => $paths["template"] . "/inc",
		];

		return (object) array_merge($paths, $rel_paths, $functions_paths);
	}
}

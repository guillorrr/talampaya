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
			"template" => THEME_DIR,
			"stylesheet" => get_stylesheet_directory(),
			"template_uri" => THEME_URI,
			"stylesheet_uri" => get_stylesheet_directory_uri(),
			"assets" => THEME_ASSETS_URI,
			"img" => THEME_IMG_URI,
			"css" => THEME_CSS_URI,
			"js" => THEME_JS_URI,
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

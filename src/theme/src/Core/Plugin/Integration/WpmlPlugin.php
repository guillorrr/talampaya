<?php

namespace App\Core\Plugin\Integration;

use App\Core\Plugin\AbstractPlugin;

/**
 * Plugin integrado para WPML
 */
class WpmlPlugin extends AbstractPlugin
{
	/**
	 * Nombre del plugin
	 */
	protected string $name = "wpml";

	/**
	 * Verifica si el plugin debe activarse
	 *
	 * @return bool True si el plugin debe activarse
	 */
	public function shouldLoad(): bool
	{
		// Comprobar si WPML está activo
		return defined("ICL_SITEPRESS_VERSION");
	}

	/**
	 * Inicializa el plugin
	 */
	public function initialize(): void
	{
		// Agregar filtros y acciones para WPML
		add_filter("timber/twig", [$this, "addWpmlTwigExtensions"]);
		add_filter("timber/context", [$this, "addWpmlContext"]);

		// Soporte para URLs de traducciones
		add_filter("timber/url_helper/url_to_file_system/path", [$this, "correctWpmlPath"]);
	}

	/**
	 * Agrega extensiones de Twig para WPML
	 *
	 * @param \Twig\Environment $twig Entorno Twig
	 * @return \Twig\Environment Entorno Twig modificado
	 */
	public function addWpmlTwigExtensions(\Twig\Environment $twig): \Twig\Environment
	{
		// Agregar función para traducir cadenas
		$twig->addFunction(
			new \Twig\TwigFunction("__", function ($text, $domain = "default") {
				return __($text, $domain);
			})
		);

		$twig->addFunction(
			new \Twig\TwigFunction("_e", function ($text, $domain = "default") {
				return _e($text, $domain);
			})
		);

		$twig->addFunction(
			new \Twig\TwigFunction("_n", function ($single, $plural, $number, $domain = "default") {
				return _n($single, $plural, $number, $domain);
			})
		);

		$twig->addFunction(
			new \Twig\TwigFunction("_x", function ($text, $context, $domain = "default") {
				return _x($text, $context, $domain);
			})
		);

		$twig->addFunction(
			new \Twig\TwigFunction("_ex", function ($text, $context, $domain = "default") {
				return _ex($text, $context, $domain);
			})
		);

		// Función para obtener el idioma actual
		$twig->addFunction(
			new \Twig\TwigFunction("icl_get_languages", function ($args = "") {
				return apply_filters("wpml_active_languages", null, $args);
			})
		);

		// Función para obtener la URL de traducción de la página actual
		$twig->addFunction(
			new \Twig\TwigFunction("icl_get_home_url", function () {
				return apply_filters("wpml_home_url", get_home_url());
			})
		);

		return $twig;
	}

	/**
	 * Agrega datos de WPML al contexto de Timber
	 *
	 * @param array $context Contexto actual
	 * @return array Contexto modificado
	 */
	public function addWpmlContext(array $context): array
	{
		// Agregar idiomas disponibles al contexto
		$context["languages"] = apply_filters("wpml_active_languages", null);

		// Agregar idioma actual al contexto
		$context["current_language"] = apply_filters("wpml_current_language", null);
		$context["default_language"] = apply_filters("wpml_default_language", null);

		return $context;
	}

	/**
	 * Corrige las rutas para que funcionen con WPML
	 *
	 * @param string $path Ruta actual
	 * @return string Ruta corregida
	 */
	public function correctWpmlPath(string $path): string
	{
		// Si la ruta incluye un código de idioma de WPML, eliminarlo para la búsqueda de archivos
		$current_language = apply_filters("wpml_current_language", null);
		$default_language = apply_filters("wpml_default_language", null);

		if ($current_language && $current_language !== $default_language) {
			// Eliminar el código de idioma de la ruta
			$path = preg_replace("|^/" . $current_language . "/|", "/", $path);
		}

		return $path;
	}

	/**
	 * Obtiene la lista de plugins requeridos por este plugin
	 *
	 * @return array Lista de plugins requeridos
	 */
	public function getRequiredPlugins(): array
	{
		return [
			[
				"name" => "WPML Multilingual CMS",
				"slug" => "sitepress-multilingual-cms",
				"required" => false,
			],
			[
				"name" => "WPML String Translation",
				"slug" => "wpml-string-translation",
				"required" => false,
			],
			[
				"name" => "ACF Multilingual",
				"slug" => "acfml",
				"required" => false,
			],
		];
	}
}

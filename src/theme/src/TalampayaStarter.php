<?php

namespace App;

use Timber\Site;
use Timber\Timber;
use Twig\Extension\StringLoaderExtension;
use Twig\Extra\Html\HtmlExtension;
use Twig\TwigFilter;

class TalampayaStarter extends Site
{
	public function __construct()
	{
		add_filter("timber/context", [$this, "addToContext"]);
		add_filter("timber/twig", [$this, "addToTwig"]);
		add_filter("timber/twig/environment/options", [$this, "updateTwigEnvironmentOptions"]);
		add_filter("timber/locations", [$this, "addLocations"]);

		parent::__construct();
	}

	/**
	 * Agrega ubicaciones adicionales para plantillas Timber/Twig
	 */
	public function addLocations($paths)
	{
		$theme_dir = defined("THEME_DIR") ? THEME_DIR : get_template_directory();

		$paths["atoms"] = ["{$theme_dir}/views/atoms"];
		$paths["molecules"] = ["{$theme_dir}/views/molecules"];
		$paths["organisms"] = ["{$theme_dir}/views/organisms"];
		$paths["templates"] = ["{$theme_dir}/views/templates"];
		$paths["macros"] = ["{$theme_dir}/views/macros"];
		$paths["pages"] = ["{$theme_dir}/views/pages"];
		$paths["layouts"] = ["{$theme_dir}/views/layouts"];
		$paths["blocks"] = ["{$theme_dir}/views/blocks"];
		$paths["components"] = ["{$theme_dir}/views/components"];

		return $paths;
	}

	/**
	 * Agrega variables al contexto de Timber
	 */
	public function addToContext(array $context): array
	{
		// Usar constantes en lugar de obtener valores dinámicamente
		$context["version"] = defined("THEME_VERSION")
			? THEME_VERSION
			: wp_get_theme()->get("Version");
		$context["site"] = $this;
		$context["menu"] = Timber::get_menu();
		$context["links"]["home"] = home_url("/");

		if (defined("FACEBOOK_PIXEL_ID")) {
			$context["FACEBOOK_PIXEL_ID"] = FACEBOOK_PIXEL_ID;
		}
		if (defined("GOOGLE_ANALYTICS_ID")) {
			$context["GOOGLE_ANALYTICS_ID"] = GOOGLE_ANALYTICS_ID;
		}

		$context["paths"] = $this->getPathsForContext();

		return $context;
	}

	/**
	 * Crea un objeto de rutas para el contexto usando las constantes definidas
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

	/**
	 * Función de ejemplo para filtros Twig
	 */
	public function myfoo(string $text): string
	{
		$text .= " bar!";
		return $text;
	}

	/**
	 * Agrega funcionalidades personalizadas a Twig
	 */
	public function addToTwig(\Twig\Environment $twig): \Twig\Environment
	{
		/**
		 * Required when you want to use Twig’s template_from_string.
		 * @link https://twig.symfony.com/doc/3.x/functions/template_from_string.html
		 */
		$twig->addExtension(new StringLoaderExtension());
		$twig->addExtension(new HtmlExtension());

		// Agregar filtros personalizados
		$twig->addFilter(new TwigFilter("myfoo", [$this, "myfoo"]));

		return $twig;
	}

	/**
	 * Updates Twig environment options.
	 *
	 * @link https://twig.symfony.com/doc/2.x/api.html#environment-options
	 *
	 */
	public function updateTwigEnvironmentOptions(array $options): array
	{
		// $options['autoescape'] = true;
		return $options;
	}
}

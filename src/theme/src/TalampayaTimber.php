<?php

namespace App;

use Timber\Site;
use Timber\Timber;
use Twig\Extension\StringLoaderExtension;
use Twig\Extra\Html\HtmlExtension;
use Twig\TwigFilter;

class TalampayaTimber extends Site
{
	public $google_analytics_id = "UA-XXXXXXXX";
	public $facebook_pixel_ids = "XXXXXXXXXXXXXXX";

	public function __construct()
	{
		add_filter("timber/context", [$this, "add_to_context"]);
		add_filter("timber/twig", [$this, "add_to_twig"]);
		add_filter("timber/twig/environment/options", [$this, "update_twig_environment_options"]);
		add_filter("timber/locations", [$this, "add_locations"]);

		parent::__construct();
	}

	public function add_locations($paths)
	{
		$theme_dir = get_template_directory();

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

	public function add_to_context(array $context): array
	{
		$theme = wp_get_theme();
		$theme_version = $theme->get("Version");
		$paths = require_once __DIR__ . "./../utils/paths.php";

		$context["foo"] = "bar";
		//		$context["FACEBOOK_PIXEL_ID"] = $this->facebook_pixel_ids;
		//      $context["GOOGLE_ANALYTICS_ID"] = $this->google_analytics_id;
		$context["menu"] = Timber::get_menu();
		$context["paths"] = $paths;
		$context["version"] = $theme_version;
		$context["site"] = $this;
		$context["links"]["home"] = home_url("/");

		return $context;
	}

	public function myfoo(string $text): string
	{
		$text .= " bar!";
		return $text;
	}

	/**
	 * This is where you can add your own functions to twig.
	 */
	public function add_to_twig(\Twig\Environment $twig): \Twig\Environment
	{
		/**
		 * Required when you want to use Twig’s template_from_string.
		 * @link https://twig.symfony.com/doc/3.x/functions/template_from_string.html
		 */
		$twig->addExtension(new StringLoaderExtension());
		$twig->addExtension(new HtmlExtension());

		$twig->addFilter(new TwigFilter("myfoo", [$this, "myfoo"]));

		return $twig;
	}

	/**
	 * Updates Twig environment options.
	 *
	 * @link https://twig.symfony.com/doc/2.x/api.html#environment-options
	 *
	 */
	function update_twig_environment_options(array $options): array
	{
		// $options['autoescape'] = true;

		return $options;
	}
}

<?php

use Timber\Site;
use Timber\Timber;
use Twig\Extension\StringLoaderExtension;
use Twig\Extra\Html\HtmlExtension;

class TalampayaTimber extends Site
{
	public $google_analytics_id = "UA-XXXXXXXX";
	public $facebook_pixel_ids = "XXXXXXXXXXXXXXX";

	public function __construct()
	{
		add_filter("timber/context", [$this, "add_to_context"]);
		add_filter("timber/twig", [$this, "add_to_twig"]);
		add_filter("timber/twig/environment/options", [$this, "update_twig_environment_options"]);

		parent::__construct();
	}

	public function add_to_context(array $context): array
	{
		$theme = wp_get_theme();
		$theme_version = $theme->get("Version");
		$paths = require_once __DIR__ . "./../inc/utils/paths.php";

		$context["foo"] = "bar";
		//		$context["FACEBOOK_PIXEL_ID"] = $this->facebook_pixel_ids;
		//      $context["GOOGLE_ANALYTICS_ID"] = $this->google_analytics_id;
		$context["menu"] = Timber::get_menu();
		$context["paths"] = $paths;
		$context["version"] = $theme_version;
		$context["site"] = $this;

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

		$twig->addFilter(new Twig\TwigFilter("myfoo", [$this, "myfoo"]));

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

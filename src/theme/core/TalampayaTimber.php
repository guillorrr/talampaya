<?php

use Timber\Site;

class TalampayaTimber extends Site
{
	public function __construct()
	{
		add_filter("timber/context", [$this, "add_to_context"]);
		add_filter("timber/twig", [$this, "add_to_twig"]);
		add_filter("timber/twig/environment/options", [$this, "update_twig_environment_options"]);

		parent::__construct();
	}

	public function add_to_context(array $context): array
	{
		global $paths, $theme_version;

		$context["foo"] = "bar";
		$context["stuff"] = "I am a value set in your functions.php file";
		$context["notes"] = "These values are available everytime you call Timber::context();";
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
		// $twig->addExtension( new Twig\Extension\StringLoaderExtension() );

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

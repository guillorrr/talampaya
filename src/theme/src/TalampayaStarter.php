<?php

namespace App;

use Timber\Site;
use Timber\Timber;
use App\Core\ContextExtender\ContextManager;
use App\Core\Endpoints\EndpointsManager;
use App\Core\TwigExtender\EnvironmentOptions;
use App\Core\TwigExtender\TwigManager;
use App\Core\Pages\PagesManager;

class TalampayaStarter extends Site
{
	/**
	 * Gestor de extensiones del contexto
	 */
	private ContextManager $contextManager;

	/**
	 * Gestor de extensiones de Twig
	 */
	private TwigManager $twigManager;

	/**
	 * Gestor de opciones del entorno Twig
	 */
	private EnvironmentOptions $environmentOptions;

	/**
	 * Gestor de endpoints de la API
	 */
	private EndpointsManager $endpointsManager;

	/**
	 * Gestor de páginas personalizadas
	 */
	private PagesManager $pagesManager;

	public function __construct()
	{
		$this->contextManager = new ContextManager();
		$this->twigManager = new TwigManager();
		$this->environmentOptions = new EnvironmentOptions();
		$this->endpointsManager = new EndpointsManager();
		$this->pagesManager = new PagesManager();

		add_filter("timber/context", [$this, "addToContext"]);
		add_filter("timber/twig", [$this, "addToTwig"]);
		add_filter("timber/twig/environment/options", [$this, "updateTwigEnvironmentOptions"]);
		add_filter("timber/locations", [$this, "addLocations"]);

		$this->endpointsManager->registerAllEndpoints();
		$this->pagesManager->initPages();

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
		$context["version"] = defined("THEME_VERSION")
			? THEME_VERSION
			: wp_get_theme()->get("Version");
		$context["site"] = $this;
		$context["menu"] = Timber::get_menu();
		$context["links"]["home"] = home_url("/");

		return $this->contextManager->extendContext($context);
	}

	/**
	 * Agrega funcionalidades personalizadas a Twig
	 */
	public function addToTwig(\Twig\Environment $twig): \Twig\Environment
	{
		return $this->twigManager->extendTwig($twig);
	}

	/**
	 * Updates Twig environment options.
	 *
	 * @link https://twig.symfony.com/doc/2.x/api.html#environment-options
	 *
	 */
	public function updateTwigEnvironmentOptions(array $options): array
	{
		return $this->environmentOptions->updateOptions($options);
	}
}

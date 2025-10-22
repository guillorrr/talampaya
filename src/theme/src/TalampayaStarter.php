<?php

namespace App;

use Timber\Site;
use Timber\Timber;
use App\Core\ContextExtender\ContextManager;
use App\Core\Endpoints\EndpointsManager;
use App\Core\TwigExtender\EnvironmentOptions;
use App\Core\TwigExtender\TwigManager;
use App\Core\Pages\PagesManager;
use App\Features\ContentGenerator\ContentGeneratorManager;

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

	/**
	 * Gestor de generadores de contenido
	 */
	private ContentGeneratorManager $contentGeneratorManager;

	public function __construct()
	{
		add_action(
			"after_setup_theme",
			function () {
				$this->contextManager = new ContextManager();
				$this->twigManager = new TwigManager();
				$this->environmentOptions = new EnvironmentOptions();
				$this->endpointsManager = new EndpointsManager();
				$this->endpointsManager->registerAllEndpoints();
				$this->pagesManager = new PagesManager();
				$this->pagesManager->initPages();
				// Inicializar el ContentGeneratorManager con auto-registro desactivado
				$this->contentGeneratorManager = new ContentGeneratorManager(true, false);
				// Inicializar generadores
				$this->initContentGenerators();
			},
			999
		);

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

	/**
	 * Inicializa los generadores de contenido en un orden específico
	 *
	 * Este método permite registrar manualmente los generadores con diferentes prioridades
	 * en lugar de depender del registro automático
	 */
	protected function initContentGenerators(): void
	{
		// Obtener generadores disponibles
		$availableGenerators = $this->contentGeneratorManager->getAvailableGenerators();

		// Ejemplo de registro con orden específico (puedes personalizar según sea necesario)
		// Las prioridades más bajas se ejecutan primero

		// Generadores de taxonomías primero (prioridad 5)
		foreach ($availableGenerators as $className => $shortName) {
			if (str_contains($shortName, "Taxonomy")) {
				$this->contentGeneratorManager->registerGeneratorByClassName($className, 5);
			}
		}

		// Luego los generadores de post types (prioridad 10)
		foreach ($availableGenerators as $className => $shortName) {
			if (str_contains($shortName, "PostType")) {
				$this->contentGeneratorManager->registerGeneratorByClassName($className, 10);
			}
		}

		// Finalmente otros generadores (prioridad 15)
		foreach ($availableGenerators as $className => $shortName) {
			if (!str_contains($shortName, "Taxonomy") && !str_contains($shortName, "PostType")) {
				$this->contentGeneratorManager->registerGeneratorByClassName($className, 15);
			}
		}

		// También puedes registrar generadores individuales con prioridades específicas
		// $this->contentGeneratorManager->registerGeneratorByClassName('\\App\\Features\\ContentGenerator\\Generators\\MiGenerador', 20);
	}

	/**
	 * Método público para regenerar todo el contenido bajo demanda
	 *
	 * Útil cuando se ha eliminado contenido y se quiere volver a crearlo
	 * sin necesidad de reactivar el tema
	 *
	 * @param bool $force Si es verdadero, regenera incluso el contenido ya existente
	 */
	public function regenerateAllContent(bool $force = false): void
	{
		if (isset($this->contentGeneratorManager)) {
			$this->contentGeneratorManager->regenerateContent($force);
		}
	}
}

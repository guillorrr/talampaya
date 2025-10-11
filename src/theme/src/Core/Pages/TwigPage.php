<?php

namespace App\Core\Pages;

use Timber\Timber;

/**
 * Clase para crear páginas personalizadas usando plantillas Twig.
 *
 * Extiende la clase AbstractPage para proporcionar funcionalidad específica
 * para páginas que utilizan Timber/Twig para renderizar su contenido.
 */
class TwigPage extends AbstractPage
{
	/**
	 * Ruta a la plantilla Twig.
	 *
	 * @var string
	 */
	protected string $template;

	/**
	 * Contexto para la plantilla Twig.
	 *
	 * @var array
	 */
	protected array $context = [];

	/**
	 * Constructor.
	 *
	 * @param string      $pageTitle  Título de la página.
	 * @param string      $menuTitle  Título del menú.
	 * @param string      $menuSlug   Slug del menú.
	 * @param string      $template   Ruta a la plantilla Twig.
	 * @param string|null $parentSlug Slug del menú padre (opcional).
	 */
	public function __construct(
		string $pageTitle,
		string $menuTitle,
		string $menuSlug,
		string $template,
		?string $parentSlug = null
	) {
		parent::__construct($pageTitle, $menuTitle, $menuSlug, $parentSlug);

		$this->template = $template;
	}

	/**
	 * Establece el contexto para la plantilla Twig.
	 *
	 * @param array $context Datos de contexto.
	 * @return self
	 */
	public function setContext(array $context): self
	{
		$this->context = $context;
		return $this;
	}

	/**
	 * Agrega datos al contexto existente.
	 *
	 * @param array $contextData Datos a agregar al contexto.
	 * @return self
	 */
	public function addToContext(array $contextData): self
	{
		$this->context = array_merge($this->context, $contextData);
		return $this;
	}

	/**
	 * Renderiza la página usando Timber/Twig.
	 */
	public function render(): void
	{
		// Asegurarse de que Timber esté disponible
		if (!class_exists("Timber\Timber")) {
			echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin.</p></div>';
			return;
		}

		$defaultContext = [
			"page_title" => $this->pageTitle,
			"admin_url" => admin_url(),
		];

		$context = array_merge($defaultContext, $this->context);

		echo '<div class="wrap">';
		Timber::render($this->template, $context);
		echo "</div>";
	}
}

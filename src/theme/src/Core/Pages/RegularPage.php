<?php

namespace App\Core\Pages;

/**
 * Clase para crear páginas personalizadas regulares (no ACF).
 *
 * Extiende la clase AbstractPage para proporcionar funcionalidad para
 * páginas que utilizan la API de configuración estándar de WordPress.
 */
class RegularPage extends AbstractPage
{
	/**
	 * Callback para renderizar el contenido de la página.
	 *
	 * @var callable
	 */
	protected $renderCallback;

	/**
	 * Define si esta es una página de configuración que debe aparecer en "Ajustes".
	 *
	 * @var bool
	 */
	protected bool $isOptionsPage = false;

	/**
	 * Constructor.
	 *
	 * @param string      $pageTitle  Título de la página.
	 * @param string      $menuTitle  Título del menú.
	 * @param string      $menuSlug   Slug del menú.
	 * @param callable    $renderCallback Función para renderizar el contenido.
	 * @param string|null $parentSlug Slug del menú padre (opcional).
	 */
	public function __construct(
		string $pageTitle,
		string $menuTitle,
		string $menuSlug,
		callable $renderCallback,
		?string $parentSlug = null
	) {
		parent::__construct($pageTitle, $menuTitle, $menuSlug, $parentSlug);
		$this->renderCallback = $renderCallback;
	}

	/**
	 * Establece si esta página debe ser una página de opciones.
	 *
	 * @param bool $isOptionsPage Verdadero si es una página de opciones.
	 * @return self
	 */
	public function setAsOptionsPage(bool $isOptionsPage = true): self
	{
		$this->isOptionsPage = $isOptionsPage;
		return $this;
	}

	/**
	 * Registra la página en WordPress.
	 *
	 * Para páginas de opciones, utiliza add_options_page, de lo contrario
	 * usa el método estándar según el tipo de página.
	 */
	public function register(): void
	{
		if ($this->isOptionsPage) {
			$this->registerOptionsPage();
		} else {
			parent::register();
		}
	}

	/**
	 * Registra una página de opciones.
	 */
	protected function registerOptionsPage(): void
	{
		add_options_page($this->pageTitle, $this->menuTitle, $this->capability, $this->menuSlug, [
			$this,
			"render",
		]);
	}

	/**
	 * Renderiza el contenido de la página.
	 *
	 * Llama a la función de callback proporcionada en el constructor.
	 */
	public function render(): void
	{
		call_user_func($this->renderCallback);
	}
}

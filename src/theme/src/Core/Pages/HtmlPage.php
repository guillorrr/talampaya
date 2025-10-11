<?php

namespace App\Core\Pages;

/**
 * Clase para crear páginas personalizadas con contenido HTML.
 *
 * Extiende la clase AbstractPage para proporcionar funcionalidad específica
 * para páginas con contenido HTML personalizado.
 */
class HtmlPage extends AbstractPage
{
	/**
	 * Callback para renderizar el contenido de la página.
	 *
	 * @var callable
	 */
	protected $renderCallback;

	/**
	 * Constructor.
	 *
	 * @param string      $pageTitle      Título de la página.
	 * @param string      $menuTitle      Título del menú.
	 * @param string      $menuSlug       Slug del menú.
	 * @param callable    $renderCallback Callback para renderizar contenido.
	 * @param string|null $parentSlug     Slug del menú padre (opcional).
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
	 * Renderiza el contenido de la página.
	 */
	public function render(): void
	{
		echo '<div class="wrap">';
		echo "<h1>" . esc_html($this->pageTitle) . "</h1>";

		call_user_func($this->renderCallback);

		echo "</div>";
	}
}

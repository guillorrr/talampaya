<?php

namespace App\Core\Pages;

/**
 * Clase base abstracta para páginas personalizadas en el panel de administración.
 *
 * Esta clase proporciona la estructura básica para crear diferentes tipos de páginas
 * en el panel de administración de WordPress de manera modular y escalable.
 */
abstract class AbstractPage
{
	/**
	 * Título de la página que se mostrará en el encabezado.
	 *
	 * @var string
	 */
	protected string $pageTitle;

	/**
	 * Título que se mostrará en el menú.
	 *
	 * @var string
	 */
	protected string $menuTitle;

	/**
	 * Slug único para la página.
	 *
	 * @var string
	 */
	protected string $menuSlug;

	/**
	 * Capacidad requerida para acceder a esta página.
	 *
	 * @var string
	 */
	protected string $capability = "manage_options";

	/**
	 * Slug del menú padre si es una subpágina.
	 *
	 * @var string|null
	 */
	protected ?string $parentSlug = null;

	/**
	 * Posición en el menú.
	 *
	 * @var int|null
	 */
	protected ?int $position = null;

	/**
	 * Icono para el menú.
	 *
	 * @var string|null
	 */
	protected ?string $iconUrl = null;

	/**
	 * Constructor.
	 *
	 * @param string      $pageTitle  Título de la página.
	 * @param string      $menuTitle  Título del menú.
	 * @param string      $menuSlug   Slug del menú.
	 * @param string|null $parentSlug Slug del menú padre (opcional).
	 */
	public function __construct(
		string $pageTitle,
		string $menuTitle,
		string $menuSlug,
		?string $parentSlug = null
	) {
		$this->pageTitle = $pageTitle;
		$this->menuTitle = $menuTitle;
		$this->menuSlug = $menuSlug;
		$this->parentSlug = $parentSlug;
	}

	/**
	 * Registra la página en WordPress.
	 */
	public function register(): void
	{
		if ($this->parentSlug) {
			$this->registerSubPage();
		} else {
			$this->registerTopLevelPage();
		}
	}

	/**
	 * Registra una página de nivel superior.
	 */
	protected function registerTopLevelPage(): void
	{
		add_menu_page(
			$this->pageTitle,
			$this->menuTitle,
			$this->capability,
			$this->menuSlug,
			[$this, "render"],
			$this->iconUrl,
			$this->position
		);
	}

	/**
	 * Registra una subpágina.
	 */
	protected function registerSubPage(): void
	{
		add_submenu_page(
			$this->parentSlug,
			$this->pageTitle,
			$this->menuTitle,
			$this->capability,
			$this->menuSlug,
			[$this, "render"]
		);
	}

	/**
	 * Establece la capacidad requerida para acceder a la página.
	 *
	 * @param string $capability Capacidad requerida.
	 * @return self
	 */
	public function setCapability(string $capability): self
	{
		$this->capability = $capability;
		return $this;
	}

	/**
	 * Establece la posición en el menú.
	 *
	 * @param int $position Posición en el menú.
	 * @return self
	 */
	public function setPosition(int $position): self
	{
		$this->position = $position;
		return $this;
	}

	/**
	 * Establece el ícono del menú.
	 *
	 * @param string $iconUrl URL o nombre del ícono.
	 * @return self
	 */
	public function setIconUrl(string $iconUrl): self
	{
		$this->iconUrl = $iconUrl;
		return $this;
	}

	/**
	 * Obtiene el slug del menú.
	 *
	 * @return string
	 */
	public function getMenuSlug(): string
	{
		return $this->menuSlug;
	}

	/**
	 * Método abstracto para renderizar el contenido de la página.
	 */
	abstract public function render(): void;

	/**
	 * Método para inicialización adicional que puede ser sobrescrito.
	 */
	public function init(): void
	{
		// Las clases hijas pueden implementar acciones adicionales aquí
	}
}

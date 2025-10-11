<?php

namespace App\Core\Pages;

/**
 * Gestor de páginas personalizadas.
 *
 * Esta clase se encarga de registrar y administrar todas las páginas personalizadas
 * del panel de administración de WordPress de manera centralizada.
 */
class PagesManager
{
	/**
	 * Array de objetos de páginas personalizadas.
	 *
	 * @var AbstractPage[]
	 */
	private array $pages = [];

	/**
	 * Constructor.
	 *
	 * Configura el hook para registrar las páginas.
	 */
	public function __construct()
	{
		add_action("admin_menu", [$this, "registerPages"]);
	}

	/**
	 * Agrega una página al gestor.
	 *
	 * @param AbstractPage $page Objeto de página a agregar.
	 * @return self
	 */
	public function addPage(AbstractPage $page): self
	{
		$this->pages[] = $page;
		return $this;
	}

	/**
	 * Registra todas las páginas en WordPress.
	 */
	public function registerPages(): void
	{
		foreach ($this->pages as $page) {
			$page->register();
		}
	}

	/**
	 * Inicializa todas las páginas.
	 */
	public function initPages(): void
	{
		do_action("talampaya_register_admin_pages", $this);

		foreach ($this->pages as $page) {
			$page->init();
		}
	}

	/**
	 * Obtiene una página por su slug.
	 *
	 * @param string $slug Slug de la página.
	 * @return AbstractPage|null
	 */
	public function getPageBySlug(string $slug): ?AbstractPage
	{
		foreach ($this->pages as $page) {
			if ($page->getMenuSlug() === $slug) {
				return $page;
			}
		}

		return null;
	}

	/**
	 * Registra las páginas predeterminadas del tema.
	 *
	 * Este método puede ser extendido por temas derivados para agregar
	 * páginas personalizadas adicionales.
	 */
	public function registerDefaultPages(): void
	{
		// Registro de páginas predeterminadas...
		// Las implementaciones concretas se hacen en clases específicas
	}
}

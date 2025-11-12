<?php

namespace App\Core\Setup;

/**
 * Clase encargada de personalizar la interfaz de administración de WordPress
 */
class AdminCustomizer
{
	public function __construct()
	{
		// Personalización de la interfaz de administración
		add_action("wp_before_admin_bar_render", [$this, "customizeAdminBar"]);
		add_action("admin_menu", [$this, "customizeAdminMenu"]);
	}

	/**
	 * Elimina elementos del menú de administración
	 */
	public function customizeAdminMenu(): void
	{
		// Eliminar sección de comentarios
		remove_menu_page("edit-comments.php");
	}

	/**
	 * Personaliza la barra de administración
	 */
	public function customizeAdminBar(): void
	{
		global $wp_admin_bar;

		// Eliminar sección de comentarios
		$wp_admin_bar->remove_menu("comments");
	}
}

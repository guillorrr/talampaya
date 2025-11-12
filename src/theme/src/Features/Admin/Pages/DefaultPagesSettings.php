<?php

namespace App\Features\Admin\Pages;

use App\Core\Pages\AcfPage;
use App\Core\Pages\PagesManager;

/**
 * Clase para gestionar las páginas predeterminadas del tema en el panel de administración
 */
class DefaultPagesSettings
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		// No usar admin_init aquí ya que las páginas ACF tienen su propio sistema de inicialización
		add_action("talampaya_register_admin_pages", [$this, "registerPages"]);
	}

	/**
	 * Registra las páginas predeterminadas del tema en el gestor de páginas.
	 *
	 * @param PagesManager $pagesManager Instancia del gestor de páginas.
	 * @return void
	 */
	public function registerPages(PagesManager $pagesManager): void
	{
		if (!function_exists("acf_add_options_page")) {
			return;
		}

		$mainSettingsPage = new AcfPage(
			"Configuración General del Tema",
			__("Configuración del Tema", "talampaya"),
			"theme-general-settings",
			"group_theme_general_settings",
			__("Configuración General", "talampaya")
		);

		$mainSettingsPage->setCapability("edit_posts")->setRedirect(true);

		$pagesManager->addPage($mainSettingsPage);

		$headerPage = new AcfPage(
			"Configuración del Encabezado",
			__("Encabezado", "talampaya"),
			"acf-options-header",
			"group_options_page_header",
			__("Encabezado", "talampaya"),
			"theme-general-settings"
		);

		$headerPage->addField([
			"key" => "field_options_page_header_text",
			"name" => "options_page_header_text",
			"label" => __("Text", "talampaya"),
			"type" => "text",
		]);

		$pagesManager->addPage($headerPage);

		$footerPage = new AcfPage(
			"Configuración del Pie de Página",
			__("Pie de página", "talampaya"),
			"acf-options-footer",
			"group_options_page_footer",
			__("Pie de página", "talampaya"),
			"theme-general-settings"
		);

		$footerPage->addField([
			"key" => "field_options_page_footer_copyright",
			"name" => "options_page_footer_copyright",
			"label" => __("Copyright", "talampaya"),
			"type" => "text",
		]);

		$pagesManager->addPage($footerPage);
	}
}

// Inicializar la clase cuando el plugin esté cargado
// Esto se ejecuta después de que WordPress haya cargado, pero antes de admin_init
add_action("plugins_loaded", function () {
	new DefaultPagesSettings();
});

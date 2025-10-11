<?php

use App\Core\Pages\AcfPage;
use App\Core\Pages\PagesManager;

/**
 * Registra las páginas predeterminadas del tema en el gestor de páginas.
 *
 * @param PagesManager $pagesManager Instancia del gestor de páginas.
 * @return void
 */
function register_talampaya_default_pages(PagesManager $pagesManager): void
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

// Gancho para permitir que los temas hijos registren páginas personalizadas
add_action("talampaya_register_admin_pages", "register_talampaya_default_pages");

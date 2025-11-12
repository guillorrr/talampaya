<?php

namespace App\Features\Admin\Pages;

use App\Core\Pages\HtmlPage;
use App\Core\Pages\PagesManager;

/**
 * Clase para gestionar la página de ejemplo de depuración en el panel de administración
 */
class ExamplePageSettings
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		// No usar admin_init aquí ya que las páginas de ejemplo solo requieren el hook de registro
		add_action("talampaya_register_admin_pages", [$this, "registerPage"]);
	}

	/**
	 * Registra la página de ejemplo en el gestor de páginas.
	 *
	 * @param PagesManager $pagesManager Instancia del gestor de páginas.
	 * @return void
	 */
	public function registerPage(PagesManager $pagesManager): void
	{
		// Solo activar esta página en entorno de desarrollo
		if (!defined("WP_DEBUG") || !WP_DEBUG) {
			return;
		}

		// Crear una página de depuración con información del sistema
		$debugPage = new HtmlPage(
			"Información de Depuración",
			__("Depuración", "talampaya"),
			"talampaya-debug",
			[$this, "renderDebugPage"]
		);

		// Establecer permisos y posición
		$debugPage->setCapability("manage_options")->setIconUrl("dashicons-warning");

		// Registrar la página
		$pagesManager->addPage($debugPage);
	}

	/**
	 * Renderiza la página de depuración
	 */
	public function renderDebugPage(): void
	{
		echo '<div class="talampaya-debug-info">';
		echo "<h2>Información del sistema</h2>";

		echo "<h3>WordPress</h3>";
		echo "<ul>";
		echo "<li>Versión: " . get_bloginfo("version") . "</li>";
		echo "<li>Modo de depuración: " .
			(defined("WP_DEBUG") && WP_DEBUG ? "Activado" : "Desactivado") .
			"</li>";
		echo "</ul>";

		echo "<h3>PHP</h3>";
		echo "<ul>";
		echo "<li>Versión: " . phpversion() . "</li>";
		echo "<li>Límite de memoria: " . ini_get("memory_limit") . "</li>";
		echo "<li>Tiempo máximo de ejecución: " . ini_get("max_execution_time") . " segundos</li>";
		echo "</ul>";

		echo "<h3>Base de datos</h3>";
		global $wpdb;
		echo "<ul>";
		echo "<li>Versión MySQL: " . $wpdb->get_var("SELECT VERSION()") . "</li>";
		echo "</ul>";

		echo "<h3>Tema</h3>";
		$theme = wp_get_theme();
		echo "<ul>";
		echo "<li>Nombre: " . $theme->get("Name") . "</li>";
		echo "<li>Versión: " . $theme->get("Version") . "</li>";
		echo "</ul>";

		echo "<h3>Plugins Activos</h3>";
		echo "<ul>";
		$active_plugins = get_option("active_plugins");
		foreach ($active_plugins as $plugin) {
			$plugin_data = get_plugin_data(WP_PLUGIN_DIR . "/" . $plugin);
			echo "<li>" .
				esc_html($plugin_data["Name"]) .
				" - v" .
				esc_html($plugin_data["Version"]) .
				"</li>";
		}
		echo "</ul>";

		echo "</div>";
	}
}

// Inicializar la clase cuando el plugin esté cargado
// Esto se ejecuta después de que WordPress haya cargado, pero antes de admin_init
add_action("plugins_loaded", function () {
	new ExamplePageSettings();
});

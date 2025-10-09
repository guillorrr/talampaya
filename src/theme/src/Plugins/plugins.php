<?php

/**
 * Configuración de plugins requeridos usando TGM Plugin Activation
 *
 * Este archivo gestiona la notificación y activación de plugins
 * requeridos por el tema utilizando el sistema de plugins integrados
 *
 * @package Talampaya
 * @subpackage Plugins
 */

// Incluir la clase TGM_Plugin_Activation
require_once get_template_directory() . "/src/Plugins/class-tgm-plugin-activation.php";

// Registrar plugins requeridos
add_action("tgmpa_register", "talampaya_register_required_plugins");

/**
 * Registra los plugins requeridos para este tema
 *
 * Esta función obtiene la lista de plugins requeridos desde el
 * PluginManager y los registra para su activación
 */
function talampaya_register_required_plugins()
{
	// Inicializar el gestor de plugins
	$pluginManager = new \App\Core\Plugin\PluginManager();

	// Obtener plugins básicos requeridos para el tema
	$basePlugins = [
		[
			"name" => "Advanced Custom Fields PRO",
			"slug" => "advanced-custom-fields-pro",
			"required" => true,
			"force_activation" => true,
		],
	];

	// Plugins personalizados desde el PluginManager
	$customPlugins = $pluginManager->getRequiredPlugins();

	// Agregar plugins adicionales específicos del proyecto
	$projectPlugins = [
		// Ejemplo de plugin local
		[
			"name" => "ACF Custom Database Tables",
			"slug" => "acf-custom-database-tables",
			"source" => get_template_directory() . "/plugins/acf-custom-database-tables.zip",
			"required" => true,
			"version" => "1.1.4",
			"force_activation" => true,
		],
		// Agrega aquí más plugins específicos del proyecto
	];

	// Combinar todos los plugins
	$plugins = array_merge($basePlugins, $customPlugins, $projectPlugins);

	// Configuración de TGMPA
	$config = [
		"id" => "talampaya",
		"default_path" => "",
		"menu" => "tgmpa-install-plugins",
		"parent_slug" => "themes.php",
		"capability" => "edit_theme_options",
		"has_notices" => true,
		"dismissable" => true,
		"is_automatic" => true,
		"message" => __(
			"Este tema requiere los siguientes plugins para funcionar correctamente.",
			"talampaya"
		),
	];

	// Registrar plugins
	tgmpa($plugins, $config);
}

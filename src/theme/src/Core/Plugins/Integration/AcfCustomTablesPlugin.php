<?php

namespace App\Core\Plugins\Integration;

use App\Core\Plugins\AbstractPlugin;

/**
 * Plugin integrado para ACF Custom Database Tables
 *
 * Proporciona integración para el plugin ACF Custom Database Tables
 * que permite almacenar campos de ACF en tablas personalizadas de la base de datos.
 */
class AcfCustomTablesPlugin extends AbstractPlugin
{
	/**
	 * Nombre del plugin
	 */
	protected string $name = "acf-custom-tables";

	/**
	 * Verifica si el plugin debe activarse
	 *
	 * @return bool True si el plugin debe activarse
	 */
	public function shouldLoad(): bool
	{
		// Verificar si el plugin ACF Custom Database Tables está activo
		return class_exists("ACF_Custom_Database_Tables") ||
			class_exists("ACF_Custom_Database_Tables\Main");
	}

	/**
	 * Inicializa el plugin
	 */
	public function initialize(): void
	{
		// Personalizaciones específicas para ACF Custom Tables

		// Por ejemplo, filtrar tablas en el admin
		add_filter("acfcdt/admin/tables", [$this, "filterCustomTables"]);

		// O ajustar la configuración de rendimiento
		add_filter("acfcdt/settings/performance", [$this, "adjustPerformanceSettings"]);

		// Habilitar soporte para campos repetidores
		add_filter("acfcdt/settings/enable_repeater_field_support", "__return_true");

		// Deshabilitar almacenamiento de valores y claves en meta core de WP
		add_filter("acfcdt/settings/store_acf_values_in_core_meta", "__return_false");
		add_filter("acfcdt/settings/store_acf_keys_in_core_meta", "__return_false");
	}

	/**
	 * Personaliza las tablas mostradas en el admin
	 *
	 * @param array $tables Lista actual de tablas
	 * @return array Lista modificada de tablas
	 */
	public function filterCustomTables(array $tables): array
	{
		// Personaliza la lista de tablas si es necesario
		return $tables;
	}

	/**
	 * Ajusta configuración de rendimiento para ACF Custom Tables
	 *
	 * @param array $settings Configuración actual
	 * @return array Configuración modificada
	 */
	public function adjustPerformanceSettings(array $settings): array
	{
		// Ajustar configuración si es necesario
		// Por ejemplo, habilitar caché de consultas
		$settings["enable_query_cache"] = true;

		return $settings;
	}

	/**
	 * Obtiene la lista de plugins requeridos por este plugin
	 *
	 * @return array Lista de plugins requeridos
	 */
	public function getRequiredPlugins(): array
	{
		return [
			[
				"name" => "ACF Custom Database Tables",
				"slug" => "acf-custom-database-tables",
				"source" =>
					get_template_directory() .
					"/src/ThirdParty/TGM/zip/acf-custom-database-tables.zip",
				"required" => false,
				"version" => "1.1.4",
				"force_activation" => false,
			],
		];
	}
}

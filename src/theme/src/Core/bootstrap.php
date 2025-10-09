<?php

namespace App\Core;

use App\Register\RegisterManager;
use App\Utils\FileUtils;
use Timber\Timber;

/**
 * Clase Bootstrap para inicializar todas las configuraciones y componentes del tema
 */
class Bootstrap
{
	/**
	 * Inicializa el tema y todas sus dependencias
	 */
	public static function init(): void
	{
		// Configurar Timber
		self::setupTimber();

		// Cargar constantes
		self::loadConstants();

		// Inicializar optimizaciones de WordPress
		self::initializeWordPressOptimizer();

		// Inicializar configuración del tema
		self::initializeThemeSetup();

		// Cargar plugins personalizados
		self::loadPlugins();

		// Registrar Custom Post Types, Taxonomías, etc.
		self::registerCustomTypes();

		// Cargar características adicionales
		self::loadFeatures();

		// Cargar archivos adicionales
		self::loadAdditionalFiles();
	}

	/**
	 * Configura Timber
	 */
	private static function setupTimber(): void
	{
		Timber::$dirname = [get_template_directory() . "/views"];
	}

	/**
	 * Carga constantes de la aplicación
	 */
	private static function loadConstants(): void
	{
		require_once __DIR__ . "/Config/constants.php";
	}

	/**
	 * Inicializa el optimizador de WordPress
	 */
	private static function initializeWordPressOptimizer(): void
	{
		if (class_exists("App\\Core\\WordPressOptimizer")) {
			new WordPressOptimizer();
		}
	}

	/**
	 * Inicializa la configuración del tema
	 */
	private static function initializeThemeSetup(): void
	{
		// Inicializar configuraciones básicas
		if (class_exists("App\\Core\\Setup\\ThemeSupport")) {
			new Setup\ThemeSupport();
		}

		// Inicializar scripts y estilos
		if (class_exists("App\\Core\\Setup\\AssetsManager")) {
			new Setup\AssetsManager();
		}

		// Inicializar personalización del admin
		if (class_exists("App\\Core\\Setup\\AdminCustomizer")) {
			new Setup\AdminCustomizer();
		}
	}

	/**
	 * Carga plugins personalizados
	 */
	private static function loadPlugins(): void
	{
		$plugins_file = get_template_directory() . "/src/Plugins/plugins.php";
		if (file_exists($plugins_file)) {
			require_once $plugins_file;
		}
	}

	/**
	 * Registra tipos personalizados (CPT, Taxonomías, etc)
	 */
	private static function registerCustomTypes(): void
	{
		if (class_exists("App\\Register\\RegisterManager")) {
			RegisterManager::registerAll();
		}
	}

	/**
	 * Carga características adicionales como ACF
	 */
	private static function loadFeatures(): void
	{
		if (class_exists("ACF")) {
			$acf_file = get_template_directory() . "/src/Features/Acf/acf.php";
			if (file_exists($acf_file)) {
				require_once $acf_file;
			}
		}
	}

	/**
	 * Carga archivos adicionales como filtros y configuraciones
	 */
	private static function loadAdditionalFiles(): void
	{
		$directories = [get_template_directory() . "/hooks/Filters"];

		foreach ($directories as $dir) {
			$files = FileUtils::talampaya_directory_iterator($dir);
			if (!empty($files)) {
				foreach ($files as $file) {
					require_once $file;
				}
			}
		}
	}
}

// Iniciar el tema
Bootstrap::init();

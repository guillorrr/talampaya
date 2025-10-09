<?php

namespace App\Core;

use App\Core\Plugin\PluginManager;
use App\Core\Setup\WordPressOptimizer;
use App\Register\RegisterManager;
use App\ThirdParty\ThirdPartyManager;
use App\Utils\FileUtils;
use Timber\Timber;

/**
 * Clase Bootstrap para inicializar todas las configuraciones y componentes del tema
 */
class Bootstrap
{
	/**
	 * Gestor de plugins
	 */
	private static ?PluginManager $pluginManager = null;

	/**
	 * Inicializa el tema y todas sus dependencias
	 */
	public static function init(): void
	{
		// Configurar Timber
		self::setupTimber();

		// Cargar constantes
		self::loadConstants();

		// Inicializar configuración del tema
		self::initializeThemeSetup();

		// Inicializar gestor de plugins integrados
		self::initializePluginManager();

		// Inicializar integraciones de terceros (incluye TGM)
		self::initializeThirdParty();

		// Inicializar plugins integrados
		self::initializeIntegratedPlugins();

		// Registrar Custom Post Types, Taxonomías, etc.
		self::registerCustomTypes();

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
	 * Inicializa la configuración del tema
	 */
	private static function initializeThemeSetup(): void
	{
		if (class_exists("App\\Core\\Setup\\WordPressOptimizer")) {
			new WordPressOptimizer();
		}

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
	 * Inicializa el gestor de plugins
	 */
	private static function initializePluginManager(): void
	{
		self::$pluginManager = new PluginManager();
	}

	/**
	 * Inicializa integraciones de terceros
	 */
	private static function initializeThirdParty(): void
	{
		if (class_exists("App\\ThirdParty\\ThirdPartyManager")) {
			ThirdPartyManager::initialize();
		}
	}

	/**
	 * Inicializa los plugins integrados
	 */
	private static function initializeIntegratedPlugins(): void
	{
		if (self::$pluginManager) {
			self::$pluginManager->initializePlugins();
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

	/**
	 * Obtiene el gestor de plugins
	 *
	 * @return PluginManager|null El gestor de plugins
	 */
	public static function getPluginManager(): ?PluginManager
	{
		return self::$pluginManager;
	}
}

// Iniciar el tema
Bootstrap::init();

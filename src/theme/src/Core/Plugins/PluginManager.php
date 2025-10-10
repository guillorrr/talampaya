<?php

namespace App\Core\Plugins;

use App\Utils\FileUtils;
use App\Core\Plugins\Integration\AcfPlugin;

/**
 * Gestor de plugins integrados del tema
 */
class PluginManager
{
	/**
	 * Plugins registrados
	 *
	 * @var PluginInterface[]
	 */
	private array $plugins = [];

	/**
	 * Lista de plugins requeridos para TGM Plugin Activation
	 *
	 * @var array
	 */
	private array $requiredPlugins = [];

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->loadPlugins();
	}

	/**
	 * Carga y registra todos los plugins disponibles
	 */
	private function loadPlugins(): void
	{
		// Cargar plugins principales del core
		$this->loadCorePlugins();

		// Cargar plugins personalizados
		$this->loadIntegrationPlugins();
	}

	/**
	 * Carga los plugins principales del core
	 */
	private function loadCorePlugins(): void
	{
		// Aquí registrar plugins específicos que siempre deben estar disponibles
		$this->registerPlugin(new AcfPlugin());
	}

	/**
	 * Carga plugins de integración desde el directorio correspondiente
	 */
	private function loadIntegrationPlugins(): void
	{
		if (is_dir(PLUGINS_INTEGRATION_PATH)) {
			$pluginFiles = FileUtils::talampaya_directory_iterator(PLUGINS_INTEGRATION_PATH);

			foreach ($pluginFiles as $file) {
				// Ignorar archivos README y otros archivos que no son clases PHP
				$extension = pathinfo($file, PATHINFO_EXTENSION);
				if ($extension !== "php") {
					continue;
				}

				$className = pathinfo($file, PATHINFO_FILENAME);
				$fullyQualifiedClassName = "\\App\\Core\\Plugins\\Integration\\{$className}";

				// Evitar cargar plugins que ya se han registrado manualmente en loadCorePlugins()
				$skipPlugins = ["AcfPlugin"];

				if (
					!in_array($className, $skipPlugins) &&
					class_exists($fullyQualifiedClassName) &&
					is_subclass_of($fullyQualifiedClassName, PluginInterface::class)
				) {
					$this->registerPlugin(new $fullyQualifiedClassName());
				}
			}
		}
	}

	/**
	 * Registra un plugin
	 *
	 * @param PluginInterface $plugin Plugin a registrar
	 */
	public function registerPlugin(PluginInterface $plugin): void
	{
		$this->plugins[$plugin->getName()] = $plugin;

		// Agregar plugins requeridos a la lista para TGM
		$requiredPlugins = $plugin->getRequiredPlugins();
		if (!empty($requiredPlugins)) {
			$this->requiredPlugins = array_merge($this->requiredPlugins, $requiredPlugins);
		}
	}

	/**
	 * Inicializa todos los plugins activos
	 */
	public function initializePlugins(): void
	{
		foreach ($this->plugins as $plugin) {
			if ($plugin->shouldLoad()) {
				$plugin->initialize();
			}
		}
	}

	/**
	 * Obtiene un plugin por su nombre
	 *
	 * @param string $name Nombre del plugin
	 * @return PluginInterface|null El plugin o null si no existe
	 */
	public function getPlugin(string $name): ?PluginInterface
	{
		return $this->plugins[$name] ?? null;
	}

	/**
	 * Verifica si un plugin está registrado y activo
	 *
	 * @param string $name Nombre del plugin
	 * @return bool True si el plugin está registrado y activo
	 */
	public function isPluginActive(string $name): bool
	{
		return isset($this->plugins[$name]) && $this->plugins[$name]->shouldLoad();
	}

	/**
	 * Obtiene la lista de plugins requeridos para TGM Plugin Activation
	 *
	 * @return array Lista de plugins requeridos
	 */
	public function getRequiredPlugins(): array
	{
		return $this->requiredPlugins;
	}
}

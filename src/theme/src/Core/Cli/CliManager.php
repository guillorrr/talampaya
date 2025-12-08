<?php

declare(strict_types=1);

namespace App\Core\Cli;

use DirectoryIterator;
use Exception;

/**
 * Manager para auto-descubrir y registrar comandos WP-CLI
 *
 * Las clases de comandos deben:
 * 1. Estar en el namespace App\Core\Cli
 * 2. Tener un método estático register() que registre el comando
 * 3. No comenzar con "Abstract" ni ser el propio CliManager
 */
class CliManager
{
	private static array $registeredCommands = [];

	/**
	 * Registra todos los comandos CLI disponibles
	 */
	public static function registerAll(): void
	{
		// Solo ejecutar si WP-CLI está activo
		if (!defined("WP_CLI") || !WP_CLI) {
			return;
		}

		self::discoverAndRegisterCommands();
	}

	/**
	 * Descubre y registra comandos en el directorio Cli
	 */
	private static function discoverAndRegisterCommands(): void
	{
		$baseDir = __DIR__;
		$baseNamespace = "App\\Core\\Cli\\";

		try {
			if (!is_dir($baseDir)) {
				return;
			}

			foreach (new DirectoryIterator($baseDir) as $file) {
				if ($file->isFile() && $file->getExtension() === "php") {
					$className = pathinfo($file->getFilename(), PATHINFO_FILENAME);

					// Evitar registrar clases abstractas y el propio manager
					if (strpos($className, "Abstract") === 0 || $className === "CliManager") {
						continue;
					}

					// Evitar archivos con prefijo _
					if (strpos($file->getFilename(), "_") === 0) {
						continue;
					}

					$fullClassName = $baseNamespace . $className;

					if (
						class_exists($fullClassName) &&
						!isset(self::$registeredCommands[$fullClassName]) &&
						method_exists($fullClassName, "register")
					) {
						$fullClassName::register();
						self::$registeredCommands[$fullClassName] = true;
					}
				}
			}
		} catch (Exception $e) {
			if (defined("WP_CLI") && WP_CLI) {
				\WP_CLI::warning("Error registering CLI commands: " . $e->getMessage());
			}
		}
	}

	/**
	 * Obtiene los comandos registrados
	 */
	public static function getRegisteredCommands(): array
	{
		return self::$registeredCommands;
	}
}

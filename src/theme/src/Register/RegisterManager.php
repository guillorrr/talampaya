<?php

namespace App\Register;

use DirectoryIterator;
use Exception;

class RegisterManager
{
	private static array $registeredClasses = [];

	public static function registerAll(): void
	{
		self::registerPostTypes();
		self::registerTaxonomies();
		self::registerMenus();
		self::registerSidebars();
	}

	public static function registerPostTypes(): void
	{
		self::registerClassesInNamespace("PostType");
	}

	public static function registerTaxonomies(): void
	{
		self::registerClassesInNamespace("Taxonomy");
	}

	public static function registerMenus(): void
	{
		self::registerClassesInNamespace("Menu");
	}

	public static function registerSidebars(): void
	{
		self::registerClassesInNamespace("Sidebar");
	}

	private static function registerClassesInNamespace(string $namespace): void
	{
		$baseDir = __DIR__ . "/" . $namespace;
		$baseNamespace = "App\\Register\\" . $namespace . "\\";

		try {
			if (!is_dir($baseDir)) {
				return;
			}

			foreach (new DirectoryIterator($baseDir) as $file) {
				if ($file->isFile() && $file->getExtension() === "php") {
					$className = pathinfo($file->getFilename(), PATHINFO_FILENAME);

					// Evitar registrar clases abstractas
					if (strpos($className, "Abstract") === 0) {
						continue;
					}

					$fullClassName = $baseNamespace . $className;

					if (
						class_exists($fullClassName) &&
						!isset(self::$registeredClasses[$fullClassName])
					) {
						new $fullClassName();
						self::$registeredClasses[$fullClassName] = true;
					}
				}
			}
		} catch (Exception $e) {
			error_log(
				"Error al registrar clases en el namespace " . $namespace . ": " . $e->getMessage()
			);
		}
	}
}

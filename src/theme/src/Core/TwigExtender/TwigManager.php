<?php

namespace App\Core\TwigExtender;

use App\Utils\FileUtils;
use Twig\Environment;

/**
 * Clase que gestiona todas las extensiones de Twig
 */
class TwigManager
{
	/**
	 * Extensores de Twig registrados
	 *
	 * @var TwigExtenderInterface[]
	 */
	private array $extenders = [];

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->registerCoreExtenders();
		$this->registerCustomExtenders();
	}

	/**
	 * Registra los extensores de Twig principales
	 */
	private function registerCoreExtenders(): void
	{
		$this->addExtender(new CoreExtensions());
		$this->addExtender(new CustomFilters());
	}

	/**
	 * Registra extensores de Twig personalizados
	 */
	private function registerCustomExtenders(): void
	{
		$extendersDir = defined("THEME_DIR")
			? THEME_DIR . "/src/Core/TwigExtender/Custom"
			: get_template_directory() . "/src/Core/TwigExtender/Custom";

		if (is_dir($extendersDir)) {
			$files = FileUtils::talampaya_directory_iterator($extendersDir);

			foreach ($files as $file) {
				require_once $file;

				$className = pathinfo($file, PATHINFO_FILENAME);
				$fullyQualifiedClassName = "\\App\\Core\\TwigExtender\\Custom\\$className";

				if (
					class_exists($fullyQualifiedClassName) &&
					is_subclass_of($fullyQualifiedClassName, TwigExtenderInterface::class)
				) {
					$this->addExtender(new $fullyQualifiedClassName());
				}
			}
		}
	}

	/**
	 * Añade un extensor de Twig
	 *
	 * @param TwigExtenderInterface $extender Extensor de Twig
	 */
	public function addExtender(TwigExtenderInterface $extender): void
	{
		$this->extenders[] = $extender;
	}

	/**
	 * Extiende el entorno Twig usando todos los extensores registrados
	 *
	 * @param Environment $twig Entorno Twig actual
	 * @return Environment Entorno Twig extendido
	 */
	public function extendTwig(Environment $twig): Environment
	{
		foreach ($this->extenders as $extender) {
			$twig = $extender->extendTwig($twig);
		}

		return $twig;
	}
}

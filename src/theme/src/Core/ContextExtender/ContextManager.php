<?php

namespace App\Core\ContextExtender;

use App\Utils\FileUtils;

/**
 * Clase que gestiona todas las extensiones del contexto de Timber
 */
class ContextManager
{
	/**
	 * Extensores de contexto registrados
	 *
	 * @var ContextExtenderInterface[]
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
	 * Registra los extensores de contexto principales
	 */
	private function registerCoreExtenders(): void
	{
		$this->addExtender(new PathsContext());
		$this->addExtender(new AnalyticsContext());
	}

	/**
	 * Registra extensores de contexto personalizados
	 */
	private function registerCustomExtenders(): void
	{
		$extendersDir = defined("THEME_DIR")
			? THEME_DIR . "/src/Core/ContextExtender/Custom"
			: get_template_directory() . "/src/Core/ContextExtender/Custom";

		if (is_dir($extendersDir)) {
			$files = FileUtils::talampaya_directory_iterator($extendersDir);

			foreach ($files as $file) {
				require_once $file;

				$className = pathinfo($file, PATHINFO_FILENAME);
				$fullyQualifiedClassName = "\\App\\Core\\ContextExtender\\Custom\\$className";

				if (
					class_exists($fullyQualifiedClassName) &&
					is_subclass_of($fullyQualifiedClassName, ContextExtenderInterface::class)
				) {
					$this->addExtender(new $fullyQualifiedClassName());
				}
			}
		}
	}

	/**
	 * Añade un extensor de contexto
	 *
	 * @param ContextExtenderInterface $extender Extensor de contexto
	 */
	public function addExtender(ContextExtenderInterface $extender): void
	{
		$this->extenders[] = $extender;
	}

	/**
	 * Extiende el contexto de Timber usando todos los extensores registrados
	 *
	 * @param array $context Contexto actual de Timber
	 * @return array Contexto extendido
	 */
	public function extendContext(array $context): array
	{
		foreach ($this->extenders as $extender) {
			$context = $extender->extendContext($context);
		}

		return $context;
	}
}

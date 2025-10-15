<?php

namespace App\Features\ContentGenerator;

use App\Utils\FileUtils;

/**
 * Administrador para coordinar los distintos generadores de contenido
 */
class ContentGeneratorManager
{
	/**
	 * Lista de generadores de contenido registrados
	 * @var AbstractContentGenerator[]
	 */
	protected array $generators = [];

	/**
	 * Instancias de clases de generadores de contenido
	 *
	 * @var object[]
	 */
	private array $generatorClasses = [];

	/**
	 * Si el generador se ejecutará al activar el tema
	 * @var bool
	 */
	protected bool $run_on_theme_activation;

	/**
	 * Constructor
	 *
	 * @param bool $run_on_theme_activation Si es verdadero, los generadores se ejecutarán cuando se active el tema
	 */
	public function __construct(bool $run_on_theme_activation = true)
	{
		$this->run_on_theme_activation = $run_on_theme_activation;

		if ($run_on_theme_activation) {
			add_action("after_switch_theme", [$this, "generateAllContent"]);
		}

		// Añadir acción para inicializar los generadores cuando se cargue el tema
		add_action("after_setup_theme", [$this, "initGenerators"], 999);
	}

	/**
	 * Registra un nuevo generador de contenido
	 *
	 * @param AbstractContentGenerator $generator El generador a registrar
	 * @param int $priority Prioridad de ejecución (menor número = mayor prioridad)
	 * @return $this
	 */
	public function register(AbstractContentGenerator $generator, int $priority = 10): self
	{
		$this->generators[$priority][] = $generator;
		return $this;
	}

	/**
	 * Genera todo el contenido registrado
	 *
	 * @param bool $force Si es verdadero, regenera el contenido incluso si ya existe
	 * @return void
	 */
	public function generateAllContent(bool $force = false): void
	{
		if (empty($this->generators)) {
			return;
		}

		// Ordenamos los generadores por prioridad
		ksort($this->generators);

		// Ejecutamos cada generador
		foreach ($this->generators as $priority_group) {
			foreach ($priority_group as $generator) {
				$generator->generate($force);
			}
		}
	}

	/**
	 * Expone una función pública para forzar la regeneración de todo el contenido
	 *
	 * @return void
	 */
	public function forceRegenerateAll(): void
	{
		$this->generateAllContent(true);
	}

	/**
	 * Inicializa todos los generadores de contenido.
	 *
	 * Este método busca y registra automáticamente todos los generadores de contenido
	 * disponibles en el directorio de generadores de contenido.
	 */
	public function initGenerators(): void
	{
		// Cargar clases de generadores en el directorio CONTENT_GENERATORS_PATH
		$this->registerCustomGenerators();

		// Permitir que otras clases registren sus generadores
		do_action("talampaya_register_content_generators", $this);
	}

	/**
	 * Registra generadores de contenido a partir de clases en el directorio de generadores.
	 *
	 * Busca todas las clases en el directorio de generadores de contenido
	 * y las instancia automáticamente si extienden AbstractContentGenerator.
	 */
	protected function registerCustomGenerators(): void
	{
		if (!defined("CONTENT_GENERATORS_PATH") || !is_dir(CONTENT_GENERATORS_PATH)) {
			return;
		}

		$files = FileUtils::talampaya_directory_iterator(CONTENT_GENERATORS_PATH);

		foreach ($files as $file) {
			$className = pathinfo($file, PATHINFO_FILENAME);
			if ($className === "AbstractContentGenerator") {
				continue;
			}

			// Determinar el namespace basado en la estructura de carpetas
			$relativePath = str_replace(
				get_template_directory() . "/src/",
				"",
				CONTENT_GENERATORS_PATH
			);
			$namespaceParts = array_map("ucfirst", explode("/", $relativePath));
			$namespace = "\\App\\" . implode("\\", $namespaceParts);

			$fullyQualifiedClassName = $namespace . "\\$className";

			if (
				class_exists($fullyQualifiedClassName) &&
				is_subclass_of($fullyQualifiedClassName, AbstractContentGenerator::class)
			) {
				$generator = new $fullyQualifiedClassName();
				$this->generatorClasses[] = $generator;
				$this->register($generator);
			}
		}
	}
}

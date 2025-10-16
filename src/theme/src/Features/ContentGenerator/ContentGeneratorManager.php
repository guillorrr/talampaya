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

		// Añadir acción para inicializar los generadores cuando se cargue el tema
		// Usar prioridad 800 para que se ejecute antes que otros procesos que dependan de estos datos
		add_action("after_setup_theme", [$this, "initGenerators"], 800);

		// Registrar el hook para la activación del tema
		if ($run_on_theme_activation) {
			// Usar una prioridad alta (30) para asegurarse de que se ejecuta después de otros procesos de inicialización
			add_action("after_switch_theme", [$this, "runGenerators"], 30);
		}
	}

	/**
	 * Método específico para ejecutar durante la activación del tema
	 *
	 * Este método asegura que los generadores se ejecuten correctamente
	 * después de haber sido inicializados
	 *
	 * @return void
	 */
	public function runGenerators(): void
	{
		// Primero nos aseguramos de que los generadores estén inicializados
		$this->initGenerators();

		// Luego ejecutamos la generación de contenido
		error_log("ContentGeneratorManager: Ejecutando generadores durante la activación del tema");
		$this->generateAllContent();
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
			error_log("ContentGeneratorManager: No hay generadores registrados para ejecutar");
			return;
		}

		// Ordenamos los generadores por prioridad
		ksort($this->generators);

		error_log(
			"ContentGeneratorManager: Ejecutando " .
				count($this->generators) .
				" grupos de generadores"
		);

		// Ejecutamos cada generador
		foreach ($this->generators as $priority => $priority_group) {
			error_log(
				"ContentGeneratorManager: Ejecutando grupo de prioridad $priority con " .
					count($priority_group) .
					" generadores"
			);

			foreach ($priority_group as $generator) {
				$class_name = get_class($generator);
				error_log("ContentGeneratorManager: Ejecutando generador $class_name");

				try {
					$generator->generate($force);
					error_log(
						"ContentGeneratorManager: Generador $class_name ejecutado correctamente"
					);
				} catch (\Exception $e) {
					error_log(
						"ContentGeneratorManager: Error al ejecutar el generador $class_name: " .
							$e->getMessage()
					);
				}
			}
		}

		error_log("ContentGeneratorManager: Generación de contenido completada");
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
		// Ruta predeterminada para los generadores
		$generators_path = get_template_directory() . "/src/Features/ContentGenerator/Generators";

		// Si está definida la constante, usarla en su lugar
		if (defined("CONTENT_GENERATORS_PATH") && is_dir(CONTENT_GENERATORS_PATH)) {
			$generators_path = CONTENT_GENERATORS_PATH;
		}

		// Verificar que el directorio existe
		if (!is_dir($generators_path)) {
			error_log(
				"ContentGeneratorManager: El directorio de generadores no existe: $generators_path"
			);
			return;
		}

		// Obtener los archivos del directorio
		$files = [];
		if (function_exists("FileUtils::talampaya_directory_iterator")) {
			$files = FileUtils::talampaya_directory_iterator($generators_path);
		} else {
			// Fallback si el método no existe
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($generators_path, \FilesystemIterator::SKIP_DOTS)
			);

			foreach ($iterator as $file) {
				if ($file->isFile() && $file->getExtension() === "php") {
					$files[] = $file->getPathname();
				}
			}
		}

		// Procesamos cada archivo
		foreach ($files as $file) {
			$className = pathinfo($file, PATHINFO_FILENAME);

			// Ignorar archivos especiales
			if ($className === "AbstractContentGenerator" || $className === "README") {
				continue;
			}

			// Determinar el namespace basado en la estructura de carpetas
			$relativePath = str_replace(get_template_directory() . "/src/", "", dirname($file));
			$namespaceParts = array_map("ucfirst", explode("/", $relativePath));
			$namespace = "\\App\\" . implode("\\", $namespaceParts);

			$fullyQualifiedClassName = $namespace . "\\" . $className;

			// Verificamos si la clase existe y hereda de AbstractContentGenerator
			if (
				class_exists($fullyQualifiedClassName) &&
				is_subclass_of($fullyQualifiedClassName, AbstractContentGenerator::class)
			) {
				error_log(
					"ContentGeneratorManager: Registrando generador: $fullyQualifiedClassName"
				);
				try {
					$generator = new $fullyQualifiedClassName();
					$this->generatorClasses[] = $generator;
					$this->register($generator);
				} catch (\Exception $e) {
					error_log(
						"ContentGeneratorManager: Error al instanciar $fullyQualifiedClassName: " .
							$e->getMessage()
					);
				}
			} else {
				// Si la clase existe pero no hereda de AbstractContentGenerator, registrarlo en el log
				if (class_exists($fullyQualifiedClassName)) {
					error_log(
						"ContentGeneratorManager: La clase $fullyQualifiedClassName no hereda de AbstractContentGenerator"
					);
				}
			}
		}
	}
}

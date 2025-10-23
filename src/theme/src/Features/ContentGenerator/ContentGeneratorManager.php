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
	 * Si el generador se ejecutará al activar el tema
	 * @var bool
	 */
	protected bool $run_on_theme_activation;

	/**
	 * Si se deben registrar automáticamente los generadores
	 * @var bool
	 */
	protected bool $auto_register_generators;

	/**
	 * Si los generadores ya fueron inicializados
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Constructor
	 *
	 * @param bool $run_on_theme_activation Si es verdadero, los generadores se ejecutarán cuando se active el tema
	 * @param bool $auto_register_generators Si es verdadero, se registrarán automáticamente los generadores encontrados
	 */
	public function __construct(
		bool $run_on_theme_activation = true,
		bool $auto_register_generators = true
	) {
		$this->run_on_theme_activation = $run_on_theme_activation;
		$this->auto_register_generators = $auto_register_generators;

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

		// Validar dependencias antes de ejecutar
		if (!$this->validateDependencies()) {
			error_log("ContentGeneratorManager: Error en validación de dependencias");
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
	 * Método público para volver a ejecutar los generadores después de que el tema ya está activo
	 * Útil cuando se ha eliminado contenido y se necesita regenerarlo
	 *
	 * @param bool $force Si es verdadero, regenera el contenido incluso si ya existe
	 * @return void
	 */
	public function regenerateContent(bool $force = false): void
	{
		// Asegurarnos de que los generadores estén inicializados
		$this->initGenerators();

		error_log("ContentGeneratorManager: Regenerando contenido bajo demanda");
		$this->generateAllContent($force);
	}

	/**
	 * Inicializa todos los generadores de contenido.
	 *
	 * Este método busca y registra automáticamente todos los generadores de contenido
	 * disponibles en el directorio de generadores de contenido si auto_register_generators es true,
	 * o los registra manualmente con prioridades específicas si auto_register_generators es false.
	 */
	public function initGenerators(): void
	{
		// Evitar doble inicialización
		if ($this->initialized) {
			return;
		}

		// Cargar clases de generadores en el directorio CONTENT_GENERATORS_PATH
		// solo si el auto registro está activado
		if ($this->auto_register_generators) {
			$this->registerCustomGenerators();
		} else {
			// Si el auto-registro está desactivado, registrar generadores con prioridades específicas
			$this->initContentGeneratorsWithPriority();
		}

		// Permitir que otras clases registren sus generadores
		do_action("talampaya_register_content_generators", $this);

		// Marcar como inicializado
		$this->initialized = true;
	}

	/**
	 * Inicializa los generadores de contenido consultando sus prioridades declaradas
	 *
	 * Este método instancia cada generador y consulta su prioridad mediante getPriority()
	 * eliminando la dependencia de convenciones de nombres.
	 *
	 * @return void
	 */
	public function initContentGeneratorsWithPriority(): void
	{
		// Obtener generadores disponibles
		$availableGenerators = $this->getAvailableGenerators();

		// Instanciar y registrar cada generador con su prioridad declarada
		foreach ($availableGenerators as $className => $shortName) {
			try {
				$generator = new $className();
				$priority = $generator->getPriority();
				error_log(
					"ContentGeneratorManager: Registrando generador: $className con prioridad $priority"
				);
				$this->register($generator, $priority);
			} catch (\Exception $e) {
				error_log(
					"ContentGeneratorManager: Error al instanciar $className: " . $e->getMessage()
				);
			}
		}
	}

	/**
	 * Obtiene la lista de generadores disponibles en el directorio de generadores
	 * sin instanciarlos ni registrarlos automáticamente.
	 *
	 * @return array Arreglo asociativo de clases con sus nombres completos como clave
	 */
	public function getAvailableGenerators(): array
	{
		return $this->scanGeneratorClasses();
	}

	/**
	 * Registra un generador por su nombre de clase completamente cualificado
	 *
	 * @param string $fullyQualifiedClassName Nombre de clase completamente cualificado
	 * @param int $priority Prioridad de ejecución (menor número = mayor prioridad)
	 * @return bool Verdadero si se registró correctamente, falso en caso contrario
	 */
	public function registerGeneratorByClassName(
		string $fullyQualifiedClassName,
		int $priority = 10
	): bool {
		// Verificamos si la clase existe y hereda de AbstractContentGenerator
		if (
			class_exists($fullyQualifiedClassName) &&
			is_subclass_of($fullyQualifiedClassName, AbstractContentGenerator::class)
		) {
			error_log(
				"ContentGeneratorManager: Registrando generador: $fullyQualifiedClassName con prioridad $priority"
			);
			try {
				$generator = new $fullyQualifiedClassName();
				$this->register($generator, $priority);
				return true;
			} catch (\Exception $e) {
				error_log(
					"ContentGeneratorManager: Error al instanciar $fullyQualifiedClassName: " .
						$e->getMessage()
				);
				return false;
			}
		} else {
			// Si la clase existe pero no hereda de AbstractContentGenerator, registrarlo en el log
			if (class_exists($fullyQualifiedClassName)) {
				error_log(
					"ContentGeneratorManager: La clase $fullyQualifiedClassName no hereda de AbstractContentGenerator"
				);
			}
			return false;
		}
	}

	/**
	 * Registra generadores de contenido a partir de clases en el directorio de generadores.
	 *
	 * Busca todas las clases en el directorio de generadores de contenido
	 * y las instancia automáticamente si extienden AbstractContentGenerator.
	 */
	protected function registerCustomGenerators(): void
	{
		$availableGenerators = $this->scanGeneratorClasses();

		// Registrar cada generador encontrado
		foreach ($availableGenerators as $fullyQualifiedClassName => $shortName) {
			error_log("ContentGeneratorManager: Registrando generador: $fullyQualifiedClassName");
			try {
				$generator = new $fullyQualifiedClassName();
				$this->register($generator);
			} catch (\Exception $e) {
				error_log(
					"ContentGeneratorManager: Error al instanciar $fullyQualifiedClassName: " .
						$e->getMessage()
				);
			}
		}
	}

	/**
	 * Valida que todas las dependencias estén satisfechas y no haya ciclos
	 *
	 * @return bool true si todas las dependencias son válidas
	 */
	private function validateDependencies(): bool
	{
		$allGenerators = $this->getAllRegisteredGenerators();

		// Verificar que todas las dependencias declaradas existan
		foreach ($allGenerators as $generator) {
			$className = get_class($generator);
			$dependencies = $generator->getDependencies();

			foreach ($dependencies as $dependencyClass) {
				if (!$this->isGeneratorRegistered($dependencyClass, $allGenerators)) {
					error_log(
						"ContentGeneratorManager: El generador $className depende de $dependencyClass que no está registrado"
					);
					return false;
				}
			}
		}

		// Verificar que no haya dependencias circulares
		if (!$this->checkCircularDependencies($allGenerators)) {
			error_log("ContentGeneratorManager: Se detectaron dependencias circulares");
			return false;
		}

		// Verificar que las prioridades respeten las dependencias
		$this->verifyDependencyOrder($allGenerators);

		return true;
	}

	/**
	 * Obtiene lista plana de todos los generadores registrados
	 *
	 * @return array<AbstractContentGenerator>
	 */
	private function getAllRegisteredGenerators(): array
	{
		$allGenerators = [];
		foreach ($this->generators as $priority_group) {
			foreach ($priority_group as $generator) {
				$allGenerators[] = $generator;
			}
		}
		return $allGenerators;
	}

	/**
	 * Verifica si un generador está registrado
	 *
	 * @param string $className Nombre de clase a buscar
	 * @param array $generators Lista de generadores
	 * @return bool
	 */
	private function isGeneratorRegistered(string $className, array $generators): bool
	{
		foreach ($generators as $generator) {
			if (get_class($generator) === $className) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Detecta dependencias circulares usando DFS
	 *
	 * @param array $generators Lista de generadores
	 * @return bool true si no hay ciclos
	 */
	private function checkCircularDependencies(array $generators): bool
	{
		$visited = [];
		$recursionStack = [];

		// Crear mapa de clase => generador para búsqueda rápida
		$generatorMap = [];
		foreach ($generators as $generator) {
			$generatorMap[get_class($generator)] = $generator;
		}

		// DFS para detectar ciclos
		foreach ($generators as $generator) {
			$className = get_class($generator);
			if (!isset($visited[$className])) {
				if ($this->hasCycleDFS($className, $generatorMap, $visited, $recursionStack)) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * DFS helper para detectar ciclos
	 *
	 * @param string $className Clase actual
	 * @param array $generatorMap Mapa de generadores
	 * @param array &$visited Nodos visitados
	 * @param array &$recursionStack Stack de recursión
	 * @return bool true si hay ciclo
	 */
	private function hasCycleDFS(
		string $className,
		array $generatorMap,
		array &$visited,
		array &$recursionStack
	): bool {
		$visited[$className] = true;
		$recursionStack[$className] = true;

		// Explorar dependencias
		if (isset($generatorMap[$className])) {
			$dependencies = $generatorMap[$className]->getDependencies();

			foreach ($dependencies as $dependencyClass) {
				// Si no visitado, explorar recursivamente
				if (!isset($visited[$dependencyClass])) {
					if (
						$this->hasCycleDFS(
							$dependencyClass,
							$generatorMap,
							$visited,
							$recursionStack
						)
					) {
						error_log(
							"ContentGeneratorManager: Ciclo detectado: $className -> $dependencyClass"
						);
						return true;
					}
				}
				// Si está en el stack de recursión, hay ciclo
				elseif (
					isset($recursionStack[$dependencyClass]) &&
					$recursionStack[$dependencyClass]
				) {
					error_log(
						"ContentGeneratorManager: Ciclo detectado: $className -> $dependencyClass"
					);
					return true;
				}
			}
		}

		$recursionStack[$className] = false;
		return false;
	}

	/**
	 * Verifica y advierte si las prioridades no respetan las dependencias
	 *
	 * @param array $generators Lista de generadores
	 * @return void
	 */
	private function verifyDependencyOrder(array $generators): void
	{
		// Crear mapa de clase => prioridad
		$priorityMap = [];
		foreach ($this->generators as $priority => $priority_group) {
			foreach ($priority_group as $generator) {
				$priorityMap[get_class($generator)] = $priority;
			}
		}

		// Verificar que las dependencias tengan mayor o igual prioridad (menor número)
		foreach ($generators as $generator) {
			$className = get_class($generator);
			$generatorPriority = $priorityMap[$className];
			$dependencies = $generator->getDependencies();

			foreach ($dependencies as $dependencyClass) {
				if (isset($priorityMap[$dependencyClass])) {
					$dependencyPriority = $priorityMap[$dependencyClass];

					// La dependencia debe tener mayor o igual prioridad (menor o igual número)
					if ($dependencyPriority > $generatorPriority) {
						error_log(
							"ContentGeneratorManager: ADVERTENCIA - El generador $className (prioridad $generatorPriority) " .
								"depende de $dependencyClass (prioridad $dependencyPriority). " .
								"La dependencia debería tener mayor prioridad (número menor)."
						);
					}
				}
			}
		}
	}

	/**
	 * Obtiene la ruta del directorio de generadores
	 *
	 * @return string Ruta absoluta al directorio de generadores
	 */
	private function getGeneratorsPath(): string
	{
		// Si está definida la constante, usarla
		if (defined("CONTENT_GENERATORS_PATH") && is_dir(CONTENT_GENERATORS_PATH)) {
			return CONTENT_GENERATORS_PATH;
		}

		// Ruta predeterminada
		return get_template_directory() . "/src/Features/ContentGenerator/Generators";
	}

	/**
	 * Obtiene la lista de archivos PHP del directorio de generadores
	 *
	 * @param string $generators_path Ruta al directorio de generadores
	 * @return array Lista de paths absolutos a archivos PHP
	 */
	private function getGeneratorFiles(string $generators_path): array
	{
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

		return $files;
	}

	/**
	 * Determina si un archivo debe ser ignorado
	 *
	 * @param string $className Nombre del archivo/clase
	 * @return bool true si debe ser ignorado, false en caso contrario
	 */
	private function shouldSkipFile(string $className): bool
	{
		return $className === "AbstractContentGenerator" || $className === "README";
	}

	/**
	 * Construye el nombre de clase completamente cualificado a partir de un archivo
	 *
	 * @param string $file Ruta completa al archivo
	 * @return string Nombre de clase completamente cualificado (ej: \App\Features\ContentGenerator\Generators\MyGenerator)
	 */
	private function buildFullyQualifiedClassName(string $file): string
	{
		$className = pathinfo($file, PATHINFO_FILENAME);

		// Determinar el namespace basado en la estructura de carpetas
		$relativePath = str_replace(get_template_directory() . "/src/", "", dirname($file));
		$namespaceParts = array_map("ucfirst", explode("/", $relativePath));
		$namespace = "\\App\\" . implode("\\", $namespaceParts);

		return $namespace . "\\" . $className;
	}

	/**
	 * Verifica si una clase es un generador válido
	 *
	 * @param string $fullyQualifiedClassName Nombre de clase completamente cualificado
	 * @return bool true si es un generador válido, false en caso contrario
	 */
	private function isValidGenerator(string $fullyQualifiedClassName): bool
	{
		if (!class_exists($fullyQualifiedClassName)) {
			return false;
		}

		if (!is_subclass_of($fullyQualifiedClassName, AbstractContentGenerator::class)) {
			error_log(
				"ContentGeneratorManager: La clase $fullyQualifiedClassName no hereda de AbstractContentGenerator"
			);
			return false;
		}

		return true;
	}

	/**
	 * Escanea el directorio de generadores y retorna las clases encontradas
	 *
	 * @return array Arreglo asociativo con nombre completo de clase => nombre corto
	 */
	private function scanGeneratorClasses(): array
	{
		$generators_path = $this->getGeneratorsPath();
		$classes = [];

		// Verificar que el directorio existe
		if (!is_dir($generators_path)) {
			error_log(
				"ContentGeneratorManager: El directorio de generadores no existe: $generators_path"
			);
			return $classes;
		}

		// Obtener los archivos del directorio
		$files = $this->getGeneratorFiles($generators_path);

		// Procesamos cada archivo
		foreach ($files as $file) {
			$className = pathinfo($file, PATHINFO_FILENAME);

			// Ignorar archivos especiales
			if ($this->shouldSkipFile($className)) {
				continue;
			}

			$fullyQualifiedClassName = $this->buildFullyQualifiedClassName($file);

			// Verificamos si la clase existe y hereda de AbstractContentGenerator
			if ($this->isValidGenerator($fullyQualifiedClassName)) {
				$classes[$fullyQualifiedClassName] = $className;
			}
		}

		return $classes;
	}
}

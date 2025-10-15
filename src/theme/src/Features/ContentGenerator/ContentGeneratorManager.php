<?php

namespace App\Features\ContentGenerator;

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
}

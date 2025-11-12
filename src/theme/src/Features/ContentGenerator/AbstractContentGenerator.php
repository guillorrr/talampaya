<?php

namespace App\Features\ContentGenerator;

/**
 * Clase abstracta que define la estructura base para todos los generadores de contenido
 */
abstract class AbstractContentGenerator implements ContentGeneratorInterface
{
	/**
	 * Clave de opción única para este generador
	 * @var string
	 */
	protected string $option_key;

	/**
	 * Constructor
	 *
	 * @param string $option_key Clave de opción para rastrear si el contenido ya ha sido creado
	 */
	public function __construct(string $option_key)
	{
		$this->option_key = $option_key;
	}

	/**
	 * Obtiene la clave de opción del generador
	 *
	 * @return string
	 */
	public function getOptionKey(): string
	{
		return $this->option_key;
	}

	/**
	 * Obtiene la prioridad de ejecución del generador
	 *
	 * Prioridades recomendadas:
	 * - 5: Taxonomías (deben crearse primero)
	 * - 10: Post types (valor por defecto)
	 * - 15: Contenido que depende de otros posts
	 *
	 * @return int Prioridad de ejecución (menor número = mayor prioridad)
	 */
	public function getPriority(): int
	{
		return 10;
	}

	/**
	 * Obtiene las dependencias del generador
	 *
	 * Retorna un array de nombres de clase completamente cualificados de los
	 * generadores que deben ejecutarse antes que este.
	 *
	 * Ejemplo:
	 * ```php
	 * public function getDependencies(): array
	 * {
	 *     return [
	 *         TaxonomyGenerator::class,
	 *         ProjectPostGenerator::class,
	 *     ];
	 * }
	 * ```
	 *
	 * @return array<string> Array de nombres de clase de generadores dependientes
	 */
	public function getDependencies(): array
	{
		return [];
	}

	/**
	 * Verifica si el contenido ya se ha generado
	 *
	 * @param bool $force Si es verdadero, ignora la verificación y genera el contenido de todos modos
	 * @return bool Verdadero si el contenido ya se ha generado y $force es falso
	 */
	protected function isAlreadyGenerated(bool $force = false): bool
	{
		$already_created = get_option($this->option_key, false);
		return $already_created && !$force;
	}

	/**
	 * Actualiza la opción para marcar el contenido como generado
	 *
	 * @param bool $success Si la generación de contenido fue exitosa
	 */
	protected function markAsGenerated(bool $success): void
	{
		if (function_exists("\App\Inc\Helpers\OptionsHelper::talampaya_update_option")) {
			\App\Inc\Helpers\OptionsHelper::talampaya_update_option($success, $this->option_key);
		} else {
			update_option($this->option_key, $success);
		}
	}

	/**
	 * Método principal para generar contenido
	 *
	 * @param bool $force Si es verdadero, regenera el contenido incluso si ya existe
	 * @return void
	 */
	public function generate(bool $force = false): void
	{
		if ($this->isAlreadyGenerated($force)) {
			return;
		}

		$success = $this->generateContent();
		$this->markAsGenerated($success);
	}

	/**
	 * Método abstracto que debe ser implementado por clases hijas para generar el contenido específico
	 *
	 * @return bool Verdadero si la generación fue exitosa, falso en caso contrario
	 */
	abstract protected function generateContent(): bool;
}

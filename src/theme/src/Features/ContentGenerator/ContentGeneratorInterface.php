<?php

namespace App\Features\ContentGenerator;

/**
 * Interfaz que define los métodos requeridos para cualquier generador de contenido
 */
interface ContentGeneratorInterface
{
	/**
	 * Método principal para generar contenido
	 *
	 * @param bool $force Si es verdadero, regenera el contenido incluso si ya existe
	 * @return void
	 */
	public function generate(bool $force = false): void;

	/**
	 * Obtiene la clave de opción del generador
	 *
	 * @return string
	 */
	public function getOptionKey(): string;
}

<?php

namespace App\Features\ContentGenerator\Generators;

use App\Features\ContentGenerator\AbstractContentGenerator;

/**
 * Generador de contenido por defecto para demostrar el auto-registro
 */
class DefaultContentGenerator extends AbstractContentGenerator
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Definir una clave única para este generador
		parent::__construct("default_content_generator");
	}

	/**
	 * Genera el contenido predeterminado
	 *
	 * @return bool Verdadero si la generación fue exitosa
	 */
	protected function generateContent(): bool
	{
		// Aquí implementarías la lógica para generar el contenido
		// Por ejemplo, crear páginas, posts, menús, etc.

		// Registramos un log para verificar que el generador fue ejecutado
		error_log("DefaultContentGenerator: Contenido generado exitosamente");

		// Retornamos true para indicar que la generación fue exitosa
		return true;
	}
}

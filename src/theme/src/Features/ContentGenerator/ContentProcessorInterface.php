<?php

namespace App\Features\ContentGenerator;

/**
 * Interfaz para procesadores de contenido
 */
interface ContentProcessorInterface
{
	/**
	 * Procesa el contenido según una estrategia específica
	 *
	 * @param mixed $content El contenido a procesar (puede ser string, array, etc.)
	 * @return string El contenido procesado listo para insertar
	 */
	public function process(mixed $content): string;
}

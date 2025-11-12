<?php

namespace App\Core\ContextExtender;

/**
 * Interfaz para extender el contexto global de Timber
 */
interface ContextExtenderInterface
{
	/**
	 * Extiende el contexto de Timber
	 *
	 * @param array $context El contexto actual de Timber
	 * @return array El contexto modificado
	 */
	public function extendContext(array $context): array;
}

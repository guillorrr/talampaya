<?php

namespace App\Core\TwigExtender;

/**
 * Clase que gestiona las opciones del entorno Twig
 */
class EnvironmentOptions
{
	/**
	 * Actualiza opciones del entorno Twig
	 *
	 * @param array $options Opciones actuales
	 * @return array Opciones modificadas
	 */
	public function updateOptions(array $options): array
	{
		// $options['autoescape'] = true;
		// Aquí puedes añadir más opciones según sea necesario

		return $options;
	}
}

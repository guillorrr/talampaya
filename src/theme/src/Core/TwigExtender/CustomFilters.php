<?php

namespace App\Core\TwigExtender;

use Twig\Environment;
use Twig\TwigFilter;

/**
 * Clase que agrega filtros personalizados a Twig
 */
class CustomFilters implements TwigExtenderInterface
{
	/**
	 * Extiende el entorno Twig
	 *
	 * @param Environment $twig Entorno Twig
	 * @return Environment Entorno Twig modificado
	 */
	public function extendTwig(Environment $twig): Environment
	{
		// Agregar filtros personalizados
		$twig->addFilter(new TwigFilter("myfoo", [$this, "myfoo"]));

		return $twig;
	}

	/**
	 * Función de ejemplo para filtros Twig
	 */
	public function myfoo(string $text): string
	{
		$text .= " bar!";
		return $text;
	}
}

<?php

namespace App\Core\TwigExtender;

use Twig\Environment;

/**
 * Interfaz para extender Twig con filtros, funciones y extensiones
 */
interface TwigExtenderInterface
{
	/**
	 * Extiende el entorno Twig
	 *
	 * @param Environment $twig Entorno Twig
	 * @return Environment Entorno Twig modificado
	 */
	public function extendTwig(Environment $twig): Environment;
}

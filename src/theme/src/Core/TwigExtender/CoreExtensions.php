<?php

namespace App\Core\TwigExtender;

use Twig\Environment;
use Twig\Extension\StringLoaderExtension;
use Twig\Extra\Html\HtmlExtension;

/**
 * Clase que agrega las extensiones principales a Twig
 */
class CoreExtensions implements TwigExtenderInterface
{
	/**
	 * Extiende el entorno Twig
	 *
	 * @param Environment $twig Entorno Twig
	 * @return Environment Entorno Twig modificado
	 */
	public function extendTwig(Environment $twig): Environment
	{
		// Agregar extensiones principales
		$twig->addExtension(new StringLoaderExtension());
		$twig->addExtension(new HtmlExtension());

		return $twig;
	}
}

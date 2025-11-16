<?php

namespace App\Core\ContextExtender\Custom;

use App\Core\ContextExtender\ContextExtenderInterface;
use App\Inc\Controllers\MenuController;

/**
 * Clase que agrega los menús al contexto global de Timber
 */
class MenuContext implements ContextExtenderInterface
{
	/**
	 * Extiende el contexto de Timber
	 *
	 * @param array $context El contexto actual de Timber
	 * @return array El contexto modificado
	 */
	public function extendContext(array $context): array
	{
		$context["main_menu"] = MenuController::getPatternLabMenu("main");
		$context["projects_menu"] = MenuController::getPatternLabMenu("projects");

		return $context;
	}
}

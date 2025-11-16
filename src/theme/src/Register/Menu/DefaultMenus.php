<?php

namespace App\Register\Menu;

use App\Register\Menu\AbstractMenu;

class DefaultMenus extends AbstractMenu
{
	protected function configure(): array
	{
		return [
			"main" => esc_html__("Principal", "talampaya"),
			"projects" => esc_html__("Proyectos", "talampaya"),
		];
	}
}

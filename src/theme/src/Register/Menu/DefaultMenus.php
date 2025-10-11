<?php

namespace App\Register\Menu;

use App\Register\Menu\AbstractMenu;

class DefaultMenus extends AbstractMenu
{
	protected function configure(): array
	{
		return [
			"menu-custom" => esc_html__("Custom", "talampaya"),
		];
	}
}

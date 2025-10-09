<?php

namespace App\Register\Menu;

use Talampaya\src\register\Menu\AbstractMenu;

class DefaultMenus extends AbstractMenu
{
	protected function configure(): array
	{
		return [
			"menu-custom" => esc_html__("Custom", "talampaya"),
		];
	}
}

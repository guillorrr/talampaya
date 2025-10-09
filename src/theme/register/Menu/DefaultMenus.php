<?php

namespace Talampaya\Register\Menu;

class DefaultMenus extends AbstractMenu
{
	protected function configure(): array
	{
		return [
			"menu-custom" => esc_html__("Custom", "talampaya"),
		];
	}
}

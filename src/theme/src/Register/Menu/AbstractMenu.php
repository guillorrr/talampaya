<?php

namespace App\Register\Menu;

abstract class AbstractMenu
{
	protected array $locations = [];

	public function __construct()
	{
		$this->register();
	}

	abstract protected function configure(): array;

	public function register(): void
	{
		add_action("after_setup_theme", function () {
			$this->locations = $this->configure();
			register_nav_menus($this->locations);
		});
	}
}

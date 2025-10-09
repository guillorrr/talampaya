<?php

namespace Talampaya\Register\Sidebar;

abstract class AbstractSidebar
{
	protected array $args = [];

	public function __construct()
	{
		$this->register();
	}

	abstract protected function configure(): array;

	public function register(): void
	{
		add_action("widgets_init", function () {
			$this->args = $this->configure();
			$this->args["name"] = esc_html__($this->args["name"], "talampaya");
			$this->args["description"] = esc_html__($this->args["description"], "talampaya");
			register_sidebar($this->args);
		});
	}
}

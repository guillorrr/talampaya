<?php

namespace Talampaya\Register\Sidebar;

class CustomSidebar extends AbstractSidebar
{
	protected function configure(): array
	{
		return [
			"name" => "Custom Sidebar",
			"description" => "Description",
			"id" => "sidebar-id",
			"class" => "class",
			"before_widget" => '<li id="%1$s" class="widget %2$s">',
			"after_widget" => "</li>",
			"before_title" => '<h2 class="widgettitle">',
			"after_title" => "</h2>",
		];
	}
}

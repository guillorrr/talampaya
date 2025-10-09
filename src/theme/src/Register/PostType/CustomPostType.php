<?php

namespace App\Register\PostType;

use App\Register\PostType\AbstractPostType;

class CustomPostType extends AbstractPostType
{
	protected string $slug = "custom_post";

	protected function configure(): array
	{
		$labels = $this->getLabels("Custom Post", "Custom Posts");

		return [
			"label" => __("Custom Post", "talampaya"),
			"description" => __("description", "talampaya"),
			"labels" => $labels,
			"menu_icon" => "dashicons-admin-appearance",
			"supports" => [],
			"taxonomies" => ["custom_taxonomy"],
			"hierarchical" => false,
			"exclude_from_search" => false,
			"publicly_queryable" => true,
			"has_archive" => true,
			"public" => true,
			"show_ui" => true,
			"show_in_menu" => true,
			"show_in_admin_bar" => true,
			"can_export" => true,
			"show_in_nav_menus" => true,
			"menu_position" => 20,
			"capability_type" => "post",
			"show_in_rest" => true,
			"rewrite" => ["slug" => "custom"],
		];
	}
}

<?php

namespace App\Register\PostType;

class ProjectPostType extends AbstractPostType
{
	protected string $slug = "project_post";

	protected function configure(): array
	{
		$labels = $this->getLabels("Project", "Projects");

		return [
			"label" => __("Project", "talampaya"),
			"description" => __("description", "talampaya"),
			"labels" => $labels,
			"menu_icon" => "dashicons-admin-appearance",
			"supports" => [],
			"taxonomies" => ["project_taxonomy"],
			"hierarchical" => false,
			"exclude_from_search" => false,
			"publicly_queryable" => true,
			"has_archive" => "projects",
			"public" => true,
			"show_ui" => true,
			"show_in_menu" => true,
			"show_in_admin_bar" => true,
			"can_export" => true,
			"show_in_nav_menus" => true,
			"menu_position" => 20,
			"capability_type" => "post",
			"show_in_rest" => true,
			"rewrite" => [
				"slug" => "project",
				"with_front" => false,
			],
		];
	}
}

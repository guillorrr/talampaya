<?php

namespace App\Register\Taxonomy;

use App\Register\Taxonomy\AbstractTaxonomy;

class EpicTaxonomy extends AbstractTaxonomy
{
	protected string $slug = "epic_taxonomy";
	protected array $object_types = ["project_post"];

	protected function configure(): array
	{
		$labels = $this->getLabels("Epic", "Epics");

		return [
			"labels" => $labels,
			"description" => __("", "talampaya"),
			"hierarchical" => false,
			"public" => true,
			"publicly_queryable" => true,
			"query_var" => true,
			"show_ui" => true,
			"show_in_menu" => true,
			"show_in_nav_menus" => false,
			"show_tagcloud" => false,
			"show_in_quick_edit" => false,
			"show_admin_column" => false,
			"show_in_rest" => false,
			"rewrite" => [
				"slug" => "epic_taxonomy",
				"with_front" => true,
			],
		];
	}
}

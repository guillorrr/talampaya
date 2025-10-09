<?php

namespace Talampaya\Register\Taxonomy;

class CustomTaxonomy extends AbstractTaxonomy
{
	protected string $slug = "custom_taxonomy";
	protected array $object_types = ["custom_post"];

	protected function configure(): array
	{
		$labels = $this->getLabels("Custom Taxonomy", "Custom Taxonomies");

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
				"slug" => "custom",
				"with_front" => true,
			],
		];
	}
}

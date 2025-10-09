<?php

$labels = talampaya_compile_taxonomy_labels("Custom Taxonomy", "Custom Taxonomies");

$args = [
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

return ["custom_taxonomy" => ["object_type" => ["custom_post_type"], "args" => $args]];

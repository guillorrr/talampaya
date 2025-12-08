<?php

function blocks_add_custom_category($categories, $post): array
{
	// Add custom category at the beginning
	$custom_category = [
		[
			"slug" => "talampaya",
			"title" => __("Talampaya", "talampaya"),
			"icon" => "marker",
		],
	];

	return array_merge($custom_category, $categories);
}
add_filter("block_categories_all", "blocks_add_custom_category", 10, 2);

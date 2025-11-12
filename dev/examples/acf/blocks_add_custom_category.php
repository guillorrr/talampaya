<?php

function blocks_add_custom_category($categories, $post)
{
	return array_merge($categories, [
		[
			"slug" => "talampaya",
			"title" => __("Talampaya", "talampaya"),
			"icon" => "marker",
		],
	]);
}
add_filter("block_categories_all", "blocks_add_custom_category", 10, 2);

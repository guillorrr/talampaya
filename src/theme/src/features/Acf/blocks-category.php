<?php

function talampaya_blocks_category($categories, $post)
{
	return array_merge($categories, [
		[
			"slug" => "talampaya",
			"title" => __("Talampaya", "talampaya"),
			"icon" => "marker",
		],
	]);
}
add_filter("block_categories_all", "talampaya_blocks_category", 10, 2);

<?php
/*
 * Add columns to custom_post_type post list
 */
function add_acf_columns_for_custom_post_type($columns)
{
	return array_merge($columns, [
		"start_date" => __("Starts"),
		"end_date" => __("Ends"),
	]);
}
add_filter("manage_custom_post_type_posts_columns", "add_acf_columns_for_custom_post_type");

/*
 * Add columns to custom_post_type post list
 */
function custom_post_type_custom_column($column, $post_id)
{
	switch ($column) {
		case "start_date":
			echo get_post_meta($post_id, "custom_post_type_start_date", true);
			break;
		case "end_date":
			echo get_post_meta($post_id, "custom_post_type_end_date", true);
			break;
	}
}
add_action("manage_custom_post_type_posts_custom_column", "custom_post_type_custom_column", 10, 2);

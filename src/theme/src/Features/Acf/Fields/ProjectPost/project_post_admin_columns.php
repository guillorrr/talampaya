<?php
/*
 * Add columns to project_post post list
 */
function add_acf_columns_for_project_post($columns): array
{
	return array_merge($columns, [
		"start_date" => __("Starts"),
		"end_date" => __("Ends"),
	]);
}
add_filter("manage_project_post_posts_columns", "add_acf_columns_for_project_post");

/*
 * Add columns to project_post post list
 */
function project_post_custom_column($column, $post_id): void
{
	switch ($column) {
		case "start_date":
			echo get_post_meta($post_id, "post_type_project_post_start_date", true);
			break;
		case "end_date":
			echo get_post_meta($post_id, "post_type_project_post_end_date", true);
			break;
	}
}
add_action("manage_project_post_posts_custom_column", "project_post_custom_column", 10, 2);

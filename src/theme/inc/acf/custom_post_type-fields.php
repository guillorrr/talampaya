<?php
// Post Type Key: custom_post_type
function add_acf_custom_post_type_fields()
{
	$key = "example";
	$post_type = "custom_post_type";
	$block_title = __("Custom Fields", "talampaya");
	$block_name = sanitize_title($key);
	$block_key = str_replace("_", "-", $block_name);

	$custom_post_type_start_date = [
		"key" => "field_custom_post_type_start_date",
		"name" => "custom_post_type_start_date",
		"label" => __("Start Date", "talampaya"),
		"type" => "date_picker",
		"display_format" => "Y-m-d",
		"return_format" => "Y-m-d",
		"first_day" => 1,
	];

	$custom_post_type_end_date = [
		"key" => "field_custom_post_type_end_date",
		"name" => "custom_post_type_end_date",
		"label" => __("End Date", "talampaya"),
		"type" => "date_picker",
		"display_format" => "Y-m-d",
		"return_format" => "Y-m-d",
		"first_day" => 1,
	];

	$field_group = [
		"key" => "post_type", // don't change this key
		"title" => $block_title,
		"fields" => [$custom_post_type_start_date, $custom_post_type_end_date],
		"location" => [
			[
				[
					"param" => "post_type",
					"operator" => "==",
					"value" => $post_type,
				],
			],
		],
		"show_in_rest" => true,
		"menu_order" => 99999,
	];

	acf_add_local_field_group(
		talampaya_replace_keys_from_acf_register_fields($field_group, $block_key, $post_type)
	);
}
add_action("acf/init", "add_acf_custom_post_type_fields", 10);

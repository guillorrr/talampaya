<?php
// Post Type Key: custom_post_type
function add_acf_custom_post_type_fields()
{
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

	acf_add_local_field_group([
		"key" => "group_custom_post_type",
		"title" => __("Text", "talampaya"),
		"fields" => [$custom_post_type_start_date, $custom_post_type_end_date],
		"location" => [
			[
				[
					"param" => "post_type",
					"operator" => "==",
					"value" => "custom_post_type",
				],
			],
		],
		"show_in_rest" => true,
		"menu_order" => 99999,
	]);
}
add_action("acf/init", "add_acf_custom_post_type_fields", 10);

<?php
// Post Type Key: custom_post_type
use Illuminate\Support\Str;
use Talampaya\src\app\Helpers\AcfHelper;

function add_acf_custom_post_type_fields(): void
{
	$post_type = "custom_post_type";
	$block_title = __("Custom Fields", "talampaya");

	$additional_args = [
		"display_format" => "Y-m-d",
		"return_format" => "Y-m-d",
		"first_day" => 1,
	];

	$fields = [
		["start_date", "date_picker", 50, null, 0, $additional_args],
		["end_date", "date_picker", 50, null, 0, $additional_args],
	];

	$groups = [[$block_title, AcfHelper::talampaya_create_acf_group_fields($fields), 1]];

	foreach ($groups as $group) {
		$field_group = [
			"key" => Str::snake($group[0]),
			"title" => __($group[0], "talampaya"),
			"fields" => $group[1],
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
			"menu_order" => $group[2],
		];

		acf_add_local_field_group(
			AcfHelper::talampaya_replace_keys_from_acf_register_fields(
				$field_group,
				$post_type,
				"post_type"
			)
		);
	}
}
add_action("acf/init", "add_acf_custom_post_type_fields", 10);

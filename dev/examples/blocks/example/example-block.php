<?php
// Block Key: 'example'

use Illuminate\Support\Str;

function add_acf_block_example(): void
{
	$key = "example";
	$key_undescore = Str::snake($key);
	$key_dash = str_replace("_", "-", $key_undescore);
	$title = Str::title(str_replace("_", " ", $key_undescore));
	$block_title = __($title, "talampaya");

	$fields = [
		["title"],
		["subtitle"],
		["intro"],
		["background_color", "color_picker"],
		["image", "image", 100, null, 0, ["return_format" => "array"]],
		[
			"list",
			"repeater",
			100,
			null,
			0,
			["layout" => "block", "sub_fields" => [talampaya_create_acf_field("text")]],
		],
	];

	$groups = [[$block_title, talampaya_create_acf_group_fields($fields), 1]];

	foreach ($groups as $group) {
		$field_group = [
			"key" => Str::snake($group[0]),
			"title" => __($group[0], "talampaya"),
			"fields" => $group[1],
			"location" => [
				[
					[
						"param" => "block",
						"operator" => "==",
						"value" => "acf/" . $key_dash,
					],
				],
			],
			"show_in_rest" => true,
			"menu_order" => $group[2],
		];

		acf_add_local_field_group(
			talampaya_replace_keys_from_acf_register_fields($field_group, $key_undescore)
		);
	}
}
add_action("acf/init", "add_acf_block_example", 10);

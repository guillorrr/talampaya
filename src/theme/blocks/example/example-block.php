<?php
// Block Key: 'example'

function add_acf_block_example()
{
	$key = "example";
	$block_title = __("Example Block", "talampaya");
	$block_name = sanitize_title($key);
	$block_key = str_replace("_", "-", $block_name);

	$title = [
		"key" => "field_title",
		"name" => "title",
		"label" => __("Title", "talampaya"),
		"type" => "text",
	];

	$subtitle = [
		"key" => "field_subtitle",
		"name" => "subtitle",
		"label" => __("Subtitle", "talampaya"),
		"type" => "text",
	];

	$intro = [
		"key" => "field_intro",
		"name" => "intro",
		"label" => __("Intro", "talampaya"),
		"type" => "text",
	];

	$bg_color = [
		"key" => "field_bg_color",
		"name" => "bg_color",
		"label" => __("Background Color", "talampaya"),
		"type" => "color_picker",
	];

	$image = [
		"key" => "field_image",
		"label" => "Desktop Image",
		"name" => "image",
		"type" => "image",
		"return_format" => "array", //'array', 'url', 'id'
	];

	$list_text = [
		"key" => "field_list_text",
		"label" => "Text",
		"name" => "list_text",
		"type" => "text",
	];

	$list = [
		"key" => "field_list",
		"label" => "List",
		"name" => "list",
		"type" => "repeater",
		"layout" => "block",
		"sub_fields" => [$list_text],
	];

	$field_group = [
		"key" => "group_block",
		"title" => $block_title,
		"fields" => [$intro, $title, $subtitle, $bg_color, $image, $list],
		"location" => [
			[
				[
					"param" => "block",
					"operator" => "==",
					"value" => "acf/" . $block_name,
				],
			],
		],
		"show_in_rest" => true,
		"menu_order" => 99999,
	];

	acf_add_local_field_group(
		talampaya_replace_keys_from_acf_register_fields($field_group, $block_key)
	);
}
add_action("acf/init", "add_acf_block_example", 10);

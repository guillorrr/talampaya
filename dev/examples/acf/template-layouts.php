<?php
// Key: template_layouts
function add_acf_template_layouts()
{
	$template_layouts_checkbox = [
		"key" => "field_template_layouts_checkbox",
		"name" => "template_layouts_checkbox",
		"label" => __("Checkbox", "talampaya"),
		"type" => "true_false",
		"message" => __("True or False", "talampaya"),
		"default_value" => 0,
		"ui" => 0,
		"ui_on_text" => __("Yes", "talampaya"),
		"ui_off_text" => __("No", "talampaya"),
	];

	acf_add_local_field_group([
		"key" => "group_template_layouts",
		"title" => __("True or False", "talampaya"),
		"fields" => [$template_layouts_checkbox],
		"location" => [
			[
				[
					"param" => "post_template",
					"operator" => "==",
					"value" => "layouts/template-custom.php",
				],
			],
		],
		"show_in_rest" => true,
		"menu_order" => 99999,
	]);
}
add_action("acf/init", "add_acf_template_layouts", 10);

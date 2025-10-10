<?php

if (function_exists("acf_add_options_page")) {
	acf_add_options_page([
		"page_title" => "Theme General Settings",
		"menu_title" => __("Theme Settings", "talampaya"),
		"menu_slug" => "theme-general-settings",
		"capability" => "edit_posts",
		"redirect" => true,
	]);

	acf_add_options_sub_page([
		"page_title" => "Theme Header Settings",
		"menu_title" => __("Header", "talampaya"),
		"parent_slug" => "theme-general-settings",
	]);

	acf_add_options_sub_page([
		"page_title" => "Theme Footer Settings",
		"menu_title" => __("Footer", "talampaya"),
		"parent_slug" => "theme-general-settings",
	]);
}

function add_acf_options_page_header(): void
{
	$options_page_header_text = [
		"key" => "field_options_page_header_text",
		"name" => "options_page_header_text",
		"label" => __("Text", "talampaya"),
		"type" => "text",
	];

	acf_add_local_field_group([
		"key" => "group_options_page_header",
		"title" => __("Header", "talampaya"),
		"fields" => [$options_page_header_text],
		"location" => [
			[
				[
					"param" => "options_page",
					"operator" => "==",
					"value" => "acf-options-header",
				],
			],
		],
		"show_in_rest" => true,
		"menu_order" => 99999,
	]);
}
add_action("acf/init", "add_acf_options_page_header", 10);

function add_acf_options_page_footer(): void
{
	$options_page_footer_copyright = [
		"key" => "field_options_page_footer_copyright",
		"name" => "options_page_footer_copyright",
		"label" => __("Copyright", "talampaya"),
		"type" => "text",
	];

	acf_add_local_field_group([
		"key" => "group_options_page_footer",
		"title" => __("Footer", "talampaya"),
		"fields" => [$options_page_footer_copyright],
		"location" => [
			[
				[
					"param" => "options_page",
					"operator" => "==",
					"value" => "acf-options-footer",
				],
			],
		],
		"show_in_rest" => true,
		"menu_order" => 99999,
	]);
}
add_action("acf/init", "add_acf_options_page_footer", 10);

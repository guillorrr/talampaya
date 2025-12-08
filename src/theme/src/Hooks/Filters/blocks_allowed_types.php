<?php

/**
 * Filter allowed block types in Gutenberg editor
 *
 * Only allows:
 * - Custom ACF blocks (talampaya category)
 * - Text blocks (paragraph, heading, list, etc.)
 * - Media blocks (image, gallery, video, etc.)
 * - Design blocks (columns, group, separator, etc.)
 */
function blocks_allowed_types($allowed_block_types, $block_editor_context): array
{
	// Text blocks
	$text_blocks = [
		"core/paragraph",
		"core/heading",
		"core/list",
		"core/list-item",
		"core/quote",
		"core/code",
		"core/preformatted",
		"core/pullquote",
		"core/table",
		"core/verse",
	];

	// Media blocks
	$media_blocks = [
		"core/image",
		"core/gallery",
		"core/audio",
		"core/cover",
		"core/file",
		"core/media-text",
		"core/video",
	];

	// Design blocks
	$design_blocks = [
		"core/buttons",
		"core/button",
		"core/columns",
		"core/column",
		"core/group",
		"core/row",
		"core/stack",
		"core/separator",
		"core/spacer",
	];

	// Get all registered ACF blocks from WordPress Block Registry
	$acf_blocks = [];
	if (class_exists("WP_Block_Type_Registry")) {
		$registry = \WP_Block_Type_Registry::get_instance();
		$all_blocks = $registry->get_all_registered();

		foreach ($all_blocks as $block_name => $block) {
			if (strpos($block_name, "acf/") === 0) {
				$acf_blocks[] = $block_name;
			}
		}
	}

	return array_merge($text_blocks, $media_blocks, $design_blocks, $acf_blocks);
}
add_filter("allowed_block_types_all", "blocks_allowed_types", 25, 2);

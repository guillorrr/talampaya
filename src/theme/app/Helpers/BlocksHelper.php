<?php

namespace Talampaya\App\Helpers;

class BlocksHelper
{
	// -----------------------------------------------------------------------------
	// Get Block Content from Custom Path
	// -----------------------------------------------------------------------------
	public static function get_block_from_page_by_path(
		string $block_name,
		string $path = "home"
	): string {
		$page = get_page_by_path($path);

		$blocks = parse_blocks($page->post_content);

		foreach ($blocks as $block) {
			if ($block["blockName"] === $block_name) {
				return render_block($block);
			}
		}

		return "";
	}

	// -----------------------------------------------------------------------------
	// Get Block Data from Custom Path
	// -----------------------------------------------------------------------------
	public static function get_block_data_from_page_by_path(
		string $block_name,
		string $path = "home"
	): array {
		$page = get_page_by_path($path);

		$blocks = parse_blocks($page->post_content);

		foreach ($blocks as $block) {
			if ($block["blockName"] === $block_name) {
				return $block["attrs"]["data"];
			}
		}

		return [];
	}
}

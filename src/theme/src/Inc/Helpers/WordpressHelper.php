<?php

namespace App\Inc\Helpers;

use function Talampaya\App\Helpers\is_shop;
use function Talampaya\App\Helpers\talampaya_string_to_slug;
use function Talampaya\App\Helpers\wc_get_page_id;

class WordpressHelper
{
	// -----------------------------------------------------------------------------
	// Theme Name
	// -----------------------------------------------------------------------------

	public static function talampaya_theme_name()
	{
		$talampaya_theme = wp_get_theme();
		return $talampaya_theme->get("Name");
	}

	// -----------------------------------------------------------------------------
	// Parent Theme Name
	// -----------------------------------------------------------------------------

	public static function talampaya_parent_theme_name()
	{
		$theme = wp_get_theme();
		if ($theme->parent()):
			$theme_name = $theme->parent()->get("Name");
		else:
			$theme_name = $theme->get("Name");
		endif;

		return $theme_name;
	}

	// -----------------------------------------------------------------------------
	// Theme Slug
	// -----------------------------------------------------------------------------

	public static function talampaya_theme_slug()
	{
		$talampaya_theme = wp_get_theme();
		return talampaya_string_to_slug($talampaya_theme->get("Name"));
	}

	// -----------------------------------------------------------------------------
	// Theme Author
	// -----------------------------------------------------------------------------

	public static function talampaya_theme_author()
	{
		$talampaya_theme = wp_get_theme();
		return $talampaya_theme->get("Author");
	}

	// -----------------------------------------------------------------------------
	// Theme Description
	// -----------------------------------------------------------------------------

	public static function talampaya_theme_description()
	{
		$talampaya_theme = wp_get_theme();
		return $talampaya_theme->get("Description");
	}

	// -----------------------------------------------------------------------------
	// Theme Version
	// -----------------------------------------------------------------------------

	public static function talampaya_theme_version()
	{
		$talampaya_theme = wp_get_theme();
		return $talampaya_theme->get("Version");
	}

	// -----------------------------------------------------------------------------
	// Page ID
	// -----------------------------------------------------------------------------

	public static function talampaya_page_id()
	{
		$page_id = "";
		if (is_single() || is_page()) {
			$page_id = get_the_ID();
		} elseif (WOOCOMMERCE_IS_ACTIVE && is_shop()) {
			$page_id = wc_get_page_id("shop");
		} else {
			$page_id = get_option("page_for_posts");
		}
		return $page_id;
	}
}

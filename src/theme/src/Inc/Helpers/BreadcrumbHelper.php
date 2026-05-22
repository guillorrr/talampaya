<?php

namespace App\Inc\Helpers;

use Timber\Timber;

class BreadcrumbHelper
{
	/**
	 * Generates simple breadcrumbs: Home > Current Page
	 *
	 * @return array Array of breadcrumb items with 'label' and optional 'url'
	 */
	public static function generateBreadcrumbs(): array
	{
		$breadcrumbs = [["label" => __("Home", "talampaya"), "url" => \home_url("/")]];

		$post = Timber::get_post();
		if ($post) {
			// Add current page title (no URL for current page)
			$breadcrumbs[] = [
				"label" => $post->title(),
			];
		}

		return $breadcrumbs;
	}
}

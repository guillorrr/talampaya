<?php

namespace App\Inc\Helpers;

/**
 * Helper class for pagination logic
 */
class PaginationHelper
{
	/**
	 * Get pagination data from a WP_Query
	 *
	 * @param \WP_Query $query The WordPress query object
	 * @param int $posts_per_page Number of posts per page
	 * @param string $base_url Base URL for pagination links
	 * @param bool $use_page_format Whether to use /page/N/ format instead of ?paged=N
	 * @param string $query_param Query parameter name (default: "paged")
	 * @param string $classes Additional CSS classes for pagination
	 * @return array|null Pagination data or null if pagination not needed
	 */
	public static function getPaginationFromQuery(
		\WP_Query $query,
		int $posts_per_page,
		string $base_url,
		bool $use_page_format = false,
		string $query_param = "paged",
		string $classes = ""
	): ?array {
		// Get current page
		$paged = $query->get("paged") ?: ($query->get("page") ?: 1);

		// Calculate total pages
		$total_posts = (int) ($query->found_posts ?? 0);
		$max_pages = (int) ($query->max_num_pages ?? 0);
		$total_pages =
			$max_pages > 0
				? $max_pages
				: ($total_posts > 0
					? (int) ceil($total_posts / $posts_per_page)
					: 0);

		// Only return pagination if there are multiple pages AND we have posts
		if ($total_pages <= 1 || $total_posts <= 0) {
			return null;
		}

		return [
			"currentPage" => $paged,
			"totalPages" => $total_pages,
			"baseUrl" => $base_url,
			"queryParam" => $query_param,
			"usePageFormat" => $use_page_format,
			"classes" => $classes,
		];
	}

	/**
	 * Check if a requested page is valid and redirect if necessary
	 *
	 * @param int $requested_page The page number requested
	 * @param int $total_pages Total number of pages
	 * @param int $total_posts Total number of posts
	 * @param string $redirect_url Base URL for redirect
	 * @param bool $use_page_format Whether to use /page/N/ format
	 * @return bool True if page is valid, false if redirect was performed
	 */
	public static function validateAndRedirect(
		int $requested_page,
		int $total_pages,
		int $total_posts,
		string $redirect_url,
		bool $use_page_format = false
	): bool {
		// If user is trying to access a page that doesn't exist, redirect to last valid page
		if ($requested_page > $total_pages && $total_pages > 0 && $total_posts > 0) {
			if ($use_page_format && $total_pages > 1) {
				$redirect_url = rtrim($redirect_url, "/") . "/page/" . $total_pages . "/";
			} else {
				$redirect_url = add_query_arg("paged", $total_pages, $redirect_url);
			}
			\wp_safe_redirect($redirect_url, 301);
			exit();
		}

		return true;
	}
}

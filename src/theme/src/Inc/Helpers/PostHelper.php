<?php

namespace App\Inc\Helpers;

use Talampaya\App\Helpers\Timber;
use Talampaya\App\Helpers\WP_Error;
use Talampaya\App\Helpers\WP_Post;
use function Talampaya\App\Helpers\talampaya_get_or_create_author;

class PostHelper
{
	public static function talampaya_create_post($data, $category_id = null): WP_Error|int
	{
		$author_id = null;
		if (!empty($data["author_email"])) {
			$author_id = talampaya_get_or_create_author($data["author_email"]);
			if (is_wp_error($author_id)) {
				return $author_id;
			}
		}

		$post_data = [
			"post_title" => $data["title"],
			"post_content" => $data["content"],
			"post_status" => $data["status"],
			"post_type" => $data["post_type"],
			"post_name" => $data["post_slug"],
			"post_date" => $data["post_date"],
			"post_date_gmt" => $data["post_date"],
			"post_modified" => $data["post_modified"],
			"post_modified_gmt" => $data["post_modified"],
		];

		if ($category_id) {
			$post_data["post_category"] = [$category_id];
		}

		if ($author_id) {
			$post_data["post_author"] = $author_id;
		}

		add_filter(
			"wp_insert_post_data",
			"talampaya_create_post_with_post_modified",
			PHP_INT_MAX,
			2
		);
		$post = wp_insert_post($post_data);
		remove_filter(
			"wp_insert_post_data",
			"talampaya_create_post_with_post_modified",
			PHP_INT_MAX
		);

		return $post;
	}

	public static function talampaya_create_post_with_post_modified($data, $array)
	{
		$data["post_modified"] = $array["post_modified"] ?? null;
		$data["post_modified_gmt"] =
			$array["post_modified_gmt"] ?? get_gmt_from_date($data["post_modified"]);
		$data["post_modified"] =
			$data["post_modified"] ?? get_date_from_gmt($data["post_modified_gmt"]);

		return $data;
	}

	// -----------------------------------------------------------------------------
	// Return array of values from custom post meta for custom post type
	// -----------------------------------------------------------------------------
	public static function talampaya_get_all_postmeta_for_post_type(
		$key,
		$post_type,
		array $excludes = null
	) {
		$args = [
			"post_type" => $post_type,
			"post_status" => "publish",
			"posts_per_page" => -1,
			"fields" => "ids",
		];

		if (!empty($excludes)) {
			$args["meta_query"] = [
				"relation" => "AND",
			];
			foreach ($excludes as $exclude) {
				$args["meta_query"][] = [
					"key" => $key,
					"value" => $exclude,
					"compare" => "!=",
				];
			}
		}

		$posts = get_posts($args);
		$values = [];
		foreach ($posts as $post) {
			$values[] = get_post_meta($post, $key, true);
		}

		return $values;
	}

	// -----------------------------------------------------------------------------
	// Get Related Posts
	// -----------------------------------------------------------------------------
	public static function get_related_posts(
		$post_id,
		$taxonomy,
		$post_type = "post",
		$number = 3
	): array|\Timber\PostCollectionInterface|null {
		$terms = wp_get_post_terms($post_id, $taxonomy);

		if (!empty($terms) && !is_wp_error($terms)) {
			$term_ids = wp_list_pluck($terms, "term_id");

			$args = [
				"post_type" => $post_type,
				"posts_per_page" => $number,
				"post__not_in" => [$post_id],
				"tax_query" => [
					[
						"taxonomy" => $taxonomy,
						"field" => "id",
						"terms" => $term_ids,
					],
				],
			];

			return Timber::get_posts($args);
		}

		return [];
	}

	// -----------------------------------------------------------------------------
	// Get Posts by Meta Key
	// -----------------------------------------------------------------------------
	public static function get_posts_by_meta_key(
		string $key,
		string $post_type = "post",
		int $quantity = 1,
		string $sort = "ASC",
		string $order_by = "meta_value",
		$post__not_in = []
	): array|WP_Post|null {
		$args = [
			"post_type" => $post_type,
			"posts_per_page" => $quantity,
			"meta_key" => $key,
			"orderby" => $order_by,
			"order" => $sort,
			"post__not_in" => $post__not_in,
		];
		return get_posts($args);
	}
}

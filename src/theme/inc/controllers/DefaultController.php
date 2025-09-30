<?php

class DefaultController
{
	public static function get_front_page_context($context = []): array
	{
		$data = self::load_json_data("data", []);

		$data["touts"] = [];
		$data["latest_posts"] = [];

		$most_used_category = null;
		$categories = get_categories([
			"orderby" => "count",
			"order" => "DESC",
			"number" => 1,
		]);
		if (!empty($categories)) {
			$most_used_category = $categories[0];
		}

		$featured_posts = [];
		if ($most_used_category) {
			$featured_posts = get_posts([
				"category" => $most_used_category->term_id,
				"numberposts" => 4,
			]);
		}

		if (!empty($featured_posts)) {
			$hero_post = array_shift($featured_posts);
			$data["hero"]["headline"]["medium"] = get_the_title($hero_post);
			$data["hero"]["url"] = get_the_permalink($hero_post);

			$featured_image = get_the_post_thumbnail_url($hero_post, "full");
			if ($featured_image) {
				$data["hero"]["img"]["landscape_16x9"] = [
					"src" => $featured_image,
					"alt" => get_post_meta(
						get_post_thumbnail_id($hero_post),
						"_wp_attachment_image_alt",
						true
					),
					"width" => 1600,
					"height" => 900,
				];
			}

			foreach ($featured_posts as $post) {
				$posts_data["headline"]["short"] = get_the_title($post);
				$posts_data["url"] = get_the_permalink($post);

				$featured_image = get_the_post_thumbnail_url($post, "medium");
				if ($featured_image) {
					$posts_data["img"]["landscape_4x3"] = [
						"src" => $featured_image,
						"alt" => get_post_meta(
							get_post_thumbnail_id($post),
							"_wp_attachment_image_alt",
							true
						),
						"width" => 800,
						"height" => 600,
					];
				}

				$data["touts"][] = $posts_data;
			}
		}

		$recent_posts = get_posts([
			"numberposts" => 4,
			"post__not_in" => array_merge([$hero_post->ID], wp_list_pluck($featured_posts, "ID")),
		]);
		foreach ($recent_posts as $post) {
			$recent_posts_data["headline"]["short"] = get_the_title($post);
			$recent_posts_data["excerpt"]["medium"] = get_the_excerpt($post);
			$recent_posts_data["url"] = get_the_permalink($post);

			$recent_featured_image = get_the_post_thumbnail_url($post, "medium");
			if ($recent_featured_image) {
				$recent_posts_data["img"]["square"] = [
					"src" => $recent_featured_image,
					"alt" => get_post_meta(
						get_post_thumbnail_id($post),
						"_wp_attachment_image_alt",
						true
					),
					"width" => 800,
					"height" => 600,
				];
			}

			$data["latest_posts"][] = $recent_posts_data;
		}

		return array_merge($data, $context);
	}

	public static function get_blog_context($context = []): array
	{
		$data = self::load_json_data("data", []);
		$data = self::load_json_data("blog", $data);

		return array_merge($data, $context);
	}

	public static function get_page_context($context = []): array
	{
		$data = self::load_json_data("data", []);
		$data = self::load_json_data("article", $data);

		$data["title"] = get_the_title();
		$data["content"] = apply_filters(
			"the_content",
			get_post_field("post_content", get_the_ID())
		);

		return array_merge($data, $context);
	}

	public static function get_single_context($context = []): array
	{
		$data = self::load_json_data("data", []);
		$data = self::load_json_data("article", $data);

		$data["title"] = get_the_title();
		$data["content"] = apply_filters(
			"the_content",
			get_post_field("post_content", get_the_ID())
		);

		$author = get_user_by("id", get_post_field("post_author", get_the_ID()));
		$data["author"] = [
			"first_name" => $author->first_name,
			"last_name" => $author->last_name,
		];

		return array_merge($data, $context);
	}

	public static function get_404_context($context = []): array
	{
		$data = self::load_json_data("data", []);
		$data = self::load_json_data("404", $data);

		return array_merge($data, $context);
	}

	/**
	 * Carga datos de un JSON específico
	 *
	 * @param string $json_name Nombre del archivo JSON sin extensión
	 * @param array $context Contexto actual
	 * @return array Contexto combinado con datos del JSON
	 */
	public static function load_json_data(string $json_name, array $context = []): array
	{
		$json_file = get_template_directory() . "/inc/mockups/{$json_name}.json";

		if (!file_exists($json_file)) {
			return $context;
		}

		$json = file_get_contents($json_file);
		$data = json_decode($json, true);

		if (!is_array($data)) {
			return $context;
		}

		return array_merge($context, $data);
	}
}

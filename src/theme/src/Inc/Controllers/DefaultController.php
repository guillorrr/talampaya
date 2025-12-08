<?php

namespace App\Inc\Controllers;

use Timber\Timber;

class DefaultController
{
	public static function get_front_page_context($context = []): array
	{
		$data = self::load_json_data("data", []);

		// Si hay una página estática configurada como front page, asegurar que el post esté en el contexto
		// Timber::context() ya debería incluir el post, pero lo verificamos por si acaso
		if (is_front_page() && get_option("page_on_front")) {
			$front_page_id = get_option("page_on_front");
			// Si no está en el contexto, obtenerlo
			if (!isset($context["post"]) || !$context["post"]) {
				$post = Timber::get_post($front_page_id);
				if ($post) {
					$context["post"] = $post;
				}
			}
		}

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
			$featured_posts = Timber::get_posts([
				"category" => $most_used_category->term_id,
				"posts_per_page" => 4,
			])->to_array();
		}

		$posts_not_in = [];

		if (!empty($featured_posts)) {
			$hero_post = array_shift($featured_posts);
			$data["hero"] = $hero_post;
			$data["touts"] = $featured_posts;

			$posts_not_in = array_merge([$hero_post->ID], wp_list_pluck($featured_posts, "ID"));
		}

		$recent_posts = Timber::get_posts([
			"orderby" => "date",
			"order" => "DESC",
			"posts_per_page" => 5,
			"post__not_in" => array_merge($posts_not_in),
		]);

		$data["posts"]["posts"] = $recent_posts;
		$data["posts"]["pagination"] = $recent_posts->pagination();
		$data["show_more_latest_posts"] = true;

		return array_merge($data, $context);
	}

	public static function get_blog_context($context = []): array
	{
		$data = self::load_json_data("data", []);
		$data = self::load_json_data("blog", $data);

		$data["show_more_latest_posts"] = false;

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

		$featured_image = get_the_post_thumbnail_url(get_the_ID(), "large");
		$data["featured_image"] = [
			"src" => $featured_image,
			"alt" => get_post_meta(
				get_post_thumbnail_id(get_the_ID()),
				"_wp_attachment_image_alt",
				true
			),
			"width" => 1600,
			"height" => 900,
		];

		$data["related_posts"] = [];
		$categories = wp_get_post_categories(get_the_ID());
		$tags = wp_get_post_tags(get_the_ID(), ["fields" => "ids"]);

		$related_posts = get_posts([
			"category__in" => $categories,
			"tag__in" => $tags,
			"post__not_in" => [get_the_ID()],
			"numberposts" => 3,
			"orderby" => "rand",
		]);

		foreach ($related_posts as $post) {
			$related_posts_data["title"] = get_the_title($post);
			$related_posts_data["url"] = get_the_permalink($post);

			$data["related_posts"]["posts"][] = $related_posts_data;
		}

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
		$json_file = get_template_directory() . "/src/Mockups/{$json_name}.json";

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

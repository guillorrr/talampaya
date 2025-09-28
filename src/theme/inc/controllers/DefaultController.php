<?php

class DefaultController
{
	public static function get_home_context($context = []): array
	{
		return self::load_json_data("homepage", $context);
	}

	public static function get_page_context($context = []): array
	{
		$data = self::load_json_data("data", $context);
		$data = self::load_json_data("article", $data);

		$data["title"] = get_the_title();
		$data["content"] = apply_filters(
			"the_content",
			get_post_field("post_content", get_the_ID())
		);

		return array_merge($context, $data);
	}

	public static function get_single_context($context = []): array
	{
		$data = self::load_json_data("data", $context);
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

		return array_merge($context, $data);
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

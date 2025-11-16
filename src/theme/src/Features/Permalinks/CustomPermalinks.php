<?php

namespace App\Features\Permalinks;

/**
 * Gestiona los permalinks personalizados para CPTs desde la raíz
 * Maneja: product_cat_post, sector_post, service_post
 */
class CustomPermalinks
{
	public function __construct()
	{
		$this->init();
	}

	private function init(): void
	{
		// Agregar reglas de rewrite personalizadas
		add_action("init", [$this, "addRewriteRules"], 10);

		// Registrar query vars personalizadas
		add_filter("query_vars", [$this, "registerQueryVars"]);

		// Parsear la query
		add_filter("request", [$this, "parseRequest"]);

		// Modificar permalink de productos
		add_filter("post_type_link", [$this, "productPermalink"], 10, 2);
	}

	/**
	 * Registra las query vars personalizadas
	 *
	 * @param array $vars Las query vars existentes
	 * @return array Las query vars con las nuevas agregadas
	 */
	public function registerQueryVars(array $vars): array
	{
		$vars[] = "project_post";
		return $vars;
	}

	/**
	 * Agrega reglas de rewrite personalizadas
	 */
	public function addRewriteRules(): void
	{
		$projects = get_posts([
			"post_type" => "project_post",
			"numberposts" => -1,
			"post_status" => "publish",
		]);

		foreach ($projects as $project) {
			add_rewrite_rule(
				"^" . $project->post_name . '/?$',
				"index.php?project_post=" . $project->post_name,
				"top"
			);
		}
	}

	/**
	 * Parsea la query para detectar los diferentes tipos de posts
	 *
	 * @param array $query_vars Las variables de la query
	 * @return array Las variables modificadas
	 */
	public function parseRequest(array $query_vars): array
	{
		// Project
		if (isset($query_vars["project_post"])) {
			$project = get_page_by_path($query_vars["project_post"], OBJECT, "project_post");

			if ($project) {
				$query_vars["post_type"] = "project_post";
				$query_vars["name"] = $query_vars["project_post"];
				unset($query_vars["project_post"]);
			}
		}

		return $query_vars;
	}
}

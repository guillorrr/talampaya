<?php

namespace App\Inc\Helpers;

use WP_Error;

/**
 * Helper para la creación y actualización de contenido en WordPress
 */
class ContentCreationHelper
{
	/**
	 * Crea o actualiza un post con el contenido especificado
	 *
	 * @param string $title Título del post
	 * @param string $slug Slug del post
	 * @param string $content Contenido del post
	 * @param string $post_type Tipo de post (default: 'page')
	 * @param string $post_status Estado del post (default: 'publish')
	 * @param array $additional_args Argumentos adicionales para wp_insert_post
	 * @return int|WP_Error ID del post creado/actualizado o error
	 */
	public static function createOrUpdatePost(
		string $title,
		string $slug,
		string $content,
		string $post_type = "page",
		string $post_status = "publish",
		array $additional_args = []
	): int|WP_Error {
		// Buscar si ya existe un post con este slug y tipo
		$existing_post = get_page_by_path($slug, OBJECT, $post_type);

		$post_data = array_merge(
			[
				"post_title" => $title,
				"post_name" => $slug,
				"post_content" => $content,
				"post_status" => $post_status,
				"post_type" => $post_type,
			],
			$additional_args
		);

		if ($existing_post) {
			$post_data["ID"] = $existing_post->ID;
			return wp_update_post($post_data);
		} else {
			return wp_insert_post($post_data);
		}
	}

	/**
	 * Crea o actualiza una página con el contenido especificado
	 *
	 * @param string $title Título de la página
	 * @param string $slug Slug de la página
	 * @param string $content Contenido de la página
	 * @param string $template Plantilla de la página (opcional)
	 * @param string $post_status Estado de la página (default: 'publish')
	 * @param array $additional_args Argumentos adicionales para wp_insert_post
	 * @return int|WP_Error ID de la página creada/actualizada o error
	 */
	public static function createOrUpdatePage(
		string $title,
		string $slug,
		string $content,
		string $template = "",
		string $post_status = "publish",
		array $additional_args = []
	): WP_Error|int {
		$args = $additional_args;

		if (!empty($template)) {
			$args["page_template"] = $template;
		}

		return self::createOrUpdatePost($title, $slug, $content, "page", $post_status, $args);
	}

	/**
	 * Crea o actualiza múltiples páginas a partir de un array de definiciones
	 *
	 * @param array $pages_definitions Array con definiciones de páginas
	 * @param bool $force Forzar actualización incluso si las páginas ya existen
	 * @return array Array con los IDs de las páginas creadas/actualizadas
	 */
	public static function createOrUpdatePages(array $pages_definitions, bool $force = false): array
	{
		$created_pages = [];

		foreach ($pages_definitions as $page_def) {
			// Verificar si ya existe y si no estamos forzando actualización
			if (!$force) {
				$existing_page = get_page_by_path($page_def["slug"], OBJECT, "page");
				if ($existing_page) {
					$created_pages[$page_def["slug"]] = $existing_page->ID;
					continue;
				}
			}

			$content = $page_def["content"] ?? "";
			$template = $page_def["template"] ?? "";
			$status = $page_def["status"] ?? "publish";
			$additional_args = $page_def["args"] ?? [];

			$page_id = self::createOrUpdatePage(
				$page_def["title"],
				$page_def["slug"],
				$content,
				$template,
				$status,
				$additional_args
			);

			if (!is_wp_error($page_id)) {
				$created_pages[$page_def["slug"]] = $page_id;

				// Si hay metadatos para asignar
				if (isset($page_def["meta"]) && is_array($page_def["meta"])) {
					foreach ($page_def["meta"] as $meta_key => $meta_value) {
						update_post_meta($page_id, $meta_key, $meta_value);
					}
				}
			}
		}

		return $created_pages;
	}

	/**
	 * Crea o actualiza un custom post type con el contenido especificado
	 *
	 * @param string $title Título del post
	 * @param string $slug Slug del post
	 * @param string $content Contenido del post
	 * @param string $post_type Tipo de post personalizado
	 * @param string $post_status Estado del post (default: 'publish')
	 * @param array $additional_args Argumentos adicionales para wp_insert_post
	 * @param array $meta_fields Campos de metadatos a guardar
	 * @return int|WP_Error ID del post creado/actualizado o error
	 */
	public static function createOrUpdateCustomPost(
		string $title,
		string $slug,
		string $content,
		string $post_type,
		string $post_status = "publish",
		array $additional_args = [],
		array $meta_fields = []
	): WP_Error|int {
		$post_id = self::createOrUpdatePost(
			$title,
			$slug,
			$content,
			$post_type,
			$post_status,
			$additional_args
		);

		if (!is_wp_error($post_id)) {
			foreach ($meta_fields as $meta_key => $meta_value) {
				update_post_meta($post_id, $meta_key, $meta_value);
			}
		}

		return $post_id;
	}
}

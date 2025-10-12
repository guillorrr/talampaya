<?php

namespace App\Core\Setup;

/**
 * Clase encargada de configurar las funcionalidades base del tema
 */
class ThemeSupport
{
	private \WP_Theme $theme;

	private string|array|false $theme_text_domain;

	public function __construct()
	{
		$this->theme = wp_get_theme();
		$this->theme_text_domain = $this->theme->get("Text Domain");

		add_action("after_setup_theme", [$this, "setupTheme"]);
		add_action("init", [$this, "setPostTypeSupport"]);
		add_action("init", [$this, "loadTextDomain"]);
	}

	/**
	 * Configura las características básicas del tema
	 */
	public function setupTheme(): void
	{
		$html5 = ["search-form", "comment-form", "comment-list", "gallery", "caption"];
		$formats = ["aside", "image", "video", "quote", "link", "gallery", "audio"];

		// Características básicas
		add_theme_support("automatic-feed-links");
		add_theme_support("title-tag");
		add_theme_support("post-thumbnails");
		add_theme_support("html5", $html5);
		add_theme_support("post-formats", $formats);

		// Características opcionales (descomentadas según necesidades)
		//show_admin_bar(false);
		//add_post_type_support('page', 'excerpt');
		// set_post_thumbnail_size(1200, 9999);
	}

	public function loadTextDomain(): void
	{
		load_theme_textdomain(
			$this->theme_text_domain,
			get_template_directory() . "/assets/languages"
		);
	}

	/**
	 * Configura soporte para tipos de posts
	 */
	public function setPostTypeSupport(): void
	{
		// Deshabilitar comentarios
		remove_post_type_support("post", "comments");
		remove_post_type_support("page", "comments");
	}
}

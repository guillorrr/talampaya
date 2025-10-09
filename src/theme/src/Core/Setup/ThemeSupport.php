<?php

namespace App\Core\Setup;

/**
 * Clase encargada de configurar las funcionalidades base del tema
 */
class ThemeSupport
{
	public function __construct()
	{
		add_action("after_setup_theme", [$this, "setupTheme"]);
		add_action("init", [$this, "setPostTypeSupport"]);
	}

	/**
	 * Configura las características básicas del tema
	 */
	public function setupTheme()
	{
		$theme = wp_get_theme();
		$theme_text_domain = $theme->get("Text Domain");

		$html5 = ["search-form", "comment-form", "comment-list", "gallery", "caption"];
		$formats = ["aside", "image", "video", "quote", "link", "gallery", "audio"];

		// Configurar traducciones
		load_theme_textdomain($theme_text_domain);

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

	/**
	 * Configura soporte para tipos de posts
	 */
	public function setPostTypeSupport()
	{
		// Deshabilitar comentarios
		remove_post_type_support("post", "comments");
		remove_post_type_support("page", "comments");
	}
}

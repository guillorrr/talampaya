<?php

namespace App\Core\Setup;

/**
 * Clase encargada de optimizar WordPress y deshabilitar funciones nativas innecesarias
 * para mejorar el rendimiento y la seguridad del sitio.
 */
class WordPressOptimizer
{
	/**
	 * Constructor que inicializa todas las optimizaciones
	 */
	function __construct()
	{
		// Inicializar optimizaciones
		add_action("init", [$this, "disableWpEmojicons"]);
		add_action("admin_head", [$this, "hideUpdateNoticeToAllButAdmin"], 1);
		add_action("widgets_init", [$this, "disableDefaultWidgets"], 11);
		add_action("wp_enqueue_scripts", [$this, "dequeueUnnecessaryScripts"], 100);

		// Ejecutar optimizaciones directamente
		$this->disableXmlrpc();
		$this->cleanWpHead();
	}

	/**
	 * Oculta notificaciones de actualización a todos excepto administradores
	 */
	function hideUpdateNoticeToAllButAdmin(): void
	{
		if (!current_user_can("update_core")) {
			remove_action("admin_notices", "update_nag", 3);
		}
	}

	/**
	 * Deshabilita emojis de WordPress para mejorar rendimiento
	 */
	function disableWpEmojicons(): void
	{
		remove_action("admin_print_styles", "print_emoji_styles");
		remove_action("wp_head", "print_emoji_detection_script", 7);
		remove_action("admin_print_scripts", "print_emoji_detection_script");
		remove_action("wp_print_styles", "print_emoji_styles");
		remove_filter("wp_mail", "wp_staticize_emoji_for_email");
		remove_filter("the_content_feed", "wp_staticize_emoji");
		remove_filter("comment_text_rss", "wp_staticize_emoji");
		add_filter("tiny_mce_plugins", [$this, "disableEmojiconsTynymce"]);
	}

	/**
	 * Elimina soporte de emojis en el editor TinyMCE
	 */
	function disableEmojiconsTynymce($plugins): array
	{
		if (is_array($plugins)) {
			return array_diff($plugins, ["wpemoji"]);
		} else {
			return [];
		}
	}

	/**
	 * Deshabilita xmlrpc.php para mejorar seguridad
	 */
	function disableXmlrpc(): void
	{
		add_filter("xmlrpc_enabled", "__return_false");
		remove_action("wp_head", "rsd_link");
		remove_action("wp_head", "wlwmanifest_link");
	}

	/**
	 * Limpia elementos innecesarios del head de WordPress
	 * @link http://cubiq.org/clean-up-and-optimize-wordpress-for-your-next-theme
	 */
	function cleanWpHead(): void
	{
		remove_action("wp_head", "wp_generator"); // WP Version
		remove_action("wp_head", "start_post_rel_link");
		remove_action("wp_head", "index_rel_link");
		remove_action("wp_head", "adjacent_posts_rel_link"); // Remove link to next and previous post
		remove_action("wp_head", "feed_links_extra", 3); // Automatic feeds for single posts
		remove_action("wp_head", "feed_links", 2);
		remove_action("wp_head", "parent_post_rel_link", 10, 0);
	}

	/**
	 * Deshabilita widgets por defecto de WordPress para mejorar rendimiento
	 */
	function disableDefaultWidgets(): void
	{
		unregister_widget("WP_Widget_Pages");
		unregister_widget("WP_Widget_Calendar");
		unregister_widget("WP_Widget_Archives");
		unregister_widget("WP_Widget_Links");
		unregister_widget("WP_Widget_Meta");
		unregister_widget("WP_Widget_Search");
		unregister_widget("WP_Widget_Text");
		unregister_widget("WP_Widget_Categories");
		unregister_widget("WP_Widget_Recent_Posts");
		unregister_widget("WP_Widget_Recent_Comments");
		unregister_widget("WP_Widget_RSS");
		unregister_widget("WP_Widget_Tag_Cloud");
		unregister_widget("WP_Nav_Menu_Widget");
		unregister_widget("Twenty_Eleven_Ephemera_Widget");
	}

	/**
	 * Elimina scripts y estilos innecesarios para mejorar rendimiento
	 */
	function dequeueUnnecessaryScripts(): void
	{
		if (!is_admin()) {
			wp_dequeue_style("wp-block-library");
			wp_dequeue_style("wp-block-library-theme");
			wp_dequeue_script("wp-embed");
		}
	}
}

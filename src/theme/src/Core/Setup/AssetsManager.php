<?php

namespace App\Core\Setup;

/**
 * Clase encargada de gestionar los assets del tema (CSS y JavaScript)
 */
class AssetsManager
{
	public function __construct()
	{
		// Frontend assets
		add_action("wp_enqueue_scripts", [$this, "enqueueFrontendStyles"]);
		add_action("wp_enqueue_scripts", [$this, "enqueueFrontendScripts"]);

		// Admin assets
		add_action("admin_enqueue_scripts", [$this, "enqueueAdminStyles"]);
		add_action("admin_enqueue_scripts", [$this, "enqueueAdminScripts"]);
	}

	/**
	 * Registra y carga estilos del frontend
	 */
	public function enqueueFrontendStyles(): void
	{
		global $theme_version;
		$version = $theme_version . "." . filemtime(get_stylesheet_directory() . "/style.css");
		wp_enqueue_style("talampaya_frontend_style", get_stylesheet_uri(), [], $version);
	}

	/**
	 * Registra y carga scripts del frontend
	 */
	public function enqueueFrontendScripts(): void
	{
		if (defined("WOOCOMMERCE_IS_ACTIVE") && WOOCOMMERCE_IS_ACTIVE) {
			wp_enqueue_script("select2");
		}

		global $theme_version;
		$version = $theme_version . "." . filemtime(get_stylesheet_directory() . "/js/main.min.js");

		// Script principal en footer
		wp_enqueue_script(
			"talampaya_footer_js",
			get_template_directory_uri() . "/js/main.min.js",
			[],
			$version,
			true
		);

		// Scripts adicionales
		wp_enqueue_script(
			"talampaya_scripts",
			get_template_directory_uri() . "/js/scripts.min.js",
			[],
			$version,
			true
		);

		// REMOVE COMMENT TO ACTIVE
		//        if (is_page('example-slug')){
		//            wp_enqueue_script('talampaya_example', get_template_directory_uri() . '/js/example.js', array(), talampaya_theme_version(), TRUE);
		//            $wp_js_vars = array(
		//                'ajax_url' => admin_url('admin-ajax.php'),
		//            );
		//            wp_localize_script('talampaya_footer_js', 'wp_js_var', $wp_js_vars);
		//        }

		// Cargar script de comentarios si es necesario
		if (is_singular() && comments_open() && get_option("thread_comments")) {
			wp_enqueue_script("comment-reply");
		}
	}

	/**
	 * Registra y carga estilos del admin
	 */
	public function enqueueAdminStyles(): void
	{
		global $theme_version;
		$file = get_stylesheet_directory() . "/css/backend-styles.css";

		if (file_exists($file)) {
			$version = $theme_version . "." . filemtime($file);

			wp_enqueue_style(
				"talampaya_backend_styles",
				get_template_directory_uri() . "/css/backend-styles.css",
				false,
				$version,
				"all"
			);
		}
	}

	/**
	 * Registra y carga scripts del admin
	 */
	public function enqueueAdminScripts(): void
	{
		global $theme_version;
		$file = get_stylesheet_directory() . "/js/backend.min.js";

		if (file_exists($file)) {
			$version = $theme_version . "." . filemtime($file);

			wp_enqueue_script(
				"talampaya_backend_scripts",
				get_template_directory_uri() . "/js/backend.min.js",
				false,
				$version,
				"all"
			);
		}
	}
}

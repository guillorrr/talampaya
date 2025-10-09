<?php

namespace Talampaya\Core;
class TalampayaSetup
{
	public function __construct()
	{
		add_action("init", [$this, "setPostTypeSupport"]);
		add_action("after_setup_theme", [$this, "setup"]);
		add_action("wp_enqueue_scripts", [$this, "talampaya_frontend_styles"]);
		add_action("wp_enqueue_scripts", [$this, "talampaya_frontend_scripts"]);
		add_action("admin_enqueue_scripts", [$this, "talampaya_backend_styles"]);
		add_action("admin_enqueue_scripts", [$this, "talampaya_backend_scripts"]);
		add_action("wp_before_admin_bar_render", [$this, "updateAdminBar"]);
		add_action("admin_menu", [$this, "updateAdminMenu"]);
	}

	public function setup()
	{
		$theme = wp_get_theme();
		$theme_text_domain = $theme->get("Text Domain");

		$html5 = ["search-form", "comment-form", "comment-list", "gallery", "caption"];
		$formats = ["aside", "image", "video", "quote", "link", "gallery", "audio"];

		load_theme_textdomain($theme_text_domain);
		add_theme_support("automatic-feed-links");
		add_theme_support("title-tag");
		add_theme_support("post-thumbnails");
		add_theme_support("html5", $html5);
		add_theme_support("post-formats", $formats);
		//show_admin_bar(false);
		//add_post_type_support('page', 'excerpt');
		// set_post_thumbnail_size(1200, 9999);
	}

	public function talampaya_frontend_styles()
	{
		global $theme_version;
		$version = $theme_version . "." . filemtime(get_stylesheet_directory() . "/style.css");
		wp_enqueue_style("talampaya_frontend_style", get_stylesheet_uri(), [], $version);
	}

	public function talampaya_frontend_scripts()
	{
		if (WOOCOMMERCE_IS_ACTIVE) {
			wp_enqueue_script("select2");
		}

		global $theme_version;
		$version = $theme_version . "." . filemtime(get_stylesheet_directory() . "/js/main.min.js");

		wp_enqueue_script(
			"talampaya_footer_js",
			get_template_directory_uri() . "/js/main.min.js",
			[],
			$version,
			true
		);

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

		if (is_singular() && comments_open() && get_option("thread_comments")) {
			wp_enqueue_script("comment-reply");
		}
	}

	public function talampaya_backend_styles()
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

	public function talampaya_backend_scripts()
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

	public function setPostTypeSupport()
	{
		remove_post_type_support("post", "comments");
		remove_post_type_support("page", "comments");
	}

	/**
	 * Removes comments from admin menu
	 */
	function updateAdminMenu()
	{
		remove_menu_page("edit-comments.php");
	}

	function updateAdminBar()
	{
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu("comments");
	}
}

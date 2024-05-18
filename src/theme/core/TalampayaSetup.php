<?php
/**
 * Setup class
 *
 * @link https://codex.wordpress.org/Post_Formats
 */
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
		add_filter("admin_footer_text", [$this, "updateAdminFooter"]);
	}

	public function setup()
	{
		global $theme_text_domain;

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

		wp_enqueue_script(
			"talampaya_footer_js",
			get_template_directory_uri() . "/js/main.min.js",
			[],
			talampaya_theme_version(),
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
		wp_enqueue_style(
			"talampaya_backend_styles",
			get_template_directory_uri() . "/css/backend-styles.css",
			false,
			talampaya_theme_version(),
			"all"
		);
	}

	public function talampaya_backend_scripts()
	{
		wp_enqueue_script(
			"talampaya_backend_scripts",
			get_template_directory_uri() . "/js/backend-scripts.js",
			["jquery"],
			talampaya_theme_version(),
			true
		);
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

	function updateAdminFooter()
	{
		echo 'Developed by <a href="https://guillo.dev" target="_blank">@guillorrr</a>';
	}
}

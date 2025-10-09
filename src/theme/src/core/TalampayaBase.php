<?php

namespace App\Core;

class TalampayaBase
{
	function __construct()
	{
		add_action("init", [$this, "disableWpEmojicons"]);
		add_action("admin_head", [$this, "hideUpdateNoticeToAllButAdmin"], 1);
		add_action("widgets_init", [$this, "disable_default_widgets"], 11);
		add_action("wp_enqueue_scripts", [$this, "dequeue_unnecessary_scripts"], 100);

		$this->disableXmlrpc();
		$this->removeFromWpHead();
	}

	/**
	 * Hide WordPress Update Nag to All But Admins
	 */
	function hideUpdateNoticeToAllButAdmin()
	{
		if (!current_user_can("update_core")) {
			remove_action("admin_notices", "update_nag", 3);
		}
	}

	/**
	 * Disable Emoji
	 */
	function disableWpEmojicons()
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

	function disableEmojiconsTynymce($plugins)
	{
		if (is_array($plugins)) {
			return array_diff($plugins, ["wpemoji"]);
		} else {
			return [];
		}
	}

	/**
	 * Disable xmlrpc.php
	 */
	function disableXmlrpc()
	{
		add_filter("xmlrpc_enabled", "__return_false");
		remove_action("wp_head", "rsd_link");
		remove_action("wp_head", "wlwmanifest_link");
	}

	/**
	 * Remove useless things from wp_head
	 * @link http://cubiq.org/clean-up-and-optimize-wordpress-for-your-next-theme
	 */
	function removeFromWpHead()
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
	 * Disables default WordPress widgets to improve performance.
	 */
	function disable_default_widgets()
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
	 * Dequeues unnecessary scripts and styles to improve performance.
	 */
	function dequeue_unnecessary_scripts()
	{
		if (!is_admin()) {
			wp_dequeue_style("wp-block-library");
			wp_dequeue_style("wp-block-library-theme");
			wp_dequeue_script("wp-embed");
		}
	}
}

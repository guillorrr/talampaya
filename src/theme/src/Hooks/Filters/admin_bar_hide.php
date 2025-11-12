<?php

if (!function_exists("admin_bar_hide")) {
	function admin_bar_hide()
	{
		if (!current_user_can("manage_options")) {
			add_filter("show_admin_bar", "__return_false");
		}
	}
}

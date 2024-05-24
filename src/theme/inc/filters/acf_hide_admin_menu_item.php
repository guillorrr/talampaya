<?php

//==============================================================================
// Hide the ACF Admin Menu Item
//==============================================================================

if (!function_exists("talampaya_acf_hide_admin_menu_item")):
	function talampaya_acf_hide_admin_menu_item($more)
	{
		return false;
	}
	add_filter("acf/settings/show_admin", "talampaya_acf_hide_admin_menu_item");
endif;

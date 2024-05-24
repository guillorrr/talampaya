<?php

//==============================================================================
// Hide the ACF Admin Menu Item
//==============================================================================

if (!function_exists("talampaya_hide_acf_admin_menu_item")):
	function talampaya_hide_acf_admin_menu_item($more)
	{
		return false;
	}
	add_filter("acf/settings/show_admin", "talampaya_hide_acf_admin_menu_item");
endif;

<?php

//==============================================================================
// Admin Footer White Label
//==============================================================================

if (!function_exists("talampaya_admin_footer_white_label")):
	function talampaya_admin_footer_white_label()
	{
		return 'Developed by <a href="https://guillo.dev" target="_blank">@guillorrr</a>';
	}
	add_filter("admin_footer_text", "talampaya_admin_footer_white_label");
endif;

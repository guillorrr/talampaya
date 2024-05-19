<?php

//==============================================================================
// Archives Excerpt More
//==============================================================================

if (!function_exists("talampaya_excerpt_more")):
	function talampaya_excerpt_more($more)
	{
		global $post;
		return "…";
	}
	add_filter("excerpt_more", "talampaya_excerpt_more");
endif;

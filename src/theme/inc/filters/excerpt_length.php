<?php

//==============================================================================
// Excerpt Lenght
//==============================================================================

if (!function_exists("talampaya_excerpt_length")):
	function talampaya_excerpt_length($length)
	{
		return 20;
	}
	add_filter("excerpt_length", "talampaya_excerpt_length", 999);
endif;

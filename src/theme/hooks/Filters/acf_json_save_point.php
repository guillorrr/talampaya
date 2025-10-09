<?php

//==============================================================================
// ACF JSON Save Point
//==============================================================================

if (!function_exists("talampaya_acf_json_save_point")):
	function talampaya_acf_json_save_point($path)
	{
		$path = get_template_directory() . "/acf-json";
		return $path;
	}
	add_filter("acf/settings/save_json", "talampaya_acf_json_save_point");
endif;

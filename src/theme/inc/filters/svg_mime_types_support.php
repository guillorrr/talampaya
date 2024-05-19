<?php

/******************************************************************************/
/* Add SVG support ************************************************************/
/******************************************************************************/

if (!function_exists("talampaya_svg_mime_types_support")):
	function talampaya_svg_mime_types_support($mimes)
	{
		$mimes["svg"] = "image/svg+xml";
		$mimes["svgz"] = "image/svg+xml";

		return $mimes;
	}
	add_filter("upload_mimes", "talampaya_svg_mime_types_support");
endif;

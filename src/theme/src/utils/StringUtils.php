<?php

namespace App\Utils;

class StringUtils
{
	// -----------------------------------------------------------------------------
	// String to Slug
	// -----------------------------------------------------------------------------

	public static function talampaya_string_to_slug($str)
	{
		$str = strtolower(trim($str));
		$str = preg_replace("/[^a-z0-9-]/", "_", $str);
		$str = preg_replace("/-+/", "_", $str);
		return $str;
	}

	// -----------------------------------------------------------------------------
	// Compress Styles
	// -----------------------------------------------------------------------------
	public static function talampaya_compress_styles($minify)
	{
		$minify = preg_replace("/\/\*((?!\*\/).)*\*\//", "", $minify); // negative look ahead
		$minify = preg_replace("/\s{2,}/", " ", $minify);
		$minify = preg_replace("/\s*([:;{}])\s*/", '$1', $minify);
		$minify = preg_replace("/;}/", "}", $minify);

		return $minify;
	}
}

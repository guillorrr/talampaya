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

	public static function talampaya_make_phrase_ucfirst($phrase): string|null
	{
		if (empty($phrase)) {
			//error_log("talampaya_make_phrase_ucfirst: Título vacío");
			return null;
		}

		// Verificar si el título está todo en mayúsculas o todo en minúsculas
		if (
			$phrase === mb_strtoupper($phrase, "UTF-8") ||
			$phrase === mb_strtolower($phrase, "UTF-8")
		) {
			$phrase = ucfirst(mb_strtolower($phrase, "UTF-8"));
			//error_log("talampaya_make_phrase_ucfirst: Título convertido a formato ucfirst");
		}

		return $phrase;
	}
}

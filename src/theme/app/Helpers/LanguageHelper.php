<?php

namespace Talampaya\App\Helpers;

class LanguageHelper
{
	// -----------------------------------------------------------------------------
	// Get Current Language
	// -----------------------------------------------------------------------------
	public static function get_current_language(): string
	{
		$current_language = "es";
		if (defined("ICL_LANGUAGE_CODE")) {
			$current_language = ICL_LANGUAGE_CODE;
		}

		return $current_language;
	}

	// -----------------------------------------------------------------------------
	// Get Current Locale
	// -----------------------------------------------------------------------------
	public static function get_current_locale(): string
	{
		$current_language = self::get_current_language();

		$locale = "en_US";
		switch ($current_language) {
			case "es":
				$locale = "es_ES";
				break;
			case "en":
				$locale = "en_US";
				break;
			case "ca":
				$locale = "ca_ES";
				break;
		}

		return $locale;
	}
}

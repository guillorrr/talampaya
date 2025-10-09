<?php

namespace Talampaya\Utils;

class ColorUtils
{
	// -----------------------------------------------------------------------------
	// Convert hex to rgb
	// -----------------------------------------------------------------------------

	public static function talampaya_hex2rgb($hex)
	{
		$hex = str_replace("#", "", $hex);

		if (strlen($hex) == 3) {
			$r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
			$g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
			$b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
		} else {
			$r = hexdec(substr($hex, 0, 2));
			$g = hexdec(substr($hex, 2, 2));
			$b = hexdec(substr($hex, 4, 2));
		}
		$rgb = [$r, $g, $b];
		return implode(",", $rgb); // returns the rgb values separated by commas
		//return $rgb; // returns an array with the rgb values
	}

	// -----------------------------------------------------------------------------
	// Get text color type
	// -----------------------------------------------------------------------------
	public static function get_text_color_type($hexcolor)
	{
		// If a leading # is provided, remove it
		if (substr($hexcolor, 0, 1) === "#") {
			$hexcolor = substr($hexcolor, 1);
		}

		// If a three-character hexcode, make six-character
		if (strlen($hexcolor) === 3) {
			$hexArray = str_split($hexcolor);

			$hexcolor = join(
				"",
				array_map(function ($hex) {
					return $hex . $hex;
				}, $hexArray)
			);
		}

		// Convert to RGB value
		$r = intval(substr($hexcolor, 0, 2), 16);
		$g = intval(substr($hexcolor, 2, 2), 16);
		$b = intval(substr($hexcolor, 4, 2), 16);

		// Get YIQ ratio
		$yiq = ($r * 299 + $g * 587 + $b * 114) / 1000;

		// Check contrast
		$yiq >= 128 ? ($text_color = "dark") : ($text_color = "light");

		return $text_color;
	}
}

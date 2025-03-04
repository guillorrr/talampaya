<?php

require "vendor/autoload.php";

use Twig\Environment;
use Twig\TwigFunction;

/**
 * @param Twig_Environment $env - The Twig Environment - https://twig.symfony.com/api/1.x/Twig_Environment.html
 * @param $config - Config of `@basalt/twig-renderer`
 */
function addCustomExtensions(Environment &$env, $config)
{
	/**
	 * @example `<h1>Hello {{ customTwigFunctionThatSaysWorld() }}!</h1>` => `<h1>Hello Custom World</h1>`
	 */
	//  $env->addFunction(new TwigFunction('customTwigFunctionThatSaysWorld', function () {
	//    return 'Custom World';
	//  }));

	/*
	 * Reverse a string
	 * @param string $theString
	 * @example `<p>{{ reverse('abc') }}</p>` => `<p>cba</p>`
	 */
	//  $env->addFunction(new TwigFunction('reverse', function ($theString) {
	//    return strrev($theString);
	//  }));

	//  $env->addExtension(new \My\CustomExtension());

	//  `{{ foo }}` => `bar`
	//  $env->addGlobal('foo', 'bar');

	// example of enabling the Twig debug mode extension (ex. {{ dump(my_variable) }} to check out the template's available data) -- comment out to disable
	$env->addExtension(new Twig\Extension\DebugExtension());
}

function addCustomFunctions(\Twig_Environment &$env, $config)
{
	$env->addFunction(
		new TwigFunction("faker", function ($string, $options = "") {
			$faker = \Faker\Factory::create();
			if ($options != "") {
				if (is_array($options)) {
					return $faker->$string(...$options);
				} else {
					return $faker->$string($options);
				}
			} else {
				return $faker->$string;
			}
		})
	);

	/**
	 * Get the current year
	 *
	 * @return string
	 */
	$current_year_function = new TwigFunction("current_year", function () {
		return date("Y");
	});
	$env->addFunction($current_year_function);

	/**
	 * Get the current date
	 *
	 * @return string
	 */
	$current_date_function = new TwigFunction("current_date", function () {
		return date("Y-m-d");
	});
	$env->addFunction($current_date_function);

	/**
	 * Get the current time
	 *
	 * @return string
	 */
	$current_time_function = new TwigFunction("current_time", function () {
		return date("H:i:s");
	});
	$env->addFunction($current_time_function);

	/**
	 * Get the current date and time
	 *
	 * @return string
	 */
	$current_datetime_function = new TwigFunction("current_datetime", function () {
		return date("Y-m-d H:i:s");
	});
	$env->addFunction($current_datetime_function);
}

function addCustomFilters(\Twig_Environment &$env, $config)
{
	/**
	 * Remove empty tags
	 *
	 * @return string
	 */
	$remove_empty_tags_filter = new \Twig\TwigFilter("remove_empty_tags", function ($content) {
		do {
			$content = preg_replace(
				'/<(\w+)[^>]*>(?:\s|&nbsp;|&#160;|&#xa0;)*<\/\1>/iu',
				"",
				$content,
				-1,
				$count
			);
		} while ($count > 0);

		return $content;
	});
	$env->addFilter($remove_empty_tags_filter);

	/**
	 * Format Date
	 *
	 * @return string
	 */
	$format_date_filter = new \Twig\TwigFilter("format_date", function ($string) {
		return $string;
	});
	$env->addFilter($format_date_filter);

	/**
	 * Luma
	 *
	 * Take in an rgba associative array return a luminance value
	 * according to ITU-R BT.709.
	 *
	 * @param array $rgba the associative array containing each color value. For example
	 *              array(4) {
	 *                ["r"] => int(0)
	 *                ["g"] => int(123)
	 *                ["b"] => int(255)
	 *                ["a"] => int(1)
	 *              }
	 *
	 * @return float
	 */
	$luma_filter = new \Twig\TwigFilter("luma", function ($rgba) {
		// Doesn't handle alpha, yet.
		return 0.2126 * $rgba["r"] + 0.7152 * $rgba["g"] + 0.0722 * $rgba["b"];
	});
	$env->addFilter($luma_filter);

	/**
	 * To rgba
	 * Hex to rgba conversion
	 *
	 * @return string
	 *
	 * @param string $color a hex color value with or without leading hash(#)
	 */
	$hex_to_rgba_filter = new \Twig\TwigFilter("hex_to_rgba", function ($color) {
		$default = "rgba(0,0,0)";

		// If "#" is provided, drop it
		if ($color[0] == "#") {
			$color = substr($color, 1);
		}

		// Check if color has 6 or 3 characters and get values
		if (strlen($color) == 6) {
			$hex = [$color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]];
		} elseif (strlen($color) == 3) {
			$hex = [$color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]];
		} else {
			return $default;
		}

		// Convert hexadec to rgb
		$rgb = array_map("hexdec", $hex);

		// Check if opacity is set(rgba or rgb)
		$output = "rgba(" . implode(",", $rgb) . ")";
		// Return rgb(a) color string
		return $output;
	});
	$env->addFilter($hex_to_rgba_filter);

	/**
	 * Placeholder
	 *
	 * @return string
	 */
	$placeholder_filter = new \Twig\TwigFilter("placeholder", function ($string) {
		return $string;
	});
	$env->addFilter($placeholder_filter);

	/**
	 * RGBA String
	 *
	 * @return string
	 */
	$rgba_string_filter = new \Twig\TwigFilter("rgba_string", function ($string) {
		$rgba = trim(str_replace(" ", "", $string));
		if (stripos($rgba, "rgba") !== false) {
			$res = sscanf($rgba, "rgba(%d, %d, %d, %f)");
		} else {
			$res = sscanf($rgba, "rgb(%d, %d, %d)");
			$res[] = 1;
		}
		return array_combine(["r", "g", "b", "a"], $res);
	});
	$env->addFilter($rgba_string_filter);
}

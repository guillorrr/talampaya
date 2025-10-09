<?php

namespace Talampaya\Utils;

class ArrayUtils
{
	// -----------------------------------------------------------------------------
	// Filter array by key and value
	// -----------------------------------------------------------------------------
	public static function talampaya_filter_array_by_key_and_value($field, $value, $arrays)
	{
		foreach ($arrays as $array) {
			if (isset($array[$field]) && $array[$field] == $value) {
				return $array;
			}
		}
		return null;
	}
}

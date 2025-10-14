<?php

namespace App\Inc\Helpers;

class OptionsHelper
{
	public static function talampaya_update_option(bool $update, string $option_key): void
	{
		if ($update) {
			if (get_option($option_key) === false) {
				add_option($option_key, true, "", false);
			} else {
				update_option($option_key, true);
			}
			error_log("Updated successfully: " . $option_key);
		} else {
			error_log("Failed to update: " . $option_key);
		}
	}
}

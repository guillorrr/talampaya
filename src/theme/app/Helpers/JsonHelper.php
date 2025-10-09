<?php

namespace Talampaya\App\Helpers;

class JsonHelper
{
	// -----------------------------------------------------------------------------
	// Get JSON data from URL
	// -----------------------------------------------------------------------------
	public static function talampaya_get_json_data_from_url($url)
	{
		$response = wp_remote_get($url);
		$body = wp_remote_retrieve_body($response);
		return json_decode($body, true);
	}
}

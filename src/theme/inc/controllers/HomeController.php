<?php

class HomeController
{
	public static function index($context = []): array
	{
		$json = file_get_contents(get_template_directory() . "/inc/mockups/homepage.json");
		$data = json_decode($json, true);
		return array_merge($context, $data);
	}
}

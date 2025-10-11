<?php

namespace App\Features\Acf\Blocks\Modifiers;

use App\Features\Acf\Blocks\BlockRenderer;
use App\Integrations\Geolocation\GeolocationServiceFactory;
use App\Utils\RequestUtils;

BlockRenderer::registerContextModifier("geolocation", function (
	$context,
	$attributes,
	$content,
	$is_preview,
	$post_id
) {
	$context["geolocation_data"] = null;

	try {
		$is_development = defined("WP_DEBUG") && WP_DEBUG;

		if (IS_DEVELOPMENT) {
			$geolocationService = GeolocationServiceFactory::createService();

			$ip = RequestUtils::getIPForTesting();

			$geoData = $geolocationService->getGeolocationData($ip);

			$geoData = apply_filters("talampaya/geolocation/data", $geoData, $ip);

			$context["geolocation_data"] = $geoData;
			$context["geolocation_ip"] = $ip;
		} else {
			$host = $_SERVER["HTTP_HOST"] ?? "localhost";
			$scheme = is_ssl() ? "https" : "http";

			$api_url = "{$scheme}://{$host}/wp-json/talampaya/v1/geolocation";

			$response = wp_remote_get($api_url, [
				"timeout" => 5,
				"redirection" => 1,
				"httpversion" => "1.1",
				"sslverify" => !$is_development, // Desactivar verificación SSL en desarrollo
			]);

			if (is_wp_error($response)) {
				error_log(
					"Error al obtener datos de geolocalización: " . $response->get_error_message()
				);
				return $context;
			}

			if (200 === wp_remote_retrieve_response_code($response)) {
				$data = json_decode(wp_remote_retrieve_body($response), true);

				if (
					!empty($data) &&
					isset($data["success"]) &&
					$data["success"] &&
					isset($data["data"])
				) {
					$context["geolocation_data"] = $data["data"];
					$context["geolocation_ip"] = $data["ip"] ?? "Unknown";
				}
			}
		}
	} catch (\Exception $e) {
		error_log("Excepción al obtener datos de geolocalización: " . $e->getMessage());
	}

	return $context;
});

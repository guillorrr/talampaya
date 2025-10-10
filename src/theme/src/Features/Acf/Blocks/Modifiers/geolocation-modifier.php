<?php

namespace App\Features\Acf\Blocks\Modifiers;

use App\Features\Acf\Blocks\BlockRenderer;
use WP_Http;

BlockRenderer::registerContextModifier("geolocation", function (
	$context,
	$attributes,
	$content,
	$is_preview,
	$post_id
) {
	// Inicializar datos de geolocalización como null (para manejar casos donde no se pueda obtener)
	$context["geolocation_data"] = null;

	// Intentar obtener datos de geolocalización de la API
	try {
		// Crear instancia de WP_Http para la solicitud
		$http = new WP_Http();

		// URL del endpoint de geolocalización
		$api_url = rest_url("talampaya/v1/geolocation");

		// Realizar solicitud a la API
		$response = $http->get($api_url);

		// Verificar si hay un error en la respuesta
		if (is_wp_error($response)) {
			error_log(
				"Error al obtener datos de geolocalización: " . $response->get_error_message()
			);
			return $context;
		}

		// Verificar código de respuesta
		if (200 === $response["response"]["code"]) {
			// Decodificar respuesta JSON
			$data = json_decode($response["body"], true);

			// Si la respuesta es correcta y contiene datos
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
	} catch (\Exception $e) {
		error_log("Excepción al obtener datos de geolocalización: " . $e->getMessage());
	}

	return $context;
});

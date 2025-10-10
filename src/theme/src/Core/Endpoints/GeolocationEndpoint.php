<?php

namespace App\Core\Endpoints;

use App\Integrations\Geolocation\GeolocationServiceFactory;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Endpoint para obtener información de geolocalización basada en IP
 */
class GeolocationEndpoint extends AbstractEndpoint
{
	/**
	 * Ruta del endpoint
	 */
	protected const ROUTE = "/geolocation";

	/**
	 * {@inheritdoc}
	 */
	public function register(): void
	{
		register_rest_route($this->getNamespace(), $this->getRoute(), [
			"methods" => "GET",
			"callback" => [$this, "getGeolocationData"],
			"permission_callback" => "__return_true",
		]);
	}

	/**
	 * Obtiene información de geolocalización basada en la IP del usuario
	 *
	 * @param WP_REST_Request $request Objeto de solicitud de WP REST API
	 * @return WP_REST_Response|WP_Error Respuesta o error
	 */
	public function getGeolocationData(WP_REST_Request $request): WP_REST_Response|WP_Error
	{
		// Obtener IP del usuario
		$ip = $this->getUserIp();

		// Si no se pudo obtener la IP, devolver error
		if (empty($ip)) {
			return new WP_Error(
				"ip_not_found",
				"No se pudo determinar la dirección IP del usuario",
				["status" => 400]
			);
		}

		try {
			// Obtener servicio de geolocalización a través de la factory
			$geolocationService = GeolocationServiceFactory::createService();

			// Obtener datos de geolocalización
			$geoData = $geolocationService->getGeolocationData($ip);

			// Aplicar filtro para que otros plugins puedan modificar los datos
			$geoData = apply_filters("talampaya/geolocation/data", $geoData, $ip);

			return new WP_REST_Response(
				[
					"success" => true,
					"ip" => $ip,
					"data" => $geoData,
				],
				200
			);
		} catch (\Exception $e) {
			return new WP_Error("geolocation_error", $e->getMessage(), ["status" => 500]);
		}
	}

	/**
	 * Obtiene la dirección IP del usuario actual
	 *
	 * @return string|null Dirección IP o null si no se pudo determinar
	 */
	protected function getUserIp(): ?string
	{
		$ip = null;

		// Intentar obtener IP de cabeceras de proxy
		if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
			$ip = $_SERVER["HTTP_CLIENT_IP"];
		} elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			// HTTP_X_FORWARDED_FOR puede contener múltiples IPs separadas por coma
			$ipList = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
			$ip = trim($ipList[0]);
		} elseif (!empty($_SERVER["REMOTE_ADDR"])) {
			$ip = $_SERVER["REMOTE_ADDR"];
		}

		// Validar que sea una IP real
		if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
			return $ip;
		}

		return null;
	}
}

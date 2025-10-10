<?php

namespace App\Integrations\Geolocation;

/**
 * Servicio de geolocalización que utiliza la API web de MaxMind GeoLite
 */
class MaxMindGeolocationService implements GeolocationServiceInterface
{
	/**
	 * URL base de la API de MaxMind
	 */
	private const API_URL = "https://geolite.info/geoip/v2.1";

	/**
	 * ID de cuenta de MaxMind
	 */
	private string $accountId;

	/**
	 * Clave de licencia de MaxMind
	 */
	private string $licenseKey;

	/**
	 * Constructor
	 *
	 * @param string $accountId ID de cuenta de MaxMind
	 * @param string $licenseKey Clave de licencia de MaxMind
	 */
	public function __construct(string $accountId, string $licenseKey)
	{
		$this->accountId = $accountId;
		$this->licenseKey = $licenseKey;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getGeolocationData(string $ip): array
	{
		try {
			// Endpoint para ciudad en la API de MaxMind
			$endpoint = self::API_URL . "/city/" . $ip;

			// Configurar petición a la API de MaxMind
			$args = [
				"headers" => [
					"Authorization" =>
						"Basic " . base64_encode($this->accountId . ":" . $this->licenseKey),
				],
				"timeout" => 5,
			];

			// Realizar petición a la API
			$response = wp_remote_get($endpoint, $args);

			// Comprobar si hay error en la petición
			if (is_wp_error($response)) {
				throw new \Exception(
					"Error en la petición a la API de MaxMind: " . $response->get_error_message()
				);
			}

			// Comprobar código de respuesta
			$status_code = wp_remote_retrieve_response_code($response);
			if ($status_code !== 200) {
				if ($status_code === 404) {
					return [
						"country" => [
							"code" => "XX",
							"name" => "Unknown",
						],
						"city" => "Unknown",
						"provider" => "maxmind-api",
						"error" => "IP address not found",
					];
				}

				throw new \Exception(
					"Error en la respuesta de la API de MaxMind. Código: " . $status_code
				);
			}

			// Decodificar respuesta
			$body = wp_remote_retrieve_body($response);
			$data = json_decode($body, true);

			if (!$data) {
				throw new \Exception("Error al decodificar la respuesta de la API de MaxMind");
			}

			// Formatear los datos para devolverlos de manera consistente
			return [
				"country" => [
					"code" => $data["country"]["iso_code"] ?? "XX",
					"name" =>
						$data["country"]["names"]["es"] ??
						($data["country"]["names"]["en"] ?? "Unknown"),
				],
				"city" =>
					$data["city"]["names"]["es"] ?? ($data["city"]["names"]["en"] ?? "Unknown"),
				"postal_code" => $data["postal"]["code"] ?? null,
				"location" => [
					"latitude" => $data["location"]["latitude"] ?? null,
					"longitude" => $data["location"]["longitude"] ?? null,
					"timezone" => $data["location"]["time_zone"] ?? null,
				],
				"continent" => isset($data["continent"])
					? [
						"code" => $data["continent"]["code"] ?? null,
						"name" =>
							$data["continent"]["names"]["es"] ??
							($data["continent"]["names"]["en"] ?? "Unknown"),
					]
					: null,
				"subdivision" => isset($data["subdivisions"][0])
					? [
						"code" => $data["subdivisions"][0]["iso_code"] ?? null,
						"name" =>
							$data["subdivisions"][0]["names"]["es"] ??
							($data["subdivisions"][0]["names"]["en"] ?? "Unknown"),
					]
					: null,
				"provider" => "maxmind-api",
			];
		} catch (\Exception $e) {
			// Registrar el error para depuración
			error_log("MaxMind API error: " . $e->getMessage());

			// En caso de error, devolver datos mínimos
			return [
				"country" => [
					"code" => "XX",
					"name" => "Unknown",
				],
				"city" => "Unknown",
				"provider" => "maxmind-api",
				"error" => $e->getMessage(),
			];
		}
	}
}

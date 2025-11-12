<?php

namespace App\Integrations\Geolocation;

/**
 * Servicio de geolocalización de respaldo que proporciona datos básicos
 * cuando no es posible conectarse a un servicio de geolocalización externo
 */
class FallbackGeolocationService implements GeolocationServiceInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function getGeolocationData(string $ip): array
	{
		// Detectar si la IP es de tipo local (rangos privados)
		$isLocalIp = $this->isLocalIp($ip);

		if ($isLocalIp) {
			return [
				"country" => [
					"code" => "LOCAL",
					"name" => "Red Local",
				],
				"city" => "Local",
				"provider" => "fallback",
				"is_local" => true,
				"ip" => $ip,
			];
		}

		// Para IPs externas, devolver información mínima
		return [
			"country" => [
				"code" => "XX",
				"name" => "Desconocido",
			],
			"city" => "Desconocido",
			"provider" => "fallback",
			"message" =>
				"Se requieren credenciales de MaxMind para obtener datos de geolocalización",
			"ip" => $ip,
		];
	}

	/**
	 * Comprueba si una dirección IP pertenece a un rango privado o local
	 *
	 * @param string $ip Dirección IP a comprobar
	 * @return bool True si es una IP local o privada
	 */
	private function isLocalIp(string $ip): bool
	{
		// Comprobar si es localhost
		if ($ip === "127.0.0.1" || $ip === "::1") {
			return true;
		}

		// Convertir IP a número para comparaciones más fáciles
		$ipLong = ip2long($ip);

		if ($ipLong === false) {
			return false; // No es una IPv4 válida
		}

		// Comprobar rangos de IPs privadas según RFC 1918
		$privateRanges = [
			["10.0.0.0", "10.255.255.255"], // 10/8
			["172.16.0.0", "172.31.255.255"], // 172.16/12
			["192.168.0.0", "192.168.255.255"], // 192.168/16
			["169.254.0.0", "169.254.255.255"], // 169.254/16 (link-local)
		];

		foreach ($privateRanges as $range) {
			$min = ip2long($range[0]);
			$max = ip2long($range[1]);

			if ($ipLong >= $min && $ipLong <= $max) {
				return true;
			}
		}

		return false;
	}
}

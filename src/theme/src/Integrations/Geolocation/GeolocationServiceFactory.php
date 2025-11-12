<?php

namespace App\Integrations\Geolocation;

/**
 * Factory para crear instancias de servicios de geolocalización
 */
class GeolocationServiceFactory
{
	/**
	 * Crea un servicio de geolocalización basado en la configuración
	 *
	 * @return GeolocationServiceInterface Servicio de geolocalización
	 * @throws \Exception Si faltan credenciales necesarias
	 */
	public static function createService(): GeolocationServiceInterface
	{
		// Obtener tipo de servicio de opciones o usar MaxMind por defecto
		$serviceType = get_option("talampaya_geolocation_service", "maxmind");

		// Crear servicio según el tipo
		switch ($serviceType) {
			case "maxmind":
			default:
				// Obtener credenciales de MaxMind
				$accountId = defined("MAXMIND_ACCOUNT_ID")
					? MAXMIND_ACCOUNT_ID
					: get_option("talampaya_maxmind_account_id");

				$licenseKey = defined("MAXMIND_LICENSE_KEY")
					? MAXMIND_LICENSE_KEY
					: get_option("talampaya_maxmind_license_key");

				// Verificar que existan las credenciales
				if (empty($accountId) || empty($licenseKey)) {
					// Si no existen credenciales, usar el servicio de fallback
					return new FallbackGeolocationService();
				}

				return new MaxMindGeolocationService($accountId, $licenseKey);
		}
	}
}

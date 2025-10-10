<?php

namespace App\Integrations\Geolocation;

/**
 * Interfaz para servicios de geolocalización basados en IP
 */
interface GeolocationServiceInterface
{
	/**
	 * Obtiene datos de geolocalización para una dirección IP
	 *
	 * @param string $ip Dirección IP para obtener la geolocalización
	 * @return array Datos de geolocalización
	 * @throws \Exception Si ocurre un error al obtener los datos
	 */
	public function getGeolocationData(string $ip): array;
}

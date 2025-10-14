<?php

namespace App\Inc\Helpers;

class LinksHelper
{
	/**
	 * Reemplaza el dominio en una URL con el dominio actual del sitio
	 *
	 * Esta función es útil para asegurar que las URLs almacenadas en opciones
	 * o campos ACF apunten siempre al dominio actual del sitio, incluso
	 * después de migraciones o cambios de entorno.
	 *
	 * @param string $url La URL original que puede contener un dominio diferente
	 * @return string La URL con el dominio reemplazado por el actual
	 */
	function replace_domain_in_url(string $url = ""): string
	{
		if (empty($url)) {
			return $url;
		}

		// Si es una URL relativa, simplemente devolver
		if (strpos($url, "http") !== 0) {
			return $url;
		}

		$site_url = site_url();
		$site_domain = parse_url($site_url, PHP_URL_HOST);
		$site_scheme = parse_url($site_url, PHP_URL_SCHEME);

		// Obtener los componentes de la URL original
		$url_parts = parse_url($url);

		// Si no se puede analizar la URL, devolver la original
		if (!$url_parts) {
			return $url;
		}

		// Reemplazar el dominio manteniendo la ruta y parámetros
		$path = isset($url_parts["path"]) ? $url_parts["path"] : "";
		$query = isset($url_parts["query"]) ? "?" . $url_parts["query"] : "";
		$fragment = isset($url_parts["fragment"]) ? "#" . $url_parts["fragment"] : "";

		return $site_scheme . "://" . $site_domain . $path . $query . $fragment;
	}
}

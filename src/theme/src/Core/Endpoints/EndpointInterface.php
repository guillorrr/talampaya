<?php

namespace App\Core\Endpoints;

/**
 * Interfaz que deben implementar todos los endpoints de la API
 */
interface EndpointInterface
{
	/**
	 * Registra el endpoint en la API de WordPress
	 */
	public function register(): void;

	/**
	 * Devuelve el namespace de la API
	 *
	 * @return string Namespace de la API
	 */
	public function getNamespace(): string;

	/**
	 * Devuelve la ruta base del endpoint
	 *
	 * @return string Ruta base
	 */
	public function getRoute(): string;
}

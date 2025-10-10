<?php

namespace App\Core\Endpoints;

/**
 * Clase base abstracta para endpoints de la API
 */
abstract class AbstractEndpoint implements EndpointInterface
{
	/**
	 * Namespace de la API por defecto
	 */
	protected const API_NAMESPACE = "talampaya/v1";

	/**
	 * Ruta base del endpoint
	 */
	protected const ROUTE = "";

	/**
	 * {@inheritdoc}
	 */
	public function getNamespace(): string
	{
		return static::API_NAMESPACE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRoute(): string
	{
		return static::ROUTE;
	}

	/**
	 * {@inheritdoc}
	 */
	abstract public function register(): void;
}

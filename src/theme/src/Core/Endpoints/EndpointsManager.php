<?php

namespace App\Core\Endpoints;

use App\Utils\FileUtils;

/**
 * Gestor centralizado para todos los endpoints de la API
 */
class EndpointsManager
{
	/**
	 * Endpoints registrados
	 *
	 * @var EndpointInterface[]
	 */
	private array $endpoints = [];

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->registerCoreEndpoints();
		$this->registerCustomEndpoints();
	}

	/**
	 * Registra los endpoints principales
	 */
	private function registerCoreEndpoints(): void
	{
		// Registrar endpoints básicos
		$this->addEndpoint(new GeolocationEndpoint());
	}

	/**
	 * Registra endpoints personalizados desde archivos
	 */
	private function registerCustomEndpoints(): void
	{
		if (defined("API_ENDPOINTS_PATH") && is_dir(API_ENDPOINTS_PATH)) {
			$files = FileUtils::talampaya_directory_iterator(API_ENDPOINTS_PATH);

			foreach ($files as $file) {
				require_once $file;

				$className = pathinfo($file, PATHINFO_FILENAME);
				$fullyQualifiedClassName = "\\App\\Core\\Endpoints\\Custom\\$className";

				if (
					class_exists($fullyQualifiedClassName) &&
					is_subclass_of($fullyQualifiedClassName, EndpointInterface::class)
				) {
					$this->addEndpoint(new $fullyQualifiedClassName());
				}
			}
		}
	}

	/**
	 * Añade un endpoint
	 *
	 * @param EndpointInterface $endpoint Endpoint a añadir
	 */
	public function addEndpoint(EndpointInterface $endpoint): void
	{
		$this->endpoints[] = $endpoint;
	}

	/**
	 * Registra todos los endpoints en WordPress
	 */
	public function registerAllEndpoints(): void
	{
		// Inicializar los endpoints cuando se inicialice la API REST
		add_action("rest_api_init", function () {
			foreach ($this->endpoints as $endpoint) {
				$endpoint->register();
			}
		});
	}
}

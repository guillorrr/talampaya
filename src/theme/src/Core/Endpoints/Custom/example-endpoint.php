<?php
/**
 * Este es un archivo de ejemplo que muestra cómo crear un endpoint personalizado
 *
 * Para usar este archivo:
 * 1. Renómbralo a 'MiEndpoint.php'
 * 2. Actualiza el nombre de la clase a 'MiEndpoint'
 * 3. Implementa la lógica de tu endpoint
 */

namespace App\Core\Endpoints\Custom;

use App\Core\Endpoints\AbstractEndpoint;
use WP_REST_Request;
use WP_REST_Response;

class ExampleEndpoint extends AbstractEndpoint
{
	/**
	 * Ruta del endpoint
	 */
	protected const ROUTE = "/ejemplo";

	/**
	 * {@inheritdoc}
	 */
	public function register(): void
	{
		register_rest_route($this->getNamespace(), $this->getRoute(), [
			"methods" => "GET",
			"callback" => [$this, "handleRequest"],
			"permission_callback" => "__return_true",
		]);

		// También puedes registrar rutas adicionales
		register_rest_route($this->getNamespace(), $this->getRoute() . "/(?P<id>\d+)", [
			"methods" => "GET",
			"callback" => [$this, "getItem"],
			"permission_callback" => "__return_true",
			"args" => [
				"id" => [
					"validate_callback" => function ($param) {
						return is_numeric($param);
					},
				],
			],
		]);
	}

	/**
	 * Maneja la solicitud al endpoint principal
	 *
	 * @param WP_REST_Request $request Solicitud
	 * @return WP_REST_Response Respuesta
	 */
	public function handleRequest(WP_REST_Request $request): WP_REST_Response
	{
		return new WP_REST_Response([
			"success" => true,
			"message" => "Endpoint de ejemplo funcionando correctamente",
			"timestamp" => current_time("timestamp"),
		]);
	}

	/**
	 * Obtiene un elemento específico por ID
	 *
	 * @param WP_REST_Request $request Solicitud
	 * @return WP_REST_Response Respuesta
	 */
	public function getItem(WP_REST_Request $request): WP_REST_Response
	{
		$id = $request->get_param("id");

		return new WP_REST_Response([
			"success" => true,
			"id" => $id,
			"item" => [
				"name" => "Elemento " . $id,
				"description" => "Este es un ejemplo del elemento " . $id,
			],
		]);
	}
}

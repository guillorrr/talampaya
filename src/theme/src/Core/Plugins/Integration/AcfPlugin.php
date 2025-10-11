<?php

namespace App\Core\Plugins\Integration;

use App\Core\Plugins\AbstractPlugin;
use App\Features\Acf\Acf;

/**
 * Plugin integrado para Advanced Custom Fields (ACF)
 */
class AcfPlugin extends AbstractPlugin
{
	/**
	 * Nombre del plugin
	 */
	protected string $name = "acf";

	/**
	 * Instancia de la clase principal de ACF
	 */
	protected ?Acf $acf = null;

	/**
	 * Verifica si el plugin debe activarse
	 *
	 * @return bool True si el plugin debe activarse
	 */
	public function shouldLoad(): bool
	{
		// Comprobar si ACF está activo
		return class_exists("ACF");
	}

	/**
	 * Inicializa el plugin
	 */
	public function initialize(): void
	{
		// Inicializar la clase principal de ACF
		$this->acf = new Acf();

		// Configurar el callback de renderizado predeterminado para bloques ACF
		add_filter("acf/register_block_type_args", [$this, "setDefaultRenderCallback"]);

		// Agregar filtros y acciones para ACF
		add_filter("timber/context", [$this, "addAcfOptionsToContext"]);
		add_filter("timber/twig", [$this, "addAcfTwigExtensions"]);
		add_filter("acf/settings/save_json", [$this, "jsonSavePath"]);
		//add_filter("acf/settings/show_admin", [$this, "hideAdminMenuItem"]);
	}

	/**
	 * Establece el callback de renderizado predeterminado para bloques ACF
	 *
	 * @param array $args Argumentos del bloque
	 * @return array Argumentos modificados
	 */
	public function setDefaultRenderCallback(array $args): array
	{
		// Solo establecer el callback si no se ha definido uno personalizado
		if (!isset($args["render_callback"]) || empty($args["render_callback"])) {
			$args["render_callback"] = [Acf::class, "renderBlock"];
		}

		return $args;
	}

	/**
	 * Agrega las opciones de ACF al contexto de Timber
	 *
	 * @param array $context Contexto actual
	 * @return array Contexto modificado
	 */
	public function addAcfOptionsToContext(array $context): array
	{
		// Verificar si existe la función de ACF
		if (function_exists("get_fields")) {
			// Agregar opciones globales de ACF al contexto
			$context["options"] = get_fields("option");
		}

		return $context;
	}

	/**
	 * Agrega funciones de ACF a Twig
	 *
	 * @param \Twig\Environment $twig Entorno Twig
	 * @return \Twig\Environment Entorno Twig modificado
	 */
	public function addAcfTwigExtensions(\Twig\Environment $twig): \Twig\Environment
	{
		// Agregar funciones de ACF a Twig
		$twig->addFunction(
			new \Twig\TwigFunction("get_field", function ($field_name, $post_id = false) {
				return function_exists("get_field") ? get_field($field_name, $post_id) : null;
			})
		);

		$twig->addFunction(
			new \Twig\TwigFunction("get_fields", function ($post_id = false) {
				return function_exists("get_fields") ? get_fields($post_id) : null;
			})
		);

		$twig->addFunction(
			new \Twig\TwigFunction("get_field_object", function ($field_name, $post_id = false) {
				return function_exists("get_field_object")
					? get_field_object($field_name, $post_id)
					: null;
			})
		);

		return $twig;
	}

	/**
	 * Obtiene la lista de plugins requeridos por este plugin
	 *
	 * @return array Lista de plugins requeridos
	 */
	public function getRequiredPlugins(): array
	{
		return [
			[
				"name" => "Advanced Custom Fields PRO",
				"slug" => "advanced-custom-fields-pro",
				"required" => true,
				"force_activation" => true,
			],
		];
	}

	/**
	 * Define la ruta para guardar los archivos JSON de ACF
	 *
	 * @param string $path Ruta predeterminada
	 * @return string Ruta modificada
	 */
	public function jsonSavePath($path): string
	{
		return get_template_directory() . "/acf-json";
	}

	/**
	 * Determina si se debe ocultar el elemento del menú de administración de ACF
	 *
	 * @param bool $show_admin Valor predeterminado
	 * @return bool Valor modificado
	 */
	public function hideAdminMenuItem($show_admin): bool
	{
		return false;
	}
}

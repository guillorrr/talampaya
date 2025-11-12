<?php

namespace App\Features\ContentGenerator\Generators;

use App\Features\ContentGenerator\AbstractContentGenerator;
use App\Features\ContentGenerator\ContentGeneratorFactory;
use App\Features\ContentGenerator\ContentGeneratorManager;
use App\Features\ContentGenerator\ContentTypeGenerator;

/**
 * Generador de páginas legales desde archivos HTML
 */
class LegalPagesGenerator extends AbstractContentGenerator
{
	/**
	 * Ruta base para los archivos HTML legales
	 * @var string
	 */
	protected string $html_base_path;

	/**
	 * Generador interno para manejar la creación de contenido
	 * @var ContentTypeGenerator|null
	 */
	protected ?ContentTypeGenerator $internalGenerator = null;

	/**
	 * Constructor
	 *
	 * @param string $html_base_path Ruta base opcional para los archivos HTML
	 */
	public function __construct(
		string $html_base_path = "/src/Features/DefaultContent/html-content/"
	) {
		parent::__construct("legal_pages_generated");
		$this->html_base_path = $html_base_path;
	}

	/**
	 * Implementación del método abstracto para generar el contenido
	 *
	 * @return bool Verdadero si la generación fue exitosa
	 */
	protected function generateContent(): bool
	{
		// Definir las páginas legales que queremos crear
		$legal_pages = [
			"aviso-legal" => [
				"title" => "Aviso Legal",
				"update" => true, // Actualizar si ya existe
				"meta" => [
					"_legal_type" => "terms",
					"_show_in_footer" => true,
				],
			],
			"politica-de-privacidad" => [
				"title" => "Política de Privacidad",
				"update" => true,
				"meta" => [
					"_legal_type" => "privacy",
					"_show_in_footer" => true,
				],
			],
			"cookies" => [
				"title" => "Política de Cookies",
				"file" => "politica-de-cookies.html", // Archivo personalizado
				"update" => true,
				"meta" => [
					"_legal_type" => "cookies",
					"_show_in_footer" => true,
				],
			],
			"politica-de-reembolsos" => [
				"title" => "Política de Reembolsos",
				"file" => "politica-de-cookies.html", // Archivo personalizado
				"update" => true,
				"meta" => [
					"_legal_type" => "refund",
					"_show_in_footer" => true,
				],
			],
		];

		// Crear el generador de contenido HTML con la factory
		$this->internalGenerator = ContentGeneratorFactory::createHtmlContentGenerator(
			$this->getOptionKey(), // Utilizar la misma clave de opción
			"page", // Tipo de post (páginas)
			$legal_pages, // Datos de las páginas
			$this->html_base_path // Ruta base para los archivos HTML
		);

		// Ejecutar la generación usando el generador interno
		try {
			// El ContentTypeGenerator.generateContent() devuelve bool
			return $this->internalGenerator->generateContent();
		} catch (\Exception $e) {
			error_log("LegalPagesGenerator: Error al generar contenido: " . $e->getMessage());
			return false;
		}
	}
}

// Ejemplo de uso:
// LegalPagesGenerator::register(); // Solo registra el generador para activación del tema
// LegalPagesGenerator::generate(); // Registra y ejecuta la generación
